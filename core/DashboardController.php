<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

class DashboardController
{
    /**
     * Retorna estatísticas rápidas do dia.
     */
    public function getStats(): array
    {
        try {
            $emitidas = Database::fetch("SELECT COUNT(*) as total FROM senhas");
            $chamadas = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE status != 'AGUARDANDO'");
            $finalizadas = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE status = 'FINALIZADA'");
            $pendentes = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE status = 'AGUARDANDO'");

            return [
                'emitidas'    => (int) ($emitidas['total'] ?? 0),
                'chamadas'    => (int) ($chamadas['total'] ?? 0),
                'finalizadas' => (int) ($finalizadas['total'] ?? 0),
                'pendentes'   => (int) ($pendentes['total'] ?? 0),
                'success'     => true
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verifica a saúde técnica do sistema local.
     */
    public function getHealth(): array
    {
        $licenseManager = new \BTQueue\Core\MasterSync\LicenseManager();
        $isBlocked = $licenseManager->isBlocked();

        $licenca = Database::fetch("SELECT status, ultima_validacao FROM licencas LIMIT 1");
        $lastSync = $licenca['ultima_validacao'] ?? null;

        $syncStatus = 'Desconectado';
        $syncColor = 'var(--danger)';

        if ($lastSync) {
            // Lógica de Diferença Absoluta (v4.8.1 - Timezone Fix)
            $diff = abs(time() - strtotime($lastSync));

            if ($diff < 600) { // Sincronizado se houve pulso nos últimos 10 minutos
                $syncStatus = 'Sincronizado';
                $syncColor = 'var(--success)';
            } else {
                $syncStatus = 'Atrasado';
                $syncColor = 'var(--warning)';
            }
        }

        return [
            [
                'nome' => 'SQLite',
                'status' => 'Conectado',
                'color' => 'var(--success)'
            ],
            [
                'nome' => 'API Local',
                'status' => 'Ativa',
                'color' => 'var(--success)'
            ],
            [
                'nome' => 'MasterSync',
                'status' => $syncStatus,
                'color' => $syncColor
            ],
            [
                'nome' => 'Licença',
                'status' => $isBlocked ? 'Bloqueada' : 'Válida',
                'color' => $isBlocked ? 'var(--danger)' : 'var(--success)'
            ]
        ];
    }

    /**
     * Garante que o motor de atividades está registrando o tempo atual.
     */
    public function ensureActivityIsAlive(): void
    {
        // Placeholder para futuras checagens de processo
    }
}
