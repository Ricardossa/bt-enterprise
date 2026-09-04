<?php
// testar_com_banco.php - Teste com dados reais do banco

require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/AIService.php';

echo "📊 Buscando dados reais do banco...\n";

try {
    // Busca dados do dia
    $stats = [
        'emitidas' => (int)(BTQueue\Core\Database::fetch(
            "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = CURDATE()"
        )['total'] ?? 0),
        'chamadas' => (int)(BTQueue\Core\Database::fetch(
            "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_chamada) = CURDATE() AND status = 'chamada'"
        )['total'] ?? 0),
        'finalizadas' => (int)(BTQueue\Core\Database::fetch(
            "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_finalizacao) = CURDATE() AND status = 'finalizada'"
        )['total'] ?? 0),
        'pendentes' => (int)(BTQueue\Core\Database::fetch(
            "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = CURDATE() AND status = 'pendente'"
        )['total'] ?? 0)
    ];
    
    echo "\n📈 Dados do dia:\n";
    echo str_repeat("─", 40) . "\n";
    echo "Senhas emitidas:   {$stats['emitidas']}\n";
    echo "Senhas chamadas:   {$stats['chamadas']}\n";
    echo "Senhas finalizadas: {$stats['finalizadas']}\n";
    echo "Senhas pendentes:   {$stats['pendentes']}\n";
    echo str_repeat("─", 40) . "\n\n";
    
    $service = new BTQueue\Core\AIService();
    echo "🤖 Gerando relatório com IA...\n\n";
    
    $resultado = $service->generateDailyReport($stats);
    echo $resultado . "\n";
    
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
