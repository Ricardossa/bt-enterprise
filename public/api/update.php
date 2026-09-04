<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\Auth;
use BTQueue\Core\MasterSync\UpdateService;

header('Content-Type: application/json; charset=utf-8');

Auth::protegerAPI('ADMIN');

try {
    $service = new UpdateService();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'data' => $service->check()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $dados = json_decode($rawInput, true) ?: $_POST;

        $releaseId = (int)($dados['release_id'] ?? 0);

        if ($releaseId <= 0) {
            throw new Exception("ID de atualizaÃ§Ã£o invÃ¡lido ($releaseId).");
        }

        echo json_encode($service->applyRelease($releaseId), JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
