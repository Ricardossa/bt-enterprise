<?php
$pdo = new PDO('sqlite:database/banco.db');
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='licencas'")->fetchAll(PDO::FETCH_ASSOC);
var_export($tables);
echo PHP_EOL;
$stmt = $pdo->query("SELECT * FROM licencas LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
var_export($rows);
echo PHP_EOL;
