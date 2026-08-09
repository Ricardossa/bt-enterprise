<?php

declare(strict_types=1);

namespace BTQueue\Core;

use PDO;
use Exception;

/**
 * Responsável pela instalação limpa do banco de dados (Schema + Seeds).
 */
final class DatabaseInstaller
{
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = dirname(__DIR__) . '/database/banco.db';
    }

    public function install(): array
    {
        $results = [];

        try {
            // 1. Garante que o diretório existe
            if (!is_dir(dirname($this->dbPath))) {
                mkdir(dirname($this->dbPath), 0775, true);
            }

            // [PROTEÇÃO] Nunca delete um banco que já possui dados vitais
            if (file_exists($this->dbPath)) {
                $dbTemp = new PDO('sqlite:' . $this->dbPath);
                // Verifica se já existe um UUID gerado (sinal de banco em uso)
                $hasUuid = $dbTemp->query("SELECT installation_uuid FROM system_info LIMIT 1")->fetch();
                $dbTemp = null;

                if ($hasUuid) {
                    $results[] = "ℹ️ Banco de dados preservado (Instalação ativa detectada).";
                    return ['success' => true, 'message' => 'Estrutura preservada.', 'details' => $results];
                }
            }

            // 2. Conecta ao banco (isso cria o arquivo se não existir)
            $db = Database::getInstance();

            // 3. Executa o Schema
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (!file_exists($schemaFile)) throw new Exception("Arquivo schema.sql não encontrado.");

            $schemaSql = file_get_contents($schemaFile);
            $db->exec($schemaSql);
            $results[] = "✅ Estrutura de tabelas criada.";

            // 4. Executa os Seeds
            $seedsFile = dirname(__DIR__) . '/database/seeds.sql';
            if (!file_exists($seedsFile)) throw new Exception("Arquivo seeds.sql não encontrado.");

            $seedsSql = file_get_contents($seedsFile);
            $db->exec($seedsSql);
            $results[] = "✅ Dados iniciais configurados.";

            // Compatibilidade também para templates gerados antes do Diamond.
            DiamondActivationService::ensureLicenseColumns();

            // 5. Garante que a URL da Master está sempre configurada no banco inicial
            Database::execute(
                "INSERT OR IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES (?, ?, 'STRING', ?)",
                [
                    'master_url',
                    'http://api.brandaotech.com.br:8080/api/v1/sync.php',
                    'URL de sincronização com a Platform Master'
                ]
            );
            $results[] = "✅ URL da Master garantida no banco.";

            // 6. Gera Identidade da Instalação (UUID Permanente)
            $uuid = $this->generateUuid();
            Database::execute(
                "INSERT OR IGNORE INTO system_info (installation_uuid, versao, build, hostname, php_version) VALUES (?, ?, ?, ?, ?)",
                [
                    $uuid,
                    Config::get('app.version', '4.0.0'),
                    date('Ymd.His'),
                    gethostname(),
                    PHP_VERSION
                ]
            );
            $results[] = "✅ Identidade gerada: $uuid";

            $migrationResult = (new Migration())->run();
            if (!$migrationResult['success']) {
                throw new Exception($migrationResult['message']);
            }
            $results[] = "✅ Migrations executadas.";

            return [
                'success' => true,
                'message' => 'Banco de dados instalado com sucesso.',
                'details' => $results,
                'uuid' => $uuid
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na instalação do banco: ' . $e->getMessage()
            ];
        }
    }

    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
