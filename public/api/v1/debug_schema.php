<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
$res = Database::fetchAll("PRAGMA table_info(configuracoes)");
echo json_encode($res, JSON_PRETTY_PRINT);
