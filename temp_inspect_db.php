<?php
$pdo = new PDO('sqlite:database/banco.db');
foreach (['operadores','clientes','configuracoes','licencas'] as $table) {
    $count = $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    echo $table . ':' . $count . PHP_EOL;
}
$ops = $pdo->query("SELECT id, nome, login, nivel, ativo FROM operadores ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo 'OPERADORES=' . json_encode($ops, JSON_UNESCAPED_UNICODE) . PHP_EOL;
$lic = $pdo->query("SELECT id, cliente_id, chave, uuid, token, status, validade, offline_dias, ultima_validacao FROM licencas ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo 'LICENCAS=' . json_encode($lic, JSON_UNESCAPED_UNICODE) . PHP_EOL;
