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

// --- TRAVA DE HORÁRIO MOBILE ---
$abertura = $config['opening_time'] ?? '00:00';
$fechamento = $config['closing_time'] ?? '23:59';
$agora = date('H:i');
$estaFechado = ($agora < $abertura || $agora > $fechamento);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Retirar Senha - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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

        <script>
            window.BT_MOBILE_CONFIG = {
                multi_ticket: <?= ($config['feature_multi_ticket'] ?? '1') === '1' ? 'true' : 'false' ?>
            };
        </script>

        <?php if ($estaFechado): ?>
            <h1 style="color: #FF4D4D;">LOJA FECHADA</h1>
            <p>Atendimento das <?= $abertura ?> às <?= $fechamento ?></p>
        <?php else: ?>
            <h1>Escolha o Serviço</h1>
            <p>Retire sua senha no seu celular</p>
        <?php endif; ?>
    </header>

    <main>
        <?php if ($estaFechado): ?>
            <section class="premium-card animate__animated animate__headShake" style="border-color: #FF4D4D;">
                <div style="padding: 50px 20px; text-align: center;">
                    <i class="fa-solid fa-clock-rotate-left" style="font-size: 60px; color: #FF4D4D; margin-bottom: 25px;"></i>
                    <h2 style="color: #fff; margin-bottom: 15px;">Atendimento Encerrado</h2>
                    <p style="color: var(--text2); font-size: 16px; line-height: 1.6;">
                        Agradecemos sua preferência. Nosso expediente para emissão de senhas via celular encerrou às <b><?= $fechamento ?></b>.<br><br>
                        Por favor, retorne amanhã a partir das <b><?= $abertura ?></b>.
                    </p>
                </div>
            </section>
        <?php else: ?>
            <section class="premium-card">
                <h2 class="premium-card-title">Olá! Seja Bem-vindo</h2>
                <div id="lista-servicos">
                    <div style="padding: 40px; text-align: center;">
                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 30px; color: var(--secondary);"></i>
                        <p style="color: var(--text2); margin-top: 15px;">Sincronizando...</p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
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
