<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain; charset=utf-8');
echo "🚑 OPERAÇÃO FAXINA DE BANCO DE DADOS v4.5.1\n";
echo "========================================\n\n";

try {
    // 1. Limpa caminhos de promoções
    $promos = Database::fetchAll("SELECT id, imagem FROM promocoes");
    echo "Limpando tabela 'promocoes'...\n";
    foreach ($promos as $p) {
        if (!empty($p['imagem']) && strpos($p['imagem'], 'uploads/promocoes/') !== false) {
            $novoNome = str_replace('uploads/promocoes/', '', $p['imagem']);
            Database::execute("UPDATE promocoes SET imagem = ? WHERE id = ?", [$novoNome, $p['id']]);
            echo "   [ID {$p['id']}] Corrigido: {$p['imagem']} -> {$novoNome}\n";
        }
    }

    // 2. Limpa caminhos de logo mobile em configuracoes
    $logoMobile = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'promo_logo'");
    if ($logoMobile && !empty($logoMobile['valor'])) {
        $path = $logoMobile['valor'];
        if (strpos($path, 'uploads/promocoes/') !== false) {
            $novoNome = str_replace('uploads/promocoes/', '', $path);
            Database::execute("UPDATE configuracoes SET valor = ? WHERE chave = 'promo_logo'", [$novoNome]);
            echo "\nLogo Mobile Corrigida: $path -> $novoNome\n";
        }
    }

    echo "\n✅ SUCESSO: Banco de dados padronizado!\n";
    echo "Agora gere o novo ZIP e atualize o cliente.";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage();
}
