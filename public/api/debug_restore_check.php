<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$backupFile = dirname(__DIR__, 2) . '/database/banco.db.backup.20260802133034.old';

try {
    if (!file_exists($backupFile)) {
        die("Arquivo de backup não encontrado.");
    }

    $db = new PDO('sqlite:' . $backupFile);

    echo "--- SENHAS NO BACKUP ---\n";
    $senhas = $db->query("SELECT id, codigo, status, created_at FROM senhas ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($senhas);

    echo "\n--- OPERADORES NO BACKUP ---\n";
    $ops = $db->query("SELECT id, nome, login, nivel FROM operadores")->fetchAll(PDO::FETCH_ASSOC);
    print_r($ops);

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
