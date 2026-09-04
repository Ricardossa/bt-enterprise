<?php
// testar_db_completo.php - Teste completo com banco SQLite

require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/AIService.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     RELATÓRIO DE PRODUTIVIDADE - BT ENTERPRISE     ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

try {
    $hoje = date('Y-m-d');
    
    // Tenta buscar dados do banco
    echo "📡 Conectando ao banco de dados...\n";
    
    // Verifica se a tabela existe
    $tabelaExiste = BTQueue\Core\Database::fetch(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='senhas'"
    );
    
    if (!$tabelaExiste) {
        echo "⚠️  Tabela 'senhas' não encontrada. Usando dados de exemplo.\n";
        $stats = [
            'emitidas' => 10,
            'chamadas' => 8,
            'finalizadas' => 6,
            'pendentes' => 4
        ];
    } else {
        echo "✅ Tabela 'senhas' encontrada\n";
        
        // Busca dados do dia
        $stats = [];
        
        $query = "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = '{$hoje}'";
        $result = BTQueue\Core\Database::fetch($query);
        $stats['emitidas'] = (int)($result['total'] ?? 0);
        
        $query = "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_chamada) = '{$hoje}' AND status = 'chamada'";
        $result = BTQueue\Core\Database::fetch($query);
        $stats['chamadas'] = (int)($result['total'] ?? 0);
        
        $query = "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_finalizacao) = '{$hoje}' AND status = 'finalizada'";
        $result = BTQueue\Core\Database::fetch($query);
        $stats['finalizadas'] = (int)($result['total'] ?? 0);
        
        $query = "SELECT COUNT(*) as total FROM senhas WHERE DATE(data_emissao) = '{$hoje}' AND status = 'pendente'";
        $result = BTQueue\Core\Database::fetch($query);
        $stats['pendentes'] = (int)($result['total'] ?? 0);
        
        echo "✅ Dados carregados para {$hoje}\n\n";
    }
    
    // Mostra os dados
    echo "📊 DADOS DA OPERAÇÃO:\n";
    echo str_repeat("─", 50) . "\n";
    echo "  📌 Senhas emitidas:    {$stats['emitidas']}\n";
    echo "  📌 Senhas chamadas:    {$stats['chamadas']}\n";
    echo "  📌 Senhas finalizadas: {$stats['finalizadas']}\n";
    echo "  📌 Senhas pendentes:   {$stats['pendentes']}\n";
    echo str_repeat("─", 50) . "\n\n";
    
    if ($stats['emitidas'] === 0) {
        echo "⚠️  Sem dados para hoje. Gerando relatório com dados de exemplo...\n\n";
        $stats = [
            'emitidas' => 25,
            'chamadas' => 20,
            'finalizadas' => 15,
            'pendentes' => 10
        ];
    }
    
    // Gera o relatório
    echo "🤖 GERANDO RELATÓRIO COM IA...\n";
    echo str_repeat("═", 60) . "\n";
    
    $service = new BTQueue\Core\AIService();
    $inicio = microtime(true);
    $resultado = $service->generateDailyReport($stats);
    $tempo = round(microtime(true) - $inicio, 2);
    
    echo "⏱️  Processado em: {$tempo}s\n";
    echo str_repeat("─", 60) . "\n";
    echo $resultado . "\n";
    echo str_repeat("═", 60) . "\n\n";
    
    echo "✅ RELATÓRIO CONCLUÍDO COM SUCESSO!\n";
    
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
