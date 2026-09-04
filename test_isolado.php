<?php
// test_isolado.php - Teste completamente isolado

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE ISOLADO - SEM DEPENDÊNCIAS              ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Define uma classe AIService simplificada
class AIServiceSimples
{
    private string $ollamaUrl = 'http://192.168.100.139:11434/api/generate';
    private string $model = 'qwen2.5:3b';
    
    public function ask(string $prompt): string
    {
        try {
            $payload = json_encode([
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false
            ]);
            
            $ch = curl_init($this->ollamaUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                return "❌ Erro cURL: {$error}";
            }
            
            if ($httpCode !== 200) {
                return "❌ HTTP {$httpCode}";
            }
            
            $data = json_decode($response, true);
            return $data['response'] ?? "❌ Resposta vazia";
            
        } catch (\Throwable $e) {
            return "❌ Exceção: " . $e->getMessage();
        }
    }
    
    public function generateDailyReport(array $stats): string
    {
        $emitidas = (int)($stats['emitidas'] ?? 0);
        $chamadas = (int)($stats['chamadas'] ?? 0);
        $finalizadas = (int)($stats['finalizadas'] ?? 0);
        $pendentes = (int)($stats['pendentes'] ?? 0);
        
        $taxaConclusao = $emitidas > 0 ? round(($finalizadas / $emitidas) * 100, 1) : 0;
        
        $prompt = "DADOS: emitidas={$emitidas}, chamadas={$chamadas}, finalizadas={$finalizadas}, pendentes={$pendentes}, taxa={$taxaConclusao}%. 
        
Responda EXATAMENTE neste formato:

SITUAÇÃO: [descreva os números]
ATENÇÃO: [oque está pendente]
RECOMENDAÇÃO: [ação baseada nos dados]";
        
        return $this->ask($prompt);
    }
}

// === TESTE ===

echo "📡 Testando conectividade...\n";
$host = '192.168.100.139';
$port = 11434;

$fp = @fsockopen($host, $port, $errno, $errstr, 3);
if ($fp) {
    echo "✅ Conectado a {$host}:{$port}\n";
    fclose($fp);
} else {
    echo "❌ Falha: {$errstr}\n";
    exit(1);
}

echo "\n🤖 Testando geração...\n";
$service = new AIServiceSimples();

$stats = ['emitidas' => 2, 'chamadas' => 1, 'finalizadas' => 1, 'pendentes' => 1];
echo "📊 Dados: " . json_encode($stats) . "\n\n";

$inicio = microtime(true);
$resultado = $service->generateDailyReport($stats);
$tempo = round(microtime(true) - $inicio, 2);

echo "⏱️  Tempo: {$tempo}s\n";
echo str_repeat("─", 60) . "\n";
echo $resultado . "\n";
echo str_repeat("─", 60) . "\n";

echo "\n✅ TESTE CONCLUÍDO!\n";
