<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\Auth;

Auth::protegerAPI();

header('Content-Type: application/json; charset=utf-8');

try {
    $dados = json_decode(file_get_contents('php://input'), true);
    $id = isset($dados['id']) ? (int)$dados['id'] : 0;

    if ($id <= 0) {
        throw new Exception("ID da senha inválido.");
    }

    $senha = Database::fetch("SELECT * FROM senhas WHERE id = ? LIMIT 1", [$id]);

    if (!$senha) {
        throw new Exception("Senha não encontrada.");
    }

    // [RECHAMAR] - Atualiza o timestamp para a TV perceber como um novo evento
    Database::execute(
        "UPDATE senhas SET chamada_em = CURRENT_TIMESTAMP WHERE id = ?",
        [$id]
    );

    // Resolve o código do guichê
    $guiche = Database::fetch("SELECT codigo FROM guiches WHERE id = ? LIMIT 1", [$senha['guiche_id']]);
    $guicheCodigo = $guiche ? $guiche['codigo'] : '01';

    // Dispara novamente o evento de chamada na fila de sincronização (MasterSync)
    Database::execute(
        "INSERT INTO sync_queue (evento, entidade, referencia_id, payload, sincronizado) VALUES (?, ?, ?, ?, 0)",
        [
            'CHAMAR',
            'senha',
            $id,
            json_encode(['senha' => $senha['codigo'] ?? $senha['senha'], 'guiche' => $guicheCodigo], JSON_UNESCAPED_UNICODE)
        ]
    );

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
