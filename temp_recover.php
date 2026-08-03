<?php
require_once __DIR__ . '/bootstrap.php';
use BTQueue\Core\Database;

$pdo = Database::getInstance();

$pdo->exec("CREATE TABLE IF NOT EXISTS clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT NOT NULL UNIQUE, nome TEXT NOT NULL, documento TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS licencas (id INTEGER PRIMARY KEY AUTOINCREMENT, cliente_id INTEGER NOT NULL, chave TEXT NOT NULL UNIQUE, token TEXT, uuid TEXT, status TEXT NOT NULL DEFAULT 'ATIVA', validade DATETIME, ultima_validacao DATETIME, cache_assinatura TEXT, offline_dias INTEGER DEFAULT 7, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(cliente_id) REFERENCES clientes(id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS operadores (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, login TEXT NOT NULL UNIQUE, senha TEXT NOT NULL, nivel TEXT NOT NULL DEFAULT 'OPERADOR', guiche_id INTEGER, servico_id INTEGER, ativo INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

$cliente = Database::fetch("SELECT id FROM clientes ORDER BY id LIMIT 1");
if (!$cliente) {
    $uuid = bin2hex(random_bytes(8));
    Database::execute("INSERT INTO clientes (uuid, nome, documento) VALUES (?, ?, ?)", [$uuid, 'Cliente Local', '00000000000']);
    $clienteId = Database::lastInsertId();
} else {
    $clienteId = (int) $cliente['id'];
}

$lic = Database::fetch("SELECT id FROM licencas LIMIT 1");
if (!$lic) {
    $chave = strtoupper(bin2hex(random_bytes(6)));
    Database::execute("INSERT INTO licencas (cliente_id, chave, uuid, token, status, validade, offline_dias) VALUES (?, ?, ?, ?, 'ATIVA', date('now', '+365 days'), 15)", [$clienteId, $chave, 'vm-local', 'vm-local-token']);
}

$op = Database::fetch("SELECT id FROM operadores WHERE login = 'admin' LIMIT 1");
if (!$op) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    Database::execute("INSERT INTO operadores (nome, login, senha, nivel, ativo) VALUES (?, ?, ?, 'ADMIN', 1)", ['Administrador Geral', 'admin', $hash]);
}

echo "RECUPERADO\n";
