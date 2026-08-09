<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "🏗️ Semeando regras de agendamento para teste...\n";

    $servicoId = 1; // Atendimento Geral

    // De Segunda (1) a Sexta (5)
    for ($dia = 1; $dia <= 5; $dia++) {
        Database::execute(
            "INSERT OR REPLACE INTO agenda_regras (servico_id, dia_semana, hora_inicio, hora_fim, duracao_slot)
             VALUES (?, ?, '08:00', '18:00', 30)",
            [$servicoId, $dia]
        );
    }

    echo "✅ Regras criadas: Segunda a Sexta, 08h às 18h (30 min cada).\n";
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
