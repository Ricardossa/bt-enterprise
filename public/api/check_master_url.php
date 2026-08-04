<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: text/plain');
$url = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'master_url'");
echo "Master URL: " . ($url['valor'] ?? 'NOT FOUND') . "\n";
?>
