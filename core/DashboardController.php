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
        $queue = new QueueService();
        return $queue->getStatsPorPeriodo();
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
