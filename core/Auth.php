<?php
declare(strict_types=1);

namespace BTQueue\Core;

use BTQueue\Core\MasterSync\LicenseManager;
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

        // 1. Verifica se a licença está bloqueada (Trava MasterSync)
        $checkLic = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='licencas'")->fetch();
        if ($checkLic) {
            $licenseManager = new LicenseManager();

            // Refinamento: Só bloqueia se o status for diferente de ATIVA.
            // Se for ATIVA e o ano for 2027, ignora atrasos de sincronia para permitir login.
            $licData = $db->query("SELECT status, validade FROM licencas LIMIT 1")->fetch();
            if ($licData && strtoupper((string)$licData['status']) !== 'ATIVA') {
                throw new \Exception("Acesso negado: Licença suspensa ou bloqueada.");
            }

            if ($licenseManager->isBlocked()) {
                try {
                    ActivityService::log('CRITICAL', 'SYSTEM', "Tentativa de login bloqueada: Licença inválida.", [], $login);
                } catch (\Throwable $e) {}

                throw new \Exception("Acesso negado: Licença suspensa ou expirada.");
            }
        }

        // 2. Busca o operador
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

        // 3. Valida Senha
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

        // 4. Grava Sessão com Nível de Acesso
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
            // Se o log falhar, nÃ£o impede o login do usuÃ¡rio
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

        // Validação Offline-First de Licença em tempo real
        $licenseManager = new LicenseManager();
        if ($licenseManager->isBlocked()) {
            self::erroAPI(403, 'Licença inválida ou suspensa. Contate o suporte.');
        }

        if ($nivelExigido === 'ADMIN' && !self::isAdmin()) {
            self::erroAPI(403, 'Acesso restrito a administradores.');
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

        // Validação Offline-First de Licença em tempo real
        $licenseManager = new LicenseManager();
        if ($licenseManager->isBlocked()) {
            header('Location: login.php?msg=licenca_invalida');
            exit;
        }

        if ($nivelExigido === 'ADMIN' && !self::isAdmin()) {
            // Se for operador tentando entrar em área admin, joga para o guichê
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
