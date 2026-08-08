<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use BTQueue\Core\Database;
use BTQueue\Core\DatabaseInstaller;
use BTQueue\Core\MasterSync\SyncService;

function activatePin(string $masterUrl, string $pin): array
{
    $payload = json_encode(['codigo' => $pin]);
    $trimUrl = rtrim($masterUrl, '/');
    if (preg_match('#/sync\.php$#i', $trimUrl)) {
        $endpoint = preg_replace('#/sync\.php$#i', '/ativar.php', $trimUrl);
    } elseif (preg_match('#/api/v1$#i', $trimUrl)) {
        $endpoint = $trimUrl . '/ativar.php';
    } else {
        $endpoint = $trimUrl . '/api/v1/ativar.php';
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Falha ao conectar à Platform Master: ' . $error];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Resposta inválida da Platform Master.'];
    }

    if ($httpCode >= 400 || empty($data['success'])) {
        return ['success' => false, 'message' => $data['message'] ?? 'Falha ao ativar código PIN.'];
    }

    $payload = $data['data'] ?? [];
    if (empty($payload['uuid']) || empty($payload['token'])) {
        return ['success' => false, 'message' => 'Resposta da Platform não contém uuid/token.'];
    }

    return ['success' => true, 'uuid' => $payload['uuid'], 'token' => $payload['token']];
}

function parseQuickLoad(string $quickLoad): array
{
    $parts = array_map('trim', explode('|', $quickLoad));
    if (count($parts) !== 3 || empty($parts[0]) || empty($parts[1]) || empty($parts[2])) {
        return [];
    }
    return ['master_url' => $parts[0], 'uuid' => $parts[1], 'token' => $parts[2]];
}

