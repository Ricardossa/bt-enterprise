<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Administração do Sistema';
include __DIR__ . '/includes/header.php';
?>

<style>
    .config-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 25px; }
    .config-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 30px; }
    .config-section-title { font-size: 14px; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

    .module-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .module-card { background: var(--sidebar); border: 1px solid var(--border); border-radius: 15px; padding: 20px; transition: .2s; text-decoration: none; color: inherit; }
    .module-card:hover { border-color: var(--secondary); transform: translateY(-3px); background: rgba(29, 180, 255, 0.05); }
    .module-card i { font-size: 24px; color: var(--secondary); margin-bottom: 10px; }
    .module-card h4 { margin: 0; font-size: 16px; }
    .module-card p { font-size: 12px; color: var(--text2); margin-top: 5px; }

    .branding-box { text-align: center; padding: 30px; background: var(--sidebar); border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 20px; }
    .branding-logo-preview { max-height: 80px; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.5)); }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div>
            <h2><i class="fa-solid fa-gears"></i> Central de Controle</h2>
            <p style="color:var(--text2); font-size:14px;">Configurações vitais e gerenciamento de módulos Enterprise.</p>
        </div>
        <button id="btnSalvar" class="bt-button bt-success" style="padding: 12px 25px;">
            <i class="fa-regular fa-floppy-disk"></i> SALVAR TUDO
        </button>
    </div>

    <div class="config-grid">

        <!-- COLUNA 1: CONFIGURAÇÕES CORE -->
        <div style="display:flex; flex-direction:column; gap:25px;">

            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-building"></i> Identidade da Empresa</div>

                <div class="form-group">
                    <label>Nome Comercial</label>
                    <input type="text" id="empresa" class="form-control" placeholder="Ex: Farmácia Brandão">
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>Logotipo Oficial (PNG/JPG)</label>
                    <div class="branding-box">
                        <img id="empresaLogoPreview" src="assets/img/logo-placeholder.png" class="branding-logo-preview" onerror="this.src='uploads/logo.png'">
                        <input type="file" id="logo" class="form-control" accept=".png,.jpg,.jpeg">
                        <small style="color:var(--text2); display:block; mt:10px;">Recomendado: 500x500px fundo transparente.</small>
                    </div>
                </div>
            </section>

            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-network-wired"></i> Conectividade e Live</div>
                <p style="color:var(--text2); font-size:13px; margin-bottom:20px;">Configure como o sistema se comunica com os celulares e a Master.</p>

                <a href="conectividade.php" class="bt-button" style="width:100%; text-decoration:none; text-align:center; background:var(--sidebar); border:1px solid var(--border);">
                    <i class="fa-solid fa-globe"></i> Acessar Central de Conectividade
                </a>
            </section>

        </div>

        <!-- COLUNA 2: ATALHOS DE MÓDULOS -->
        <div>
            <section class="config-card">
                <div class="config-section-title"><i class="fa-solid fa-cubes"></i> Gestão de Módulos</div>

                <div class="module-grid">
                    <a href="servicos.php" class="module-card">
                        <i class="fa-solid fa-user-doctor"></i>
                        <h4>Serviços</h4>
                        <p>Filas e especialidades.</p>
                    </a>
                    <a href="guiches.php" class="module-card">
                        <i class="fa-solid fa-desktop"></i>
                        <h4>Guichês</h4>
                        <p>Mesas e locais físicos.</p>
                    </a>
                    <a href="promocoes.php" class="module-card">
                        <i class="fa-solid fa-tags"></i>
                        <h4>Promoções</h4>
                        <p>Ofertas no celular.</p>
                    </a>
                    <a href="operadores.php" class="module-card">
                        <i class="fa-solid fa-user-tie"></i>
                        <h4>Operadores</h4>
                        <p>Equipe e acessos.</p>
                    </a>
                </div>
            </section>

            <div class="bt-card" style="margin-top:25px; border-left: 4px solid var(--warning); background: rgba(255, 193, 7, 0.05);">
                <h4 style="color:var(--warning); margin-bottom:10px;"><i class="fa-solid fa-circle-info"></i> Atenção</h4>
                <p style="font-size:12px; color:var(--text2); line-height:1.4;">
                    Alterações na identidade da empresa podem levar alguns minutos para serem sincronizadas com a TV e os dispositivos móveis.
                </p>
            </div>
        </div>

    </div>

</main>

<script src="assets/js/api.js?v=4"></script>
<script src="assets/js/config.js?v=4"></script>
<script src="assets/js/configuracoes.js?v=4"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
