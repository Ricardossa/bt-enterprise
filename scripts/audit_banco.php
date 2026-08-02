<?php

declare(strict_types=1);

$fileName = $argv[1] ?? 'banco.db';
$dbFile = __DIR__ . '/../database/' . $fileName;
if (!file_exists($dbFile)) {
    fwrite(STDERR, "ERRO: $fileName não encontrado em $dbFile\n");
    exit(1);
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tables = ['operadores', 'licencas', 'clientes', 'senhas', 'configuracoes', 'system_info'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT count(*) AS cnt FROM $table");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo sprintf("%s: %s\n", $table, $row['cnt'] ?? '0');
    } catch (Throwable $e) {
        echo sprintf("%s: ERRO - %s\n", $table, $e->getMessage());
    }
}
