<?php
try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) die("W: DATABASE NOT FOUND\n");

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- AUDITORIA W: (MINI PC) ---\n";

    // 1. Serviços
    $servicos = $db->query("SELECT id, nome FROM servicos")->fetchAll(PDO::FETCH_ASSOC);
    echo "Serviços no W:\n";
    print_r($servicos);

    // 2. Agendamentos
    $agendados = $db->query("SELECT id, nome_cliente, data_agendamento, status, servico_id FROM senhas WHERE status = 'AGENDADO'")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nAgendados no W:\n";
    print_r($agendados);

    // 3. Regras
    $regras = $db->query("SELECT * FROM agenda_regras")->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRegras no W:\n";
    print_r($regras);

} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
