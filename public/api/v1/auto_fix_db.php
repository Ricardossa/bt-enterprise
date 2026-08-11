<?php
/**
 * BT Queue - Silent Auto-Fix Database (DIAMOND v6.4.2)
 * Garante colunas novas, tabelas de agenda e selo de hardware automático.
 */

use BTQueue\Core\Database;
use BTQueue\Core\SecurityService;

// Força o fuso horário para evitar qualquer erro de data
date_default_timezone_set('America/Bahia');

try {
    // 1. GARANTE TABELAS DE AGENDAMENTO NATIVO (v5.9.6)
    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_regras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            servico_id INTEGER NOT NULL,
            dia_semana INTEGER NOT NULL,
            hora_inicio TEXT NOT NULL,
            hora_fim TEXT NOT NULL,
            duracao_slot INTEGER DEFAULT 20,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_bloqueios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data DATE NOT NULL,
            hora_inicio TEXT,
            hora_fim TEXT,
            motivo TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. GARANTE TABELA DE SUSPENSÕES (v6.4)
    Database::execute("
        CREATE TABLE IF NOT EXISTS agenda_suspensoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            identificador TEXT NOT NULL UNIQUE,
            motivo TEXT,
            data_fim DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 3. GARANTE CONFIGURAÇÕES DE WHATSAPP (v6.6)
    $waConfigs = [
        ['whatsapp_enabled', '0', 'BOOLEAN', 'Habilita notificações automáticas via WhatsApp'],
        ['whatsapp_api_url', '', 'STRING', 'URL da Instância da API (Ex: Evolution API)'],
        ['whatsapp_api_token', '', 'STRING', 'Token de autenticação da API'],
        ['ai_url', 'http://192.168.100.250:11434/api/generate', 'STRING', 'URL do motor de Inteligência Artificial (Ollama)']
    ];

    foreach ($waConfigs as $c) {
        Database::execute(
            "INSERT OR IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES (?, ?, ?, ?)",
            $c
        );
    }

    // 4. GARANTE COLUNAS NOVAS EM TABELAS EXISTENTES
    $migrations = [
        'senhas' => [
            'nome_cliente' => 'TEXT',
            'atendente_nome' => 'TEXT',
            'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
            'data_agendamento' => 'DATETIME',
            'emitida_em' => 'DATETIME',
            'whatsapp' => 'TEXT',
            'cancel_token' => 'TEXT'
        ],
        'configuracoes' => [
            'label_cliente' => "TEXT DEFAULT 'Paciente'"
        ],
        'licencas' => [
            'assinatura' => 'TEXT',
            'hardware_id' => 'TEXT'
        ],
        'agenda_regras' => [
            'liberacao_dia_semana' => 'INTEGER NULL',
            'liberacao_hora_inicio' => "TEXT DEFAULT '00:00'",
            'liberacao_hora_fim' => "TEXT DEFAULT '23:59'"
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

    // 3. AUTO-SELO DE HARDWARE (MIGRAÇÃO DE SEGURANÇA)
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
