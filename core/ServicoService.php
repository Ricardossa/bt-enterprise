<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class ServicoService
{
    public function listar(): array
    {
        return Database::fetchAll(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, created_at, updated_at
             FROM servicos
             WHERE ativo = 1
             ORDER BY ordem ASC, nome ASC"
        );
    }

    public function buscar(int $id): ?array
    {
        return Database::fetch(
            "SELECT id, codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio, ativo, created_at, updated_at
             FROM servicos
             WHERE id = ?",
            [$id]
        );
    }

    public function adicionar(
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio
    ): array {
        try {
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ?", [$codigo]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ?", [$slug]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "INSERT INTO servicos (codigo, nome, slug, prefixo, icone, cor, ordem, tempo_medio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio]
            );

            return ['success' => true];

        } catch (Throwable $e) {
            Logger::error($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editar(
        int $id,
        string $codigo,
        string $nome,
        string $slug,
        string $prefixo,
        string $icone,
        string $cor,
        int $ordem,
        int $tempo_medio
    ): array {
        try {
            $existeCodigo = Database::fetch("SELECT id FROM servicos WHERE codigo = ? AND id != ?", [$codigo, $id]);
            if ($existeCodigo) {
                return ['success' => false, 'message' => 'DUPLICATE_CODE'];
            }

            $existeSlug = Database::fetch("SELECT id FROM servicos WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($existeSlug) {
                return ['success' => false, 'message' => 'DUPLICATE_SLUG'];
            }

            Database::execute(
                "UPDATE servicos
                 SET codigo = ?, nome = ?, slug = ?, prefixo = ?, icone = ?, cor = ?, ordem = ?, tempo_medio = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$codigo, $nome, $slug, $prefixo, $icone, $cor, $ordem, $tempo_medio, $id]
            );

            return ['success' => true];

        } catch (Throwable $e) {
            Logger::error("Erro ao editar serviço (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }

    public function excluir(int $id): array
    {
        try {
            Database::execute(
                "UPDATE servicos 
                 SET ativo = 0, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ?", 
                [$id]
            );
            return ['success' => true];
        } catch (Throwable $e) {
            Logger::error("Erro ao excluir serviço (ID $id): " . $e->getMessage());
            return ['success' => false, 'message' => 'INTERNAL_ERROR'];
        }
    }
}
