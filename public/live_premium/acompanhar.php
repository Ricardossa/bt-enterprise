<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

$uuid = $_GET['uuid'] ?? '';

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue Enterprise';
$promo_logo = $config['promo_logo'] ?? '';
$promo_campanha = $config['promo_campanha'] ?? '';

// CÁLCULO DE BASE_URL ATÔMICA
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('live_premium/acompanhar.php', '', $_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title>Acompanhamento - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="noc-theme">

<div id="app" class="live-container">
    <header class="header-premium">
        <?php
            $logoUrl = null;
            if (!empty($promo_logo)) {
                $logoUrl = $baseUrl . ltrim($promo_logo, '/');
            }
        ?>
        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" style="max-height: 100px; max-width: 80%; object-fit: contain; margin-bottom: 15px;">
        <?php else: ?>
            <i class="fa-solid fa-print"></i>
        <?php endif; ?>
        <h1>Sua Senha</h1>
        <p>Acompanhe em tempo real</p>
    </header>

    <main>
        <section class="premium-card">
            <div class="ticket-white-box">
                <div id="ticket-number" class="ticket-number">---</div>
                <div class="badge-status">
                    <div class="dot-status"></div>
                    <span id="status-label">Consultando...</span>
                </div>
                <div class="grid-stats">
                    <div class="stat-item"><label>Fila</label><b id="stat-posicao">--</b></div>
                    <div class="stat-item"><label>Espera</label><b id="stat-tempo">--</b></div>
                    <div class="stat-item"><label>Local</label><b id="stat-guiche">--</b></div>
                </div>
                <div id="call-alert" class="call-overlay-premium" style="display:none;">
                     <h2 style="font-weight:900;">🔔 CHAMANDO!</h2>
                     <h1 id="alert-guiche">GUICHÊ --</h1>
                </div>
            </div>

            <!-- ÁREA DE PROMOÇÕES -->
            <div class="promo-premium-container">
                <div id="promo-display" class="promo-item-row">
                    <img id="promo-img" src="" class="promo-item-img" onerror="this.style.display='none'">
                    <div class="promo-item-info">
                        <h4 id="promo-title">...</h4>
                        <div id="promo-price" class="promo-item-price"></div>
                        <p id="promo-desc" class="promo-item-desc"></p>
                    </div>
                </div>
                <div class="promo-banner-red">
                    <span>⚡ OFERTAS IMPERDÍVEIS</span>
                </div>
            </div>
        </section>

        <!-- NOVO BLOCO: CAMPANHA (Rodapé Solto) -->
        <?php if (!empty($promo_campanha)): ?>
        <div style="width:100%; margin-top:20px; border-radius:25px; overflow:hidden; box-shadow:var(--shadow-premium); background:var(--card); border:1px solid var(--border); padding:10px;">
            <img src="<?= $baseUrl . ltrim($promo_campanha, '/') ?>" alt="Campanha" style="width:100%; border-radius:15px; display:block;">
        </div>
        <?php endif; ?>

    </main>

    <footer class="footer-signature">
        <p>Desenvolvido por</p>
        <img src="http://api.brandaotech.com.br:8080/uploads/logo/logo.png" alt="Brandão Tech">
    </footer>
</div>

<script src="../assets/js/api.js?v=4.8"></script>
<script src="assets/js/tracker.js?v=4.8"></script>
</body>
</html>
