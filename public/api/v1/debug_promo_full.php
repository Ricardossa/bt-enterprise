<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;

$servicos = Database::fetchAll("SELECT id, nome, preco FROM servicos WHERE ativo = 1");
echo json_encode($servicos, JSON_PRETTY_PRINT);
