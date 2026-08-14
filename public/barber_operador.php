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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --bg: #081421;
            --card: #132238;
            --sidebar: #0D1B2A;
            --primary: #1565C0;
            --secondary: #1DB4FF;
            --accent: #1DB4FF;
            --border: #1E3552;
            --success: #18C964;
            --warning: #F5A623;
            --danger: #FF4D4D;
            --text: #FFFFFF;
            --text2: #94a3b8;
            --text3: #64748b;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 0; min-height: 100vh; overflow-x: hidden; }

        .barber-container { max-width: 500px; margin: 0 auto; padding: 20px; display: flex; flex-direction: column; min-height: 100vh; box-sizing: border-box; }

        .barber-header { text-align: center; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
        .barber-header h1 { font-size: 18px; margin: 0; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .barber-header .badge { display: inline-block; margin-top: 8px; font-size: 10px; background: var(--primary); padding: 4px 12px; border-radius: 50px; font-weight: 900; }

        .queue-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 20px; padding: 20px; text-align: center; }
        .stat-card b { font-size: 36px; display: block; color: var(--secondary); line-height: 1; }
        .stat-card span { font-size: 11px; text-transform: uppercase; color: var(--text2); font-weight: 800; letter-spacing: 1px; margin-top: 8px; display: block; }

        .btn-call-next { width: 100%; height: 140px; border-radius: 25px; background: linear-gradient(135deg, var(--primary) 0%, #0012CC 100%); border: none; color: #fff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 15px 30px rgba(0,25,255,0.3); transition: 0.2s; }
        .btn-call-next:active { transform: scale(0.95); }
        .btn-call-next i { font-size: 40px; }
        .btn-call-next span { font-size: 22px; font-weight: 900; letter-spacing: 1px; }

        /* CARD DE ATENDIMENTO ATUAL (DIAMOND STYLE) */
        .active-ticket-card { background: #fff; border-radius: 30px; padding: 35px 20px; margin-bottom: 30px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); border: 3px solid var(--secondary); }
        .active-ticket-card label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }
        .active-ticket-card .senha-num { font-size: 80px; font-weight: 900; color: var(--primary); line-height: 1; margin: 10px 0; letter-spacing: -2px; }
        .active-ticket-card .cliente-nome { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 20px; background: #f1f5f9; padding: 10px; border-radius: 12px; display: block; }

        .op-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-op { padding: 18px; border-radius: 15px; border: none; font-weight: 900; font-size: 14px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-op:active { transform: scale(0.95); }
        .btn-op-success { background: var(--success); color: #fff; grid-column: span 2; }
        .btn-op-warning { background: var(--warning); color: #000; }
        .btn-op-danger { background: var(--danger); color: #fff; }

        .next-list { background: rgba(255,255,255,0.03); border-radius: 25px; padding: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .next-list h3 { font-size: 13px; margin: 0 0 20px; color: var(--text3); text-transform: uppercase; font-weight: 900; letter-spacing: 2px; }
        .next-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .next-item:last-child { border: none; }
        .next-item .idx { width: 35px; height: 35px; background: rgba(29, 180, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--secondary); font-size: 14px; }
        .next-item .info { flex: 1; }
        .next-item .info b { display: block; font-size: 16px; color: #fff; }
        .next-item .info span { font-size: 12px; color: var(--text2); }

        .setup-screen { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: var(--bg); z-index: 2000; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; }
        .hidden { display: none !important; }
        .bt-footer { margin-top: auto; padding: 30px 0; text-align: center; opacity: 0.2; }
        .bt-footer img { height: 18px; }
    </style>
</head>
<body>

    <!-- TELA DE SETUP (ESCOLHA DE SERVIÇO) -->
    <div id="setup-screen" class="setup-screen <?= $operadorLogado['servico_id'] ? 'hidden' : '' ?>">
        <div class="animate__animated animate__fadeInDown">
            <i class="fa-solid fa-chair" style="font-size: 60px; color: var(--secondary); margin-bottom: 25px;"></i>
            <h2 style="font-weight:900;">Configurar sua Cadeira</h2>
            <p style="color: var(--text2); margin-bottom: 40px;">Selecione o serviço de hoje:</p>
        </div>

        <div style="width: 100%; display: flex; flex-direction: column; gap: 15px;" class="animate__animated animate__fadeInUp">
            <?php foreach ($servicos as $s): ?>
                <button onclick="Barber.setService(<?= $s['id'] ?>, '<?= htmlspecialchars($s['nome']) ?>')" class="btn-call-next" style="height: 80px; border-radius: 20px; box-shadow: none;">
                    <span><?= htmlspecialchars($s['nome']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="barber-container">
        <header class="barber-header">
            <h1><?= htmlspecialchars($operadorLogado['nome']) ?></h1>
            <span id="service-badge" class="badge">CARREGANDO...</span>
        </header>

        <div class="queue-stats">
            <div class="stat-card">
                <b id="total-fila">0</b>
                <span>Na Fila</span>
            </div>
            <div class="stat-card">
                <b id="total-agenda" style="color: var(--warning);">0</b>
                <span>Agendados</span>
            </div>
        </div>

        <div id="view-free" class="animate__animated animate__fadeIn">
            <button onclick="Barber.chamarProximo()" class="btn-call-next">
                <i class="fa-solid fa-bell"></i>
                <span>CHAMAR PRÓXIMO</span>
            </button>
        </div>

        <div id="view-busy" class="hidden">
            <div class="active-ticket-card animate__animated animate__zoomIn">
                <label>Atendendo agora</label>
                <div id="current-code" class="senha-num">---</div>
                <span id="current-name" class="cliente-nome">Cliente não identificado</span>

                <div class="op-actions">
                    <button onclick="Barber.finalizar()" class="btn-op btn-op-success">
                        <i class="fa-solid fa-check-double"></i> FINALIZAR ATENDIMENTO
                    </button>
                    <button onclick="Barber.rechamar()" class="btn-op btn-op-warning">
                        <i class="fa-solid fa-bullhorn"></i> RECHAMAR
                    </button>
                    <button onclick="location.reload()" class="btn-op btn-op-danger" style="background:#334155;">
                        <i class="fa-solid fa-rotate"></i> PULSAR
                    </button>
                </div>
            </div>
        </div>

        <section class="next-list animate__animated animate__fadeInUp">
            <h3><i class="fa-solid fa-list-ul" style="color:var(--secondary); margin-right:8px;"></i> Próximos na Fila</h3>
            <div id="list-next">
                <p style="color: var(--text3); text-align: center; font-size: 13px; padding: 20px;">Fila vazia.</p>
            </div>
        </section>

        <footer class="bt-footer">
            <p style="font-size: 10px; font-weight: 800; letter-spacing: 2px; margin-bottom: 5px;">POWERED BY</p>
            <img src="http://api.brandaotech.com.br:8080/uploads/logo/logo.png" alt="BT">
        </footer>
    </div>

    <script src="assets/js/api.js?v=4.7"></script>
    <script>
        window.Barber = {
            serviceId: <?= (int)$operadorLogado['servico_id'] ?: 'null' ?>,
            guicheId: <?= (int)$operadorLogado['guiche_id'] ?: '1' ?>,
            currentId: null,
            lastFilaCount: 0,

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

            async sync() {
                if (!this.serviceId) return;
                try {
                    const res = await BT.api.estado(this.serviceId, this.guicheId);
                    if (res.success) {
                        const d = res.data;
                        const currentFilaCount = d.fila.length;

                        // Alerta de Vibração (v2.7.5)
                        if (currentFilaCount > this.lastFilaCount) {
                            if (navigator.vibrate) navigator.vibrate([300, 100, 300]);
                        }
                        this.lastFilaCount = currentFilaCount;

                        document.getElementById('total-fila').innerText = currentFilaCount;
                        document.getElementById('total-agenda').innerText = d.agendados.length;

                        // Atualiza Visão (Atendimento Ativo)
                        if (d.chamando) {
                            this.currentId = d.chamando.id;
                            document.getElementById('current-code').innerText = d.chamando.codigo;
                            document.getElementById('current-name').innerText = d.chamando.nome_cliente || 'Identificação não disponível';
                            document.getElementById('view-busy').classList.remove('hidden');
                            document.getElementById('view-free').classList.add('hidden');
                        } else {
                            this.currentId = null;
                            document.getElementById('view-busy').classList.add('hidden');
                            document.getElementById('view-free').classList.remove('hidden');
                        }

                        // Renderiza Lista de Próximos com Nomes
                        const list = document.getElementById('list-next');
                        let items = [];

                        // 1. Agendados (Prioridade Visual)
                        if (d.agendados && d.agendados.length > 0) {
                            d.agendados.slice(0, 2).forEach(a => {
                                items.push({
                                    codigo: a.codigo,
                                    nome: a.nome_cliente || 'Agendamento',
                                    info: '📅 AGENDADO'
                                });
                            });
                        }

                        // 2. Fila Normal
                        if (d.fila) {
                            d.fila.slice(0, 3).forEach(f => {
                                if (items.length < 5) {
                                    items.push({
                                        codigo: f.codigo,
                                        nome: f.nome_cliente || 'Cliente de Porta',
                                        info: f.status === 'CONGELADA' ? '❄️ RESERVADA' : 'EM ESPERA'
                                    });
                                }
                            });
                        }

                        if (items.length > 0) {
                            list.innerHTML = items.map((t, i) => `
                                <div class="next-item">
                                    <span class="idx">${i+1}º</span>
                                    <div class="info">
                                        <b>${t.nome}</b>
                                        <span>${t.codigo} · ${t.info}</span>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            list.innerHTML = '<p style="color: var(--text3); text-align: center; font-size: 13px; padding: 20px;">Ninguém na fila.</p>';
                        }
                    }
                } catch (e) {}
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
