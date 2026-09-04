<?php
// testar_sem_logger.php - Versão que substitui o Logger por uma versão mock

define('BASE_PATH', __DIR__);

// Cria uma versão mock do Logger antes de carregar o AIService
if (!class_exists('BTQueue\Core\Logger')) {
    class BTQueue\Core\Logger {
        public static function error($message) {
            // Não faz nada
        }
        public static function info($message) {
            // Não faz nada
        }
        public static function warning($message) {
            // Não faz nada
        }
    }
}

// Agora carrega as dependências
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/AIService.php';

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║     TESTE DE IA - SEM LOGGER                       ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

try {
    // Tenta instanciar com a URL direta (sem banco)
    $service = new BTQueue\Core\AIService();
    echo "✅ AIService instanciado com sucesso\n\n";
    
    // Teste rápido
    $stats = [
        'emitidas' => 10,
        'chamadas' => 8,
        'finalizadas' => 6,
        'pendentes' => 4
    ];
    
    echo "📊 Testando com dados: " . json_encode($stats) . "\n\n";
    echo str_repeat("─", 60) . "\n";
    
    $resultado = $service->generateDailyReport($stats);
    
    echo "📝 RESPOSTA:\n";
    echo str_repeat("─", 60) . "\n";
    echo $resultado . "\n";
    echo str_repeat("─", 60) . "\n\n";
    
    echo "✅ TESTE CONCLUÍDO!\n";
    
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
