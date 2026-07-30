<?php
declare(strict_types=1);

$noSidebar = true; // Ativa modo Kiosk (Oculta menu lateral)
$pageTitle = 'Totem de Autoatendimento';

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

// --- CARGA DE CONFIGURAÇÕES PARA O TOTEM (OFFLINE-SAFE) ---
$configs = [];
try {
    $rows = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($rows as $r) { $configs[$r['chave']] = $r['valor']; }
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<script>
    // Injeta as configurações diretamente para o JS do Totem
    window.BT_TOTEM_CONFIG = {
        empresa: "<?= addslashes($configs['empresa'] ?? 'BT Queue') ?>",
        url_local: "<?= addslashes($configs['url_local'] ?? '') ?>",
        url_publica: "<?= addslashes($configs['url_publica'] ?? '') ?>",
        modo: "<?= addslashes($configs['modo'] ?? 'lan') ?>",
        print_ip: "<?= addslashes($configs['local_print_ip'] ?? '') ?>"
    };
</script>

<style>
    body {
        overflow: hidden;
        background-color: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }

    .totem-container {
        width: 100%;
        max-width: 1200px;
        padding: 40px;
        text-align: center;
    }

    .totem-header { margin-bottom: 60px; }
    .totem-header img { max-height: 120px; margin-bottom: 25px; filter: drop-shadow(0 0 15px rgba(29, 180, 255, 0.4)); }
    .totem-header h1 { font-size: 56px; font-weight: 900; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 2px; }
    .totem-header p { font-size: 24px; color: var(--text2); margin-top: 15px; opacity: 0.8; }

    .totem-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .totem-btn {
        height: 220px;
        border-radius: 40px;
        border: 3px solid var(--border);
        background: var(--card);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .totem-btn:active { transform: scale(0.92); background: var(--sidebar); border-color: var(--secondary); }
    .totem-btn span { font-size: 64px; }
    .totem-btn label { font-size: 32px; font-weight: 900; cursor: pointer; text-transform: uppercase; }

    /* MODAL OVERLAY (FOCO NO PAPEL) */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: rgba(8, 20, 33, 0.99);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal-content-premium {
        background: var(--card);
        border: 3px solid var(--border);
        border-radius: 50px;
        padding: 60px;
        text-align: center;
        width: 95%;
        max-width: 600px;
        box-shadow: 0 0 120px rgba(0,0,0,0.9);
    }

    .modal-ticket-number {
        font-size: 160px;
        font-weight: 900;
        color: var(--secondary);
        line-height: 1;
        margin: 30px 0;
        text-shadow: 0 0 50px rgba(29, 180, 255, 0.6);
        letter-spacing: -5px;
    }

    /* ESTILOS DO PROTETOR DE TELA (IDLE MODE) */
    #idleOverlay {
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: radial-gradient(circle, var(--bg) 0%, #000 100%);
        z-index: 10000;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        transition: opacity 0.8s ease, visibility 0.8s;
    }

    #idleOverlay.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .qr-giant-box {
        background: #fff;
        padding: 30px;
        border-radius: 40px;
        box-shadow: 0 0 80px rgba(29, 180, 255, 0.5);
        margin-bottom: 40px;
        transition: transform 0.5s ease;
    }

    .qr-giant-box img {
        width: 400px;
        height: 400px;
    }

    .idle-text { color: #fff; max-width: 800px; }
    .idle-text h2 { font-size: 42px; font-weight: 900; margin-bottom: 20px; text-transform: uppercase; }
    .idle-text p { font-size: 24px; color: var(--text2); line-height: 1.4; }
    .touch-prompt {
        margin-top: 50px;
        font-size: 18px;
        color: var(--secondary);
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 0.4; }
        50% { opacity: 1; }
        100% { opacity: 0.4; }
    }
</style>

<!-- PROTETOR DE TELA (ATIVADO POR INATIVIDADE) -->
<div id="idleOverlay" onclick="BT.totem.wakeUp()">
    <div class="qr-giant-box animate__animated animate__fadeInDown">
        <div id="qrGiant"></div>
    </div>
    <div class="idle-text animate__animated animate__fadeInUp">
        <h2>Atendimento Digital</h2>
        <p>Escaneie o código acima e retire sua senha pelo celular para acompanhar sua posição na fila de onde estiver!</p>
        <div class="touch-prompt">Toque na tela para senha impressa</div>
    </div>
</div>

<div class="totem-container animate__animated animate__zoomIn">
    
    <header class="totem-header">
        <img src="uploads/logo.png" onerror="this.src='http://api.brandaotech.com.br:8080/uploads/logo/logo.png'">
        <h1>SISTEMA DE SENHAS</h1>
        <p>Toque no serviço desejado para retirar sua senha</p>
    </header>

    <div id="containerServicos" class="totem-grid">
        <p style="color: var(--text2); font-size: 20px;">Sincronizando serviços...</p>
    </div>

</div>

<!-- MODAL DE SENHA EMITIDA (LIMPO E RÁPIDO) -->
<div id="modalSenhaTotem" class="modal-overlay">
    <div class="modal-content-premium animate__animated animate__bounceIn">
        <i class="fa-solid fa-print" style="font-size: 80px; color: var(--success); margin-bottom: 25px;"></i>
        <h2 style="color:#fff; font-weight:900; font-size: 32px; margin:0;">POR FAVOR</h2>
        <h1 style="color:var(--success); font-weight:900; font-size: 40px; margin:5px 0;">RETIRE SEU COMPROVANTE</h1>

        <div id="displaySenha" class="modal-ticket-number">---</div>

        <h3 id="displayServico" style="color:var(--text2); text-transform:uppercase; letter-spacing:4px; font-size: 24px; margin-bottom:50px;">---</h3>

        <button class="bt-button bt-primary" style="width:100%; padding:25px; font-size:28px; border-radius:25px; font-weight: 900;" id="btnFecharModal">CONCLUÍDO</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script src="assets/js/api.js?v=8"></script>
<script src="assets/js/totem.js?v=8"></script>

<!-- IFRAME DE IMPRESSÃO OCULTO -->
<iframe id="iframeImpressao" style="display:none;"></iframe>

<?php include __DIR__ . '/includes/footer.php'; ?>
