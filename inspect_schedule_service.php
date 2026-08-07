<?php
/**
 * BT Queue - Auditoria de Agendamentos Google
 * Verifica para qual serviço os agendamentos estão sendo importados.
 */
try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) {
        die("❌ Banco não encontrado em W:");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- AGENDAMENTOS RECENTES (Google Calendar) ---\n";

    $sql = "SELECT s.id, s.nome_cliente, s.data_agendamento, s.servico_id, sv.nome as servico_nome
            FROM senhas s
            LEFT JOIN servicos sv ON sv.id = s.servico_id
            WHERE s.cliente_uuid = 'GOOGLE-CALENDAR'
            ORDER BY s.id DESC LIMIT 10";

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "ℹ️ Nenhum agendamento encontrado no banco do cliente.";
    } else {
        foreach ($rows as $r) {
            echo "Paciente: {$r['nome_cliente']} | Hora: {$r['data_agendamento']} | Serviço: [ID {$r['servico_id']}] {$r['servico_nome']}\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
