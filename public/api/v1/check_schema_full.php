<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
$res = Database::fetchAll("PRAGMA table_info(servicos)");
echo json_encode($res, JSON_PRETTY_PRINT);