function generateUuid(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$step = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$error = '';
$success_msg = '';

// ETAPA 1: Verificação de Requisitos
$requirements = [
    'PHP 8.1+' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensão PDO SQLite' => extension_loaded('pdo_sqlite'),
    'Extensão CURL' => extension_loaded('curl'),
    'Pasta Database Escrita' => is_writable(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database'),
    'Pasta Logs Escrita' => is_writable(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs'),
];

$all_ok = !in_array(false, $requirements, true);

// Lógica de Transição de Passos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // PASSO 2: Inicializar Banco (Blindado contra destruição)
    if ($step === 2) {
        $installer = new DatabaseInstaller();
        $res = $installer->install();
        if ($res['success']) {
            header('Location: setup.php?step=3&ok=1');
            exit;
        } else {
            $error = $res['message'];
        }
    }

    // PASSO 3: MasterSync Provisioning
    if ($step === 3) {
        $url = trim($_POST['master_url'] ?? '');
        $uuid = trim($_POST['uuid'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $quickLoad = trim($_POST['quick_load'] ?? '');
        $pinCode = trim($_POST['pin_code'] ?? '');

        if ($quickLoad !== '') {
            $quickData = parseQuickLoad($quickLoad);
            if (!$quickData) {
                $error = 'Carga Rápida inválida. Use o bloco no formato master_url|uuid|token.';
            } else {
                $url = $quickData['master_url'];
                $uuid = $quickData['uuid'];
                $token = $quickData['token'];
            }
        } elseif ($pinCode !== '') {
            if (!$url) {
                $error = 'Informe a URL da Master antes de usar o PIN de ativação.';
            } else {
                $pinResult = activatePin($url, $pinCode);
                if (!$pinResult['success']) {
                    $error = $pinResult['message'];
                } else {
                    $uuid = $pinResult['uuid'];
                    $token = $pinResult['token'];
                }
            }
        }

        if (!$error) {
            if (!$url || !$uuid || !$token) {
                $error = 'Informe UUID/token ou use Carga Rápida / PIN para ativar a licença.';
            } else {
                Database::execute("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('master_url', ?)", [$url]);
                Database::execute("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('uuid', ?)", [$uuid]);
                Database::execute("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('token', ?)", [$token]);

                $cliente = Database::fetch("SELECT id FROM clientes ORDER BY id LIMIT 1");
                if (!$cliente) {
                    $clientUuid = generateUuid();
                    Database::execute(
                        "INSERT INTO clientes (uuid, nome, documento) VALUES (?, ?, ?)",
                        [$clientUuid, 'Cliente Local', '00000000000']
                    );
                    $clienteId = Database::lastInsertId();
                } else {
                    $clienteId = (int)$cliente['id'];
                }

                $licenseKey = strtoupper(bin2hex(random_bytes(6)));

                // Tenta criar licença inicial no banco
                Database::execute(
                    "INSERT OR REPLACE INTO licencas (cliente_id, chave, uuid, token, status, validade) VALUES (?, ?, ?, ?, ?, date('now', '+1 year'))",
                    [$clienteId, $licenseKey, $uuid, $token, 'ATIVA']
                );

                $sync = new SyncService();
                $res = $sync->synchronize();
                if ($res['success']) {
                    header('Location: setup.php?step=4&ok=2');
                    exit;
                } else {
                    $error = "Conectado ao banco, mas falha no sincronismo MasterSync: " . $res['message'];
                }
            }
        }
    }

    // PASSO 4: Criar Admin
    if ($step === 4) {
        $user = trim($_POST['user'] ?? '');
        $pass = trim($_POST['pass'] ?? '');

        if (!$user || !$pass) {
            $error = "Usuário e senha são obrigatórios.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            Database::execute("DELETE FROM operadores WHERE login = ?", [$user]);
            Database::execute("INSERT INTO operadores (nome, login, senha, nivel, ativo) VALUES (?, ?, ?, 'ADMIN', 1)", ['Administrador Geral', $user, $hash]);

            // Cria arquivo de trava de instalação
            file_put_contents(dirname(__DIR__) . '/database/.installed', date('Y-m-d H:i:s'));

            $success_msg = "SISTEMA INSTALADO COM SUCESSO! O motor invisível e a sincronização já estão ativos.";
        }
    }
}

$pageTitle = 'Setup Wizard - BT Queue Enterprise';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .setup-container { max-width: 700px; margin: 50px auto; padding: 40px; background: var(--card); border-radius: 15px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .step { flex: 1; text-align: center; color: #666; font-size: 12px; font-weight: bold; }
        .step.active { color: var(--secondary); }
        .step.done { color: var(--success); }
        .req-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body style="background: var(--bg); color: var(--text);">

<div class="setup-container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: var(--secondary);">🛡️ BT Queue Enterprise</h1>
        <p>Assistente de Instalação Profissional (v5.2.0)</p>
    </div>

    <div class="step-indicator">
        <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'done' : '' ?>">1. AMBIENTE</div>
        <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'done' : '' ?>">2. BANCO</div>
        <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'done' : '' ?>">3. MASTERSYNC</div>
        <div class="step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'done' : '' ?>">4. ADMIN</div>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(255,77,77,0.1); color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ff4d4d;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div style="background: rgba(24,201,100,0.1); color: #18C964; padding: 30px; border-radius: 8px; text-align: center; border: 1px solid #18C964;">
            <h2 style="margin-bottom: 15px;">🎉 Sucesso!</h2>
            <p><?= $success_msg ?></p>
            <br>
            <a href="login.php" class="bt-button bt-primary" style="text-decoration: none; padding: 15px 40px;">ENTRAR NO SISTEMA</a>
        </div>
    <?php else: ?>

        <form method="POST" action="setup.php?step=<?= $step ?>">
            <input type="hidden" name="step" value="<?= $step ?>">

            <?php if ($step === 1): ?>
                <h3>Verificação de Saúde do Sistema</h3>
                <div style="margin: 20px 0;">
                    <?php foreach($requirements as $name => $ok): ?>
                        <div class="req-item">
                            <span><?= $name ?></span>
                            <span><?= $ok ? '✅ OK' : '❌ FALHA' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($all_ok): ?>
                    <a href="setup.php?step=2" class="bt-button bt-primary" style="display: block; text-align: center; text-decoration: none;">CONTINUAR</a>
                <?php else: ?>
                    <p style="color: #ff4d4d; font-size: 14px; text-align: center;">Corrija as pendências acima para prosseguir.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($step === 2): ?>
                <h3>Inicialização do Banco de Dados</h3>
                <p style="color: var(--text2); margin: 15px 0;">O sistema irá criar o arquivo <code>banco.db</code> e configurar as tabelas essenciais para a operação offline.</p>
                <button type="submit" class="bt-button bt-primary" style="width: 100%;">INSTALAR BANCO LOCAL</button>
            <?php endif; ?>

            <?php if ($step === 3): ?>
                <h3>Provisionamento MasterSync</h3>
                <p style="color: var(--text2); margin-bottom: 20px;">Use Carga Rápida ou PIN para ativar esta unidade, ou insira UUID/token manualmente.</p>
                <div class="form-group">
                    <label>Carga Rápida</label>
                    <textarea name="quick_load" class="form-control" rows="3" placeholder="Cole aqui o bloco master_url|uuid|token"></textarea>
                </div>
                <div style="display:flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                    <div class="form-group" style="flex:1; min-width:220px;">
                        <label>URL da Master</label>
                        <input type="text" name="master_url" class="form-control" value="http://api.brandaotech.com.br:8080/api/v1/sync.php">
                    </div>
                    <div class="form-group" style="flex:1; min-width:220px;">
                        <label>PIN de Ativação</label>
                        <input type="text" name="pin_code" class="form-control" placeholder="Digite o PIN aqui">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>UUID da Unidade</label>
                    <input type="text" name="uuid" class="form-control" placeholder="Cole o UUID aqui">
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Token de Segurança</label>
                    <input type="text" name="token" class="form-control" placeholder="Cole o Token aqui">
                </div>
                <button type="submit" class="bt-button bt-primary" style="width: 100%; margin-top: 25px;">ATIVAR LICENÇA</button>
            <?php endif; ?>

            <?php if ($step === 4): ?>
                <h3>Acesso Administrativo</h3>
                <p style="color: var(--text2); margin-bottom: 20px;">Crie a senha do administrador local para gerenciar esta unidade.</p>
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="user" class="form-control" value="admin" required>
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Senha</label>
                    <input type="password" name="pass" class="form-control" placeholder="Crie uma senha segura" required>
                </div>
                <button type="submit" class="bt-button bt-primary" style="width: 100%; margin-top: 25px;">FINALIZAR E LIGAR</button>
            <?php endif; ?>

        </form>

    <?php endif; ?>

    <div style="text-align: center; margin-top: 40px; font-size: 11px; color: #555;">
        &copy; 2026 Brandão Tech Integration. Todos os direitos reservados.
    </div>
</div>

</body>
</html>
