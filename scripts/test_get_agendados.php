<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\QueueService;

try {
    $service = new QueueService();
    echo "--- TESTING getAgendados(1) ---\n";
    $res = $service->getAgendados(1);
    print_r($res);

    echo "\n--- TESTING getAgendados(null) ---\n";
    $res2 = $service->getAgendados(null);
    print_r($res2);

} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
