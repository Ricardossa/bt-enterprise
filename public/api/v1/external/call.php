<?php

declare(strict_types=1);

/**
 * BT Integration Hub - Chamada Externa (Hospitalar)
 */

require_once __DIR__ . '/../../../../bootstrap.php';
use BTQueue\Core\Integration\IntegrationHubController;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Apenas POST permitido.']);
    exit;
}

// TODO: Adicionar validação de API Key para segurança de integração em produção
$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$hub = new IntegrationHubController();
$result = $hub->externalCall($payload);

if (!$result['success']) {
    http_response_code(500);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
