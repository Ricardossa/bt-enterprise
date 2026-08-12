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

        // 2. Busca slots já ocupados no banco (v7.3.0: Agora inclui status PRESENTE)
        $ocupadosRaw = Database::fetchAll(
            "SELECT data_agendamento FROM senhas
             WHERE servico_id = ?
             AND date(data_agendamento) = date(?)
             AND status IN ('AGENDADO', 'CHAMANDO', 'FINALIZADA', 'PRESENTE')",
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
     * Realiza a reserva de um slot com regras de Compliance Triplo (v6.4).
     */
    public function reservar(int $servicoId, string $nome, string $dataHora, string $whatsapp = '', string $deviceId = ''): array
    {
        try {
            Database::beginImmediate();

            $hojeData = date('Y-m-d', strtotime($dataHora));
            $horaMinuto = date('H:i', strtotime($dataHora));
            $mesAno = date('Y-m', strtotime($dataHora));

            // --- 0. VALIDAÇÃO DE GRADE E HORIZONTE (v7.3 Hardened) ---
            $slotsValidos = $this->getSlotsDisponiveis($servicoId, $hojeData);
            if (!in_array($horaMinuto, $slotsValidos)) {
                throw new Exception("Desculpe, o horário selecionado não está mais disponível ou é inválido.");
            }

            // 1. VERIFICA SUSPENSÃO ATIVA (Cadeado Triplo: Nome, WhatsApp ou DeviceID)
            $whatsappLimpo = preg_replace('/\D/', '', $whatsapp);

            $sqlSusp = "SELECT * FROM agenda_suspensoes
                        WHERE data_fim >= date('now')
                        AND (
                            identificador = ?
                            OR (identificador = ? AND ? != '')
                            OR (identificador = ? AND ? != '')
                        ) LIMIT 1";

            $suspensao = Database::fetch($sqlSusp, [$nome, $whatsappLimpo, $whatsappLimpo, $deviceId, $deviceId]);

            if ($suspensao) {
                throw new Exception("Seu acesso está suspenso até " . date('d/m/Y', strtotime($suspensao['data_fim'])) . " por descumprimento das regras de agendamento.");
            }

            // 2. REGRA: APENAS 1 AGENDAMENTO POR DIA (Checagem por Nome ou WhatsApp)
            $jaTemHoje = Database::fetch(
                "SELECT id FROM senhas WHERE (nome_cliente = ? OR (whatsapp = ? AND ? != '')) AND date(data_agendamento) = ? AND status != 'CANCELADO' LIMIT 1",
                [$nome, $whatsappLimpo, $whatsappLimpo, $hojeData]
            );
            if ($jaTemHoje) {
                throw new Exception("Limite diário: Você já possui um agendamento para este dia.");
            }

            // 3. REGRA: MÁXIMO 2 VEZES POR MÊS
            $mesContagem = Database::fetch(
                "SELECT COUNT(*) as total FROM senhas
                 WHERE (nome_cliente = ? OR (whatsapp = ? AND ? != ''))
                 AND strftime('%Y-%m', data_agendamento) = ?
                 AND status != 'CANCELADO'",
                [$nome, $whatsappLimpo, $whatsappLimpo, $mesAno]
            );
            if ((int)$mesContagem['total'] >= 2) {
                throw new Exception("Limite mensal atingido. Só é permitida a marcação de horário 2 VEZES no mês por fornecedor.");
            }

            // 4. VERIFICA DISPONIBILIDADE DO SLOT (CONCORRÊNCIA)
            $check = Database::fetch(
                "SELECT id FROM senhas WHERE servico_id = ? AND data_agendamento = ? AND status != 'CANCELADO' LIMIT 1",
                [$servicoId, $dataHora]
            );

            if ($check) {
                throw new Exception("Desculpe, este horário acabou de ser preenchido por outro representante.");
            }

            $uuid = bin2hex(random_bytes(16));
            $cancelToken = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            Database::execute(
                "INSERT INTO senhas (
                    uuid, cliente_uuid, codigo, numero, prefixo,
                    nome_cliente, status, data_agendamento, servico_id, created_at, emitida_em, whatsapp, cancel_token
                ) VALUES (?, 'NATIVO', 'AGD', 0, 'G', ?, 'AGENDADO', ?, ?, datetime('now', 'localtime'), ?, ?, ?)",
                [$uuid, $nome, $dataHora, $servicoId, $dataHora, $whatsapp, $cancelToken]
            );

            Database::commit();

            // --- NOTIFICAÇÃO WHATSAPP AUTOMÁTICA (v6.6) ---
            if (!empty($whatsapp)) {
                $msg = "✅ AGENDAMENTO CONFIRMADO!\n\n" .
                       "📍 Local: " . (Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'empresa' LIMIT 1")['valor'] ?? 'Brandão Tech') . "\n" .
                       "👤 Nome: $nome\n" .
                       "📅 Data: " . date('d/m/Y', strtotime($dataHora)) . "\n" .
                       "🕒 Hora: " . date('H:i', strtotime($dataHora)) . "\n" .
                       "🔑 Código: $cancelToken\n\n" .
                       "Para cancelar, acesse o link enviado no momento da reserva.";

                WhatsAppService::send($whatsapp, $msg);
            }

            return [
                'success' => true,
                'token' => $cancelToken,
                'horario' => date('H:i', strtotime($dataHora)),
                'data' => date('d/m/Y', strtotime($dataHora)),
                'whatsapp' => $whatsapp
            ];

        } catch (Exception $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cancela um agendamento via Token.
     */
    public function cancelar(string $token): array
    {
        $agendado = Database::fetch("SELECT * FROM senhas WHERE cancel_token = ? AND status = 'AGENDADO' LIMIT 1", [$token]);

        if (!$agendado) {
            return ['success' => false, 'message' => 'Agendamento não encontrado ou já processado.'];
        }

        Database::execute("UPDATE senhas SET status = 'CANCELADO', updated_at = datetime('now', 'localtime') WHERE id = ?", [$agendado['id']]);

        return ['success' => true, 'message' => 'Agendamento cancelado com sucesso.'];
    }
}
