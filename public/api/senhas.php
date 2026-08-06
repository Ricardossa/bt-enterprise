<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use BTQueue\Core\QueueService;
use BTQueue\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        \BTQueue\Core\Auth::protegerAPI();

        // Detecta colunas da tabela senhas
        $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
        $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
        $campoCodigo = in_array('codigo', $cols) ? 's.codigo' : 's.senha';

        $fila = Database::fetchAll(
            "SELECT s.*, $campoCodigo as codigo, sv.nome AS servico_nome
             FROM senhas s
             LEFT JOIN servicos sv ON sv.id = s.servico_id
             WHERE s.status='AGUARDANDO'
             ORDER BY s.id"
        );

        echo json_encode([
            'success' => true,
            'data' => $fila
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if ($method === 'POST') {

        $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $servicoId = (int)($dados['servico_id'] ?? 0);
        $deviceId = trim((string)($dados['device_id'] ?? ''));
        $tokenEnviado = trim((string)($dados['t'] ?? ''));

        // --- VALIDAÇÃO DE SEGURANÇA (Anti-Fila Remota) ---
        // Se a chamada vem do Mobile (sem estar logado como admin), exige o Token do Totem
        if (!\BTQueue\Core\Auth::autenticado()) {
            $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'qr_security_salt'");
            $salt = $config['valor'] ?? 'default_salt';

            $valid = false;
            // Valida o token para o minuto atual e os 2 minutos anteriores (janela de tolerância)
            for ($i = 0; $i <= 2; $i++) {
                $checkHash = md5($salt . date('YmdHi', strtotime("-$i minutes")));
                if (hash_equals($checkHash, $tokenEnviado)) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => '❌ QR Code Expirado. Por favor, escaneie novamente o código no Totem da loja.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($servicoId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Serviço não informado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $servico = Database::fetch("SELECT prefixo, nome FROM servicos WHERE id=? LIMIT 1", [$servicoId]);

        if (!$servico) {
            echo json_encode(['success' => false, 'message' => 'Serviço não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($deviceId !== '') {
            $resInfo = Database::getInstance()->query("PRAGMA table_info(senhas)");
            $cols = array_column($resInfo->fetchAll(PDO::FETCH_ASSOC), 'name');
            $campoCodigo = in_array('codigo', $cols) ? 'codigo' : 'senha';

            $senhaExistente = Database::fetch(
                "SELECT $campoCodigo as codigo, cliente_uuid, status
                 FROM senhas
                 WHERE device_id = ?
                 AND status IN ('AGUARDANDO','CHAMANDO')
                 ORDER BY id DESC
                 LIMIT 1",
                [$deviceId]
            );

            if ($senhaExistente) {
                echo json_encode([
                    'success' => true,
                    'modo' => 'existente',
                    'message' => 'Senha já existente.',
                    'data' => $senhaExistente
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $clienteUuid = bin2hex(random_bytes(16));
        $queue = new QueueService();

        $resultado = $queue->emitir(
            $servico['prefixo'],
            $servicoId,
            $clienteUuid,
            $deviceId
        );

        if (!$resultado['success']) {
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado['servico_nome'] = $servico['nome'];
        $resultado['cliente_uuid'] = $clienteUuid;

        echo json_encode([
            'success' => true,
            'message' => 'Senha gerada com sucesso!',
            'data' => $resultado
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
