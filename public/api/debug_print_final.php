<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $configs = Database::fetchAll("SELECT chave, valor FROM configuracoes WHERE chave IN ('url_local', 'url_publica', 'local_print_ip', 'printer_name', 'modo')");

    // Teste de conectividade real da VM para o PC da impressora
    $ip = '';
    foreach($configs as $c) if($c['chave'] == 'local_print_ip') $ip = $c['valor'];

    $ping = 'N/A';
    if ($ip) {
        $connection = @fsockopen($ip, 8001, $errno, $errstr, 2);
        $ping = $connection ? 'CONECTADO' : "FALHA: $errstr ($errno)";
        if($connection) fclose($connection);
    }

    echo json_encode([
        'success' => true,
        'configuracoes' => $configs,
        'teste_conexao_vm_para_pc' => [
            'alvo' => $ip . ":8001",
            'resultado' => $ping
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
