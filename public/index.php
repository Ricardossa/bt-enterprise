<?php
declare(strict_types=1);

/**
 * BT Queue Enterprise - Porteiro de Integridade (Diamond Bootstrap)
 * Responsável por garantir que o sistema só abra se estiver 100% provisionado.
 */

$bootFile = null;
$searchPaths = [
    __DIR__ . '/../bootstrap.php',
    __DIR__ . '/../../bootstrap.php'
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $bootFile = $path;
        break;
    }
}

if (!$bootFile) die("<h1>❌ ERRO CRÍTICO: Motor não encontrado.</h1>");
require_once $bootFile;

use BTQueue\Core\Database;
use BTQueue\Core\SecurityService;

// --- CONFIGURAÇÃO DE CAMINHOS ---
$dbFolder = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
$dbFile = $dbFolder . DIRECTORY_SEPARATOR . 'banco.db';
$lockFile = $dbFolder . DIRECTORY_SEPARATOR . '.installed';

/**
 * FUNÇÃO DE REDIRECIONAMENTO PARA SETUP
 */
$goToSetup = function() {
    if (basename($_SERVER['PHP_SELF']) !== 'setup.php') {
        header('Location: setup.php');
        exit;
    }
};

// 1. Verificação de Arquivos Básicos
if (!file_exists($dbFile) || !file_exists($lockFile)) {
    $goToSetup();
}

// 2. Verificação de Integridade de Dados (Check-list Industrial)
try {
    // A. Existe UUID de Identidade?
    $uuid = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'uuid' LIMIT 1");
    if (!$uuid || empty($uuid['valor'])) $goToSetup();

    // B. Existe Licença Ativa?
    $licenca = Database::fetch("SELECT * FROM licencas LIMIT 1");
    if (!$licenca || strtoupper($licenca['status']) !== 'ATIVA') $goToSetup();
    if (!SecurityService::validateIntegrity($licenca)) $goToSetup();

    // C. Existe pelo menos um Administrador?
    $admin = Database::fetch("SELECT id FROM operadores WHERE nivel = 'ADMIN' LIMIT 1");
    if (!$admin) $goToSetup();

} catch (\Throwable $e) {
    // Se o banco estiver corrompido ou sem tabelas, manda para o setup
    $goToSetup();
}

// --- TUDO OK! ---
// Se chegou aqui, o sistema está provisionado.
// O index apenas decide se vai para o Dashboard ou para a TV.
header('Location: dashboard.php');
exit;
