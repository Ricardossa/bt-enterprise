<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Throwable;

class GuicheService
{

    public function listar(): array
    {
        return Database::fetchAll(
            "SELECT *
             FROM guiches
             WHERE ativo=1
             ORDER BY codigo"
        );
    }

    public function buscar(int $id): ?array
    {
        return Database::fetch(
            "SELECT *
             FROM guiches
             WHERE id=?",
            [$id]
        );
    }

    public function adicionar(
        string $codigo,
        string $nome,
        string $icone='',
        string $cor='#1565C0'
    ): array
    {

        $existe = Database::fetch(

            "SELECT id
             FROM guiches
             WHERE codigo=?",

            [$codigo]

        );

        if($existe){

            return [

                'success'=>false,

                'message'=>'Já existe um guichê com este código.'

            ];

        }

        Database::execute(

            "INSERT INTO guiches
            (
                codigo,
                nome,
                icone,
                cor
            )
            VALUES
            (
                ?,?,?,?
            )",

            [
                $codigo,
                $nome,
                $icone,
                $cor
            ]

        );

        return [

            'success'=>true

        ];

    }

    public function editar(

        int $id,
        string $codigo,
        string $nome,
        string $icone,
        string $cor

    ): array
    {

        Database::execute(

            "UPDATE guiches

             SET

                codigo=?,
                nome=?,
                icone=?,
                cor=?,
                updated_at=CURRENT_TIMESTAMP

             WHERE id=?",

            [

                $codigo,
                $nome,
                $icone,
                $cor,
                $id

            ]

        );

        return [

            'success'=>true

        ];

    }

    public function excluir(int $id): array
    {

        Database::execute(

            "UPDATE guiches

             SET

                ativo = 0,
                updated_at = CURRENT_TIMESTAMP

             WHERE id = ?",

            [

                $id

            ]

        );

        return [

            'success' => true

        ];

    }

}
