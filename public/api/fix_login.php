<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');
echo "🚑 Operação Resgate do Admin Iniciada...\n";

try {
    $pass = 'admin123';
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Força o admin padrão
    $ok = Database::execute(
        "UPDATE operadores SET login = 'admin', senha = ?, ativo = 1, nivel = 'ADMIN' WHERE id = 1",
        [$hash]
    );

    if ($ok) {
        echo "✅ Senha do 'admin' resetada para: $pass\n";
        echo "✅ Usuário ativado como ADMINISTRADOR.\n";
    } else {
        echo "❌ Falha ao atualizar banco de dados.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
echo "\n--- FIM ---";
