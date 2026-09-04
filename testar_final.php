<?php
// testar_final.php - Teste final do sistema

require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Logger.php';
require_once __DIR__ . '/core/AIService.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE FINAL - IA BT ENTERPRISE                 ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

try {
    $service = new BTQueue\Core\AIService();
    
    // Mostra configuração atual
    $reflection = new ReflectionClass($service);
    $modelProp = $reflection->getProperty('model');
    $modelProp->setAccessible(true);
    $modelo = $modelProp->getValue($service);
    
    $urlProp = $reflection->getProperty('ollamaUrl');
    $urlProp->setAccessible(true);
    $url = $urlProp->getValue($service);
    
    echo "📋 CONFIGURAÇÃO:\n";
    echo "  Modelo: {$modelo}\n";
    echo "  URL: {$url}\n\n";
    
    // Dados reais do relatório (2 emitidas, 1 pendente)
    $stats = [
        'emitidas' => 2,
        'chamadas' => 1,
        'finalizadas' => 1,
        'pendentes' => 1
    ];
    
    echo "📊 DADOS DO RELATÓRIO:\n";
    echo str_repeat("─", 50) . "\n";
    echo "  Senhas emitidas:    {$stats['emitidas']}\n";
    echo "  Senhas chamadas:    {$stats['chamadas']}\n";
    echo "  Senhas finalizadas: {$stats['finalizadas']}\n";
    echo "  Senhas pendentes:   {$stats['pendentes']}\n";
    echo str_repeat("─", 50) . "\n\n";
    
    echo "🤖 GERANDO INSIGHT...\n";
    echo str_repeat("═", 60) . "\n";
    
    $inicio = microtime(true);
    $resultado = $service->generateDailyReport($stats);
    $tempo = round(microtime(true) - $inicio, 2);
    
    echo "⏱️  Tempo: {$tempo}s\n";
    echo str_repeat("─", 60) . "\n";
    echo $resultado . "\n";
    echo str_repeat("═", 60) . "\n\n";
    
    // Verifica se teve erro
    if (strpos($resultado, 'Erro ao consultar') !== false) {
        echo "❌ AINDA HÁ ERRO NA COMUNICAÇÃO!\n";
        echo "Verifique:\n";
        echo "  1. O IP está correto? {$url}\n";
        echo "  2. O modelo existe? {$modelo}\n";
        echo "  3. O servidor está acessível?\n";
    } else {
        echo "✅ SISTEMA FUNCIONANDO PERFEITAMENTE!\n";
        echo "   Modelo: {$modelo}\n";
        echo "   Tempo: {$tempo}s\n";
    }
    
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
