<?php
/**
 * BT Queue - Limpeza de Agendamentos Duplicados
 */
header('Content-Type: text/plain; charset=utf-8');

try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) {
        die("❌ Banco não encontrado em W:");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🚨 INICIANDO FAXINA DE DUPLICATAS...\n\n";

    // Deleta senhas duplicadas mantendo apenas a que tem o menor ID
    $sql = "DELETE FROM senhas
            WHERE status = 'AGENDADO'
            AND id NOT IN (
                SELECT MIN(id)
                FROM senhas
                WHERE status = 'AGENDADO'
                GROUP BY nome_cliente, data_agendamento
            )";

    $deleted = $db->exec($sql);

    echo "✅ SUCESSO! Foram removidas $deleted entradas duplicadas.\n";
    echo "O painel agora deve mostrar apenas um registro por horário.";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
?>
