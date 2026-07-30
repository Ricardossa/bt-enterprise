<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

// Trava de segurança: Apenas ADMIN vê relatórios
Auth::protegerAPI('ADMIN');

try {
    // Detecta colunas da tabela senhas para ser ultra-compatível
    $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
    $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
    $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

    // 1. Ranking de Atendimentos por Operador
    $rankingOperadores = Database::fetchAll("
        SELECT
            atendente as nome,
            COUNT(*) as total,
            AVG(CAST((strftime('%s', finalizada_em) - strftime('%s', chamada_em)) AS INT) / 60.0) as tempo_medio_atendimento
        FROM senhas
        WHERE status = 'FINALIZADA' AND atendente IS NOT NULL
        GROUP BY atendente
        ORDER BY total DESC
    ");

    // 2. Tempo Médio de Espera por Serviço
    $esperaPorServico = Database::fetchAll("
        SELECT
            s.nome,
            AVG(CAST((strftime('%s', sen.chamada_em) - strftime('%s', sen.emitida_em)) AS INT) / 60.0) as tempo_espera
        FROM senhas sen
        JOIN servicos s ON s.id = sen.servico_id
        WHERE sen.status IN ('CHAMANDO', 'FINALIZADA', 'ATENDIMENTO')
        AND sen.chamada_em IS NOT NULL
        GROUP BY s.id
    ");

    // 3. Resumo Global
    $resumo = Database::fetch("
        SELECT
            COUNT(*) as total_emitidas,
            AVG(CAST((strftime('%s', chamada_em) - strftime('%s', emitida_em)) AS INT) / 60.0) as espera_global
        FROM senhas
        WHERE chamada_em IS NOT NULL
    ");

    // 4. Movimento por Hora (Picos)
    $movimentoHora = Database::fetchAll("
        SELECT
            strftime('%H:00', emitida_em) as hora,
            COUNT(*) as total
        FROM senhas
        GROUP BY hora
        ORDER BY hora ASC
    ");

    echo json_encode([
        'success' => true,
        'data' => [
            'ranking' => $rankingOperadores ?: [],
            'espera_servico' => $esperaPorServico ?: [],
            'resumo' => [
                'total_emitidas' => (int)($resumo['total_emitidas'] ?? 0),
                'espera_global' => (float)($resumo['espera_global'] ?? 0)
            ],
            'picos' => $movimentoHora ?: []
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
