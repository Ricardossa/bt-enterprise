<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * Serviço de Registro de Atividades Locais (Enterprise).
 */
final class ActivityService
{
    public static function log(
        string $tipo,
        string $categoria,
        string $mensagem,
        array $metadata = [],
        ?string $usuario = null
    ): void {
        try {
            self::ensureTableExists();

            Database::execute(
                "INSERT INTO atividades (tipo, categoria, mensagem, metadata, usuario) VALUES (?, ?, ?, ?, ?)",
                [
                    $tipo,
                    $categoria,
                    $mensagem,
                    !empty($metadata) ? json_encode($metadata) : null,
                    $usuario
                ]
            );
        } catch (Exception $e) {
            // Falha silenciosa
            error_log("Erro ao registrar atividade local: " . $e->getMessage());
        }
    }

    private static function ensureTableExists(): void
    {
        static $checked = false;
        if ($checked) return;

        $sql = "CREATE TABLE IF NOT EXISTS atividades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tipo TEXT DEFAULT 'INFO',
            categoria TEXT DEFAULT 'SYSTEM',
            mensagem TEXT NOT NULL,
            usuario TEXT NULL,
            metadata TEXT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        try {
            Database::execute($sql);
        } catch (Exception $e) {}

        $checked = true;
    }

    public static function getRecent(int $limit = 10): array
    {
        try {
            return Database::fetchAll(
                "SELECT * FROM atividades ORDER BY id DESC LIMIT " . (int) $limit
            );
        } catch (Exception $e) {
            return [];
        }
    }
}
