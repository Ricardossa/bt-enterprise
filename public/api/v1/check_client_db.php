<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
try {
    $res = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'empresa'");
    echo json_encode(['empresa' => $res['valor'] ?? 'NOT_FOUND']);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
