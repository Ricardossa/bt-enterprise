<?php
header('Content-Type: text/plain');
echo "CWD: " . getcwd() . "\n";
echo "DIR: " . __DIR__ . "\n";
$dbPath = realpath(dirname(__DIR__, 2) . '/database/banco.db');
echo "DB PATH: " . $dbPath . "\n";
echo "DB EXISTS: " . (file_exists($dbPath) ? "YES" : "NO") . "\n";
if (file_exists($dbPath)) {
    $db = new PDO("sqlite:" . $dbPath);
    $stmt = $db->query("PRAGMA table_info(senhas)");
    echo "COLUMNS:\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) echo "- " . $row['name'] . "\n";
}
?>
