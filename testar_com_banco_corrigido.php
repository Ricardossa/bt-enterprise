<?php
// testar_com_banco_corrigido.php - Teste com dados reais do banco (SQLite)

require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/AIService.php';

echo "📊 Buscando dados reais do banco...\n\n";

try {
    // SQLite usa DATE('now') em vez de CURDATE()
    $hoje = date('Y-m-d');
    
    // Busca dados do dia
    $emitidas = BTQueue\Core\Database::fetch(
        "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = '{$hoje}'"
    );
    
    $chamadas = BTQueue\Core\Database::fetch(
        "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_chamada) = '{$hoje}' AND status = 'chamada'"
    );
    
    $finalizadas = BTQueue\Core\Database::fetch(
        "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_finalizacao) = '{$hoje}' AND status = 'finalizada'"
    );
    
    $pendentes = BTQueue\Core\Database::fetch(
        "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = '{$hoje}' AND status = 'pendente'"
    );
    
    $stats = [
        'emitidas' => (int)($emitidas['total'] ?? 0),
        'chamadas' => (int)($chamadas['total'] ?? 0),
        'finalizadas' => (int)($finalizadas['total'] ?? 0),
        'pendentes' => (int)($pendentes['total'] ?? 0)
    ];
    
    echo "📈 Dados do dia ({$hoje}):\n";
    echo str_repeat("─", 50) . "\n";
    echo "  Senhas emitidas:    {$stats['emitidas']}\n";
    echo "  Senhas chamadas:    {$stats['chamadas']}\n";
    echo "  Senhas finalizadas: {$stats['finalizadas']}\n";
    echo "  Senhas pendentes:   {$stats['pendentes']}\n";
    echo str_repeat("─", 50) . "\n\n";
    
    if ($stats['emitidas'] === 0) {
        echo "⚠️  Nenhuma senha emitida hoje. Usando dados de exemplo para teste.\n\n";
        $stats = [
            'emitidas' => 15,
            'chamadas' => 12,
            'finalizadas' => 9,
            'pendentes' => 6
        ];
        echo "📊 Dados de exemplo: " . json_encode($stats) . "\n\n";
    }
    
    $service = new BTQueue\Core\AIService();
    echo "🤖 Gerando relatório com IA...\n\n";
    echo str_repeat("═", 60) . "\n";
    
    $inicio = microtime(true);
    $resultado = $service->generateDailyReport($stats);
    $tempo = round(microtime(true) - $inicio, 2);
    
    echo "⏱️  Tempo: {$tempo}s\n";
    echo str_repeat("─", 60) . "\n";
    echo $resultado . "\n";
    echo str_repeat("═", 60) . "\n\n";
    
    echo "✅ RELATÓRIO CONCLUÍDO!\n";
    
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
