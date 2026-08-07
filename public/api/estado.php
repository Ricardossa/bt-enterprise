<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\QueueService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

$queue = new QueueService();

$servicoId = isset($_GET['servico_id']) ? (int)$_GET['servico_id'] : 0;
$guicheId  = isset($_GET['guiche_id']) ? (int)$_GET['guiche_id'] : 0;

try {

    if ($servicoId > 0 && $guicheId > 0) {

        $estado = $queue->estado($servicoId, $guicheId);

    } else {

        $guiche = $_GET['guiche'] ?? '01';
        $estado = $queue->estado($guiche);

    }

    // Carrega rótulo personalizado
    $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' LIMIT 1");
    $estado['label_cliente'] = $config['valor'] ?? 'Paciente';

    $estado['agendados'] = $queue->getAgendados($servicoId > 0 ? $servicoId : null);
    $estado['estatisticas'] = $queue->estatisticas();
    // Histórico com suporte flexível a 'senha' ou 'codigo'
    try {
        $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
        $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
        $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

        // --- DETECÇÃO DINÂMICA DE COLUNAS (HOSPITAL SAFE) ---
        $extraCols = [];
        if (in_array('nome_cliente', $cols)) $extraCols[] = "s.nome_cliente";
        if (in_array('atendente_nome', $cols)) $extraCols[] = "s.atendente_nome";
        if (in_array('tipo_atendimento', $cols)) $extraCols[] = "s.tipo_atendimento";

        $sqlExtra = !empty($extraCols) ? ", " . implode(", ", $extraCols) : "";

        $estado['historico'] = Database::fetchAll(
            "SELECT s.id, $campoCodigo as senha, g.nome as guiche_nome, s.chamada_em $sqlExtra
             FROM senhas s
             LEFT JOIN guiches g ON g.id = s.guiche_id
             WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
             ORDER BY s.id DESC
             LIMIT 5"
        );

        // --- ENRIQUECIMENTO PARA TV HOSPITALAR ---
        foreach ($estado['historico'] as &$item) {
            $item['is_hospital'] = !empty($item['nome_cliente']);
            if ($item['is_hospital']) {
                $item['senha'] = $item['nome_cliente']; // Força o nome no lugar da senha
            }
        }
    } catch (Exception $e) {
        $estado['historico'] = [];
    }

    echo json_encode([
        'success' => true,
        'data' => $estado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}
