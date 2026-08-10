<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\ScheduleService;
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
use BTQueue\Core\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $service = new ScheduleService();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // 1. LISTAR SERVIÇOS QUE PERMITEM AGENDAMENTO (PÚBLICO)
    if ($method === 'GET' && $action === 'servicos') {
        $servicos = Database::fetchAll("SELECT id, nome, icone, cor FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
        echo json_encode(['success' => true, 'data' => $servicos]);
        exit;
    }

    // 2. BUSCAR SLOTS DISPONÍVEIS (PÚBLICO)
    if ($method === 'GET' && isset($_GET['servico_id'], $_GET['data'])) {
        $servicoId = (int)$_GET['servico_id'];
        $data = $_GET['data']; // YYYY-MM-DD

        $slots = $service->getSlotsDisponiveis($servicoId, $data);
        echo json_encode(['success' => true, 'data' => $slots]);
        exit;
    }

    // --- AÇÕES ADMINISTRATIVAS (REQUER LOGIN) ---

    // 3. BUSCAR REGRAS DE UM SERVIÇO + CONFIGURAÇÃO GLOBAL
    if ($method === 'GET' && $action === 'get_regras') {
        Auth::protegerAPI('ADMIN');
        $servicoId = (int)$_GET['servico_id'];
        $regras = Database::fetchAll("SELECT * FROM agenda_regras WHERE servico_id = ? ORDER BY dia_semana ASC", [$servicoId]);

        // Busca também o horizonte global
        $horizonte = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'agenda_horizonte' LIMIT 1");

        echo json_encode([
            'success' => true,
            'data' => $regras,
            'horizonte' => $horizonte ? (int)$horizonte['valor'] : 30
        ]);
        exit;
    }

    // 4. SALVAR REGRAS DE UM SERVIÇO + CONFIGURAÇÃO GLOBAL
    if ($method === 'POST' && $action === 'save_regras') {
        Auth::protegerAPI('ADMIN');

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!$input) {
            $input = $_POST;
        }

        $servicoId = (int)($input['servico_id'] ?? 0);
        $regras = $input['regras'] ?? [];
        $horizonte = (int)($input['horizonte'] ?? 30);

        if (!$servicoId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do serviço inválido.']);
            exit;
        }

        try {
            $db = Database::getInstance();

            // 1. Atualiza Horizonte Global
            Database::execute(
                "INSERT OR REPLACE INTO configuracoes (chave, valor, tipo) VALUES ('agenda_horizonte', ?, 'NUMBER')",
                [(string)$horizonte]
            );

            // Inicia operação atômica de troca de regras
            Database::beginImmediate();

            // 1. Limpa regras antigas
            $stmtDel = $db->prepare("DELETE FROM agenda_regras WHERE servico_id = ?");
            $stmtDel->execute([$servicoId]);

            // 2. Insere novas regras (v6.1 Diamond Support)
            $stmtIns = $db->prepare("INSERT INTO agenda_regras (
                servico_id, dia_semana, hora_inicio, hora_fim, duracao_slot,
                liberacao_dia_semana, liberacao_hora_inicio, liberacao_hora_fim, ativo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($regras as $r) {
                if (!isset($r['dia_semana'])) continue;

                $libDia = (isset($r['liberacao_dia']) && $r['liberacao_dia'] !== "") ? (int)$r['liberacao_dia'] : null;

                $stmtIns->execute([
                    $servicoId,
                    (int)$r['dia_semana'],
                    (string)($r['hora_inicio'] ?? '08:00'),
                    (string)($r['hora_fim'] ?? '18:00'),
                    (int)($r['duracao_slot'] ?? 30),
                    $libDia,
                    (string)($r['liberacao_inicio'] ?? '00:00'),
                    (string)($r['liberacao_fim'] ?? '23:59'),
                    (int)($r['ativo'] ?? 0)
                ]);
            }

            Database::commit();
            Logger::info("Agenda do serviço $servicoId atualizada.", ['regras' => count($regras)], 'agenda');

            echo json_encode(['success' => true, 'message' => 'Agenda salva com sucesso!']);
            exit;
        } catch (Throwable $dbError) {
            Database::rollback();
            Logger::error("Falha ao salvar agenda: " . $dbError->getMessage(), [], 'agenda');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno no banco de dados.']);
            exit;
        }
    }

    // --- MÓDULO DE SUSPENSÕES (ADMIN) ---

    if ($method === 'GET' && $action === 'get_suspensoes') {
        Auth::protegerAPI('ADMIN');
        $lista = Database::fetchAll("SELECT * FROM agenda_suspensoes WHERE data_fim >= date('now') ORDER BY data_fim DESC");
        echo json_encode(['success' => true, 'data' => $lista]);
        exit;
    }

    if ($method === 'POST' && $action === 'add_suspensao') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true);
        $nome = strtoupper(trim((string)($input['nome'] ?? '')));
        $dias = (int)($input['dias'] ?? 14);

        if (!$nome) throw new Exception("Nome é obrigatório.");

        $dataFim = date('Y-m-d', strtotime("+$dias days"));
        Database::execute(
            "INSERT OR REPLACE INTO agenda_suspensoes (identificador, motivo, data_fim) VALUES (?, 'Reincidência de faltas sem aviso prévio', ?)",
            [$nome, $dataFim]
        );

        echo json_encode(['success' => true, 'message' => 'Fornecedor suspenso com sucesso!']);
        exit;
    }

    if ($method === 'POST' && $action === 'remove_suspensao') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        Database::execute("DELETE FROM agenda_suspensoes WHERE id = ?", [$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // 5. REALIZAR CHECK-IN (PÚBLICO NO TOTEM)
    if ($method === 'POST' && $action === 'checkin') {
        $input = json_decode(file_get_contents('php://input'), true);
        $query = strtoupper(trim((string)($input['query'] ?? '')));
        $hoje = date('Y-m-d');

        if (!$query) throw new Exception("Digite seu nome ou código.");

        // Busca agendamento para HOJE que ainda não foi atendido (v6.4.3: Busca por Token, Nome ou UUID)
        $agendamento = Database::fetch(
            "SELECT * FROM senhas
             WHERE status = 'AGENDADO'
             AND date(data_agendamento) = ?
             AND (cancel_token = ? OR nome_cliente LIKE ? OR uuid LIKE ?)
             LIMIT 1",
            [$hoje, $query, "%$query%", "$query%"]
        );

        if (!$agendamento) {
            echo json_encode(['success' => false, 'message' => 'Agendamento não localizado para hoje.']);
            exit;
        }

        // Marca como PRESENTE (Check-in realizado)
        Database::execute(
            "UPDATE senhas SET status = 'PRESENTE', updated_at = datetime('now', 'localtime') WHERE id = ?",
            [$agendamento['id']]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Check-in realizado com sucesso!',
            'data' => [
                'id' => $agendamento['id'],
                'codigo' => $agendamento['codigo'],
                'uuid' => $agendamento['uuid'],
                'cliente_uuid' => $agendamento['uuid'], // Alias para compatibilidade mobile
                'nome_cliente' => $agendamento['nome_cliente'],
                'hora' => date('H:i', strtotime($agendamento['data_agendamento']))
            ]
        ]);
        exit;
    }

    // 6. REALIZAR RESERVA (PÚBLICO)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $servicoId = (int)($input['servico_id'] ?? 0);
        $nome = strtoupper(trim((string)($input['nome_cliente'] ?? '')));
        $data = trim((string)($input['data'] ?? ''));
        $hora = trim((string)($input['hora'] ?? ''));
        $whatsapp = trim((string)($input['whatsapp'] ?? ''));
        $deviceId = trim((string)($input['device_id'] ?? ''));

        if (!$servicoId || !$nome || !$data || !$hora) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        $dataHora = "$data $hora:00";
        $resultado = $service->reservar($servicoId, $nome, $dataHora, $whatsapp, $deviceId);

        echo json_encode($resultado);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
