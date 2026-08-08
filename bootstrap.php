<?php
declare(strict_types=1);

date_default_timezone_set('America/Bahia');

// [SESSÃO DE LONGA DURAÇÃO] - 24 Horas de validade (v5.8.7 Diamond)
ini_set('session.gc_maxlifetime', '86400');
ini_set('session.cookie_lifetime', '86400');
session_set_cookie_params(86400);

// [CARREGAMENTO DE VERSÃO]
$versionFile = __DIR__ . '/public/version.json';
$versionData = file_exists($versionFile) ? json_decode((string)file_get_contents($versionFile), true) : null;
define('BT_VERSION', $versionData['version'] ?? '4.0.0');

// [AUTOLOADER] - Carregamento dinâmico de classes
spl_autoload_register(function (string $class): void {
    $prefix = 'BTQueue\\Core\\';
    $base_dir = __DIR__ . '/core/';

    if (strpos($class, $prefix) !== 0) return;

    $relative_class = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use BTQueue\Core\Config;
use BTQueue\Core\Database;
use BTQueue\Core\Logger;
use BTQueue\Core\QueueService;
use BTQueue\Core\GuicheService;
use BTQueue\Core\ServicoService;
use BTQueue\Core\Auth;

header_remove('X-Powered-By');

error_reporting(E_ALL);

ini_set(
    'display_errors',
    Config::get('app.debug', false) ? '1' : '0'
);

set_exception_handler(function (Throwable $e) {

    Logger::error(
        'Exceção não tratada',
        [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine()
        ]
    );

    http_response_code(500);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

});

// --- SILENT DATABASE AUTO-REPAIR (V5.0.2) ---
if (file_exists(__DIR__ . '/public/api/v1/auto_fix_db.php')) {
    include_once __DIR__ . '/public/api/v1/auto_fix_db.php';
}
