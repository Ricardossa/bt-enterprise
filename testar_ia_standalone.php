<?php
// testar_ia_standalone.php - Versão completamente independente

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE IA - STANDALONE                       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Testa conectividade primeiro
echo "📡 Testando conectividade com a IA...\n";
$host = '192.168.100.139';
$port = 11434;

$ping = @fsockopen($host, $port, $errno, $errstr, 3);
if (!$ping) {
    echo "❌ Não foi possível conectar a {$host}:{$port}\n";
    echo "   Erro: {$errstr} ({$errno})\n";
    exit(1);
}
echo "✅ Conectado a {$host}:{$port}\n";
fclose($ping);
echo "\n";

// Testa se o modelo existe
echo "🔍 Verificando modelo qwen2.5:3b...\n";
$ch = curl_init("http://{$host}:{$port}/api/tags");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ API não respondeu (HTTP {$httpCode})\n";
    exit(1);
}

$data = json_decode($response, true);
$modelFound = false;
if (isset($data['models'])) {
    foreach ($data['models'] as $model) {
        if (strpos($model['name'] ?? '', 'qwen') !== false) {
            $modelFound = true;
            echo "✅ Modelo encontrado: {$model['name']}\n";
            break;
        }
    }
}

if (!$modelFound) {
    echo "⚠️  Modelo qwen2.5:3b não encontrado. Tentando usar o primeiro disponível...\n";
}
echo "\n";

// Função para chamar a IA
function askAI($prompt, $model = 'qwen2.5:3b') {
    $url = 'http://192.168.100.139:11434/api/generate';
    
    $payload = json_encode([
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || $error !== '') {
        return "❌ Erro: {$error}";
    }
    
    if ($httpCode !== 200) {
        return "❌ HTTP Error: {$httpCode}";
    }
    
    $json = json_decode($response, true);
    return $json['response'] ?? "Resposta vazia";
}

// Função que gera o relatório
function generateDailyReport($stats) {
    $emitidas = (int)($stats['emitidas'] ?? 0);
    $chamadas = (int)($stats['chamadas'] ?? 0);
    $finalizadas = (int)($stats['finalizadas'] ?? 0);
    $pendentes = (int)($stats['pendentes'] ?? 0);
    
    $taxaConclusao = $emitidas > 0
        ? round(($finalizadas / $emitidas) * 100, 1)
        : 0;
    
    if ($pendentes > 0) {
        $situacao = "Há {$pendentes} senha(s) pendente(s) de atendimento.";
    } else {
        $situacao = "Não há senhas pendentes de atendimento.";
    }
    
    $prompt = "Você é o AI Core do BT Queue Enterprise da Brandão Tech.

Transforme os dados abaixo em um diagnóstico curto e profissional.

DADOS REAIS:
Senhas emitidas: {$emitidas}
Senhas chamadas: {$chamadas}
Senhas finalizadas: {$finalizadas}
Senhas pendentes: {$pendentes}
Taxa de conclusão: {$taxaConclusao}%

SITUAÇÃO DETERMINADA PELO SISTEMA:
{$situacao}

REGRAS ABSOLUTAS:
- Use somente os dados fornecidos.
- Não invente informações.
- Não mencione funcionários.
- Não mencione faltas.
- Não invente causas.
- Não invente tempos de atendimento.
- Não diga que existem gargalos.
- Não diga que existe baixa produtividade.
- Não transforme uma senha pendente em uma falta.
- Não faça recomendações genéricas.
- Se não houver dados suficientes para determinar uma causa, informe isso.
- A taxa de conclusão é apenas um indicador; não determine sozinho que existe um problema.
- Não faça cálculos adicionais.

Responda exatamente neste formato:

SITUAÇÃO:
[explique somente o que os dados comprovam]

ATENÇÃO:
[aponte somente o que realmente está pendente ou necessita acompanhamento]

RECOMENDAÇÃO:
[recomendação diretamente relacionada aos dados disponíveis]";
    
    return askAI($prompt);
}

// === TESTES ===

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
    'Cenário 4: Sua operação' => [
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
        $resultado = generateDailyReport($dados);
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
