/**
 * BT QUEUE LIVE PREMIUM - MULTI-TRACKER ENGINE v5.1
 * Gerencia o acompanhamento de múltiplas senhas simultâneas.
 */

window.BT = window.BT || {};

BT.tracker = {
    uuids: [],
    promos: [],
    promoIdx: 0,
    polling: null,
    isCalling: false,

    async init() {
        this.updateTicketList();
        this.startPolling();
        try { await this.loadPromos(); } catch(e) {}
    },

    updateTicketList() {
        const saved = localStorage.getItem('bt_premium_tickets');
        const tickets = saved ? JSON.parse(saved) : [];

        // Se não houver senhas, volta para a tela inicial (Totem)
        if (tickets.length === 0) {
            window.location.href = 'index.php';
            return;
        }

        this.uuids = tickets.map(t => t.cliente_uuid);
    },

    startPolling() {
        this.sync();
        this.polling = setInterval(() => this.sync(), 3000);
    },

    async sync() {
        try {
            const res = await fetch('../api/v1/multi_check.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ uuids: this.uuids })
            });
            const json = await res.json();

            if (json.success) {
                this.renderTickets(json.data);
                this.checkCalls(json.data);

                // --- LÓGICA DE LIMPEZA E SAÍDA (v5.3) ---
                const activeTickets = json.data.filter(t => t.status !== 'FINALIZADA');

                if (activeTickets.length === 0 && json.data.length > 0) {
                    // Se todas as senhas foram finalizadas, espera 5 segundos para o cliente ver o "Obrigado" e encerra
                    if (!this.exitTimer) {
                        this.exitTimer = setTimeout(() => this.finishSession(), 6000);
                    }
                } else {
                    // Atualiza a lista de UUIDs apenas com os ativos para o próximo ciclo
                    this.uuids = activeTickets.map(t => t.uuid);
                    // Atualiza o localStorage para refletir apenas o que ainda não terminou
                    localStorage.setItem('bt_premium_tickets', JSON.stringify(activeTickets));
                }
            }
        } catch (e) { console.error("Multi-Sync Error", e); }
    },

    renderTickets(data) {
        const container = document.getElementById('tickets-container');
        if (!container) return;

        container.innerHTML = data.map(t => {
            const isFrozen = (t.status === 'CONGELADA');
            const isCalling = (t.status === 'CHAMANDO');
            const isFinished = (t.status === 'FINALIZADA');

            let statusLabel = 'Em Espera';
            let dotColor = 'var(--secondary)';
            let msg = t.mensagem;

            if (isFrozen) {
                statusLabel = 'Pausada ❄️';
                dotColor = '#1DB4FF';
            } else if (isCalling) {
                statusLabel = 'SUA VEZ! 🔔';
                dotColor = 'var(--success)';
            } else if (isFinished) {
                statusLabel = 'Concluído ✅';
                dotColor = '#94a3b8';
            }

            return `
                <section class="premium-card ${isFrozen ? 'frozen-mode' : ''} ${isCalling ? 'calling-mode' : ''}">
                    <div class="ticket-header-row">
                        <span class="serv-info">${t.icone} ${t.servico}</span>
                        <div class="badge-status">
                            <div class="dot-status" style="background:${dotColor}"></div>
                            <span>${statusLabel}</span>
                        </div>
                    </div>
                    <div class="ticket-white-box">
                        <div class="ticket-number">${t.senha}</div>
                        <p class="ticket-msg">${msg}</p>

                        <div class="grid-stats">
                            <div class="stat-item"><label>Fila</label><b>${t.posicao}</b></div>
                            <div class="stat-item"><label>Espera</label><b>${t.tempo_estimado} min</b></div>
                            <div class="stat-item"><label>Local</label><b style="color:var(--success)">${t.guiche}</b></div>
                        </div>
                    </div>
                </section>
            `;
        }).join('');
    },

    checkCalls(data) {
        const activeCall = data.find(t => t.status === 'CHAMANDO');
        const overlay = document.getElementById('call-alert');

        if (activeCall) {
            if (!this.isCalling) {
                this.playAlert();
                this.isCalling = true;
            }
            overlay.style.display = 'flex';
            document.getElementById('alert-guiche').textContent = 'LOCAL: ' + activeCall.guiche;
            document.getElementById('alert-servico').textContent = activeCall.servico;
        } else {
            this.isCalling = false;
            overlay.style.display = 'none';
        }
    },

    playAlert() {
        try {
            const audio = new Audio('assets/audio/ding.mp3');
            audio.play();
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
        } catch(e) {}
    },

    finishSession() {
        clearInterval(this.polling);
        localStorage.removeItem('bt_premium_tickets');
        window.location.href = 'fim.php';
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
                    img = img.replace('uploads/promocoes/', '').replace('../', '');
                    elImg.src = '../uploads/promocoes/' + img;
                } else { elImg.src = img; }
                elImg.style.display = 'block';
            }
            this.promoIdx = (this.promoIdx + 1) % this.promos.length;
        } catch(e) {}
    }
};

document.addEventListener('DOMContentLoaded', () => BT.tracker.init());
