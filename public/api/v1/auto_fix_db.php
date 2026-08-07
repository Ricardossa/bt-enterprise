<?php
/**
 * BT Queue - Silent Auto-Fix Database (DIAMOND v5.6.2)
 * Garante que todas as colunas novas existam no banco do cliente.
 */

use BTQueue\Core\Database;

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
        ]
    ];

    foreach ($migrations as $table => $columns) {
        // Tenta buscar colunas existentes
        $resInfo = Database::fetchAll("PRAGMA table_info($table)");

        // Se a tabela não existe, ignoramos (ela será criada pelo instalador se for o caso)
        if (empty($resInfo)) continue;

        $existingCols = array_column($resInfo, 'name');

        foreach ($columns as $col => $type) {
            if (!in_array($col, $existingCols)) {
                try {
                    // Executa a alteração
                    Database::execute("ALTER TABLE $table ADD COLUMN $col $type");

                    // Se for a tabela configurações e for o campo label_cliente, insere o valor inicial
                    if ($table === 'configuracoes' && $col === 'label_cliente') {
                        Database::execute(
                            "INSERT OR IGNORE INTO configuracoes (chave, valor, tipo, descricao)
                             VALUES ('label_cliente', 'Paciente', 'STRING', 'Rótulo padrão para o cliente')"
                        );
                    }
                } catch (Exception $e) {
                    // Se falhar (ex: banco travado), tentará novamente no próximo boot
                }
            }
        }
    }
} catch (Exception $e) {
    // Log silencioso para não quebrar o sistema
    if (class_exists('BTQueue\Core\Logger')) {
        \BTQueue\Core\Logger::error("Auto-Fix DB Error: " . $e->getMessage());
    }
}
?>
