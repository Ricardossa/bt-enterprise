<?php
/**
 * BT QUEUE - PRINT BRIDGE v1.0
 * Este script transforma seu Windows em um Servidor de Impressão para a Nuvem.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);

        if (!$dados || !isset($dados['senha'])) {
            throw new Exception("Dados invalidos.");
        }

        $senha = $dados['senha'];
        $servico = $dados['servico'] ?? 'Atendimento';
        $empresa = $dados['empresa'] ?? 'BT Queue';
        $printer = $dados['printer'] ?? 'BT_TICKET';
        $data = date('d/m/Y H:i:s');

        echo "🖨️ Recebido: Senha $senha para $printer\n";

        // Formatação ESC/POS básica
        $ticket = "\x1B\x40";
        $ticket .= "\x1B\x61\x01";
        $ticket .= "--------------------------------\n";
        $ticket .= strtoupper($empresa) . "\n";
        $ticket .= "--------------------------------\n\n";
        $ticket .= "SUA SENHA:\n";
        $ticket .= "\x1B\x21\x30" . $senha . "\n\x1B\x21\x00";
        $ticket .= strtoupper($servico) . "\n\n";
        $ticket .= "Data: " . $data . "\n";
        $ticket .= "--------------------------------\n\n\n\n\n";
        $ticket .= "\x1D\x56\x41";

        $tempFile = 'temp_ticket.txt';
        file_put_contents($tempFile, $ticket);

        $cmd = "copy /b \"$tempFile\" \"\\\\127.0.0.1\\$printer\"";
        exec($cmd, $output, $res);
        unlink($tempFile);

        if ($res === 0) {
            echo json_encode(['success' => true, 'message' => 'Impresso com sucesso na ' . $printer]);
        } else {
            throw new Exception("Erro ao enviar para spooler. Verifique o compartilhamento.");
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Se for GET, mostra o status
echo "🚀 BT Print Bridge está ATIVO!\n";
echo "Aguardando pedidos de impressão na porta 8001...\n";
echo "Endereço local: http://" . gethostbyname(gethostname()) . ":8001\n";
