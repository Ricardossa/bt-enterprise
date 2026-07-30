<?php
/**
 * TESTE DE IMPRESSÃO DIRETA BEMATECH
 */
header('Content-Type: text/plain; charset=utf-8');

$printer = "BT_TICKET"; // Nome do compartilhamento
$testFile = "test_print.txt";

echo "🧪 INICIANDO TESTE DE IMPRESSÃO NA PORTA USB...\n";
echo "Alvo: \\\\127.0.0.1\\$printer\n\n";

$conteudo = "--------------------------------\n";
$conteudo .= "      TESTE DE IMPRESSAO        \n";
$conteudo .= "    SISTEMA BT QUEUE 2.0        \n";
$conteudo .= "--------------------------------\n";
$conteudo .= "Data: " . date('d/m/Y H:i:s') . "\n";
$conteudo .= "Se voce esta lendo isso, a\n";
$conteudo .= "comunicacao PHP -> USB funciona!\n";
$conteudo .= "--------------------------------\n\n\n\n\n";
$conteudo .= "\x1D\x56\x41"; // Comando de corte

file_put_contents($testFile, $conteudo);

$cmd = "copy /b \"$testFile\" \"\\\\127.0.0.1\\$printer\"";
echo "Executando: $cmd\n";

exec($cmd, $output, $res);

if ($res === 0) {
    echo "\n✅ SUCESSO! O Windows aceitou o comando.";
} else {
    echo "\n❌ ERRO! O Windows recusou. Verifique se:\n";
    echo "1. A impressora esta compartilhada como '$printer'.\n";
    echo "2. O servico de Spooler do Windows esta ligado.\n";
}

unlink($testFile);
