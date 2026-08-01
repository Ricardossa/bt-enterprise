<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\DashboardController;

header('Content-Type: application/json');

$dashboard = new DashboardController();
$stats = $dashboard->getStats();

echo json_encode([
    'hoje_php' => date('Y-m-d'),
    'stats_retornados' => $stats
]);
