/**
 * BT QUEUE LIVE PREMIUM - EMITTER ENGINE (V4.2 - UTF8 FIX)
 * Lógica para escolha de serviço e redirecionamento.
 */

window.BT = window.BT || {};

BT.emitter = {
    async init() {
        // --- MASTER RESET: Limpa tudo se houver reset=1 ou new=1 na URL ---
        if (window.location.search.includes('reset=1') || window.location.search.includes('new=1')) {
            localStorage.removeItem('bt_premium_tickets');
            // Remove o parâmetro da URL para não ficar limpando sempre
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }

        const saved = localStorage.getItem('bt_premium_tickets');
        const tickets = saved ? JSON.parse(saved) : [];

        // Regra de 1 Senha Ativa
        if (tickets.length >= 1) {
            window.location.href = 'acompanhar.php?uuid=' + tickets[0].cliente_uuid;
            return;
        }

        await this.loadServices();
    },

    async loadServices() {
        const list = document.getElementById('lista-servicos');
        try {
            const res = await fetch('../api/servicos.php');
            const json = await res.json();
            if (json.success) {
                list.innerHTML = '';
                json.data.forEach(s => {
                    const btn = document.createElement('button');
                    btn.className = 'btn-premium-service';
                    btn.style.borderColor = s.cor || 'var(--primary)';
                    // Usando innerText para evitar problemas de encoding HTML
                    btn.innerHTML = `<span>${s.icone}</span> <div>${s.nome}</div>`;
                    btn.onclick = () => this.emitir(s.id);
                    list.appendChild(btn);
                });
            }
        } catch (e) {
            console.error("Erro API:", e);
            list.innerHTML = '<p style="color:var(--text2);">Falha ao carregar os serviços de atendimento.</p>';
        }
    },

    async emitir(id) {
        try {
            const res = await fetch('../api/senhas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ servico_id: id })
            });
            const json = await res.json();
            if (json.success) {
                localStorage.setItem('bt_premium_tickets', JSON.stringify([json.data]));
                window.location.href = 'acompanhar.php?uuid=' + json.data.cliente_uuid;
            } else {
                alert("Erro: " + json.message);
            }
        } catch (e) { alert("Falha de conexão ao emitir senha."); }
    }
};

document.addEventListener('DOMContentLoaded', () => BT.emitter.init());
