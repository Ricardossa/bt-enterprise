<?php
// diagnostic_ai_final.php - Diagnóstico para BT Enterprise

// Define o caminho base
define('BASE_PATH', __DIR__);

// Carrega as classes necessárias
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Logger.php';
require_once BASE_PATH . '/core/AIService.php';

// Verifica se a classe existe
if (!class_exists('BTQueue\Core\AIService')) {
    echo "❌ Classe BTQueue\Core\AIService não encontrada\n";
    echo "Tentando carregar sem namespace...\n";
    
    // Tenta usar a classe sem namespace
    if (class_exists('AIService')) {
        class_alias('AIService', 'BTQueue\Core\AIService');
        echo "✅ Classe AIService carregada com alias\n";
    } else {
        echo "❌ Não foi possível carregar AIService\n";
        exit(1);
    }
}

// Função de diagnóstico
function diagnosticarConexaoAI(): void {
    echo "\n" . str_repeat("═", 70) . "\n";
    echo "  🔍 DIAGNÓSTICO DA CONEXÃO COM IA - BT ENTERPRISE\n";
    echo str_repeat("═", 70) . "\n\n";
    
    // 1. Testa a classe AIService
    echo "📦 INSTANCIANDO AIService...\n";
    try {
        $service = new BTQueue\Core\AIService();
        echo "  ✅ AIService instanciado com sucesso\n\n";
    } catch (\Throwable $e) {
        echo "  ❌ Erro ao instanciar: " . $e->getMessage() . "\n";
        return;
    }
    
    // 2. Tenta ler a URL do banco
    echo "📡 VERIFICANDO CONFIGURAÇÃO DO BANCO...\n";
    try {
        // Verifica se Database::fetch existe
        if (method_exists('BTQueue\Core\Database', 'fetch')) {
            $config = BTQueue\Core\Database::fetch(
                "SELECT valor FROM configuracoes WHERE chave = 'ai_url' LIMIT 1"
            );
            
            if ($config) {
                echo "  ✅ URL configurada no banco: " . $config['valor'] . "\n";
                $url = $config['valor'];
            } else {
                echo "  ⚠️  URL não encontrada no banco, usando padrão\n";
                $url = 'http://192.168.100.139:11434/api/generate';
            }
        } else {
            echo "  ⚠️  Database::fetch não disponível, usando URL padrão\n";
            $url = 'http://192.168.100.139:11434/api/generate';
        }
    } catch (\Throwable $e) {
        echo "  ⚠️  Erro ao acessar banco: " . $e->getMessage() . "\n";
        $url = 'http://192.168.100.139:11434/api/generate';
    }
    echo "  📍 URL: {$url}\n\n";
    
    // 3. Testa conectividade com a rede
    $host = '192.168.100.139';
    $port = 11434;
    
    echo "📡 TESTE DE REDE...\n";
    echo "  ▪ Testando {$host}:{$port}... ";
    
    $ping = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if ($ping) {
        echo "✅ CONECTADO\n";
        fclose($ping);
    } else {
        echo "❌ FALHA: {$errstr} ({$errno})\n";
        echo "\n⚠️  A IA não está acessível. Verifique:\n";
        echo "  1. O servidor 192.168.100.139 está ligado?\n";
        echo "  2. O Ollama está rodando?\n";
        echo "  3. O firewall está bloqueando a porta 11434?\n";
        return;
    }
    echo "\n";
    
    // 4. Testa API
    echo "🏥 TESTE DA API OLLAMA...\n";
    echo "  ▪ Health check... ";
    
    $ch = curl_init("http://{$host}:{$port}/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response !== false && $httpCode === 200) {
        echo "✅ OK (HTTP {$httpCode})\n";
        
        $data = json_decode($response, true);
        if (isset($data['models']) && !empty($data['models'])) {
            echo "  📦 Modelos disponíveis:\n";
            foreach ($data['models'] as $model) {
                $name = $model['name'] ?? 'desconhecido';
                $size = isset($model['size']) ? ' (' . round($model['size'] / 1024 / 1024 / 1024, 1) . 'GB)' : '';
                echo "    - {$name}{$size}\n";
            }
        }
    } else {
        echo "❌ FALHA: ";
        if ($curlError) {
            echo "cURL: {$curlError}\n";
        } else {
            echo "HTTP {$httpCode}\n";
        }
        return;
    }
    echo "\n";
    
    // 5. Testa geração com qwen2.5:3b
    echo "📊 TESTE DE GERAÇÃO (qwen2.5:3b)...\n";
    
    $stats = [
        'emitidas' => 15,
        'chamadas' => 12,
        'finalizadas' => 9,
        'pendentes' => 6
    ];
    
    echo "  Dados de teste: " . json_encode($stats) . "\n";
    echo "  Processando... ";
    
    try {
        $inicio = microtime(true);
        $resultado = $service->generateDailyReport($stats);
        $fim = microtime(true);
        
        $tempo = round($fim - $inicio, 2);
        
        // Verifica se é erro ou resposta válida
        if (strpos($resultado, 'Erro') === false && strpos($resultado, 'Falha') === false) {
            echo "✅ SUCESSO ({$tempo}s)\n";
            
            echo "\n  " . str_repeat("─", 70) . "\n";
            echo "  📝 RESPOSTA DA IA:\n";
            echo "  " . str_repeat("─", 70) . "\n";
            
            // Quebra a resposta em linhas
            $lines = explode("\n", $resultado);
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    echo "  " . wordwrap($line, 66, "\n  ") . "\n";
                }
            }
            echo "  " . str_repeat("─", 70) . "\n";
        } else {
            echo "❌ FALHA ({$tempo}s)\n";
            echo "\n  📝 Resposta de erro:\n";
            echo "  " . wordwrap($resultado, 66, "\n  ") . "\n";
        }
        
    } catch (\Throwable $e) {
        echo "❌ EXCEÇÃO\n";
        echo "  Mensagem: " . $e->getMessage() . "\n";
        echo "  Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    // 6. Teste de modelos alternativos
    echo "\n" . str_repeat("─", 70) . "\n";
    echo "🔍 TESTANDO MODELOS ALTERNATIVOS...\n";
    
    $models = ['qwen2.5:3b', 'llama3.2', 'mistral', 'phi3'];
    
    foreach ($models as $model) {
        echo "  ▪ Testando {$model}... ";
        
        $payload = json_encode([
            'model' => $model,
            'prompt' => 'Responda apenas: OK',
            'stream' => false
        ]);
        
        $ch = curl_init("http://{$host}:{$port}/api/generate");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Disponível\n";
        } else {
            echo "❌ Não disponível (HTTP {$httpCode})\n";
        }
    }
    
    echo "\n" . str_repeat("═", 70) . "\n";
    echo "  ✅ DIAGNÓSTICO CONCLUÍDO!\n";
    echo str_repeat("═", 70) . "\n";
}

// Executa o diagnóstico
try {
    diagnosticarConexaoAI();
} catch (\Throwable $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "  Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
