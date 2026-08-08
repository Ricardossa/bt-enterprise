<?php
declare(strict_types=1);

require_once __DIR__ . '/components/brand.php';
use BTQueue\Core\Auth;

$pageTitle = $pageTitle ?? $empresa;
$usuarioLogado = Auth::operador();
$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>

<?php if (!isset($noSidebar) || !$noSidebar): ?>
<div class="sidebar">
    <div class="logo">
        <?php if ($logoExiste): ?>
            <img id="empresaLogo" src="<?= $logoUrl ?>" alt="<?= htmlspecialchars($empresa) ?>" style="max-width:180px; max-height:80px; object-fit:contain;">
        <?php else: ?>
            <div class="logo-icon">BT</div>
        <?php endif; ?>
        <div class="logo-text">
            <h2 id="empresaNome"><?= htmlspecialchars($empresa) ?></h2>
            <span>Enterprise NOC</span>
        </div>
    </div>

    <nav>
        <!-- ACESSO COMUM: Ambos vÃªm o guichÃª e TV -->
        <a href="operador.php">
            <i class="fa-solid fa-headset"></i> Atendimento
        </a>

        <a href="tv_v2.php" target="_blank">
            <i class="fa-solid fa-tv"></i> Painel TV
        </a>

        <a href="live_premium/" target="_blank">
            <i class="fa-solid fa-mobile-screen-button"></i> Painel Mobile
        </a>

        <a href="totem.php" target="_blank">
            <i class="fa-solid fa-tablet-screen-button"></i> Totem de Impressão
        </a>

        <?php if ($isAdmin): ?>
            <hr style="margin:15px 0; opacity:.1; border-color: #fff;">
            <label style="padding: 0 25px; font-size: 10px; color: var(--secondary); font-weight: bold; text-transform: uppercase;">Administração</label>

            <a href="dashboard.php">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>

            <a href="relatorios.php">
                <i class="fa-solid fa-chart-pie"></i> Relatórios Master
            </a>

            <a href="operadores.php">
                <i class="fa-solid fa-user-tie"></i> Operadores
            </a>

            <a href="servicos.php">
                <i class="fa-solid fa-briefcase"></i> Serviços
            </a>

            <a href="guiches.php">
                <i class="fa-solid fa-desktop"></i> Guichês
            </a>

            <a href="promocoes.php">
                <i class="fa-solid fa-tags"></i> Promoções
            </a>

            <a href="conectividade.php">
                <i class="fa-solid fa-globe"></i> Conectividade
            </a>

            <a href="configuracoes.php">
                <i class="fa-solid fa-gears"></i> Sistema
            </a>
        <?php endif; ?>

        <hr style="margin:15px 0; opacity:.1; border-color: #fff;">

        <a href="logout.php" style="color:var(--danger);">
            <i class="fa-solid fa-right-from-bracket"></i> Sair
        </a>
        <div style="margin-top:auto; padding: 20px 25px; font-size: 9px; color: rgba(255,255,255,0.2); text-align: center;">
            <i class="fa-solid fa-microchip"></i> Build v<?= defined('BT_VERSION') ? BT_VERSION : '4.0.0' ?>
        </div>
    </nav>
</div>
<?php endif; ?>

<div class="content" <?= (isset($noSidebar) && $noSidebar) ? 'style="margin-left:0; width:100%;"' : '' ?>>
    <header class="topbar">
        <div style="display:flex; align-items:center; gap:15px;">
             <h1 style="font-size:18px;"><?= htmlspecialchars($pageTitle) ?></h1>
             <?php if($usuarioLogado): ?>
                <span style="font-size:11px; background:var(--sidebar); padding:4px 10px; border-radius:4px; border:1px solid var(--border);">
                    <i class="fa-solid fa-user"></i> <?= $usuarioLogado['nome'] ?> (<?= $usuarioLogado['nivel'] ?>)
                </span>
             <?php endif; ?>
        </div>
        <div class="status">
            <span class="online"></span> Online
        </div>
    </header>
