<?php
/**
 * BT Queue - DIAMOND SECURITY ACTIVATOR (RESCUE)
 * Gera o cadeado digital vinculado ao hardware do cliente.
 */
header('Content-Type: text/plain; charset=utf-8');

try {
    require_once __DIR__ . '/../bootstrap.php';
    use BTQueue\Core\Database;
    use BTQueue\Core\SecurityService;

    echo "🛡️ INICIANDO VINCULAÇÃO DE HARDWARE DIAMOND...\n\n";

    // 1. Garante que as colunas de segurança existam
    $cols = Database::fetchAll("PRAGMA table_info(licencas)");
    $existing = array_column($cols, 'name');

    if (!in_array('assinatura', $existing)) {
        Database::execute("ALTER TABLE licencas ADD COLUMN assinatura TEXT");
        echo "✅ Coluna 'assinatura' criada.\n";
    }
    if (!in_array('hardware_id', $existing)) {
        Database::execute("ALTER TABLE licencas ADD COLUMN hardware_id TEXT");
        echo "✅ Coluna 'hardware_id' criada.\n";
    }

    // 2. Coleta a licença atual e assina com o hardware real do PC
    $lic = Database::fetch("SELECT * FROM licencas LIMIT 1");
    if (!$lic) {
        die("❌ ERRO: Nenhuma licença encontrada. O sistema precisa ser ativado via PIN primeiro.");
    }

    $hwid = SecurityService::getHardwareId();
    $sig = SecurityService::signData([
        'uuid' => $lic['uuid'],
        'status' => $lic['status'],
        'validade' => $lic['validade']
    ], $lic['token']);

    Database::execute(
        "UPDATE licencas SET hardware_id = ?, assinatura = ? WHERE id = ?",
        [$hwid, $sig, $lic['id']]
    );

    echo "\n✅ HARDWARE SELADO: $hwid\n";
    echo "✅ ASSINATURA GERADA COM SUCESSO!\n";
    echo "\n🔥 O SISTEMA FOI DESTRAVADO! Pode fazer login no painel agora.";

} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage();
}
?>
