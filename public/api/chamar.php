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
$senhaId   = (int)($dados['senha_id'] ?? 0); // Opcional (Super-Poder Admin)

// --- TRAVA DE SEGURANÇA: CONGELAMENTO DE OPERADOR (Sprint Elite) ---
$operador = Auth::operador();
if ($operador && $operador['nivel'] !== 'ADMIN') {
    if ($operador['servico_id'] > 0) $servicoId = (int)$operador['servico_id'];
    if ($operador['guiche_id'] > 0) $guicheId = (int)$operador['guiche_id'];
    $senhaId = 0; // Operador comum não pode escolher senha específica
}

if ($guicheId <= 0 || ($servicoId <= 0 && $senhaId <= 0)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros insuficientes para chamada.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$queue = new QueueService();
$atendenteNome = $operador ? $operador['nome'] : 'Sistema';

if ($senhaId > 0) {
    // CHAMADA ESPECÍFICA (Fura Fila Admin)
    echo json_encode($queue->chamarEspecifico($senhaId, $guicheId, $atendenteNome), JSON_UNESCAPED_UNICODE);
} else {
    // CHAMADA PADRÃO (Próximo da fila)
    echo json_encode($queue->chamar($servicoId, $guicheId, $atendenteNome), JSON_UNESCAPED_UNICODE);
}
