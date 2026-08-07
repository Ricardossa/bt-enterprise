<?php

declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {

            $databasePath = dirname(__DIR__) . '/database/banco.db';

            if (!is_dir(dirname($databasePath))) {
                mkdir(dirname($databasePath), 0775, true);
            }

            try {

                self::$instance = new PDO(
                    'sqlite:' . $databasePath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                self::configure();

            } catch (PDOException $e) {
                throw new Exception('Erro ao conectar ao SQLite: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private static function configure(): void
    {
        self::$instance->exec("
            PRAGMA journal_mode = WAL;
            PRAGMA synchronous = NORMAL;
            PRAGMA foreign_keys = ON;
            PRAGMA temp_store = MEMORY;
            PRAGMA cache_size = -20000;
            PRAGMA busy_timeout = 5000;
        ");
    }

    public static function begin(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function beginImmediate(): void
    {
        self::getInstance()->exec("BEGIN IMMEDIATE TRANSACTION");
    }

    public static function commit(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->rollBack();
        }
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getInstance()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function lastInsertId(): int
    {
        return (int) self::getInstance()->lastInsertId();
    }

    public static function exists(): bool
    {
        return file_exists(dirname(__DIR__) . '/database/banco.db');
    }
}
