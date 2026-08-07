<?php
header('Content-Type: text/plain');
try {
    $dbPath = 'Y:/bt-enterprise/database/temp_client.db';
    echo "Consertando cópia local em $dbPath...\n";
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columns = [
        'nome_cliente' => 'TEXT',
        'atendente_nome' => 'TEXT',
        'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
        'data_agendamento' => 'DATETIME'
    ];

    foreach ($columns as $col => $type) {
        try {
            $db->exec("ALTER TABLE senhas ADD COLUMN $col $type");
            echo "✅ Coluna $col adicionada.\n";
        } catch (Exception $e) { echo "ℹ️ Coluna $col já existe.\n"; }
    }
    echo "\n🔥 CÓPIA CURADA!";
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
