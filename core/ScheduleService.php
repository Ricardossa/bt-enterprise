<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use PDO;

/**
 * Motor de Agendamento Nativo - BT Scheduler
 * Gerencia slots de tempo, disponibilidade e reservas.
 */
final class ScheduleService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna os horários disponíveis para um serviço em uma data específica.
     */
    public function getSlotsDisponiveis(int $servicoId, string $data): array
    {
        // 1. Busca a regra de funcionamento para o dia da semana (Garante que esteja ativa)
        $diaSemana = (int)date('w', strtotime($data));
        $regra = Database::fetch(
            "SELECT * FROM agenda_regras WHERE servico_id = ? AND dia_semana = ? AND ativo = 1 LIMIT 1",
            [$servicoId, $diaSemana]
        );

        if (!$regra) return []; // Não atende ou está desativado neste dia

        // --- TRAVA DE JANELA DE LIBERAÇÃO (v6.1 Diamond) ---
        if ($regra['liberacao_dia_semana'] !== null) {
            $hojeDiaSemana = (int)date('w');
            $agoraHora = date('H:i');

            // Se hoje não for o dia de abertura OU estiver fora da janela de horário
            if ($hojeDiaSemana !== (int)$regra['liberacao_dia_semana'] ||
                ($agoraHora < $regra['liberacao_hora_inicio'] || $agoraHora > $regra['liberacao_hora_fim'])) {
                return []; // Esconde a data
            }
        }

        $inicio = $regra['hora_inicio'];
        $fim = $regra['hora_fim'];
        $duracao = (int)$regra['duracao_slot']; // em minutos

        // 2. Busca slots já ocupados no banco
        $ocupadosRaw = Database::fetchAll(
            "SELECT data_agendamento FROM senhas
             WHERE servico_id = ?
             AND date(data_agendamento) = date(?)
             AND status IN ('AGENDADO', 'CHAMANDO', 'FINALIZADA')",
            [$servicoId, $data]
        );

        $ocupados = array_map(function($item) {
            return date('H:i', strtotime($item['data_agendamento']));
        }, $ocupadosRaw);

        // 3. Gera a grade de horários
        $slots = [];
        $atual = strtotime("$data $inicio");
        $limite = strtotime("$data $fim");

        while ($atual < $limite) {
            $horaFormatada = date('H:i', $atual);

            // Só adiciona se não estiver ocupado e não for horário passado (se for hoje)
            $isPassado = (date('Y-m-d') === $data && $atual < time());

            if (!in_array($horaFormatada, $ocupados) && !$isPassado) {
                $slots[] = $horaFormatada;
            }

            $atual = strtotime("+$duracao minutes", $atual);
        }

        return $slots;
    }

    /**
     * Realiza a reserva de um slot.
     */
    public function reservar(int $servicoId, string $nome, string $dataHora, string $whatsapp = ''): array
    {
        try {
            Database::beginImmediate();

            // Verifica novamente se o slot ainda está livre (concorrência)
            $check = Database::fetch(
                "SELECT id FROM senhas WHERE servico_id = ? AND data_agendamento = ? LIMIT 1",
                [$servicoId, $dataHora]
            );

            if ($check) {
                throw new Exception("Desculpe, este horário acabou de ser preenchido.");
            }

            $uuid = bin2hex(random_bytes(16));
            $token = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

            Database::execute(
                "INSERT INTO senhas (
                    uuid, cliente_uuid, codigo, numero, prefixo,
                    nome_cliente, status, data_agendamento, servico_id, created_at, emitida_em
                ) VALUES (?, 'NATIVO', 'AGD', 0, 'G', ?, 'AGENDADO', ?, ?, datetime('now', 'localtime'), ?)",
                [$uuid, $nome, $dataHora, $servicoId, $dataHora]
            );

            Database::commit();

            return [
                'success' => true,
                'token' => $token,
                'horario' => date('H:i', strtotime($dataHora)),
                'data' => date('d/m/Y', strtotime($dataHora))
            ];

        } catch (Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
