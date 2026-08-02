<?php

declare(strict_types=1);

$dbPath = __DIR__ . '/../database/banco_template.db';
$schemaFile = __DIR__ . '/../database/schema.sql';
$seedsFile = __DIR__ . '/../database/seeds.sql';

if (file_exists($dbPath)) {
    unlink($dbPath);
}

if (!file_exists($schemaFile) || !file_exists($seedsFile)) {
    fwrite(STDERR, "schema.sql ou seeds.sql não encontrado.\n");
    exit(1);
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys = ON;');
$db->exec(file_get_contents($schemaFile));
$db->exec(file_get_contents($seedsFile));

fwrite(STDOUT, "banco_template.db criado com sucesso.\n");
