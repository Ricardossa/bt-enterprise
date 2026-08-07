<?php
header('Content-Type: text/plain');
try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) {
        die("FILE NOT FOUND: $dbPath");
    }

    echo "Connecting to $dbPath...\n";
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
            echo "Adding column $col... ";
            $db->exec("ALTER TABLE senhas ADD COLUMN $col $type");
            echo "DONE\n";
        } catch (Exception $e) {
            echo "ALREADY EXISTS or ERROR: " . $e->getMessage() . "\n";
        }
    }

    echo "\nVerifying table structure:\n";
    $stmt = $db->query('PRAGMA table_info(senhas)');
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . "\n";
    }

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage();
}
?>
