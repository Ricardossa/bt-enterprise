<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    Database::execute(
        "INSERT OR REPLACE INTO agenda_regras (servico_id, dia_semana, hora_inicio, hora_fim, duracao_slot)
         VALUES (1, 0, '08:00', '22:00', 30)"
    );
    echo "✅ Horários de Domingo (0) adicionados com sucesso!";
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
