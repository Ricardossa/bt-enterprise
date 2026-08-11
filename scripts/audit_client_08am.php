<?php
try {
    $db = new PDO('sqlite:W:/BTQueue/database/banco.db');
    $hoje = date('Y-m-d');
    $sql = "SELECT id, nome_cliente, data_agendamento, status, updated_at
            FROM senhas
            WHERE date(data_agendamento) = ?
            AND strftime('%H', data_agendamento) = '08'
            ORDER BY id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$hoje]);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- AUDIT 08:00 AM APPOINTMENTS FOR $hoje ---\n";
    if (empty($res)) {
        echo "No appointments found for 08:00 AM today.\n";
    } else {
        print_r($res);
    }

    echo "\nCurrent Server Time: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>
