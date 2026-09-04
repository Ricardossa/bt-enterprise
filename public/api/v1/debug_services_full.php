<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

try {
    $res = Database::fetchAll("SELECT * FROM servicos WHERE ativo = 1");
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
