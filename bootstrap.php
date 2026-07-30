<?php
declare(strict_types=1);

date_default_timezone_set('America/Bahia');

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
