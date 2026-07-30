/**
 * BT QUEUE LIVE PREMIUM - TRACKER ENGINE v4.7.4
 */

window.BT = window.BT || {};

BT.tracker = {
    uuid: "",
    status: "",
    promos: [],
    promoIdx: 0,
    polling: null,

    async init() {
        this.uuid = new URLSearchParams(window.location.search).get("uuid");
        if (!this.uuid) { window.location.href = 'index.php'; return; }

        this.startPolling();
        try { await this.loadPromos(); } catch(e) {}
    },

    startPolling() {
        this.update();
        this.polling = setInterval(() => this.update(), 3000);
    },

    async update() {
        try {
            const res = await fetch('../api/acompanhar.php?uuid=' + this.uuid);
            const json = await res.json();

            if (json.success) {
                const d = json.data;
                const elNum = document.getElementById('ticket-number');
                const elMsg = document.getElementById('status-label');
                const elPos = document.getElementById('stat-posicao');
                const elTim = document.getElementById('stat-tempo');
                const elGui = document.getElementById('stat-guiche');

                if(elNum) elNum.textContent = d.senha || "---";
                if(elMsg) elMsg.textContent = d.mensagem || "Acompanhando...";
                if(elPos) elPos.textContent = d.posicao ?? "0";
                if(elTim) elTim.textContent = (d.tempo_estimado ?? 0) + " min";
                if(elGui) elGui.textContent = d.guiche || "--";

                if (d.status === 'CHAMANDO') {
                    const overlay = document.getElementById('call-alert');
                    if(overlay) {
                        overlay.style.display = 'flex';
                        document.getElementById('alert-guiche').textContent = 'GUICHÊ ' + d.guiche;
                    }
                } else {
                    const overlay = document.getElementById('call-alert');
                    if(overlay) overlay.style.display = 'none';
                }

                if (d.status === 'FINALIZADA') {
                    clearInterval(this.polling);
                    localStorage.removeItem('bt_premium_tickets');
                    setTimeout(() => { window.location.href = 'fim.php'; }, 4000);
                }
                this.status = d.status;
            } else {
                // --- AUTO-HEALING: Se a senha não existe no servidor, limpa o celular ---
                console.warn("Senha inválida ou expirada. Resetando sessão.");
                localStorage.removeItem('bt_premium_tickets');
                window.location.href = 'index.php';
            }
        } catch (e) {
            console.error("Sync Error", e);
        }
    },

    async loadPromos() {
        const res = await fetch('../api/promocoes.php');
        const json = await res.json();
        if (json.success && json.data.length > 0) {
            this.promos = json.data;
            this.renderPromo();
            setInterval(() => this.renderPromo(), 7000);
        }
    },

    renderPromo() {
        try {
            if (this.promos.length === 0) return;
            const p = this.promos[this.promoIdx];

            const elTitle = document.getElementById('promo-title');
            const elImg = document.getElementById('promo-img');
            const elPrice = document.getElementById('promo-price');
            const elDesc = document.getElementById('promo-desc');

            if(elTitle) elTitle.textContent = p.titulo;
            if(elPrice) elPrice.textContent = p.preco || '';
            if(elDesc) elDesc.textContent = p.descricao || '';

            if(elImg) {
                let img = p.imagem || '';
                if (img && !img.startsWith('http')) {
                    // Limpa prefixos duplicados se existirem no banco
                    img = img.replace('uploads/promocoes/', '').replace('../', '');
                    elImg.src = BT.api.url('uploads/promocoes/' + img);
                } else {
                    elImg.src = img;
                }
                elImg.style.display = 'block';
            }

            this.promoIdx = (this.promoIdx + 1) % this.promos.length;
        } catch(e) {}
    }
};

document.addEventListener('DOMContentLoaded', () => BT.tracker.init());
