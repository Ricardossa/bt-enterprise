<?php

declare(strict_types=1);

namespace BTQueue\Core;

/**
 * Módulo de Compliance Industrial - BT Guardian
 * Responsável por vigiar agendamentos e aplicar punições automáticas.
 */
final class ComplianceService
{
    /**
     * Executa o radar de abandonos.
     * Marca como 'FALTOU' quem não fez check-in em até 15 minutos.
     */
    public function runRadar(): void
    {
        // --- MODO AMNESTY (v6.5.2) ---
        // Radar pausado temporariamente para transição de sistema.
        return;

        try {
            // Busca agendamentos expirados (15 min de tolerância)
            // Somente de hoje para evitar processar lixo antigo em massa
            $expirados = Database::fetchAll(
                "SELECT * FROM senhas
                 WHERE status = 'AGENDADO'
                 AND data_agendamento < datetime('now', 'localtime', '-15 minutes')
                 AND date(data_agendamento) = date('now', 'localtime')"
            );

            foreach ($expirados as $s) {
                $this->processarFalta($s);
            }
        } catch (\Throwable $e) {
            Logger::error("Erro no Radar de Abandonados: " . $e->getMessage());
        }
    }

    private function processarFalta(array $senha): void
    {
        // 1. Marca como FALTOU
        Database::execute(
            "UPDATE senhas SET status = 'FALTOU', updated_at = datetime('now', 'localtime') WHERE id = ?",
            [$senha['id']]
        );

        Logger::info("📍 FALTA REGISTRADA: {$senha['nome_cliente']} não compareceu ao horário das " . date('H:i', strtotime($senha['data_agendamento'])));

        // 2. Conta reincidências nos últimos 90 dias
        $faltas = Database::fetch(
            "SELECT COUNT(*) as total FROM senhas
             WHERE nome_cliente = ?
             AND status = 'FALTOU'
             AND created_at > datetime('now', '-90 days')",
            [$senha['nome_cliente']]
        );

        $totalFaltas = (int)($faltas['total'] ?? 0);

        // 3. Aplica suspensão gradual
        if ($totalFaltas >= 1) {
            $dias = 14;
            if ($totalFaltas == 2) $dias = 30;
            if ($totalFaltas >= 3) $dias = 60;

            $dataFim = date('Y-m-d', strtotime("+$dias days"));
            $motivo = "Suspensão automática: $totalFaltas falta(s) sem aviso prévio nos últimos 90 dias.";

            Database::execute(
                "INSERT OR REPLACE INTO agenda_suspensoes (identificador, motivo, data_fim) VALUES (?, ?, ?)",
                [$senha['nome_cliente'], $motivo, $dataFim]
            );

            // Tenta suspender pelo WhatsApp também se disponível
            if (!empty($senha['whatsapp'])) {
                $whatsappLimpo = preg_replace('/\D/', '', $senha['whatsapp']);
                Database::execute(
                    "INSERT OR REPLACE INTO agenda_suspensoes (identificador, motivo, data_fim) VALUES (?, ?, ?)",
                    [$whatsappLimpo, $motivo, $dataFim]
                );
            }

            Logger::warning("🚨 SUSPENSÃO APLICADA: {$senha['nome_cliente']} suspenso por $dias dias.");
        }
    }
}
