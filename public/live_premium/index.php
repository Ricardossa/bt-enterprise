<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue Enterprise';

// CÁLCULO DE BASE_URL PARA O MOBILE (Atomic Path)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('live_premium/index.php', '', $_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Retirar Senha - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<div class="live-container">
    <header class="header-premium">
        <?php
            $logoUrl = null;
            if (isset($config['promo_logo']) && !empty($config['promo_logo'])) {
                $filename = basename($config['promo_logo']);
                $logoUrl = $baseUrl . 'uploads/' . $filename;
            }
        ?>

        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" style="max-height: 100px; max-width: 80%; object-fit: contain; margin-bottom: 15px;">
        <?php else: ?>
            <i class="fa-solid fa-qrcode"></i>
        <?php endif; ?>
        <h1>Escolha o Serviço</h1>
        <p>Retire sua senha no seu celular</p>
    </header>

    <main>
        <section class="premium-card">
            <h2 class="premium-card-title">Olá! Seja Bem-vindo</h2>
            <div id="lista-servicos">
                <div style="padding: 40px; text-align: center;">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 30px; color: var(--secondary);"></i>
                    <p style="color: var(--text2); margin-top: 15px;">Sincronizando...</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-signature">
        <p>Desenvolvido por</p>
        <img src="http://api.brandaotech.com.br:8080/uploads/logo/logo.png" alt="Brandão Tech">
    </footer>
</div>

<script src="../assets/js/api.js?v=4.7"></script>
<script src="assets/js/emitter.js?v=4.7"></script>
</body>
</html>
