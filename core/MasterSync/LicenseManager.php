<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Database;
use Exception;

/**
 * Gestor de Licenciamento Local e Sincronia de Status.
 */
class LicenseManager
{
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
     * Atualiza os dados da licença com base na resposta da Master.
     */
    public function updateLicense(array $licenseData, string $uuid, string $token): void
    {
        $licenca = Database::fetch("SELECT id FROM licencas LIMIT 1");
        $now = date('Y-m-d H:i:s'); // PHP Time (America/Bahia)

        if ($licenca) {
            Database::execute(
                "UPDATE licencas SET
                    status = ?,
                    validade = ?,
                    uuid = ?,
                    token = ?,
                    ultima_validacao = ?
                WHERE id = ?",
                [
                    strtoupper($licenseData['status'] ?? 'ATIVA'),
                    $licenseData['expires'] ?? date('Y-m-d', strtotime('+1 year')),
                    $uuid,
                    $token,
                    $now,
                    $licenca['id']
                ]
            );
        } else {
            Database::execute(
                "INSERT INTO licencas (cliente_id, chave, uuid, token, status, validade, ultima_validacao)
                 VALUES (1, ?, ?, ?, ?, ?, ?)",
                [
                    $this->generateLicenseKey(),
                    $uuid,
                    $token,
                    strtoupper($licenseData['status'] ?? 'ATIVA'),
                    $licenseData['expires'] ?? date('Y-m-d', strtotime('+1 year')),
                    $now
                ]
            );
        }
    }

    private function generateLicenseKey(): string
    {
        return strtoupper(bin2hex(random_bytes(6)));
    }
}
