<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "--- AUDITORIA ESTRUTURAL BT SCHEDULER ---\n";

    // 1. Verificar Tabelas
    $tables = Database::fetchAll("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'agenda_%'");
    echo "Tabelas encontradas: " . implode(', ', array_column($tables, 'name')) . "\n\n";

    // 2. Verificar Regras de Agendamento
    echo "--- REGRAS DE AGENDAMENTO (agenda_regras) ---\n";
    $regras = Database::fetchAll("SELECT * FROM agenda_regras");
    print_r($regras);

    // 3. Verificar Agendamentos na tabela senhas
    echo "\n--- AGENDAMENTOS NATIVOS (senhas com cliente_uuid='NATIVO') ---\n";
    $agendados = Database::fetchAll("SELECT id, nome_cliente, data_agendamento, status FROM senhas WHERE cliente_uuid = 'NATIVO' ORDER BY id DESC LIMIT 5");
    print_r($agendados);

    // 4. Verificar Horário do Sistema vs Horário Local
    echo "\n--- TEMPO DO SISTEMA ---\n";
    echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";
    $res = Database::fetch("SELECT datetime('now', 'localtime') as local_now");
    echo "SQLite Local Time: " . $res['local_now'] . "\n";

} catch (Exception $e) { echo "❌ ERRO NA AUDITORIA: " . $e->getMessage(); }
?>
