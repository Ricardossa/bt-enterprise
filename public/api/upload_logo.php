<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerAPI('ADMIN');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_FILES['logo'])) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada.']);
    exit;
}

$tipo = $_POST['tipo'] ?? 'admin';

// Mapeamento de arquivos por tipo de identidade
$arquivos = [
    'admin'    => 'logo.png',
    'mobile'   => 'logo_mobile.png',
    'campaign' => 'logo_campanha.png'
];

$nomeArquivo = $arquivos[$tipo] ?? 'logo.png';
$destino = __DIR__ . '/../uploads/' . $nomeArquivo;

if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
    // [v7.7.2] Garante que a URL da logo no banco seja SEMPRE um caminho relativo (Blindagem de Porta)
    $relativeUrl = 'uploads/' . $nomeArquivo;

    Database::execute(
        "INSERT OR REPLACE INTO configuracoes (chave, valor, tipo) VALUES (?, ?, 'STRING')",
        ['logo_url', $relativeUrl]
    );

    echo json_encode(['success' => true, 'arquivo' => $nomeArquivo, 'url' => $relativeUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar a imagem em disco.']);
}
