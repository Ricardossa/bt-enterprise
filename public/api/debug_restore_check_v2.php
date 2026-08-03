<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

$backupFile = dirname(__DIR__, 2) . '/database/banco.db.backup.20260802133034.old';

try {
    $db = new PDO('sqlite:' . $backupFile);

    echo "--- MASTER URL NO BACKUP ---\n";
    $url = $db->query("SELECT valor FROM configuracoes WHERE chave = 'master_url'")->fetchColumn();
    echo "URL: " . ($url ?: 'Não configurada') . "\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
