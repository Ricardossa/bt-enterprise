<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "--- SERVIÇOS ---\n";
    $servicos = Database::fetchAll("SELECT id, nome, ativo FROM servicos");
    print_r($servicos);

    echo "\n--- AGENDADOS ATIVOS ---\n";
    $agendados = Database::fetchAll("SELECT s.id, s.nome_cliente, s.data_agendamento, s.servico_id, sv.nome as servico_nome
                                     FROM senhas s
                                     LEFT JOIN servicos sv ON sv.id = s.servico_id
                                     WHERE s.status = 'AGENDADO'");
    print_r($agendados);

} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
