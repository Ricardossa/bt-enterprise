<?php
$files = [
    'Y:/bt-enterprise/database/banco.db',
    'Y:/bt-enterprise/database/temp_client.db',
    'Y:/bt-enterprise/database/banco.db.db'
];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    echo "--- CHECKING FILE: $f ---\n";
    try {
        $db = new PDO('sqlite:' . $f);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Find tickets
        $stmt = $db->query("SELECT id, codigo, status, created_at, device_id FROM senhas WHERE codigo IN ('CR004', 'CR008', 'CR011')");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            echo "FOUND: ID {$row['id']} | CODE {$row['codigo']} | STATUS {$row['status']} | DATE {$row['created_at']}\n";
            if ($row['codigo'] === 'CR004' || $row['codigo'] === 'CR008') {
                if ($row['status'] === 'AGUARDANDO' || $row['status'] === 'CONGELADA') {
                    echo "CANCELING ID {$row['id']}...\n";
                    $db->exec("UPDATE senhas SET status = 'CANCELADO' WHERE id = " . $row['id']);
                }
            }
        }
    } catch (Exception $e) {
        echo "ERROR IN $f: " . $e->getMessage() . "\n";
    }
}
?>
