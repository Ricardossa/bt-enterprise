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

    /**
     * Auxiliar: Detecta quais colunas existem na tabela para evitar erros de NOT NULL
     */
    private function getTableColumns(string $table): array
    {
        $res = $this->db->query("PRAGMA table_info($table)");
        return array_column($res->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    /**
     * Emitir Senha - Versão Ultra-Compatível (V4.2+)
     */
    public function emitir(string $prefixo, int $servicoId, string $clienteUuid, ?string $deviceId = null): array
    {
        try {
            Database::begin();

            // --- LÓGICA DE RESET DIÁRIO BLINDADA (V5.2) ---
            // Usamos a data do PHP para garantir sincronia com o fuso horário local
            $agora = date('Y-m-d H:i:s');
            $hoje = date('Y-m-d');

            $ultima = Database::fetch(
                "SELECT numero FROM senhas
                 WHERE servico_id = ?
                 AND date(created_at) = ?
                 ORDER BY numero DESC LIMIT 1",
                [$servicoId, $hoje]
            );

            $numero = $ultima ? ((int)$ultima['numero']) + 1 : 1;
            $codigoGerado = strtoupper($prefixo) . str_pad((string)$numero, 3, '0', STR_PAD_LEFT);
            $uuid = bin2hex(random_bytes(16));

            $cols = $this->getTableColumns('senhas');

            // Prepara o mapeamento dinâmico
            $data = [
                'uuid' => $uuid,
                'cliente_uuid' => $clienteUuid,
                'servico_id' => $servicoId,
                'numero' => $numero,
                'prefixo' => $prefixo,
                'status' => 'AGUARDANDO',
                'device_id' => $deviceId,
                'created_at' => $agora, // Força a data do PHP
                'emitida_em' => $agora  // Força a data do PHP
            ];

            // Garante compatibilidade entre 'senha' e 'codigo'
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

    /**
     * Chamar Próxima Senha
     */
    public function chamar($param1, ?int $guicheId = null, ?string $atendente = null): array
    {
        try {
            Database::begin();

            $isModoNovo = ($guicheId !== null);
            $senha = null;
            $guicheCodigoLogico = '';
            $guicheIdFinal = null;

            if (!$isModoNovo) {
                $guicheCodigoLogico = (string)$param1;
                $guicheInfo = Database::fetch("SELECT id FROM guiches WHERE codigo = ? LIMIT 1", [$guicheCodigoLogico]);
                if (!$guicheInfo) {
                    Database::rollback();
                    return ['success' => false, 'message' => 'Guichê não encontrado.'];
                }
                $guicheIdFinal = (int)$guicheInfo['id'];

                $senha = Database::fetch(
                    "SELECT s.* FROM senhas s
                     JOIN guiche_servicos gs ON s.servico_id = gs.servico_id
                     WHERE s.status = 'AGUARDANDO'
                     AND gs.guiche_id = ?
                     AND date(s.created_at) = ?
                     ORDER BY s.id LIMIT 1",
                    [$guicheIdFinal, date('Y-m-d')]
                );
            } else {
                $servicoId = (int)$param1;
                $guicheIdFinal = $guicheId;
                $guicheInfo = Database::fetch("SELECT codigo, nome FROM guiches WHERE id = ? LIMIT 1", [$guicheIdFinal]);
                $guicheCodigoLogico = $guicheInfo ? $guicheInfo['codigo'] : (string)$guicheIdFinal;

                $senha = Database::fetch(
                    "SELECT * FROM senhas
                     WHERE status = 'AGUARDANDO'
                     AND servico_id = ?
                     AND date(created_at) = ?
                     ORDER BY id LIMIT 1",
                    [$servicoId, date('Y-m-d')]
                );
            }

            if (!$senha) {
                Database::rollback();
                return ['success' => false, 'message' => 'Nenhuma senha disponível.'];
            }

            Database::execute(
                "UPDATE senhas SET status='CHAMANDO', guiche_id=?, atendente=?, chamada_em=CURRENT_TIMESTAMP WHERE id=?",
                [$guicheIdFinal, $atendente, $senha['id']]
            );

            // Payload para TV e Sync
            $codigoExibir = $senha['codigo'] ?? ($senha['senha'] ?? '---');

            Database::execute(
                "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
                [
                    'CHAMAR', 
                    'senha',
                    (int)$senha['id'],
                    json_encode(['senha' => $codigoExibir, 'guiche' => $guicheCodigoLogico], JSON_UNESCAPED_UNICODE)
                ]
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

    public function finalizar(int $id): array
    {
        Database::execute("UPDATE senhas SET status='FINALIZADA', finalizada_em=CURRENT_TIMESTAMP WHERE id=?", [$id]);
        return ['success' => true];
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

            $chamando = Database::fetch(
                "SELECT * FROM senhas WHERE status='CHAMANDO' AND guiche_id = ? ORDER BY chamada_em DESC LIMIT 1",
                [$guicheIdLogico]
            );

            $fila = Database::fetchAll(
                "SELECT s.*, sv.nome as servico_nome
                 FROM senhas s
                 JOIN servicos sv ON s.servico_id = sv.id
                 JOIN guiche_servicos gs ON s.servico_id = gs.servico_id
                 WHERE s.status='AGUARDANDO'
                 AND gs.guiche_id = ?
                 AND date(s.created_at) = ?
                 ORDER BY s.id",
                [$guicheIdLogico, date('Y-m-d')]
            );
            
            $guicheIdParaOperador = $guicheIdLogico;
        } else {
            $servicoId = ($param1 !== null) ? (int)$param1 : null;
            $guicheInfo = Database::fetch("SELECT nome FROM guiches WHERE id = ? LIMIT 1", [$guicheId]);
            $guicheCodigo = $guicheInfo ? $guicheInfo['nome'] : "Guichê " . $guicheId;

            $chamando = Database::fetch(
                "SELECT * FROM senhas WHERE status='CHAMANDO' AND guiche_id = ? ORDER BY chamada_em DESC LIMIT 1",
                [$guicheId]
            );

            if ($servicoId) {
                $fila = Database::fetchAll(
                    "SELECT s.*, sv.nome as servico_nome
                     FROM senhas s
                     JOIN servicos sv ON s.servico_id = sv.id
                     WHERE s.status='AGUARDANDO'
                     AND s.servico_id = ?
                     AND date(s.created_at) = ?
                     ORDER BY s.id ASC",
                    [$servicoId, date('Y-m-d')]
                );
            }
            
            $guicheIdParaOperador = $guicheId;
        }

        $operadorNome = 'Operador Geral';
        if ($guicheIdParaOperador) {
            $operador = Database::fetch("SELECT nome FROM operadores WHERE guiche_id = ? AND ativo = 1 LIMIT 1", [$guicheIdParaOperador]);
            if ($operador) $operadorNome = $operador['nome'];
        }

        return [
            'operador_nome' => $operadorNome,
            'guiche_codigo' => $guicheCodigo,
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
                    'servico_id' => isset($item['servico_id']) ? (int)$item['servico_id'] : null
                ];
            }, $fila ?? [])
        ];
    }

    public function getHistoricoChamadas(int $limit = 5): array
    {
        $hoje = date('Y-m-d');
        return Database::fetchAll(
            "SELECT COALESCE(s.codigo, s.senha) as senha, g.nome as guiche_nome
             FROM senhas s
             LEFT JOIN guiches g ON g.id = s.guiche_id
             WHERE s.status IN ('CHAMANDO', 'FINALIZADA')
             AND date(s.created_at) = ?
             ORDER BY s.chamada_em DESC, s.id DESC
             LIMIT ?",
            [$hoje, $limit]
        );
    }

    public function estatisticas(): array
    {
        $hoje = date('Y-m-d');
        $where = " WHERE date(created_at) = '$hoje'";

        return [
            'emitidas' => (int) Database::fetch("SELECT COUNT(*) AS total FROM senhas" . $where)['total'],
            'pendentes' => (int) Database::fetch("SELECT COUNT(*) AS total FROM senhas" . $where . " AND status = 'AGUARDANDO'")['total'],
            'atendimento' => (int) Database::fetch("SELECT COUNT(*) AS total FROM senhas" . $where . " AND status = 'CHAMANDO'")['total'],
            'finalizadas' => (int) Database::fetch("SELECT COUNT(*) AS total FROM senhas" . $where . " AND status = 'FINALIZADA'")['total']
        ];
    }
}
