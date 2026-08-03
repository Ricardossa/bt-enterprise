<?php
$pdo = new PDO('sqlite:database/banco.db');
$licencas = $pdo->query("SELECT COUNT(*) FROM licencas")->fetchColumn();
$admin = $pdo->query("SELECT COUNT(*) FROM operadores WHERE login='admin' AND ativo=1")->fetchColumn();
$lic = $pdo->query("SELECT status, validade, offline_dias FROM licencas LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo 'licencas=' . $licencas . PHP_EOL;
echo 'admin=' . $admin . PHP_EOL;
echo 'lic_status=' . json_encode($lic, JSON_UNESCAPED_UNICODE) . PHP_EOL;
