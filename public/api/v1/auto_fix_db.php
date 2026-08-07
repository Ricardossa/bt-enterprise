<?php
/**
 * BT Queue - Silent Auto-Fix Database
 * Rodado automaticamente durante o boot para garantir integridade hospitalar.
 */

// NOTA: Este arquivo é incluído pelo bootstrap.php, não precisa de require_once.

use BTQueue\Core\Database;

try {
    $columns = [
        'nome_cliente' => 'TEXT',
        'atendente_nome' => 'TEXT',
        'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'"
    ];

    foreach ($columns as $col => $type) {
        try {
            // Verifica se a coluna já existe antes de tentar o ALTER
            $check = Database::fetch("PRAGMA table_info(senhas)");
            $exists = false;
            $resInfo = Database::fetchAll("PRAGMA table_info(senhas)");
            foreach($resInfo as $row) {
                if($row['name'] === $col) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                Database::execute("ALTER TABLE senhas ADD COLUMN $col $type");
            }
        } catch (Exception $e) { /* Silencioso */ }
    }
} catch (Exception $e) { /* Silencioso */ }
?>
