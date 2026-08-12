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
    currentToken: '',

    init() {
        this.carregarServicos();
        this.resetIdleTimer();
        this.iniciarCicloToken();

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
                    <button class="totem-btn" style="border-color: ${s.cor || 'var(--border)'}; width:100%;" onclick="BT.totem.selectService(${s.id})">
                        <span>${s.icone || '📋'}</span>
                        <label style="cursor:pointer;">${s.nome}</label>
                    </button>
                `).join('');
            }
        } catch (e) { container.innerHTML = 'Erro ao carregar serviços.'; }
    },

    // --- MÓDULO DE PRIORIDADE (v7.0.4 Diamond) ---
    selectedServiceId: null,

    selectService(id) {
        console.log("Serviço selecionado para prioridade:", id);
        this.selectedServiceId = id;

        const stepServices = document.getElementById('step-services');
        const stepPriority = document.getElementById('step-priority');

        if (stepServices) stepServices.classList.add('hidden');
        if (stepPriority) stepPriority.classList.remove('hidden');

        this.resetIdleTimer();
    },

    backToServices() {
        this.selectedServiceId = null;
        const stepServices = document.getElementById('step-services');
        const stepPriority = document.getElementById('step-priority');

        if (stepPriority) stepPriority.classList.add('hidden');
        if (stepServices) stepServices.classList.remove('hidden');

        this.resetIdleTimer();
    },

    async emitirSenha(tipo = 'NORMAL') {
        const id = this.selectedServiceId;
        console.log("Tentando emitir senha tipo:", tipo, "para serviço:", id);

        if (!id) {
            alert("Erro: Serviço não identificado. Por favor, volte e tente novamente.");
            this.backToServices();
            return;
        }

        try {
            const res = await fetch(this.apiSenhas, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ servico_id: parseInt(id), tipo: tipo })
            });
            const json = await res.json();
            if (json.success) {
                this.backToServices();
                this.mostrarSenha(json.data);
                this.dispararImpressao(json.data);
            } else {
                alert(json.message);
            }
        } catch (e) { alert('Falha ao emitir senha.'); }
    },

    configurarModoHibrido() {
        // Obsoleto: Substituído pela injeção via HTML no init()
    },

    async iniciarCicloToken() {
        const fetchToken = async () => {
            try {
                const res = await fetch('api/totem_token.php');
                const json = await res.json();
                if (json.success) {
                    this.currentToken = json.token;
                    if (window.BT_TOTEM_CONFIG) {
                        this.gerarQRDigital(window.BT_TOTEM_CONFIG);
                    }
                }
            } catch (e) { console.error("Erro ao renovar token do Totem."); }
        };

        fetchToken();
        setInterval(fetchToken, 30000); // Renova a cada 30 segundos
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
        let pathMobile = "live_premium/index.php"; // Removido o ?new=1 para preservar múltiplas senhas
        if (this.currentToken) {
            pathMobile += "?t=" + this.currentToken;
        }

        // --- SMART PATH DETECTION (Fix para Cliente vs Nuvem) ---
        let finalPath = "/";

        // Se a URL contém /painel_v4/, estamos em ambiente de Desenvolvimento ou Nuvem
        if (window.location.pathname.includes('/painel_v4/')) {
            finalPath = "/painel_v4/public/";
        }

        const destino = base.replace(/\/$/, "") + finalPath + pathMobile;

        console.log("Gerando QR Dinâmico:", destino);

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
        // Obsoleto: Substituído por selectService e emitirSenha(tipo)
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

        // --- LÓGICA DE PONTE INTELIGENTE (V5.3) ---
        let printEndpoint = 'api/imprimir.php';
        let localIp = window.BT_TOTEM_CONFIG?.print_ip;

        // Fallback Automático: Se não houver IP configurado, assume que a impressora está no servidor
        if (!localIp || localIp === '127.0.0.1' || localIp === 'localhost') {
            printEndpoint = `http://${window.location.hostname}:8001/`;
        } else {
            printEndpoint = `http://${localIp}:8001/`;
        }

        console.log("🖨️ Acionando Motor de Impressão:", printEndpoint);

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
    },

    // --- MÓDULO DE CHECK-IN NATIVO (v6.2 Diamond) ---
    showCheckin() {
        document.getElementById('inputCheckin').value = '';
        document.getElementById('modalCheckin').style.display = 'flex';
    },

    hideCheckin() {
        document.getElementById('modalCheckin').style.display = 'none';
    },

    key(k) {
        const input = document.getElementById('inputCheckin');
        if (k === 'BACK') input.value = input.value.slice(0, -1);
        else if (k === 'CLEAR') input.value = '';
        else if (k === 'SPACE') input.value += ' ';
        else if (input.value.length < 20) input.value += k;
    },

    async doCheckin() {
        const query = document.getElementById('inputCheckin').value.trim();
        if (!query) return alert("Digite seu nome ou token.");

        const btn = document.querySelector('#modalCheckin .bt-primary');
        btn.disabled = true; btn.innerText = "VERIFICANDO...";

        try {
            const res = await fetch('api/v1/agenda.php?action=checkin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });
            const json = await res.json();

            if (json.success) {
                this.hideCheckin();
                // Mostra a senha agendada no display de sucesso
                this.mostrarSenha({
                    senha: json.data.codigo,
                    servico_nome: "CHECK-IN: " + json.data.nome_cliente
                });
                // Tenta imprimir o comprovante de chegada
                this.dispararImpressao({
                    senha: json.data.codigo,
                    servico_nome: "AGENDADO: " + json.data.hora
                });
            } else {
                alert(json.message || "Agendamento não encontrado para hoje.");
            }
        } catch (e) { alert("Erro ao realizar check-in."); }

        btn.disabled = false; btn.innerText = "CONFIRMAR CHEGADA";
    }
};

document.getElementById('btnFecharModal').onclick = () => BT.totem.fecharModal();

document.addEventListener('DOMContentLoaded', () => BT.totem.init());
