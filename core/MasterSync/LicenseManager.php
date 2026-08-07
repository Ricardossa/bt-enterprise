<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Database;
use Exception;

/**
 * Gestor de Licenciamento Local e Sincronia de Status.
 * Gerencia as diretrizes e recursos (Feature Flags) recebidos da Master.
 */
class LicenseManager
{
    public function __construct()
    {
        $this->ensureFeatureColumn();
    }

    private function ensureFeatureColumn(): void
    {
        try {
            $cols = Database::fetchAll("PRAGMA table_info(licencas)");
            $exists = false;
            foreach ($cols as $c) {
                if ($c['name'] === 'cache_features') {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                Database::execute("ALTER TABLE licencas ADD COLUMN cache_features TEXT");
            }
        } catch (Exception $e) {}
    }

    /**
     * Obtém a identidade da licença atual.
     */
    public function getLicenseIdentity(): array
    {
        $res = Database::fetch("SELECT uuid, token FROM licencas LIMIT 1");
        return $res ?: ['uuid' => '', 'token' => ''];
    }

    /**
     * Verifica se o sistema está bloqueado pela Master.
     */
    public function isBlocked(): bool
    {
        $res = Database::fetch("SELECT status FROM licencas LIMIT 1");
        if (!$res) return true; // Sem licença = Bloqueado
        return strtoupper($res['status']) === 'BLOQUEADA';
    }

    /**
     * Verifica se um recurso específico está habilitado na licença.
     */
    public static function hasFeature(string $featureName): bool
    {
        $res = Database::fetch("SELECT cache_features FROM licencas LIMIT 1");
        if (!$res || empty($res['cache_features'])) return true; // Fallback para compatibilidade

        $features = json_decode((string)$res['cache_features'], true);
        return (bool)($features[$featureName] ?? false);
    }

    /**
     * Atualiza os dados da licença com base na resposta da Master.
     */
    public function updateLicense(array $licenseData, string $uuid, string $token, array $features = []): void
    {
        $licenca = Database::fetch("SELECT id FROM licencas LIMIT 1");
        $now = date('Y-m-d H:i:s');
        $featuresJson = json_encode($features);

        if ($licenca) {
            Database::execute(
                "UPDATE licencas SET
                    status = ?,
                    validade = ?,
                    uuid = ?,
                    token = ?,
                    ultima_validacao = ?,
                    cache_features = ?
                WHERE id = ?",
                [
                    strtoupper($licenseData['status'] ?? 'ATIVA'),
                    $licenseData['expires'] ?? date('Y-m-d', strtotime('+1 year')),
                    $uuid,
                    $token,
                    $now,
                    $featuresJson,
                    $licenca['id']
                ]
            );

            // --- SINCRONIZA FEATURES NA TABELA DE CONFIGURAÇÕES (v5.7.3) ---
            foreach ($features as $key => $val) {
                Database::execute(
                    "INSERT OR REPLACE INTO configuracoes (chave, valor, tipo) VALUES (?, ?, 'BOOLEAN')",
                    ['feature_' . $key, $val ? '1' : '0']
                );
            }
        } else {
            Database::execute(
                "INSERT INTO licencas (cliente_id, chave, uuid, token, status, validade, ultima_validacao, cache_features)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $this->generateLicenseKey(),
                    $uuid,
                    $token,
                    strtoupper($licenseData['status'] ?? 'ATIVA'),
                    $licenseData['expires'] ?? date('Y-m-d', strtotime('+1 year')),
                    $now,
                    $featuresJson
                ]
            );
        }
    }

    private function generateLicenseKey(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}
