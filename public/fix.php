<?php
/**
 * BT Queue - Emergency Independent Fix
 * No bootstrap dependencies to avoid redirects.
 */
header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🚨 INICIANDO REPARO DE EMERGÊNCIA (MODO INDEPENDENTE)...\n\n";

    $dbPath = __DIR__ . '/../database/banco.db';
    if (!file_exists($dbPath)) {
        die("❌ Erro: Arquivo de banco não encontrado em $dbPath");
    }

    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columns = [
        'nome_cliente' => 'TEXT',
        'atendente_nome' => 'TEXT',
        'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
        'data_agendamento' => 'DATETIME'
    ];

    foreach ($columns as $col => $type) {
        try {
            echo "Adicionando '$col'... ";
            $db->exec("ALTER TABLE senhas ADD COLUMN $col $type");
            echo "✅ OK!\n";
        } catch (Exception $e) {
            echo "ℹ️ Já existe.\n";
        }
    }

    echo "\n🔥 TUDO PRONTO! Tente abrir o painel agora.";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
