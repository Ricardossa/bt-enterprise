<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🚨 INICIANDO OPERAÇÃO RESGATE (FORCE FIX)...\n\n";

    $dbFile = dirname(__DIR__, 2) . '/database/banco.db';
    echo "Arquivo do Banco: $dbFile\n";
    echo "Existe? " . (file_exists($dbFile) ? "SIM" : "NÃO") . "\n";
    echo "Permissão de Escrita? " . (is_writable(dirname($dbFile)) ? "SIM" : "NÃO") . "\n\n";

    $columnsToAdd = [
        'nome_cliente' => 'TEXT',
        'atendente_nome' => 'TEXT',
        'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
        'data_agendamento' => 'DATETIME'
    ];

    foreach ($columnsToAdd as $col => $type) {
        try {
            echo "Tentando adicionar '$col'... ";
            Database::execute("ALTER TABLE senhas ADD COLUMN $col $type");
            echo "✅ SUCESSO!\n";
        } catch (Exception $e) {
            echo "ℹ️ INFO: " . $e->getMessage() . "\n";
        }
    }

    echo "\n--- ESTRUTURA ATUAL ---\n";
    $res = Database::fetchAll("PRAGMA table_info(senhas)");
    foreach($res as $r) {
        echo "- {$r['name']} ({$r['type']})\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage();
}
?>
