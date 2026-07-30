<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');
echo "🚑 OPERAÇÃO FAXINA DE CAMINHOS v4.6\n";
echo "========================================\n\n";

try {
    // 1. Limpa tabela de promoções (Deixa apenas o nome do arquivo)
    $promos = Database::fetchAll("SELECT id, titulo, imagem FROM promocoes");
    echo "Limpando tabela 'promocoes'...\n";
    foreach ($promos as $p) {
        if (!empty($p['imagem'])) {
            $novoNome = basename($p['imagem']); // Pega só 'foto.png', tira 'uploads/...'
            Database::execute("UPDATE promocoes SET imagem = ? WHERE id = ?", [$novoNome, $p['id']]);
            echo "   [ID {$p['id']}] Corrigido: {$p['imagem']} -> {$novoNome}\n";
        }
    }

    // 2. Garante que a logo mobile no banco aponte para o arquivo fixo
    Database::execute("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('promo_logo', 'uploads/logo_mobile.png')");
    echo "\nLogo Mobile Padronizada: uploads/logo_mobile.png\n";

    echo "\n✅ SUCESSO: O Banco de Dados agora fala a língua universal!\n";
    echo "Agora gere o ZIP v4.6 e atualize o cliente.";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage();
}
