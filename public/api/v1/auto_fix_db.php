<?php
/**
 * BT Queue - Silent Auto-Fix Database (DIAMOND v5.8.1)
 * Garante colunas novas e realiza o selo de hardware automático.
 */

use BTQueue\Core\Database;
use BTQueue\Core\SecurityService;

// Força o fuso horário para evitar qualquer erro de data
date_default_timezone_set('America/Bahia');

try {
    $migrations = [
        'senhas' => [
            'nome_cliente' => 'TEXT',
            'atendente_nome' => 'TEXT',
            'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
            'data_agendamento' => 'DATETIME'
        ],
        'configuracoes' => [
            'label_cliente' => "TEXT DEFAULT 'Paciente'"
        ],
        'licencas' => [
            'assinatura' => 'TEXT',
            'hardware_id' => 'TEXT'
        ]
    ];

    foreach ($migrations as $table => $columns) {
        $resInfo = Database::fetchAll("PRAGMA table_info($table)");
        if (empty($resInfo)) continue;

        $existingCols = array_column($resInfo, 'name');

        foreach ($columns as $col => $type) {
            if (!in_array($col, $existingCols)) {
                try {
                    Database::execute("ALTER TABLE $table ADD COLUMN $col $type");
                } catch (Exception $e) {}
            }
        }
    }

    // --- AUTO-SELO DE HARDWARE (MIGRAÇÃO PARA v5.8.1) ---
    $lic = Database::fetch("SELECT * FROM licencas LIMIT 1");
    if ($lic && empty($lic['assinatura']) && class_exists('BTQueue\Core\SecurityService')) {
        $hwid = SecurityService::getHardwareId();
        $sig = SecurityService::signData([
            'uuid' => $lic['uuid'],
            'status' => $lic['status'],
            'validade' => $lic['validade']
        ], $lic['token']);

        Database::execute(
            "UPDATE licencas SET hardware_id = ?, assinatura = ? WHERE id = ?",
            [$hwid, $sig, $lic['id']]
        );
    }

} catch (Exception $e) {
    if (class_exists('BTQueue\Core\Logger')) {
        \BTQueue\Core\Logger::error("Auto-Fix DB Error: " . $e->getMessage());
    }
}
?>
