<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: text/plain');
try {
    $count = Database::fetch("SELECT COUNT(*) as total FROM senhas");
    echo "Total Senhas: " . ($count['total'] ?? 0) . "\n";
    $last = Database::fetch("SELECT codigo FROM senhas ORDER BY id DESC LIMIT 1");
    echo "Ultima Senha: " . ($last['codigo'] ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
