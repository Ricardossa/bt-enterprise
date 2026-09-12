/**
 * BT QUEUE LOYALTY (Supplier Edition) v7.6.0
 */

window.Loyalty = {
    api: '../api/v1/cliente.php',
    client: null,

    init() {
        const uuid = localStorage.getItem('bt_loyalty_uuid');
        if (uuid) {
            this.fetchProfile(uuid);
        } else {
            this.showView('register');
        }
    },

    async fetchProfile(uuid) {
        try {
            const res = await fetch(`${this.api}?uuid=${uuid}`);
            const json = await res.json();

            if (json.success) {
                this.client = json.cliente;
                this.renderProfile(json);
                this.showView('profile');
            } else {
                localStorage.removeItem('bt_loyalty_uuid');
                this.showView('register');
            }
        } catch (e) {
            this.showView('register');
        }
    },

    renderProfile(data) {
        const c = data.cliente;
        document.getElementById('welcome-title').innerText = "Olá, " + c.nome.split(' ')[0] + "!";
        document.getElementById('welcome-subtitle').innerText = "Sua empresa: " + (c.empresa || 'Identificada');
        document.getElementById('display-empresa').innerText = c.empresa || 'Sua Empresa';
    },

    async registrar() {
        const nome = document.getElementById('reg-nome').value.trim();
        const empresa = document.getElementById('reg-empresa').value.trim();
        const whatsapp = document.getElementById('reg-whatsapp').value.trim();

        if (!nome || !empresa || !whatsapp) return alert("Por favor, preencha todos os campos obrigatórios.");

        const btn = document.getElementById('btn-save');
        btn.disabled = true; btn.innerText = "CRIANDO PERFIL...";

        try {
            const res = await fetch(this.api, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nome, empresa, whatsapp
                })
            });
            const json = await res.json();

            if (json.success) {
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message);
                btn.disabled = false; btn.innerText = "FINALIZAR CADASTRO";
            }
        } catch (e) {
            alert("Falha ao conectar com o servidor.");
            btn.disabled = false;
        }
    },

    async recover() {
        const whatsapp = document.getElementById('rec-whatsapp').value.trim();
        if (!whatsapp) return alert("Informe seu WhatsApp.");

        const btn = document.getElementById('btn-recover');
        btn.disabled = true; btn.innerText = "BUSCANDO...";

        try {
            const res = await fetch(`${this.api}?action=buscar_whatsapp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ whatsapp })
            });
            const json = await res.json();

            if (json.success) {
                localStorage.setItem('bt_loyalty_uuid', json.cliente.uuid);
                localStorage.setItem('bt_cliente_uuid', json.cliente.uuid);
                this.fetchProfile(json.cliente.uuid);
            } else {
                alert(json.message || "Cadastro não encontrado.");
                btn.disabled = false; btn.innerText = "BUSCAR MEU PERFIL";
            }
        } catch (e) {
            alert("Erro de conexão.");
            btn.disabled = false;
        }
    },

    goService() {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('t');

        // Redireciona para o Totem Mobile, preservando o token de segurança
        let url = '../live_premium/index.php';
        if (token) url += '?t=' + token;

        window.location.href = url;
    },

    logout() {
        if (confirm("Deseja sair da sua conta neste celular?")) {
            localStorage.removeItem('bt_loyalty_uuid');
            localStorage.removeItem('bt_cliente_uuid');
            location.reload();
        }
    },

    showView(view) {
        document.getElementById('loading-view').classList.add('hidden');
        document.getElementById('register-view').classList.add('hidden');
        document.getElementById('profile-view').classList.add('hidden');
        document.getElementById('recovery-view').classList.add('hidden');

        const el = document.getElementById(`${view}-view`);
        if (el) el.classList.remove('hidden');
    },

    showRecovery() { this.showView('recovery'); },
    showRegister() { this.showView('register'); }
};

document.addEventListener('DOMContentLoaded', () => Loyalty.init());
