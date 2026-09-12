<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BTQueue\Core\MasterSync\SyncService;

/**
 * Script de acionamento do Ciclo MasterSync.
 * Pode ser chamado via Cron Job ou via Web.
 */

$sync = new SyncService();
$result = $sync->synchronize();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
