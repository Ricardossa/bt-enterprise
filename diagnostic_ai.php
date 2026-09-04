<?php
// diagnostic_ai.php - Teste de conectividade com a IA

// Inclui o autoload do composer
require_once __DIR__ . '/vendor/autoload.php';

use BTQueue\Core\AIService;

function diagnosticarConexaoAI(): void
{
    echo "🔍 DIAGNÓSTICO DA CONEXÃO COM IA\n";
    echo str_repeat("=", 50) . "\n\n";

    // 1. Testa a classe AIService
    try {
        echo "✅ AIService instanciada com sucesso\n";
        $service = new AIService();
    } catch (\Throwable $e) {
        echo "❌ Erro ao instanciar AIService: " . $e->getMessage() . "\n";
        return;
    }

    // 2. Verifica configuração
    try {
        $reflection = new \ReflectionClass($service);
        $ollamaUrl = $reflection->getProperty('ollamaUrl');
        $ollamaUrl->setAccessible(true);
        $url = $ollamaUrl->getValue($service);

        echo "📍 URL da IA configurada: {$url}\n";
    } catch (\Throwable $e) {
        echo "❌ Erro ao ler configuração: " . $e->getMessage() . "\n";
        $url = 'http://192.168.100.139:11434/api/generate';
        echo "📍 Usando URL padrão: {$url}\n";
    }

    // 3. Testa conectividade com a rede
    echo "\n📡 TESTANDO CONECTIVIDADE:\n";

    $host = parse_url($url, PHP_URL_HOST);
    $port = parse_url($url, PHP_URL_PORT) ?: 11434;

    // Teste de ping
    echo "  ▪ Ping no host {$host}: ";
    $ping = @fsockopen($host, $port, $errno, $errstr, 5);

    if ($ping) {
        echo "✅ Conectado\n";
        fclose($ping);
    } else {
        echo "❌ Falha: {$errstr} ({$errno})\n";
    }

    // 4. Testa API de health check
    echo "\n🏥 TESTANDO HEALTH CHECK DA API:\n";
    
    $ch = curl_init("http://{$host}:{$port}/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response !== false && $httpCode === 200) {
        echo "✅ API respondendo (HTTP {$httpCode})\n";
        
        $data = json_decode($response, true);
        if (isset($data['models']) && !empty($data['models'])) {
            echo "  📦 Modelos disponíveis:\n";
            foreach ($data['models'] as $model) {
                echo "    - " . ($model['name'] ?? 'desconhecido') . "\n";
            }
        }
    } else {
        echo "❌ API não respondeu: ";
        if ($curlError) {
            echo "Erro cURL: {$curlError}\n";
        } else {
            echo "HTTP {$httpCode}\n";
        }
    }

    // 5. Testa a função generateDailyReport com dados simples
    echo "\n📊 TESTANDO generateDailyReport:\n";
    
    $stats = [
        'emitidas' => 10,
        'chamadas' => 8,
        'finalizadas' => 6,
        'pendentes' => 4
    ];
    
    echo "  Dados de teste: " . json_encode($stats) . "\n";
    echo "  Processando... ";
    
    try {
        $inicio = microtime(true);
        $resultado = $service->generateDailyReport($stats);
        $fim = microtime(true);
        
        if ($resultado && $resultado !== "Erro ao consultar a Inteligência Artificial.") {
            echo "✅ Sucesso em " . round($fim - $inicio, 2) . "s\n";
            echo "\n  Resposta:\n";
            echo wordwrap($resultado, 80, "\n  ") . "\n";
        } else {
            echo "❌ Falha: Resposta vazia ou erro\n";
        }
    } catch (\Throwable $e) {
        echo "❌ Exceção: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Diagnóstico concluído!\n";
}

// Executa o diagnóstico
diagnosticarConexaoAI();
