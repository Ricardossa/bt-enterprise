<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\DashboardController;
use BTQueue\Core\ActivityService;
use BTQueue\Core\Auth;

Auth::protegerAPI();

try {
    $dashboard = new DashboardController();

    // Força o sistema a estar sempre "vivo"
    $dashboard->ensureActivityIsAlive();

    $stats = $dashboard->getStats();
    $health = $dashboard->getHealth();

    // Busca atividades com conversão de fuso horário (UTC para Local)
    $activities = \BTQueue\Core\Database::fetchAll(
        "SELECT id, tipo, categoria, mensagem, usuario,
                datetime(data_criacao, 'localtime') as data_criacao
         FROM atividades
         ORDER BY id DESC LIMIT 10"
    );

    // Obtém status da licença local
    $licenca = \BTQueue\Core\Database::fetch("SELECT status, ultima_validacao FROM licencas LIMIT 1");

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'health' => $health,
        'activities' => $activities,
        'license' => [
            'status' => $licenca['status'] ?? 'N/A',
            'last_sync' => $licenca['ultima_validacao'] ?? 'Nunca'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
