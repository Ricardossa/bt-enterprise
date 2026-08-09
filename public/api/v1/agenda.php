<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\ScheduleService;
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

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

    // 3. BUSCAR REGRAS DE UM SERVIÇO
    if ($method === 'GET' && $action === 'get_regras') {
        Auth::protegerAPI('ADMIN');
        $servicoId = (int)$_GET['servico_id'];
        $regras = Database::fetchAll("SELECT * FROM agenda_regras WHERE servico_id = ? ORDER BY dia_semana ASC", [$servicoId]);
        echo json_encode(['success' => true, 'data' => $regras]);
        exit;
    }

    // 4. SALVAR REGRAS DE UM SERVIÇO
    if ($method === 'POST' && $action === 'save_regras') {
        Auth::protegerAPI('ADMIN');
        $input = json_decode(file_get_contents('php://input'), true);
        $servicoId = (int)($input['servico_id'] ?? 0);
        $regras = $input['regras'] ?? [];

        if (!$servicoId) throw new Exception("ID do serviço inválido.");

        // Limpa regras antigas para este serviço de forma segura
        Database::execute("DELETE FROM agenda_regras WHERE servico_id = ?", [$servicoId]);

        foreach ($regras as $r) {
            Database::execute(
                "INSERT INTO agenda_regras (servico_id, dia_semana, hora_inicio, hora_fim, duracao_slot, ativo)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $servicoId,
                    (int)$r['dia_semana'],
                    $r['hora_inicio'],
                    $r['hora_fim'],
                    (int)($r['duracao_slot'] ?? 30),
                    (int)($r['ativo'] ?? 1)
                ]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Regras salvas com sucesso!']);
        exit;
    }

    // 5. REALIZAR RESERVA (PÚBLICO)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $servicoId = (int)($input['servico_id'] ?? 0);
        $nome = strtoupper(trim((string)($input['nome_cliente'] ?? '')));
        $data = trim((string)($input['data'] ?? ''));
        $hora = trim((string)($input['hora'] ?? ''));

        if (!$servicoId || !$nome || !$data || !$hora) {
            throw new Exception("Preencha todos os campos obrigatórios.");
        }

        $dataHora = "$data $hora:00";
        $resultado = $service->reservar($servicoId, $nome, $dataHora);

        echo json_encode($resultado);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
