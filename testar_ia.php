<?php
// testar_ia.php - Teste com múltiplos cenários

require_once __DIR__ . '/core/AIService.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE IA - BT ENTERPRISE                    ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$service = new BTQueue\Core\AIService();

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
    'Cenário 5: Alta eficiência' => [
        'emitidas' => 80,
        'chamadas' => 78,
        'finalizadas' => 76,
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
        echo "❌ Erro: " . $e->getMessage() . "\n\n";
    }
    
    echo str_repeat("═", 60) . "\n\n";
}

echo "✅ TESTES CONCLUÍDOS!\n";
