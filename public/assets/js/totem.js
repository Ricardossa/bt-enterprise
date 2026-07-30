/*
==========================================================
BT Queue Enterprise - Totem Kiosk Mode (Hybrid Edition)
==========================================================
*/

window.BT = window.BT || {};

BT.totem = {
    apiServicos: 'api/servicos.php',
    apiSenhas: 'api/senhas.php',
    apiConfig: 'api/configuracoes.php',

    idleTimeout: 60000, // 60 segundos de inatividade
    idleTimer: null,

    init() {
        this.carregarServicos();
        this.resetIdleTimer();

        // Carrega o Modo Híbrido e QR Code usando as configurações injetadas no HTML
        if (window.BT_TOTEM_CONFIG) {
            this.gerarQRDigital(window.BT_TOTEM_CONFIG);
        }

        // Listeners globais para resetar o timer em qualquer toque (quando acordado)
        document.addEventListener('click', () => this.resetIdleTimer());
        document.addEventListener('touchstart', () => this.resetIdleTimer());
    },

    async carregarServicos() {
        const container = document.getElementById('containerServicos');
        try {
            const res = await fetch(this.apiServicos);
            const json = await res.json();
            if (json.success) {
                container.innerHTML = json.data.map(s => `
                    <div class="totem-btn" style="border-color: ${s.cor || 'var(--border)'};" onclick="BT.totem.emitirSenha(${s.id})">
                        <span>${s.icone || '📋'}</span>
                        <label>${s.nome}</label>
                    </div>
                `).join('');
            }
        } catch (e) { container.innerHTML = 'Erro ao carregar serviços.'; }
    },

    configurarModoHibrido() {
        // Obsoleto: Substituído pela injeção via HTML no init()
    },

    gerarQRDigital(cfg) {
        // --- LÓGICA DE URL OFICIAL (Configurada na aba Conectividade) ---
        let base = (cfg.modo === "cloud" ? cfg.url_publica : cfg.url_local);

        // Fallback caso não tenha nada configurado: usa a URL atual
        if (!base) {
            const pathAtual = window.location.pathname;
            const pastaBase = pathAtual.substring(0, pathAtual.lastIndexOf('/') + 1);
            base = window.location.origin + pastaBase;
        }

        // Garante que a barra final esteja correta e aponta para o mobile
        let pathMobile = "live_premium/index.php?new=1";

        // --- SMART PATH DETECTION (Fix para Cliente vs Nuvem) ---
        let finalPath = "/";

        // Se a URL contém /painel_v4/, estamos em ambiente de Desenvolvimento ou Nuvem
        if (window.location.pathname.includes('/painel_v4/')) {
            finalPath = "/painel_v4/public/";
        }

        const destino = base.replace(/\/$/, "") + finalPath + pathMobile;

        console.log("Gerando QR Oficial para:", destino);

        const qrContainer = document.getElementById('qrGiant');
        if (qrContainer) {
            const qr = qrcode(0, 'H');
            qr.addData(destino);
            qr.make();
            qrContainer.innerHTML = qr.createImgTag(12, 0);
        }
    },

    resetIdleTimer() {
        if (this.idleTimer) clearTimeout(this.idleTimer);
        this.idleTimer = setTimeout(() => this.showIdle(), this.idleTimeout);
    },

    wakeUp() {
        const overlay = document.getElementById('idleOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
        this.resetIdleTimer();
    },

    showIdle() {
        const overlay = document.getElementById('idleOverlay');
        const modal = document.getElementById('modalSenhaTotem');

        // Se houver um modal de senha aberto, não volta para o idle ainda
        if (modal && modal.style.display === 'flex') return;

        if (overlay) {
            overlay.classList.remove('hidden');
        }
    },

    async emitirSenha(servicoId) {
        try {
            const res = await fetch(this.apiSenhas, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ servico_id: parseInt(servicoId) })
            });
            const json = await res.json();
            if (json.success) {
                this.mostrarSenha(json.data);
                this.dispararImpressao(json.data);
            } else {
                alert(json.message);
            }
        } catch (e) { alert('Falha ao emitir senha.'); }
    },

    mostrarSenha(dados) {
        document.getElementById('displaySenha').innerText = dados.senha;
        document.getElementById('displayServico').innerText = dados.servico_nome || 'Atendimento';

        const modal = document.getElementById('modalSenhaTotem');
        modal.style.display = 'flex';

        // Auto-fechar em 8 segundos e voltar para o Protetor de Tela mais rápido
        if (this.timer) clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.fecharModal();
            this.showIdle(); // Volta para o QR Code gigante após imprimir
        }, 8000);
    },

    async dispararImpressao(dados) {
        const empresa = window.BT_TOTEM_CONFIG?.empresa || 'BT Queue';

        // --- LÓGICA DE PONTE LOCAL (Nuvem ↔ Local) ---
        let printEndpoint = 'api/imprimir.php';
        const localIp = window.BT_TOTEM_CONFIG?.print_ip;

        if (localIp) {
            printEndpoint = `http://${localIp}:8001/`;
            console.log("📡 Usando Ponte de Impressão Local Injetada:", printEndpoint);
        }

        console.log("🖨️ Solicitando impressão para:", dados.senha);

        try {
            const response = await fetch(printEndpoint, {
                method: 'POST',
                mode: 'cors', // Ativa CORS para chamadas entre Nuvem e Local
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    senha: dados.senha,
                    servico: dados.servico_nome,
                    empresa: empresa,
                    printer: 'BT_TICKET'
                })
            });

            const result = await response.json();
            if (!result.success) console.error("Falha na impressão:", result.message);
        } catch (e) {
            console.error("Erro ao conectar com a impressora:", e);
        }
    },

    fecharModal() {
        document.getElementById('modalSenhaTotem').style.display = 'none';
        this.resetIdleTimer();
    }
};

document.getElementById('btnFecharModal').onclick = () => BT.totem.fecharModal();

document.addEventListener('DOMContentLoaded', () => BT.totem.init());
