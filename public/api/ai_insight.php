<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\AIService;
use BTQueue\Core\QueueService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {

    /*
     * ============================================================
     * DADOS PRINCIPAIS DA FILA
     * Usa o mesmo QueueService utilizado pelo restante do Enterprise.
     * Isso evita divergência de status e regras entre o relatório e a IA.
     * ============================================================
     */
    $queue = new QueueService();
    $stats = $queue->getStatsPorPeriodo();

    $emitidas    = (int)($stats['emitidas'] ?? 0);
    $chamadas    = (int)($stats['chamadas'] ?? 0);
    $finalizadas = (int)($stats['finalizadas'] ?? 0);
    $pendentes   = (int)($stats['pendentes'] ?? 0);

    /*
     * ============================================================
     * TAXA DE CONCLUSÃO
     * ============================================================
     */
    $taxaConclusao = $emitidas > 0
        ? round(($finalizadas / $emitidas) * 100)
        : 0;

    /*
     * ============================================================
     * TEMPO MÉDIO DE ESPERA
     * Somente senhas que já foram chamadas.
     * ============================================================
     */
    $esperaMedia = 0;

    try {
        $resultado = Database::fetch(
            "SELECT AVG(
                (julianday(chamada_em) - julianday(emitida_em)) * 24 * 60
             ) AS media
             FROM senhas
             WHERE date(created_at) = date('now', 'localtime')
             AND chamada_em IS NOT NULL"
        );

        $esperaMedia = (int)round($resultado['media'] ?? 0);

    } catch (\Throwable $e) {
        $esperaMedia = 0;
    }

    /*
     * ============================================================
     * PICO DE MOVIMENTO
     * ============================================================
     */
    $horarioPico = 'Não disponível';

    try {
        $pico = Database::fetch(
            "SELECT strftime('%H', created_at) AS hora,
                    COUNT(*) AS total
             FROM senhas
             WHERE date(created_at) = date('now', 'localtime')
             GROUP BY hora
             ORDER BY total DESC
             LIMIT 1"
        );

        if ($pico) {
            $horarioPico = $pico['hora'] . 'h';
        }

    } catch (\Throwable $e) {
        $horarioPico = 'Não disponível';
    }

    /*
     * ============================================================
     * INDICADORES OPERACIONAIS ADICIONAIS
     * ============================================================
     */

    // Melhor operador por quantidade de atendimentos finalizados
    $nomeMelhorOperador = 'Não disponível';
    $totalMelhorOperador = 0;

    try {
        $melhorOperador = Database::fetch(
            "SELECT atendente AS nome, COUNT(*) AS total
             FROM senhas
             WHERE date(finalizada_em) = date('now', 'localtime')
             AND atendente IS NOT NULL
             AND atendente != ''
             GROUP BY atendente
             ORDER BY total DESC
             LIMIT 1"
        );

        if ($melhorOperador) {
            $nomeMelhorOperador = $melhorOperador['nome'];
            $totalMelhorOperador = (int)$melhorOperador['total'];
        }
    } catch (\Throwable $e) {
        // Mantém indisponível
    }

    // Total de operadores identificados
    $totalAtendentes = 0;

    try {
        $row = Database::fetch(
            "SELECT COUNT(DISTINCT atendente) AS total
             FROM senhas
             WHERE date(finalizada_em) = date('now', 'localtime')
             AND atendente IS NOT NULL
             AND atendente != ''"
        );

        $totalAtendentes = (int)($row['total'] ?? 0);
    } catch (\Throwable $e) {
        // Mantém zero
    }

    // Tempo médio de atendimento
    $mediaAtendimento = 0;

    try {
        $row = Database::fetch(
            "SELECT AVG(
                (julianday(finalizada_em) - julianday(chamada_em)) * 24 * 60
             ) AS media
             FROM senhas
             WHERE date(created_at) = date('now', 'localtime')
             AND chamada_em IS NOT NULL
             AND finalizada_em IS NOT NULL"
        );

        $mediaAtendimento = (int)round($row['media'] ?? 0);
    } catch (\Throwable $e) {
        // Mantém zero
    }

    // Serviço com maior tempo médio entre emissão e finalização
    $nomeServicoLento = 'Não disponível';
    $tempoServicoLento = 0;

    try {
        $row = Database::fetch(
            "SELECT
                s.nome AS servico,
                AVG(
                    (julianday(sen.finalizada_em) - julianday(sen.emitida_em)) * 24 * 60
                ) AS media
             FROM senhas sen
             LEFT JOIN servicos s ON s.id = sen.servico_id
             WHERE date(sen.created_at) = date('now', 'localtime')
             AND sen.finalizada_em IS NOT NULL
             AND sen.servico_id IS NOT NULL
             GROUP BY sen.servico_id
             ORDER BY media DESC
             LIMIT 1"
        );

        if ($row) {
            $nomeServicoLento = $row['servico'] ?? 'Não disponível';
            $tempoServicoLento = (int)round($row['media'] ?? 0);
        }
    } catch (\Throwable $e) {
        // Mantém indisponível
    }

    /*
     * ============================================================
     * MONTA OS DADOS PARA A IA
     * ============================================================
     */
    $dadosIA = [
        'emitidas'          => $emitidas,
        'chamadas'          => $chamadas,
        'finalizadas'       => $finalizadas,
        'pendentes'         => $pendentes,
        'taxa_conclusao'    => $taxaConclusao,
        'espera_media'      => $esperaMedia,
        'pico_movimento'    => $horarioPico,
        'horario_pico'      => $horarioPico,
        'total_atendentes'  => $totalAtendentes,
        'melhor_operador'   => $nomeMelhorOperador !== 'Não disponível'
            ? $nomeMelhorOperador . " ({$totalMelhorOperador} senhas)"
            : 'Não disponível',
        'tempo_atendimento' => $mediaAtendimento,
        'servico_mais_lento' => $nomeServicoLento !== 'Não disponível'
            ? $nomeServicoLento . " ({$tempoServicoLento}min)"
            : 'Não disponível'
    ];

    /*
     * ============================================================
     * IA
     * ============================================================
     */
    $service = new AIService();

    $resultado = $service->generateDailyReport($dadosIA);

    /*
     * ============================================================
     * RESPOSTA
     * ============================================================
     */
    echo json_encode([
        'success' => true,
        'insight' => $resultado,
        'dados'   => $dadosIA
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
        'insight' => 'SITUAÇÃO:' . PHP_EOL .
                     'Não foi possível gerar a análise.' . PHP_EOL . PHP_EOL .
                     'ATENÇÃO:' . PHP_EOL .
                     'O serviço de inteligência apresentou uma falha.' . PHP_EOL . PHP_EOL .
                     'RECOMENDAÇÃO:' . PHP_EOL .
                     'Verifique o serviço de Inteligência Artificial local.'
    ], JSON_UNESCAPED_UNICODE);
}
