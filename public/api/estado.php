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

    $estado['estatisticas'] = $queue->estatisticas();
    // Histórico com suporte flexível a 'senha' ou 'codigo'
    try {
        $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
        $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
        $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

        $estado['historico'] = Database::fetchAll(
            "SELECT s.id, $campoCodigo as senha, g.nome as guiche_nome, s.chamada_em
             FROM senhas s
             LEFT JOIN guiches g ON g.id = s.guiche_id
             WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
             ORDER BY s.id DESC
             LIMIT 5"
        );
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
