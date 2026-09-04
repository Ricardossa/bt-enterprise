<?php
// testar_modelos.php - Testa todos os modelos disponíveis

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE MODELOS - IA BT ENTERPRISE            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$host = '192.168.100.139';
$port = 11434;

// Lista de modelos para testar
$modelos = [
    'qwen2.5:3b' => 'Modelo padrão (3B)',
    'qwen3:4b' => 'Modelo intermediário (4B)',
    'qwen3:8b' => 'Modelo potente (8B)'
];

echo "📡 Conectando ao servidor: {$host}:{$port}\n\n";

foreach ($modelos as $modelo => $descricao) {
    echo "🔍 Testando: {$descricao} ({$modelo})\n";
    echo str_repeat("─", 60) . "\n";
    
    // Testa se o modelo existe
    $check = curl_init("http://{$host}:{$port}/api/tags");
    curl_setopt($check, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($check, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($check);
    curl_close($check);
    
    $existe = strpos($response, $modelo) !== false;
    
    if (!$existe) {
        echo "❌ Modelo não encontrado\n\n";
        continue;
    }
    
    echo "✅ Modelo encontrado\n";
    
    // Testa geração
    $payload = json_encode([
        'model' => $modelo,
        'prompt' => "Responda apenas com a palavra: FUNCIONANDO",
        'stream' => false
    ]);
    
    $ch = curl_init("http://{$host}:{$port}/api/generate");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $inicio = microtime(true);
    $response = curl_exec($ch);
    $tempo = round(microtime(true) - $inicio, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        echo "❌ Erro: {$error}\n\n";
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n\n";
        continue;
    }
    
    $data = json_decode($response, true);
    $resposta = $data['response'] ?? 'Sem resposta';
    
    echo "✅ Resposta: {$resposta}\n";
    echo "⏱️  Tempo: {$tempo}s\n";
    echo "📦 Tamanho: " . round(strlen($response) / 1024, 2) . "KB\n\n";
}

echo str_repeat("═", 60) . "\n";
echo "✅ TESTES CONCLUÍDOS!\n";
