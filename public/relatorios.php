<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Relatórios de Performance';
include __DIR__ . '/includes/header.php';
?>

<style>
    .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 25px; }
    .metric-card { background: var(--card); border: 1px solid var(--border); border-radius: 15px; padding: 25px; }
    .metric-title { font-size: 14px; font-weight: 800; color: var(--secondary); text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

    .stats-table { width: 100%; border-collapse: collapse; }
    .stats-table th { text-align: left; font-size: 11px; color: var(--text2); text-transform: uppercase; padding: 10px; border-bottom: 1px solid var(--border); }
    .stats-table td { padding: 12px 10px; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.03); }

    .big-number { font-size: 32px; font-weight: bold; color: #fff; }
    .unit { font-size: 14px; color: var(--text2); font-weight: normal; margin-left: 5px; }
</style>

<main class="bt-main">

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-chart-pie"></i> Inteligência de Negócio</h2>
            <p style="color:var(--text2); font-size:14px;">Análise de produtividade e tempo médio de espera.</p>
        </div>

        <!-- FILTROS DE PERÍODO -->
        <div style="display:flex; gap:10px; background:var(--sidebar); padding:5px; border-radius:10px; border:1px solid var(--border);">
            <button onclick="setPeriodo('hoje')" class="bt-button" id="btn-hoje" style="padding:8px 15px; font-size:12px;">HOJE</button>
            <button onclick="setPeriodo('mes')" class="bt-button" id="btn-mes" style="padding:8px 15px; font-size:12px; background:transparent;">MÊS</button>
            <button onclick="setPeriodo('ano')" class="bt-button" id="btn-ano" style="padding:8px 15px; font-size:12px; background:transparent;">ANO</button>
        </div>
    </div>

    <!-- RESUMO RÁPIDO -->
    <div class="report-grid" style="grid-template-columns: repeat(3, 1fr); margin-top: 30px;">
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-ticket"></i> Total Emitidas</div>
            <div class="big-number" id="total-emitidas">--</div>
        </div>
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-clock-rotate-left"></i> Espera Média</div>
            <div class="big-number" id="espera-global">-- <span class="unit">min</span></div>
        </div>
        <div class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-bolt"></i> Pico de Movimento</div>
            <div class="big-number" id="horario-pico">--</div>
        </div>
    </div>

    <div class="report-grid">

        <!-- RANKING OPERADORES -->
        <section class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-medal"></i> Ranking de Produtividade</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Atendente</th>
                        <th>Total</th>
                        <th>Tempo Médio (Atend.)</th>
                    </tr>
                </thead>
                <tbody id="lista-ranking">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>

        <!-- ESPERA POR SERVIÇO -->
        <section class="metric-card">
            <div class="metric-title"><i class="fa-solid fa-hourglass-half"></i> Espera por Serviço</div>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Tempo de Espera</th>
                    </tr>
                </thead>
                <tbody id="lista-espera-servico">
                    <!-- Injetado via JS -->
                </tbody>
            </table>
        </section>

    </div>

</main>

<script>
let filtroAtual = 'hoje';

function getDatas() {
    const agora = new Date();
    let inicio, fim;

    const format = (d) => d.toISOString().split('T')[0];

    if (filtroAtual === 'mes') {
        inicio = format(new Date(agora.getFullYear(), agora.getMonth(), 1));
        fim = format(new Date(agora.getFullYear(), agora.getMonth() + 1, 0));
    } else if (filtroAtual === 'ano') {
        inicio = format(new Date(agora.getFullYear(), 0, 1));
        fim = format(new Date(agora.getFullYear(), 11, 31));
    } else {
        inicio = format(agora);
        fim = format(agora);
    }
    return { inicio, fim };
}

function setPeriodo(p) {
    filtroAtual = p;
    ['hoje', 'mes', 'ano'].forEach(btn => {
        const el = document.getElementById('btn-' + btn);
        el.style.background = (btn === p) ? 'var(--primary)' : 'transparent';
    });
    carregarDados();
}

async function carregarDados() {
    const { inicio, fim } = getDatas();
    try {
        const res = await fetch(`api/relatorios_stats.php?inicio=${inicio}&fim=${fim}`);
        if (!res.ok) {
            if (res.status === 401) {
                window.location = 'login.php';
                return;
            }
            throw new Error(`Erro HTTP: ${res.status}`);
        }

        const json = await res.json();

        if (!json.success) {
            console.error("API Error:", json.message);
            return;
        }
        const d = json.data;

        // Resumo
        document.getElementById('total-emitidas').innerText = d.resumo.total_emitidas || 0;
        document.getElementById('espera-global').innerHTML = `${Math.round(d.resumo.espera_global || 0)} <span class="unit">min</span>`;

        // Horário de Pico
        if (d.picos && d.picos.length > 0) {
            const pico = [...d.picos].sort((a,b) => b.total - a.total)[0];
            document.getElementById('horario-pico').innerText = pico.hora;
        }

        // Ranking Operadores
        const rankingBody = document.getElementById('lista-ranking');
        rankingBody.innerHTML = d.ranking.map(op => `
            <tr>
                <td><b>${op.nome}</b></td>
                <td>${op.total} senhas</td>
                <td>${Math.round(op.tempo_medio_atendimento || 0)} min</td>
            </tr>
        `).join('') || '<tr><td colspan="3" class="text-center">Nenhum dado.</td></tr>';

        // Espera por Serviço
        const esperaBody = document.getElementById('lista-espera-servico');
        esperaBody.innerHTML = d.espera_servico.map(s => `
            <tr>
                <td><b>${s.nome}</b></td>
                <td style="color: ${s.tempo_espera > 15 ? 'var(--danger)' : 'var(--success)'}; font-weight: bold;">
                    ${Math.round(s.tempo_espera || 0)} min
                </td>
            </tr>
        `).join('') || '<tr><td colspan="2" class="text-center">Nenhum dado.</td></tr>';

    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', carregarDados);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
