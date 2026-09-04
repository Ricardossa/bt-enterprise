<?php
// testar_ia_full.php - Carrega todas as dependências

define('BASE_PATH', __DIR__);

// Carrega todas as classes necessárias
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Logger.php';
require_once BASE_PATH . '/core/AIService.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE IA - BT ENTERPRISE (FULL)             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Verifica se a classe existe
if (!class_exists('BTQueue\Core\AIService')) {
    echo "❌ Classe BTQueue\Core\AIService não encontrada\n";
    exit(1);
}

echo "✅ Dependências carregadas com sucesso\n\n";

try {
    $service = new BTQueue\Core\AIService();
    echo "✅ AIService instanciado com sucesso\n\n";
    
    // Cenários de teste
    $cenarios = [
        'Cenário 1: Operação normal' => [
            'emitidas' => 50,
            'chamadas' => 45,
            'finalizadas' => 40,
            'pendentes' => 10
        ],
        'Cenário 2: Sem pendências' => [
            'emitidas' => 30,
            'chamadas' => 30,
            'finalizadas' => 30,
            'pendentes' => 0
        ],
        'Cenário 3: Muitas pendências' => [
            'emitidas' => 100,
            'chamadas' => 60,
            'finalizadas' => 40,
            'pendentes' => 60
        ],
        'Cenário 4: Baixo volume' => [
            'emitidas' => 3,
            'chamadas' => 2,
            'finalizadas' => 1,
            'pendentes' => 2
        ],
        'Cenário 5: Sua operação' => [
            'emitidas' => 10,
            'chamadas' => 8,
            'finalizadas' => 6,
            'pendentes' => 4
        ]
    ];
    
    foreach ($cenarios as $nome => $dados) {
        echo "📊 {$nome}\n";
        echo str_repeat("─", 60) . "\n";
        echo "Dados: " . json_encode($dados) . "\n\n";
        
        try {
            $inicio = microtime(true);
            $resultado = $service->generateDailyReport($dados);
            $tempo = round(microtime(true) - $inicio, 2);
            
            echo "⏱️  Tempo: {$tempo}s\n";
            echo "📝 Resposta:\n";
            echo str_repeat("─", 60) . "\n";
            echo $resultado . "\n";
            echo str_repeat("─", 60) . "\n\n";
            
        } catch (\Throwable $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        }
        
        echo str_repeat("═", 60) . "\n\n";
    }
    
    echo "✅ TESTES CONCLUÍDOS!\n";
    
} catch (\Throwable $e) {
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
