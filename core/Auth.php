<?php
declare(strict_types=1);

namespace BTQueue\Core;

use PDO;

class Auth
{
    public static function iniciar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(string $login, string $senha): bool
    {
        self::iniciar();

        $db = Database::getInstance();

        // 1. Busca o operador
        $operador = Database::fetch(
            "SELECT o.*, g.nome AS guiche_nome, s.nome AS servico_nome
             FROM operadores o
             LEFT JOIN guiches g ON g.id = o.guiche_id
             LEFT JOIN servicos s ON s.id = o.servico_id
             WHERE o.login = ? AND o.ativo = 1 LIMIT 1",
            [$login]
        );

        if (!$operador) {
             throw new \Exception("Usuário não encontrado ou inativo.");
        }

        // 2. Valida Senha
        $senhaBanco = $operador['senha'];
        $valida = false;

        if (!str_starts_with($senhaBanco, '$2y$')) {
            $valida = ($senhaBanco === $senha);
            if ($valida) {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                Database::execute("UPDATE operadores SET senha = ? WHERE id = ?", [$hash, $operador['id']]);
            }
        } else {
            $valida = password_verify($senha, $senhaBanco);
        }

        if (!$valida) {
            throw new \Exception("Senha incorreta.");
        }

        // 3. Grava Sessão com Nível de Acesso
        $_SESSION['operador'] = [
            'id'            => (int)$operador['id'],
            'nome'          => $operador['nome'],
            'login'         => $operador['login'],
            'nivel'         => strtoupper($operador['nivel'] ?? 'OPERADOR'),
            'guiche_id'     => $operador['guiche_id'],
            'servico_id'    => $operador['servico_id'],
            'guiche_nome'   => $operador['guiche_nome'],
            'servico_nome'  => $operador['servico_nome']
        ];

        try {
            ActivityService::log('INFO', 'SYSTEM', "Login realizado: {$operador['nome']} (" . $_SESSION['operador']['nivel'] . ")", [], $operador['nome']);
        } catch (\Throwable $e) {
            // Se o log falhar, não impede o login do usuário
        }

        return true;
    }

    public static function logout(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }

    public static function operador(): ?array
    {
        self::iniciar();
        return $_SESSION['operador'] ?? null;
    }

    public static function autenticado(): bool
    {
        self::iniciar();
        return isset($_SESSION['operador']);
    }

    public static function isAdmin(): bool
    {
        $op = self::operador();
        return $op && $op['nivel'] === 'ADMIN';
    }

    /**
     * Trava de segurança para APIs.
     */
    public static function protegerAPI(?string $nivelExigido = null): void
    {
        if (!self::autenticado()) {
            self::erroAPI(401, 'Sessão expirada ou não autorizada.');
        }
    }

    /**
     * Trava de segurança para páginas (Redirecionamento).
     */
    public static function protegerPagina(?string $nivelExigido = null): void
    {
        if (!self::autenticado()) {
            header('Location: login.php');
            exit;
        }

        if ($nivelExigido === 'ADMIN' && !self::isAdmin()) {
            header('Location: operador.php?msg=acesso_negado');
            exit;
        }
    }

    private static function erroAPI(int $codigo, string $mensagem): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $mensagem], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
