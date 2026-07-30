<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\QueueService;
use BTQueue\Core\Auth;

Auth::protegerAPI();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados) {
    $dados = $_POST;
}

$servicoId = (int)($dados['servico_id'] ?? 0);
$guicheId  = (int)($dados['guiche_id'] ?? 0);

if ($servicoId <= 0 || $guicheId <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'servico_id e guiche_id são obrigatórios.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$queue = new QueueService();
$operador = Auth::operador();
$atendenteNome = $operador ? $operador['nome'] : 'Sistema';

echo json_encode(
    $queue->chamar($servicoId, $guicheId, $atendenteNome),
    JSON_UNESCAPED_UNICODE
);
