<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');

try {
    $novaSenha = password_hash('admin123', PASSWORD_DEFAULT);

    // Tenta atualizar o usuário admin
    $res = Database::execute("UPDATE operadores SET senha = ?, ativo = 1 WHERE login = 'admin'", [$novaSenha]);

    if ($res) {
        echo "✅ SENHA RESETADA COM SUCESSO!\n";
        echo "Usuário: admin\n";
        echo "Senha: admin123\n";
    } else {
        echo "❌ ERRO: Usuário 'admin' não encontrado no banco.";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
