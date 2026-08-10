<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "🏗️ APLICANDO MIGRAÇÃO 009 (JANELAS DE LIBERAÇÃO)...\n";

    $sql = "ALTER TABLE agenda_regras ADD COLUMN liberacao_dia_semana INTEGER NULL;
            ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_inicio TEXT DEFAULT '00:00';
            ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_fim TEXT DEFAULT '23:59';";

    // SQLite doesn't support multiple ALTER TABLE in one exec() with some drivers, so we do it line by line
    Database::execute("ALTER TABLE agenda_regras ADD COLUMN liberacao_dia_semana INTEGER NULL");
    Database::execute("ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_inicio TEXT DEFAULT '00:00'");
    Database::execute("ALTER TABLE agenda_regras ADD COLUMN liberacao_hora_fim TEXT DEFAULT '23:59'");

    echo "✅ Colunas criadas com sucesso!\n";
} catch (Exception $e) { echo "❌ ERRO: " . $e->getMessage(); }
?>
