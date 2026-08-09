<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "🏗️ Criando tabelas de agenda...\n";
    $sql = file_get_contents(__DIR__ . '/../database/migrations/008_create_agenda_tables.sql');
    Database::getInstance()->exec($sql);
    echo "✅ Tabelas criadas.\n";
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
