<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\ScheduleService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $service = new ScheduleService();
    $method = $_SERVER['REQUEST_METHOD'];

    // 1. LISTAR SERVIÇOS QUE PERMITEM AGENDAMENTO
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'servicos') {
        $servicos = Database::fetchAll("SELECT id, nome, icone, cor FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
        echo json_encode(['success' => true, 'data' => $servicos]);
        exit;
    }

    // 2. BUSCAR SLOTS DISPONÍVEIS
    if ($method === 'GET' && isset($_GET['servico_id'], $_GET['data'])) {
        $servicoId = (int)$_GET['servico_id'];
        $data = $_GET['data']; // YYYY-MM-DD

        $slots = $service->getSlotsDisponiveis($servicoId, $data);
        echo json_encode(['success' => true, 'data' => $slots]);
        exit;
    }

    // 3. REALIZAR RESERVA
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
