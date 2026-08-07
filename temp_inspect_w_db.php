<?php
try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) {
        die("ERRO: Banco não encontrado em $dbPath");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- AUDITORIA BANCO CLIENTE (W:) ---\n";

    // 1. Operadores
    $ops = $db->query("SELECT COUNT(*) as total FROM operadores")->fetch(PDO::FETCH_ASSOC);
    echo "Total Operadores: " . $ops['total'] . "\n";

    // 2. Serviços
    $servs = $db->query("SELECT COUNT(*) as total FROM servicos")->fetch(PDO::FETCH_ASSOC);
    echo "Total Serviços: " . $servs['total'] . "\n";

    // 3. Guichês
    $gui = $db->query("SELECT COUNT(*) as total FROM guiches")->fetch(PDO::FETCH_ASSOC);
    echo "Total Guichês: " . $gui['total'] . "\n";

    // 4. Últimas 5 senhas
    echo "\nÚltimas 5 senhas:\n";
    $senhas = $db->query("SELECT id, codigo, status FROM senhas ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach($senhas as $s) {
        echo "ID: {$s['id']} | Cod: {$s['codigo']} | Status: {$s['status']}\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
