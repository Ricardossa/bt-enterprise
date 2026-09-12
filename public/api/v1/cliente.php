<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\ClientService;
use BTQueue\Core\Auth;

header('Content-Type: application/json; charset=utf-8');

try {
    $service = new ClientService();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $uuid = $_GET['uuid'] ?? '';
        if (empty($uuid)) throw new Exception("UUID necessário.");

        $cliente = $service->buscarPorUuid($uuid);
        if (!$cliente) {
            echo json_encode(['success' => false, 'message' => 'Perfil não encontrado.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'cliente' => $cliente
        ]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $_GET['action'] ?? 'registrar';

        if ($action === 'buscar_whatsapp') {
            $whatsapp = $input['whatsapp'] ?? '';
            $cliente = $service->buscarPorWhatsapp($whatsapp);

            if ($cliente) {
                echo json_encode(['success' => true, 'cliente' => $cliente]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cadastro não localizado.']);
            }
            exit;
        }

        $resultado = $service->registrar($input);
        echo json_encode($resultado);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception("ID invÃ¡lido para exclusÃ£o.");

        echo json_encode($service->excluir($id));
        exit;
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
