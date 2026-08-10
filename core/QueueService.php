<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;
use PDO;
use Throwable;

class QueueService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    private function getTableColumns(string $table): array
    {
        $res = $this->db->query("PRAGMA table_info($table)");
        return array_column($res->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    public function emitir(string $prefixo, int $servicoId, string $clienteUuid, ?string $deviceId = null): array
    {
        try {
            Database::begin();

            $agora = date('Y-m-d H:i:s');
            // Reset diário inteligente (considera 18h de janela para evitar fuso UTC)
            $ultima = Database::fetch(
                "SELECT numero FROM senhas
                 WHERE servico_id = ?
                 AND created_at > datetime('now', '-18 hours')
                 ORDER BY numero DESC LIMIT 1",
                [$servicoId]
            );

            $numero = $ultima ? ((int)$ultima['numero']) + 1 : 1;
            $codigoGerado = strtoupper($prefixo) . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
            $uuid = bin2hex(random_bytes(16));

            $cols = $this->getTableColumns('senhas');

            $data = [
                'uuid' => $uuid,
                'cliente_uuid' => $clienteUuid,
                'servico_id' => $servicoId,
                'numero' => $numero,
                'prefixo' => $prefixo,
                'status' => 'AGUARDANDO',
                'device_id' => $deviceId,
                'created_at' => $agora,
                'emitida_em' => $agora
            ];

            if (in_array('codigo', $cols)) $data['codigo'] = $codigoGerado;
            if (in_array('senha', $cols)) $data['senha'] = $codigoGerado;

            $fields = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_fill(0, count($data), "?"));

            Database::execute(
                "INSERT INTO senhas ($fields) VALUES ($placeholders)",
                array_values($data)
            );

            $id = Database::lastInsertId();
            ActivityService::log('INFO', 'QUEUE', "Senha $codigoGerado emitida.", [], 'Sistema');

            Database::commit();

            return [
                'success' => true,
                'id'      => $id,
                'senha'   => $codigoGerado,
                'uuid'    => $uuid
            ];

        } catch (Throwable $e) {
            Database::rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function chamar($param1, ?int $guicheId = null, ?string $atendente = null): array
    {
        try {
            // [ATOMICIDADE] Bloqueio imediato para evitar condição de corrida
            Database::beginImmediate();

            $isModoNovo = ($guicheId !== null);
            $guicheIdFinal = null;

            if (!$isModoNovo) {
                $guicheCodigoLogico = (string)$param1;
                $guicheInfo = Database::fetch("SELECT id FROM guiches WHERE codigo = ? LIMIT 1", [$guicheCodigoLogico]);
                if (!$guicheInfo) {
                    Database::rollback();
                    return ['success' => false, 'message' => 'Guichê não encontrado.'];
                }
                $guicheIdFinal = (int)$guicheInfo['id'];

                $sqlBusca = "SELECT s.* FROM senhas s
                             JOIN guiche_servicos gs ON s.servico_id = gs.servico_id
                             WHERE s.status = 'AGUARDANDO'
                             AND gs.guiche_id = ?
                             AND s.created_at > datetime('now', '-18 hours')
                             AND (s.device_id IS NULL OR s.device_id = '' OR s.device_id NOT IN (
                                 SELECT device_id FROM senhas WHERE status = 'CHAMANDO' AND device_id IS NOT NULL AND device_id != ''
                             ))
                             ORDER BY s.id ASC LIMIT 1";
                $senha = Database::fetch($sqlBusca, [$guicheIdFinal]);
            } else {
                $servicoId = (int)$param1;
                $guicheIdFinal = $guicheId;

                $sqlBusca = "SELECT * FROM senhas
                             WHERE status = 'AGUARDANDO'
                             AND servico_id = ?
                             AND created_at > datetime('now', '-18 hours')
                             AND (device_id IS NULL OR device_id = '' OR device_id NOT IN (
                                 SELECT device_id FROM senhas WHERE status = 'CHAMANDO' AND device_id IS NOT NULL AND device_id != ''
                             ))
                             ORDER BY id ASC LIMIT 1";
                $senha = Database::fetch($sqlBusca, [$servicoId]);
            }

            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Nenhuma senha elegível no momento.'];
            }

            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=CURRENT_TIMESTAMP WHERE id=?",
                [$guicheIdFinal, $atendente, $senha['id']]
            );

            if (!empty($senha['device_id'])) {
                Database::execute(
                    "UPDATE senhas SET status = 'CONGELADA' WHERE device_id = ? AND status = 'AGUARDANDO'",
                    [$senha['device_id']]
                );
            }

            $guicheInfo = Database::fetch("SELECT codigo FROM guiches WHERE id = ? LIMIT 1", [$guicheIdFinal]);
            $guicheCodigoLogico = $guicheInfo ? $guicheInfo['codigo'] : (string)$guicheIdFinal;
            $codigoExibir = $senha['codigo'] ?? ($senha['senha'] ?? '---');

            Database::execute(
                "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
                ['CHAMAR', 'senha', (int)$senha['id'], json_encode(['senha' => $codigoExibir, 'guiche' => $guicheCodigoLogico], JSON_UNESCAPED_UNICODE)]
            );

            ActivityService::log('SUCCESS', 'QUEUE', "Senha $codigoExibir chamada no $guicheCodigoLogico", [], 'Operador');

            Database::commit();

            return [
                'success' => true,
                'id' => $senha['id'],
                'codigo' => $codigoExibir,
                'guiche' => $guicheCodigoLogico
            ];

        } catch (Throwable $e) {
            Database::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Chama uma senha específica pelo ID (Super-Poder Admin).
     */
    public function chamarEspecifico(int $id, int $guicheId, ?string $atendente = null): array
    {
        try {
            Database::beginImmediate();

            $senha = Database::fetch("SELECT * FROM senhas WHERE id = ? AND status IN ('AGUARDANDO', 'CONGELADA') LIMIT 1", [$id]);
            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Senha não encontrada ou já processada.'];
            }

            // 1. Muda para CHAMANDO
            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=CURRENT_TIMESTAMP WHERE id=?",
                [$guicheId, $atendente, $id]
            );

            // 2. [CONGELAMENTO] - Se houver outras senhas do mesmo dispositivo, congela-as
            if (!empty($senha['device_id'])) {
                Database::execute(
                    "UPDATE senhas SET status = 'CONGELADA' WHERE device_id = ? AND status = 'AGUARDANDO'",
                    [$senha['device_id']]
                );
            }

            $guicheInfo = Database::fetch("SELECT codigo FROM guiches WHERE id = ? LIMIT 1", [$guicheId]);
            $guicheCodigoLogico = $guicheInfo ? $guicheInfo['codigo'] : (string)$guicheId;
            $codigoExibir = $senha['codigo'] ?? ($senha['senha'] ?? '---');

            Database::execute(
                "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
                ['CHAMAR', 'senha', $id, json_encode(['senha' => $codigoExibir, 'guiche' => $guicheCodigoLogico], JSON_UNESCAPED_UNICODE)]
            );

            ActivityService::log('SUCCESS', 'QUEUE', "[ADMIN] Senha $codigoExibir (Fura Fila) chamada no $guicheCodigoLogico", [], 'Administrador');

            Database::commit();

            return [
                'success' => true,
                'id' => $id,
                'codigo' => $codigoExibir,
                'guiche' => $guicheCodigoLogico
            ];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function finalizar(int $id): array
    {
        try {
            Database::beginImmediate();

            $senha = Database::fetch("SELECT device_id, status FROM senhas WHERE id = ?", [$id]);
            if (!$senha) throw new Exception("Senha não encontrada.");

            // 1. Finaliza o atendimento atual
            Database::execute("UPDATE senhas SET status='FINALIZADA', finalizada_em=CURRENT_TIMESTAMP WHERE id=?", [$id]);

            // 2. [DESCONGELAMENTO SEGURO]
            if (!empty($senha['device_id'])) {
                $dev = $senha['device_id'];
                Database::execute(
                    "UPDATE senhas SET status = 'AGUARDANDO'
                     WHERE device_id = ?
                     AND status = 'CONGELADA'
                     AND NOT EXISTS (SELECT 1 FROM senhas WHERE device_id = ? AND status = 'CHAMANDO')",
                    [$dev, $dev]
                );
            }

            Database::commit();
            return ['success' => true];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAgendados(?int $servicoId = null): array
    {
        try {
            // [DIAMOND SAFE] Verifica colunas antes de realizar a query (Anti-Error 500)
            $resInfo = $this->db->query("PRAGMA table_info(senhas)");
            $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');

            if (!in_array('data_agendamento', $cols) || !in_array('nome_cliente', $cols)) {
                return [];
            }

            // [TIMEZONE SAFE] Usamos 'localtime' para bater com o horário de Brasília/Bahia
            $sql = "SELECT id, codigo, nome_cliente, data_agendamento, status
                    FROM senhas
                    WHERE status IN ('AGENDADO', 'PRESENTE')
                    AND data_agendamento > datetime('now', 'localtime', '-5 hours')
                    AND data_agendamento < datetime('now', 'localtime', '+18 hours') ";

            $params = [];
            if ($servicoId) {
                $sql .= " AND servico_id = ? ";
                $params[] = $servicoId;
            }

            // Agrupa e ordena no final
            $sql .= " GROUP BY nome_cliente, data_agendamento ORDER BY data_agendamento ASC";

            return Database::fetchAll($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function chamarAgendado(int $id, int $guicheId, ?string $atendente = null): array
    {
        try {
            Database::beginImmediate();

            $senha = Database::fetch("SELECT * FROM senhas WHERE id = ? AND status IN ('AGENDADO', 'PRESENTE') LIMIT 1", [$id]);
            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Agendamento não encontrado ou já processado.'];
            }

            // [SEGURANÇA] Verifica se o dispositivo já está em atendimento
            if (!empty($senha['device_id'])) {
                $check = Database::fetch("SELECT id FROM senhas WHERE device_id = ? AND status = 'CHAMANDO' LIMIT 1", [$senha['device_id']]);
                if ($check) {
                    Database::rollback();
                    return ['success' => false, 'message' => 'Este paciente já está sendo chamado em outro guichê!'];
                }
            }

            // 1. Muda para CHAMANDO
            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=CURRENT_TIMESTAMP WHERE id=?",
                [$guicheId, $atendente, $id]
            );

            // 2. [CONGELAMENTO]
            if (!empty($senha['device_id'])) {
                Database::execute(
                    "UPDATE senhas SET status = 'CONGELADA' WHERE device_id = ? AND status = 'AGUARDANDO'",
                    [$senha['device_id']]
                );
            }

            $guicheInfo = Database::fetch("SELECT codigo FROM guiches WHERE id = ? LIMIT 1", [$guicheId]);
            $guicheCodigoLogico = $guicheInfo ? $guicheInfo['codigo'] : (string)$guicheId;
            $codigoExibir = $senha['codigo'] ?? ($senha['senha'] ?? '---');

            Database::execute(
                "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
                ['CHAMAR', 'senha', $id, json_encode(['senha' => $codigoExibir, 'guiche' => $guicheCodigoLogico], JSON_UNESCAPED_UNICODE)]
            );

            ActivityService::log('SUCCESS', 'QUEUE', "Agendado $codigoExibir chamado no $guicheCodigoLogico", [], 'Operador');

            Database::commit();

            return [
                'success' => true,
                'id' => $id,
                'codigo' => $codigoExibir,
                'guiche' => $guicheCodigoLogico
            ];
        } catch (Throwable $e) {
            Database::rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function estado($param1 = null, ?int $guicheId = null): array
    {
        $servicoId = null;
        $guicheCodigo = '01';
        $chamando = null;
        $fila = [];
        $guicheIdParaOperador = null;

        $isModoNovo = ($guicheId !== null);

        if (!$isModoNovo) {
            $guicheCodigo = ($param1 !== null) ? (string)$param1 : '01';
            $guicheInfo = Database::fetch("SELECT id FROM guiches WHERE codigo = ? LIMIT 1", [$guicheCodigo]);
            $guicheIdLogico = $guicheInfo ? (int)$guicheInfo['id'] : 1;
            $guicheIdParaOperador = $guicheIdLogico;

            $chamando = Database::fetch(
                "SELECT * FROM senhas WHERE status='CHAMANDO' AND guiche_id = ? ORDER BY chamada_em DESC LIMIT 1",
                [$guicheIdLogico]
            );

            $fila = Database::fetchAll(
                "SELECT s.*, sv.nome as servico_nome
                 FROM senhas s
                 JOIN servicos sv ON s.servico_id = sv.id
                 JOIN guiche_servicos gs ON s.servico_id = gs.servico_id
                 WHERE s.status IN ('AGUARDANDO', 'CONGELADA')
                 AND gs.guiche_id = ?
                 AND s.created_at > datetime('now', '-18 hours')
                 ORDER BY s.id",
                [$guicheIdLogico]
            );
        } else {
            $servicoId = ($param1 !== null) ? (int)$param1 : null;
            $guicheInfo = Database::fetch("SELECT nome FROM guiches WHERE id = ? LIMIT 1", [$guicheId]);
            $guicheCodigo = $guicheInfo ? $guicheInfo['nome'] : "Guichê " . $guicheId;
            $guicheIdParaOperador = $guicheId;

            $chamando = Database::fetch(
                "SELECT * FROM senhas WHERE status='CHAMANDO' AND guiche_id = ? ORDER BY chamada_em DESC LIMIT 1",
                [$guicheId]
            );

            if ($servicoId) {
                $fila = Database::fetchAll(
                    "SELECT s.*, sv.nome as servico_nome
                     FROM senhas s
                     JOIN servicos sv ON s.servico_id = sv.id
                     WHERE s.status IN ('AGUARDANDO', 'CONGELADA')
                     AND s.servico_id = ?
                     AND s.created_at > datetime('now', '-18 hours')
                     ORDER BY s.id ASC",
                    [$servicoId]
                );
            }
        }

        $operadorNome = 'Operador Geral';
        if ($guicheIdParaOperador) {
            $operador = Database::fetch("SELECT nome FROM operadores WHERE guiche_id = ? AND ativo = 1 LIMIT 1", [$guicheIdParaOperador]);
            if ($operador) $operadorNome = $operador['nome'];
        }

        // Carrega rótulo personalizado
        $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'label_cliente' LIMIT 1");
        $label = $config['valor'] ?? 'Paciente';

        return [
            'operador_nome' => $operadorNome,
            'guiche_codigo' => $guicheCodigo,
            'label_cliente' => $label,
            'chamando' => $chamando ? [
                'id' => $chamando['id'],
                'codigo' => $chamando['codigo'] ?? $chamando['senha'],
                'guiche' => $guicheCodigo
            ] : null,
            'fila' => array_map(function($item) {
                return [
                    'id' => (int)$item['id'],
                    'codigo' => $item['codigo'] ?? $item['senha'],
                    'servico_nome' => $item['servico_nome'],
                    'servico_id' => isset($item['servico_id']) ? (int)$item['servico_id'] : null,
                    'status' => $item['status']
                ];
            }, $fila ?? [])
        ];
    }

    public function getHistoricoChamadas(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT COALESCE(s.codigo, s.senha) as senha, g.nome as guiche_nome
             FROM senhas s
             LEFT JOIN guiches g ON g.id = s.guiche_id
             WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
             AND s.created_at > datetime('now', '-18 hours')
             ORDER BY s.chamada_em DESC, s.id DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getStatsPorPeriodo(?string $inicio = null, ?string $fim = null): array
    {
        if ($inicio === null) {
            $sqlBase = "SELECT COUNT(*) as total FROM senhas WHERE created_at > datetime('now', '-18 hours')";
            $params = [];
        } else {
            $sqlBase = "SELECT COUNT(*) as total FROM senhas WHERE date(created_at) BETWEEN ? AND ?";
            $params = [$inicio, $fim ?: $inicio];
        }

        return [
            'emitidas'    => (int) (Database::fetch($sqlBase, $params)['total'] ?? 0),
            'pendentes'   => (int) (Database::fetch($sqlBase . " AND status IN ('AGUARDANDO', 'CONGELADA')", $params)['total'] ?? 0),
            'chamadas'    => (int) (Database::fetch($sqlBase . " AND status NOT IN ('AGUARDANDO', 'CONGELADA')", $params)['total'] ?? 0),
            'finalizadas' => (int) (Database::fetch($sqlBase . " AND status = 'FINALIZADA'", $params)['total'] ?? 0),
            'success'     => true
        ];
    }

    public function estatisticas(): array
    {
        return $this->getStatsPorPeriodo();
    }
}
