<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use BTQueue\Core\Database;

/**
 * Gestor da Fila de Saída (Outbox).
 */
final class QueueSender
{
    /**
     * Obtém itens pendentes e marca como 'Enviando'.
     */
    public function getPendingItems(int $limit = 50): array
    {
        // Marca como 3 (ENVIANDO) para evitar duplicidade em pulses paralelos
        $items = Database::fetchAll("SELECT * FROM sync_queue WHERE sincronizado = 0 LIMIT $limit");

        foreach ($items as $item) {
            Database::execute("UPDATE sync_queue SET sincronizado = 3 WHERE id = ?", [$item['id']]);
        }

        return $items;
    }

    /**
     * Marca itens como processados com sucesso ou erro.
     */
    public function markProcessed(array $ids, bool $success): void
    {
        $status = $success ? 1 : 2; // 1: SINCRONIZADO, 2: ERRO
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if (!empty($ids)) {
            Database::execute("UPDATE sync_queue SET sincronizado = $status WHERE id IN ($placeholders)", $ids);
        }
    }
}
