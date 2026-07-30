<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: text/plain');

$licenca = Database::fetch("SELECT status, ultima_validacao FROM licencas LIMIT 1");
$lastSync = $licenca['ultima_validacao'] ?? 'Nunca';

echo "PHP Current Time: " . date('Y-m-d H:i:s') . " (Zone: " . date_default_timezone_get() . ")\n";
echo "DB Last Sync: " . $lastSync . "\n";

if ($lastSync !== 'Nunca') {
    $diff = time() - strtotime($lastSync);
    echo "Difference (seconds): " . $diff . "\n";
    echo "Difference (minutes): " . round($diff / 60, 2) . "\n";
}
