<?php
/**
 * BT Queue - DIAMOND EMERGENCY REPAIR
 * Este script força a atualização do banco de dados do cliente.
 */
header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🚨 INICIANDO REPARO INDEPENDENTE...\n\n";

    // Localiza o banco relativo ao arquivo public/
    $dbPath = __DIR__ . '/../database/banco.db';

    if (!file_exists($dbPath)) {
        die("❌ ERRO: Banco de dados não encontrado em: $dbPath");
    }

    echo "Conectando ao banco: $dbPath\n";
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
            echo "Adicionando coluna '$col'... ";
            $db->exec("ALTER TABLE senhas ADD COLUMN $col $type");
            echo "✅ OK!\n";
        } catch (Exception $e) {
            echo "ℹ️ Já existe ou banco ocupado.\n";
        }
    }

    echo "\n🔥 OPERAÇÃO CONCLUÍDA! O sistema deve destravar agora.";

} catch (Exception $e) {
    echo "\n❌ ERRO CRÍTICO: " . $e->getMessage();
    echo "\n\nDICA: Verifique se o sistema está aberto em outro lugar e tente novamente.";
}
?>
