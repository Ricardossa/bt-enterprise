<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::iniciar();
if (!Auth::autenticado()) {
    header('Location: login.php');
    exit;
}

$operadorLogado = Auth::operador();
$servicos = Database::fetchAll("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC");

$pageTitle = 'Operador de Bolso';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="live/assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        :root {
            --barber-gold: #D4AF37;
            --barber-dark: #121212;
        }
        body { background: var(--barber-dark); color: #fff; }
        .barber-container { max-width: 500px; margin: 0 auto; padding: 20px; }
        .barber-header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
        .barber-header h1 { font-size: 20px; margin: 0; text-transform: uppercase; color: var(--secondary); }

        .queue-stats { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; }
        .stat-circle { width: 100px; height: 100px; border: 4px solid var(--primary); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,25,255,0.1); }
        .stat-circle b { font-size: 32px; line-height: 1; }
        .stat-circle span { font-size: 10px; text-transform: uppercase; font-weight: bold; }

        .btn-call-next { width: 100%; height: 120px; border-radius: 25px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border: none; color: #fff; font-size: 24px; font-weight: 900; margin-bottom: 30px; box-shadow: 0 15px 30px rgba(0,25,255,0.3); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; }
        .btn-call-next:active { transform: scale(0.95); box-shadow: 0 5px 15px rgba(0,25,255,0.2); }

        .current-ticket { background: #fff; color: #000; border-radius: 25px; padding: 25px; margin-bottom: 30px; text-align: center; position: relative; }
        .current-ticket label { font-size: 11px; font-weight: 800; color: #666; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .current-ticket b { font-size: 60px; color: var(--primary); line-height: 1; }

        .next-list { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; }
        .next-list h3 { font-size: 14px; margin: 0 0 15px; color: var(--text2); text-transform: uppercase; display: flex; justify-content: space-between; }
        .next-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .next-item:last-child { border: none; }
        .next-item .idx { width: 30px; font-weight: 900; color: var(--secondary); }
        .next-item .info { flex: 1; }
        .next-item .info b { display: block; font-size: 16px; }
        .next-item .info span { font-size: 12px; color: var(--text2); }

        .setup-screen { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: var(--barber-dark); z-index: 2000; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <!-- TELA DE SETUP (ESCOLHA DE SERVIÇO) -->
    <div id="setup-screen" class="setup-screen <?= $operadorLogado['servico_id'] ? 'hidden' : '' ?>">
        <i class="fa-solid fa-chair" style="font-size: 60px; color: var(--primary); margin-bottom: 20px;"></i>
        <h2>Configurar sua Cadeira</h2>
        <p style="color: var(--text2); margin-bottom: 30px;">Qual serviço você atenderá hoje?</p>

        <div style="width: 100%; display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($servicos as $s): ?>
                <button onclick="Barber.setService(<?= $s['id'] ?>, '<?= $s['nome'] ?>')" class="bt-button" style="padding: 20px; border-radius: 15px; background: var(--card); border: 1px solid var(--border);">
                    <?= htmlspecialchars($s['nome']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="barber-container">
        <header class="barber-header">
            <h1><?= htmlspecialchars($operadorLogado['nome']) ?></h1>
            <span id="service-badge" style="font-size: 11px; background: var(--primary); padding: 4px 12px; border-radius: 50px;">Carregando...</span>
        </header>

        <div class="queue-stats">
            <div class="stat-circle">
                <b id="total-fila">0</b>
                <span>Na Fila</span>
            </div>
            <div class="stat-circle" style="border-color: var(--warning); background: rgba(245, 165, 36, 0.1);">
                <b id="total-agenda">0</b>
                <span>Agendados</span>
            </div>
        </div>

        <div id="view-free">
            <button onclick="Barber.chamarProximo()" class="btn-call-next">
                <i class="fa-solid fa-bell"></i>
                CHAMAR PRÓXIMO
            </button>
        </div>

        <div id="view-busy" class="hidden">
            <div class="current-ticket animate__animated animate__pulse animate__infinite">
                <label>Atendendo agora</label>
                <b id="current-code">---</b>
                <button onclick="Barber.finalizar()" class="bt-button bt-success" style="width: 100%; margin-top: 20px; padding: 18px;">
                    <i class="fa-solid fa-check-double"></i> FINALIZAR ATENDIMENTO
                </button>
                <button onclick="Barber.rechamar()" class="bt-button" style="width: 100%; margin-top: 10px; background: transparent; color: var(--warning); border: 1px solid var(--warning);">
                    <i class="fa-solid fa-bullhorn"></i> RECHAMAR
                </button>
            </div>
        </div>

        <section class="next-list">
            <h3><i class="fa-solid fa-list-ul"></i> Próximos 3 <span>Hoje</span></h3>
            <div id="list-next">
                <p style="color: var(--text3); text-align: center; font-size: 13px;">Ninguém na fila.</p>
            </div>
        </section>

        <footer style="text-align: center; margin-top: 40px; opacity: 0.3;">
            <img src="http://api.brandaotech.com.br:8080/uploads/logo/logo.png" style="height: 15px;">
        </footer>
    </div>

    <script src="assets/js/api.js?v=4.7"></script>
    <script>
        window.Barber = {
            serviceId: <?= (int)$operadorLogado['servico_id'] ?: 'null' ?>,
            guicheId: <?= (int)$operadorLogado['guiche_id'] ?: '1' ?>,
            currentId: null,

            init() {
                if (this.serviceId) {
                    this.updateUI();
                    this.startPolling();
                }
            },

            setService(id, name) {
                this.serviceId = id;
                document.getElementById('setup-screen').classList.add('hidden');
                this.updateUI();
                this.startPolling();
            },

            updateUI() {
                document.getElementById('service-badge').innerText = "CADEIRA: " + (this.serviceId ? this.serviceId : '--');
            },

            startPolling() {
            this.sync();
            setInterval(() => this.sync(), 3000);
        },

        lastFilaCount: 0,
        async sync() {
            if (!this.serviceId) return;
            try {
                const res = await BT.api.estado(this.serviceId, this.guicheId);
                if (res.success) {
                    const d = res.data;
                    const currentFilaCount = d.fila.length;

                    // v2.7.0: Alerta de Bolso (Vibra se entrar gente nova na fila)
                    if (currentFilaCount > this.lastFilaCount) {
                        this.notificarNovoCliente();
                    }
                    this.lastFilaCount = currentFilaCount;

                    document.getElementById('total-fila').innerText = currentFilaCount;
                    document.getElementById('total-agenda').innerText = d.agendados.length;

                    // Atualiza Visão (Ocupado/Livre)
                    if (d.chamando) {
                        this.currentId = d.chamando.id;
                        document.getElementById('current-code').innerText = d.chamando.codigo;
                        document.getElementById('view-busy').classList.remove('hidden');
                        document.getElementById('view-free').classList.add('hidden');
                    } else {
                        this.currentId = null;
                        document.getElementById('view-busy').classList.add('hidden');
                        document.getElementById('view-free').classList.remove('hidden');
                    }

                    // Renderiza Próximos 3 (Com suporte a Agendados)
                    const list = document.getElementById('list-next');
                    let nextItems = [];

                    // Prioriza agendados que chegaram (PRESENTE) ou estão próximos
                    if (d.agendados && d.agendados.length > 0) {
                        d.agendados.slice(0, 2).forEach(a => {
                            nextItems.push({
                                codigo: a.codigo,
                                info: '📅 ' + a.nome_cliente.split(' ')[0],
                                status: a.status
                            });
                        });
                    }

                    // Preenche com a fila normal
                    if (d.fila) {
                        d.fila.slice(0, 3).forEach(f => {
                            if (nextItems.length < 3) {
                                nextItems.push({
                                    codigo: f.codigo,
                                    info: f.status === 'CONGELADA' ? '❄️ Reservada' : 'Fila Normal',
                                    status: f.status
                                });
                            }
                        });
                    }

                    if (nextItems.length > 0) {
                        list.innerHTML = nextItems.map((t, i) => `
                            <div class="next-item">
                                <span class="idx">${i+1}º</span>
                                <div class="info">
                                    <b>${t.codigo}</b>
                                    <span>${t.info}</span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        list.innerHTML = '<p style="color: var(--text3); text-align: center; font-size: 13px;">Ninguém na fila.</p>';
                    }
                }
            } catch (e) {}
        },

        notificarNovoCliente() {
            console.log("🔔 NOVO CLIENTE NA FILA!");
            if (navigator.vibrate) {
                navigator.vibrate([300, 100, 300]); // Vibração dupla
            }
            try {
                const audio = new Audio('../assets/audio/ding.mp3');
                audio.volume = 0.5;
                audio.play().catch(e => console.warn("Áudio bloqueado"));
            } catch(e) {}
        },

            async chamarProximo() {
                try {
                    const res = await BT.api.chamar(this.serviceId, this.guicheId);
                    if (!res.success) alert(res.message);
                } catch (e) { alert("Falha na conexão."); }
            },

            async rechamar() {
                if (!this.currentId) return;
                try {
                    await BT.api.rechamar(this.currentId);
                } catch (e) {}
            },

            async finalizar() {
                if (!this.currentId) return;
                try {
                    const res = await BT.api.finalizar(this.currentId);
                    if (res.success) this.sync();
                } catch (e) {}
            }
        };
        document.addEventListener('DOMContentLoaded', () => Barber.init());
    </script>
</body>
</html>
