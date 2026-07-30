<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');
echo "--- CONFIGURATIONS ---\n";
try {
    $rows = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('logo', 'promo_logo', 'empresa')");
    print_r($rows);
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }

echo "\n--- PROMOTIONS ---\n";
try {
    $rows = Database::fetchAll("SELECT id, titulo, imagem FROM promocoes");
    print_r($rows);
} catch (Exception $e) { echo "Error: " . $e->getMessage() . "\n"; }
