<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;
use Exception;

Auth::protegerAPI('ADMIN');

header('Content-Type: application/json; charset=utf-8');

try{

$metodo=$_SERVER['REQUEST_METHOD']??'GET';

if($metodo==='PUT'){
    $metodo='POST';
}

if($metodo==='GET'){

    if(isset($_GET['id'])){

        $op=Database::fetch("
            SELECT
                id, nome, login, nivel, guiche_id, servico_id, ativo, created_at, updated_at,
                (SELECT nome FROM servicos WHERE id=operadores.servico_id) AS servico_nome,
                (SELECT nome FROM guiches WHERE id=operadores.guiche_id) AS guiche_nome
            FROM operadores
            WHERE id=?
            LIMIT 1
        ",[(int)$_GET['id']]);

        if(!$op){

            http_response_code(404);

            echo json_encode([
                'success'=>false,
                'message'=>'Operador não encontrado.'
            ],JSON_UNESCAPED_UNICODE);

            exit;
        }

        echo json_encode([
            'success'=>true,
            'data'=>$op
        ],JSON_UNESCAPED_UNICODE);

        exit;

    }

    $lista=Database::fetchAll("
        SELECT
            o.id,
            o.nome,
            o.login,
            o.nivel,
            o.ativo,
            o.servico_id,
            o.guiche_id,
            s.nome AS servico,
            g.nome AS guiche
        FROM operadores o
        LEFT JOIN servicos s
            ON s.id=o.servico_id
        LEFT JOIN guiches g
            ON g.id=o.guiche_id
        WHERE o.ativo=1
        ORDER BY o.nome
    ");

    echo json_encode([
        'success'=>true,
        'data'=>$lista
    ],JSON_UNESCAPED_UNICODE);

    exit;
}

if($metodo==='POST'){

    $dados=json_decode(file_get_contents("php://input"),true)??$_POST;

    $id=isset($dados['id'])?(int)$dados['id']:0;

    $nome=trim($dados['nome']??'');
    $login=trim($dados['login']??'');
    $senha=(string)($dados['senha']??'');

    $servico=(int)($dados['servico_id']??0);
    $guiche=(int)($dados['guiche_id']??0);

    $ativo=(int)($dados['ativo']??1);

    if($nome===''){
        throw new Exception('Informe o nome.');
    }

    if($login===''){
        throw new Exception('Informe o login.');
    }

    if($guiche > 0){

        $guicheEmUso = Database::fetch(
            "SELECT nome
             FROM operadores
             WHERE guiche_id = ?
               AND ativo = 1
               AND id <> ?
             LIMIT 1",
            [
                $guiche,
                $id
            ]
        );

        if($guicheEmUso){
            throw new Exception(
                'Este guichê já está vinculado ao operador ' .
                $guicheEmUso['nome'] . '.'
            );
        }

    }

    $existe=Database::fetch(
        "SELECT id FROM operadores
         WHERE login=?
         LIMIT 1",
        [$login]
    );

    if($id===0){

        if($existe){
            throw new Exception('Login já cadastrado.');
        }

        if($senha===''){
            throw new Exception('Informe a senha.');
        }

        $hash=password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        Database::execute(
            "INSERT INTO operadores
            (
                nome,
                login,
                senha,
                ativo,
                servico_id,
                guiche_id
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )",
            [
                $nome,
                $login,
                $hash,
                $ativo,
                $servico,
                $guiche
            ]
        );

    }else{

        if(
            $existe &&
            (int)$existe['id']!==$id
        ){
            throw new Exception(
                'Login já utilizado.'
            );
        }

        if($senha===''){

            Database::execute(
                "UPDATE operadores
                 SET
                    nome=?,
                    login=?,
                    ativo=?,
                    servico_id=?,
                    guiche_id=?
                 WHERE id=?",
                [
                    $nome,
                    $login,
                    $ativo,
                    $servico,
                    $guiche,
                    $id
                ]
            );

        }else{

            $hash=password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            Database::execute(
                "UPDATE operadores
                 SET
                    nome=?,
                    login=?,
                    senha=?,
                    ativo=?,
                    servico_id=?,
                    guiche_id=?
                 WHERE id=?",
                [
                    $nome,
                    $login,
                    $hash,
                    $ativo,
                    $servico,
                    $guiche,
                    $id
                ]
            );

        }

    }

    echo json_encode([
        'success'=>true
    ],JSON_UNESCAPED_UNICODE);

    exit;
}

if($metodo==='DELETE'){

    $dados=json_decode(
        file_get_contents("php://input"),
        true
    )??[];

    $id=(int)($dados['id']??0);

    if($id<=0){
        throw new Exception('ID inválido.');
    }

    // [SEGURANÇA] - Verifica se o operador tem senhas CHAMANDO ou EM ATENDIMENTO
    $atendimentoAtivo = Database::fetch(
        "SELECT id FROM senhas WHERE atendente = (SELECT login FROM operadores WHERE id = ?) AND status IN ('CHAMANDO', 'ATENDIMENTO') LIMIT 1",
        [$id]
    );

    if($atendimentoAtivo){
        throw new Exception('Não é possível excluir um operador com atendimento ativo. Finalize as senhas primeiro.');
    }

    Database::execute(
        "UPDATE operadores
         SET ativo=0
         WHERE id=?
         AND ativo=1",
        [$id]
    );

    echo json_encode([
        'success'=>true
    ],JSON_UNESCAPED_UNICODE);

    exit;
}

http_response_code(405);

echo json_encode([
    'success'=>false,
    'message'=>'Método não permitido.'
],JSON_UNESCAPED_UNICODE);

}catch(Throwable $e){

http_response_code(500);

echo json_encode([
    'success'=>false,
    'message'=>$e->getMessage()
],JSON_UNESCAPED_UNICODE);

}

