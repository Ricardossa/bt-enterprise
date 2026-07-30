<?php
declare(strict_types=1);

namespace BTQueue\Core;

class PromocaoService
{
    public function listar(): array
    {
        return Database::fetchAll(
            "SELECT *
             FROM promocoes
             ORDER BY ordem, titulo"
        );
    }

    public function buscar(int $id): ?array
    {
        return Database::fetch(
            "SELECT *
             FROM promocoes
             WHERE id=?",
            [$id]
        );
    }

    public function adicionar(
        string $titulo,
        string $descricao,
        string $preco = '',
        string $imagem = '',
        int $ordem = 0
    ): array
    {
        Database::execute(
            "INSERT INTO promocoes
            (
                titulo,
                descricao,
                preco,
                imagem,
                ordem
            )
            VALUES
            (
                ?,?,?,?,?
            )",
            [
                $titulo,
                $descricao,
                $preco,
                $imagem,
                $ordem
            ]
        );

        return [
            'success' => true
        ];
    }

    public function editar(
        int $id,
        string $titulo,
        string $descricao,
        string $preco,
        string $imagem,
        int $ordem
    ): array
    {
        Database::execute(
            "UPDATE promocoes
             SET
                titulo=?,
                descricao=?,
                preco=?,
                imagem=?,
                ordem=?,
                updated_at=CURRENT_TIMESTAMP
             WHERE id=?",
            [
                $titulo,
                $descricao,
                $preco,
                $imagem,
                $ordem,
                $id
            ]
        );

        return [
            'success' => true
        ];
    }

    public function excluir(int $id): array
    {
        Database::execute(
            "DELETE FROM promocoes
             WHERE id=?",
            [
                $id
            ]
        );

        return [
            'success' => true
        ];
    }
}
