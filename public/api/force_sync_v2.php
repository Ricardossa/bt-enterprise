<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\MasterSync\SyncService;

header('Content-Type: application/json; charset=utf-8');

try {
    $sync = new SyncService();
    $res = $sync->synchronize();

    echo json_encode([
        'success' => true,
        'message' => 'Sincronia Forçada e Timezone Normalizado.',
        'details' => $res
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
