<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: text/plain');
$res = Database::fetchAll("SELECT * FROM senhas ORDER BY id DESC LIMIT 10");
print_r($res);
