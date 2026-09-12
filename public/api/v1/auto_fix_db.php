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
        ['ai_url', 'http://192.168.100.250:11434/api/generate', 'STRING', 'URL do motor de Inteligência Artificial (Ollama)'],
        ['radar_enabled', '0', 'BOOLEAN', 'Habilita o radar de faltas automáticas'],
        ['radar_tolerance', '15', 'NUMBER', 'Minutos de tolerância para falta automática'],
        ['priority_mode', 'STRICT', 'STRING', 'Modo de chamada: STRICT (Sempre Prioridade) ou BALANCED (Intercalado)'],
        ['priority_ratio', '3', 'NUMBER', 'Quantidade de prioridades antes de um normal (no modo BALANCED)'],
        ['feature_priority_selection', '1', 'BOOLEAN', 'Habilita a tela de escolha entre Normal e Prioritário no Totem']
    ];

    foreach ($waConfigs as $c) {
        Database::execute(
            "INSERT OR IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES (?, ?, ?, ?)",
            $c
        );
    }

    // 4. GARANTE COLUNAS NOVAS EM TABELAS EXISTENTES (v7.6.0)
    $migrations = [
        'clientes' => [
            'empresa' => 'TEXT',
            'whatsapp' => 'TEXT'
        ],
        'senhas' => [
            'cliente_id' => 'INTEGER DEFAULT 0',
            'nome_cliente' => 'TEXT',
            'atendente_nome' => 'TEXT',
            'tipo_atendimento' => "TEXT DEFAULT 'NORMAL'",
            'data_agendamento' => 'DATETIME',
            'emitida_em' => 'DATETIME',
            'whatsapp' => 'TEXT',
            'cancel_token' => 'TEXT',
            'valor_total' => 'DECIMAL(10,2) DEFAULT 0.00',
            'is_promo' => 'INTEGER DEFAULT 0'
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
        ],
        'servicos' => [
            'preco' => 'DECIMAL(10,2) DEFAULT 0.00',
            'promo_ativa' => 'INTEGER DEFAULT 0',
            'promo_desconto' => 'DECIMAL(5,2) DEFAULT 20.00',
            'promo_dias' => 'TEXT'
        ],
        'senhas' => [
            'valor_total' => 'DECIMAL(10,2) DEFAULT 0.00',
            'is_promo' => 'INTEGER DEFAULT 0'
        ],
        'configuracoes' => [
            'qr_security_salt' => "TEXT DEFAULT 'brandao_tech_2026'"
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

    // 3. CURA DE TEMPO (v6.8.6: Remove horários do futuro que travam a TV)
    Database::execute("
        UPDATE senhas
        SET chamada_em = datetime('now', 'localtime')
        WHERE status IN ('CHAMANDO', 'FINALIZADA')
        AND date(created_at) = date('now', 'localtime')
        AND chamada_em > datetime('now', 'localtime', '+5 minutes')
    ");

    // [v7.8.1] LIMPEZA SUPREMA: Finaliza senhas "esquecidas" de dias anteriores
    Database::execute("
        UPDATE senhas
        SET status = 'FINALIZADA',
            finalizada_em = datetime('now', 'localtime'),
            updated_at = datetime('now', 'localtime')
        WHERE status IN ('AGUARDANDO', 'CHAMANDO', 'CONGELADA', 'ATENDIMENTO')
        AND date(created_at) < date('now', 'localtime')
    ");

} catch (Exception $e) {
    if (class_exists('BTQueue\Core\Logger')) {
        \BTQueue\Core\Logger::error("Auto-Fix DB Error: " . $e->getMessage());
    }
}
?>
