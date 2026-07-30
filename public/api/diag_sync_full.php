<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\MasterSync\SyncService;

header('Content-Type: text/plain; charset=utf-8');
echo "🔍 DIAGNÓSTICO COMPLETO DE SINCRONIZAÇÃO\n";
echo "========================================\n\n";

try {
    echo "1. Verificando Identidade Local...\n";
    $licenca = Database::fetch("SELECT uuid, token, status, validade FROM licencas LIMIT 1");
    if (!$licenca) {
        die("❌ ERRO: Nenhuma licença encontrada no banco local.\n");
    }
    echo "   UUID: " . $licenca['uuid'] . "\n";
    echo "   Status Local Atual: " . $licenca['status'] . "\n";
    echo "   Validade Local: " . $licenca['validade'] . "\n\n";

    echo "2. Iniciando Ciclo de Sincronização Manual...\n";
    $sync = new SyncService();
    $res = $sync->synchronize();

    echo "   Resultado do synchronize():\n";
    print_r($res);
    echo "\n";

    echo "3. Verificando Banco após Sincronização...\n";
    $licencaNova = Database::fetch("SELECT status, ultima_validacao, validade FROM licencas LIMIT 1");
    echo "   Status Local AGORA: " . $licencaNova['status'] . "\n";
    echo "   Último Pulso Gravado: " . $licencaNova['ultima_validacao'] . "\n";
    echo "   Validade AGORA: " . $licencaNova['validade'] . "\n\n";

    if ($licencaNova['status'] === $licenca['status'] && $licencaNova['status'] === 'ATIVA') {
        echo "⚠️ AVISO: O status continua ATIVA. Se você mudou para SUSPENSA na Master, a sincronização não atualizou o banco local.\n";
    } elseif ($licencaNova['status'] === 'SUSPENSA') {
        echo "✅ SUCESSO: O sistema local agora reconhece o status SUSPENSA.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
