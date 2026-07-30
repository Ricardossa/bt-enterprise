<?php
declare(strict_types=1);

/**
 * BT Queue Enterprise - Ponto de Entrada Inteligente (Auto-Boot)
 */

$bootFile = null;
$searchPaths = [
    __DIR__ . '/../bootstrap.php',  // Ambiente Instalador
    __DIR__ . '/../../bootstrap.php' // Ambiente Desenvolvimento
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $bootFile = $path;
        break;
    }
}

if (!$bootFile) {
    die("<h1>❌ ERRO CRÍTICO: Motor não encontrado.</h1>");
}

require_once $bootFile;

$lockFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . '.installed';
$dbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'banco.db';

if (!file_exists($lockFile) || !file_exists($dbFile)) {
    header('Location: setup.php');
    exit;
}

header('Location: dashboard.php');
exit;
