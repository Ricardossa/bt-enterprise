<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    $res = Database::fetch("SELECT datetime('now', 'localtime', '-5 hours') as start, datetime('now', 'localtime', '+18 hours') as end, datetime('now', 'localtime') as now");
    print_r($res);

    echo "\nTrying raw search for AGENDADO:\n";
    $rows = Database::fetchAll("SELECT id, nome_cliente, data_agendamento FROM senhas WHERE status = 'AGENDADO'");
    print_r($rows);

} catch (Exception $e) { echo $e->getMessage(); }
?>
