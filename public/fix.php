<?php
/**
 * BT Queue - Manual Emergency Fix
 */
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🚨 EXECUTANDO REPARO MANUAL DE BANCO...\n\n";

    $migrations = [
        'senhas' => [
            'nome_cliente' => 'TEXT',
            'atendente_nome' => 'TEXT',
            'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
            'data_agendamento' => 'DATETIME'
        ]
    ];

    foreach ($migrations as $table => $columns) {
        foreach ($columns as $col => $type) {
            try {
                echo "Tentando: ALTER TABLE $table ADD COLUMN $col... ";
                Database::execute("ALTER TABLE $table ADD COLUMN $col $type");
                echo "✅ OK!\n";
            } catch (Exception $e) {
                echo "ℹ️ INFO: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n🔥 OPERAÇÃO CONCLUÍDA! Tente abrir o painel agora.";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
