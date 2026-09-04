<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

$config = [];
$empresa = 'Brandão Tech';

try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    if (is_array($linhas)) {
        foreach ($linhas as $linha) {
            if (isset($linha['chave'], $linha['valor'])) {
                $config[$linha['chave']] = $linha['valor'];
            }
        }
    }
    // Garante que seja sempre uma string para evitar erro no htmlspecialchars
    $empresa = (string)($config['empresa'] ?? 'Brandão Tech');
} catch (Throwable $e) {
    $empresa = 'Brandão Tech';
}

// [v7.7.2] Lógica de Logo Dinâmica Port-Safe
$logoRelativa = $config['logo_url'] ?? $config['promo_logo'] ?? 'uploads/logo.png';
$logoPath = __DIR__ . '/../' . ltrim($logoRelativa, '/');

if (is_file($logoPath)) {
    $logoUrl = '../' . ltrim($logoRelativa, '/') . '?v=' . filemtime($logoPath);
} else {
    $logoUrl = '../assets/img/logo_brandao.png';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no,maximum-scale=1">
    <title>Check-in - <?= htmlspecialchars((string)$empresa) ?></title>
    <link rel="stylesheet" href="../live_premium/assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .form-profile { background: var(--card); border-radius: 25px; padding: 30px; border: 1px solid var(--border); }
        .form-profile label { display: block; font-size: 12px; color: var(--text3); margin-bottom: 8px; text-transform: uppercase; font-weight: bold; }
        .form-profile .form-control { background: var(--sidebar); border: 1px solid var(--border); color: #fff; padding: 15px; border-radius: 12px; width: 100%; margin-bottom: 20px; box-sizing: border-box; }
        .footer-signature { text-align: center; padding: 30px; opacity: 0.5; }
        .footer-signature img { height: 20px; }
    </style>
</head>
<body class="noc-theme">

<div id="loyalty-app" class="live-container">

    <header class="header-premium">
        <img src="<?= $logoUrl ?>" alt="Logo" style="max-height: 80px; max-width: 80%; object-fit: contain; margin-bottom: 15px;">
        <h1 id="welcome-title">Identificação</h1>
        <p id="welcome-subtitle">Acesso de Fornecedores e Parceiros</p>
    </header>

    <main id="main-content">
        <!-- LOADING -->
        <div id="loading-view" style="text-align:center; padding:100px 0;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:40px; color:var(--secondary);"></i>
            <p style="margin-top:20px; color:var(--text2);">Reconhecendo celular...</p>
        </div>

        <!-- VISÃO 1: CADASTRO -->
        <div id="register-view" class="hidden animate__animated animate__fadeInUp">
            <section class="form-profile">
                <h2 style="font-size:20px; margin-bottom:25px; text-align:center;">Crie seu Perfil</h2>

                <label>Seu Nome Completo *</label>
                <input type="text" id="reg-nome" class="form-control" placeholder="Quem está visitando?">

                <label>Sua Empresa / Distribuidora *</label>
                <input type="text" id="reg-empresa" class="form-control" placeholder="Nome da empresa">

                <label>WhatsApp *</label>
                <input type="tel" id="reg-whatsapp" class="form-control" placeholder="(00) 00000-0000">

                <button onclick="Loyalty.registrar()" id="btn-save" class="bt-button bt-primary" style="width: 100%; padding: 18px; font-weight: 900;">
                    FINALIZAR CADASTRO
                </button>

                <div style="text-align:center; margin-top:25px;">
                    <p style="font-size:12px; color:var(--text3);">Já possui cadastro mas trocou de celular?</p>
                    <button onclick="Loyalty.showRecovery()" style="background:transparent; border:none; color:var(--secondary); font-weight:bold; font-size:13px; text-decoration:underline; cursor:pointer;">
                        Recuperar meu acesso
                    </button>
                </div>
            </section>
        </div>

        <!-- VISÃO 2: DASHBOARD -->
        <div id="profile-view" class="hidden animate__animated animate__fadeIn">
            <section class="premium-card" style="text-align: center; padding: 40px 20px; margin-bottom: 30px;">
                <div style="font-size: 50px; margin-bottom: 15px;">🏢</div>
                <h2 style="color:#fff;" id="display-empresa">EMPRESA</h2>
                <p style="color:var(--text2);">Seu cadastro está ativo.</p>
            </section>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <button onclick="Loyalty.goService()" class="bt-button bt-primary" style="width:100%; padding:20px; font-weight:900; font-size:18px;">
                    <i class="fa-solid fa-ticket"></i> RETIRAR MINHA SENHA
                </button>

                <button onclick="Loyalty.logout()" class="bt-button" style="background:transparent; border:1px solid var(--border); font-size:11px; opacity:0.5;">
                    Não é você? Sair da conta
                </button>
            </div>
        </div>

        <!-- VISÃO 3: RECUPERAÇÃO -->
        <div id="recovery-view" class="hidden animate__animated animate__fadeIn">
             <section class="form-profile">
                <h2 style="font-size:20px; margin-bottom:15px; text-align:center;">Recuperar Acesso</h2>
                <p style="text-align:center; font-size:13px; color:var(--text2); margin-bottom:25px;">Informe o seu WhatsApp cadastrado.</p>

                <label>WhatsApp</label>
                <input type="tel" id="rec-whatsapp" class="form-control" placeholder="(00) 00000-0000">

                <button onclick="Loyalty.recover()" id="btn-recover" class="bt-button bt-secondary" style="width: 100%; padding: 18px; font-weight: 900;">
                    BUSCAR MEU PERFIL
                </button>

                <button onclick="Loyalty.showRegister()" style="width:100%; margin-top:15px; background:transparent; border:none; color:var(--text3); font-size:12px;">
                    ← Voltar para cadastro
                </button>
            </section>
        </div>
    </main>

    <footer class="footer-signature">
        <p>Identidade Protegida por</p>
        <img src="http://api.brandaotech.com.br/uploads/logo/logo.png" alt="BT">
    </footer>
</div>

<script src="assets/js/loyalty.js?v=7.7.7"></script>
</body>
</html>
