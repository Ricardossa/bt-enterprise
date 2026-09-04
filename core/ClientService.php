<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * Serviço de Gestão de Clientes/Fornecedores (Enterprise Edition)
 */
final class ClientService
{
    /**
     * Busca um cliente pelo UUID (ID Permanente do Celular)
     */
    public function buscarPorUuid(string $uuid): ?array
    {
        return Database::fetch("SELECT * FROM clientes WHERE uuid = ? LIMIT 1", [$uuid]);
    }

    /**
     * Busca um cliente pelo WhatsApp
     */
    public function buscarPorWhatsapp(string $whatsapp): ?array
    {
        return Database::fetch("SELECT * FROM clientes WHERE whatsapp = ? LIMIT 1", [$whatsapp]);
    }

    /**
     * Registra ou Atualiza um cliente
     */
    public function registrar(array $dados): array
    {
        $uuid = $dados['uuid'] ?? bin2hex(random_bytes(16));
        $nome = strtoupper(trim((string)($dados['nome'] ?? '')));
        $empresa = strtoupper(trim((string)($dados['empresa'] ?? '')));
        $whatsapp = trim((string)($dados['whatsapp'] ?? ''));

        if (empty($nome) || empty($whatsapp)) {
            return ['success' => false, 'message' => 'Nome e WhatsApp são obrigatórios.'];
        }

        $existente = $this->buscarPorUuid($uuid);

        if ($existente) {
            Database::execute(
                "UPDATE clientes SET nome = ?, empresa = ?, whatsapp = ? WHERE uuid = ?",
                [$nome, $empresa, $whatsapp, $uuid]
            );
        } else {
            Database::execute(
                "INSERT INTO clientes (uuid, nome, empresa, whatsapp) VALUES (?, ?, ?, ?)",
                [$uuid, $nome, $empresa, $whatsapp]
            );
        }

        return [
            'success' => true,
            'cliente' => $this->buscarPorUuid($uuid)
        ];
    }

    /**
     * Remove um fornecedor do sistema
     */
    public function excluir(int $id): array
    {
        try {
            Database::execute("DELETE FROM clientes WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Fornecedor removido com sucesso.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()];
        }
    }
}
