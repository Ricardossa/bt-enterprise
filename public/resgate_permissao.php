<?php
header('Content-Type: text/plain');
echo "🚑 BT RESGATE DE PERMISSÃO\n";
echo "========================\n\n";

$pastas = [
    'Enterprise' => __DIR__,
    'Master' => dirname(__DIR__, 2) . '/bt-platform/public'
];

foreach ($pastas as $nome => $path) {
    echo "Verificando $nome ($path):\n";
    if (is_dir($path)) {
        echo "   Pasta Existe: SIM\n";
        echo "   Leitura: " . (is_readable($path) ? "OK" : "NEGADO ❌") . "\n";
        echo "   Escrita: " . (is_writable($path) ? "OK" : "NEGADO ❌") . "\n";

        $index = $path . '/index.php';
        echo "   index.php: " . (file_exists($index) ? "EXISTE" : "FALTANDO ❌") . "\n";
        if (file_exists($index)) {
            echo "   Leitura index.php: " . (is_readable($index) ? "OK" : "NEGADO ❌") . "\n";
        }
    } else {
        echo "   Pasta Existe: NÃO ❌\n";
    }
    echo "\n";
}
