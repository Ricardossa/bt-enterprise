<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

// Carrega Identidade
$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'BT Queue Enterprise';
$logoUrl = '/painel_v4/public/uploads/logo.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>TV Cinema - <?= htmlspecialchars($empresa) ?></title>

    <link rel="stylesheet" href="assets/css/tv_premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

    <!-- TOPBAR -->
    <header class="tv-topbar">
        <img src="<?= $logoUrl ?>" class="tv-logo" alt="Logo" onerror="this.src='http://api.brandaotech.com.br:8080/uploads/logo/logo.png'">
        <div id="tv-clock" class="tv-clock">00:00:00</div>
    </header>

    <div class="tv-layout">

        <!-- ÁREA PRINCIPAL (CHAMADA ATUAL) -->
        <main class="tv-main">
            <div class="label-chamada">Senha em Atendimento</div>
            <div id="main-ticket" class="ticket-giant pulse-ticket">---</div>
            <div id="main-guiche" class="guiche-label">---</div>
        </main>

        <!-- ÁREA LATERAL (HISTÓRICO) -->
        <aside class="tv-sidebar">
            <h2 class="sidebar-title">Últimas Chamadas</h2>
            <ul id="history-list" class="history-list">
                <!-- Injetado via JS -->
            </ul>
        </aside>

    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/tv_premium.js"></script>

</body>
</html>
