<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;
use BTQueue\Core\MasterSync\LicenseManager;

if (!LicenseManager::hasFeature('hybrid_scheduling')) {
    die("<h1 style='text-align:center; margin-top:50px; color:#ff4d4d;'>Módulo de Agendamento não disponível para este plano.</h1>");
}

$config = [];
try {
    $linhas = Database::fetchAll("SELECT chave, valor FROM configuracoes");
    foreach ($linhas as $linha) { $config[$linha['chave']] = $linha['valor']; }
} catch (Exception $e) {}

$empresa = $config['empresa'] ?? 'Brandão Tech';
$logoExiste = file_exists(__DIR__ . '/uploads/logo.png');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Online - <?= htmlspecialchars($empresa) ?></title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .agenda-card { background: var(--card); width: 100%; max-width: 500px; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow); padding: 30px; text-align: center; }
        .service-btn { background: var(--sidebar); border: 1px solid var(--border); padding: 15px; border-radius: 12px; margin-bottom: 10px; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.2s; width: 100%; text-align: left; color: #fff; }
        .service-btn:hover { border-color: var(--secondary); background: rgba(29, 180, 255, 0.05); }
        .service-btn.active { border-color: var(--secondary); background: rgba(29, 180, 255, 0.1); }
        .slot-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 20px; }
        .slot-item { background: var(--sidebar); padding: 10px; border-radius: 8px; border: 1px solid var(--border); cursor: pointer; font-weight: bold; transition: 0.2s; }
        .slot-item:hover { border-color: var(--warning); color: var(--warning); }
        .slot-item.active { background: var(--warning); color: #000; border-color: var(--warning); }
        .hidden { display: none; }
        input.form-control { background: var(--sidebar); border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; width: 100%; margin-top: 10px; }
    </style>
</head>
<body>

<div class="agenda-card animate__animated animate__fadeIn">
    <header style="margin-bottom: 30px;">
        <?php if ($logoExiste): ?>
            <img src="uploads/logo.png" alt="Logo" style="max-height: 60px; margin-bottom: 15px;">
        <?php endif; ?>
        <h2 style="font-weight: 900;"><?= htmlspecialchars($empresa) ?></h2>
        <p style="color: var(--text2); font-size: 14px;">Reserve seu horário de atendimento</p>
    </header>

    <!-- PAINEL DE REGRAS (v6.3 Compliance) -->
    <div id="rules-box" style="text-align: left; background: rgba(255, 193, 7, 0.05); border: 1px solid var(--warning); padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 12px;">
        <b style="color: var(--warning); display: block; margin-bottom: 10px;"><i class="fa-solid fa-circle-info"></i> REGRAS IMPORTANTES:</b>
        <ul style="padding-left: 20px; color: var(--text2); line-height: 1.6;">
            <li>Tolerância de <b>10 minutos</b>.</li>
            <li>Limite de <b>1 agendamento por dia</b> por representante.</li>
            <li>Máximo de <b>2 agendamentos por mês</b>.</li>
            <li>Não comparecer sem aviso prévio gera <b>suspensão</b> (14 a 60 dias).</li>
        </ul>
    </div>

    <!-- ETAPA 1: ESCOLHA DO SERVIÇO -->
    <div id="step-1">
        <h3 style="font-size: 16px; margin-bottom: 20px; text-align: left;">1. Qual serviço deseja?</h3>
        <div id="lista-servicos">
            <!-- Injetado via JS -->
        </div>
    </div>

    <!-- ETAPA 2: ESCOLHA DATA E HORA -->
    <div id="step-2" class="hidden">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <button onclick="prevStep(1)" class="bt-button" style="padding: 5px 10px; font-size: 12px;"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
            <h3 style="font-size: 16px; margin: 0;">2. Escolha o Horário</h3>
        </div>

        <?php
            $hoje = date('Y-m-d');
            $horizonte = (int)($config['agenda_horizonte'] ?? 30);
            $maxData = date('Y-m-d', strtotime("+$horizonte days"));
        ?>
        <input type="date" id="data-agenda" class="form-control"
               min="<?= $hoje ?>"
               max="<?= $maxData ?>"
               value="<?= $hoje ?>">

        <div id="lista-slots" class="slot-grid">
            <!-- Injetado via JS -->
        </div>
    </div>

    <!-- ETAPA 3: IDENTIFICAÇÃO -->
    <div id="step-3" class="hidden">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <button onclick="prevStep(2)" class="bt-button" style="padding: 5px 10px; font-size: 12px;"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
            <h3 style="font-size: 16px; margin: 0;">3. Seus Dados</h3>
        </div>

        <div style="text-align: left;">
            <label style="font-size: 12px; color: var(--text2);">Nome Completo / Empresa</label>
            <input type="text" id="nome_cliente" class="form-control" placeholder="Como devemos te chamar?">

            <label style="font-size: 12px; color: var(--text2); margin-top: 15px; display: block;">WhatsApp (Opcional)</label>
            <input type="tel" id="whatsapp" class="form-control" placeholder="(00) 00000-0000">
        </div>

        <button id="btnConfirmar" class="bt-button bt-primary" style="width: 100%; margin-top: 30px; padding: 15px; font-weight: bold;">
            CONFIRMAR AGENDAMENTO
        </button>
    </div>

    <!-- SUCESSO -->
    <div id="step-success" class="hidden">
        <div style="padding: 10px 0;">
            <i class="fa-solid fa-circle-check" style="font-size: 60px; color: var(--success); margin-bottom: 20px;"></i>
            <h2 style="color: #fff;">Agendado!</h2>
            <p style="color: var(--text2); margin-top: 10px;">Seu horário foi reservado com sucesso.</p>

            <div style="background: var(--sidebar); padding: 20px; border-radius: 15px; margin-top: 25px; border: 1px solid var(--border);">
                <span style="font-size: 11px; color: var(--text2); text-transform: uppercase;">Código de Cancelamento</span>
                <div id="resumo-token" style="font-size: 28px; font-weight: bold; color: var(--warning); margin: 5px 0; letter-spacing: 3px;">--------</div>
                <div id="resumo-horario" style="font-size: 18px; font-weight: bold; color: #fff;">--:--</div>
                <div id="resumo-data" style="font-size: 14px; color: var(--text2);">--/--/--</div>
            </div>

            <button id="btnZap" class="bt-button" style="margin-top: 25px; width: 100%; background: #25D366; color: #000; font-weight: bold; border: none;">
                <i class="fa-brands fa-whatsapp"></i> NOTIFICAR WHATSAPP
            </button>

            <p style="font-size: 11px; color: var(--text3); margin-top: 20px;">
                Guarde seu código. Para cancelar, acesse: <br>
                <b id="display-cancel-url">...</b>
            </p>

            <button onclick="location.reload()" class="bt-button" style="margin-top: 20px; width: 100%; background: transparent; border: 1px solid var(--border);">NOVO AGENDAMENTO</button>
        </div>
    </div>
</div>

<script>
    const state = { servico_id: null, data: document.getElementById('data-agenda').value, hora: null };

    async function loadServices() {
        const res = await fetch('api/v1/agenda.php?action=servicos');
        const json = await res.json();
        const container = document.getElementById('lista-servicos');
        container.innerHTML = json.data.map(s => `
            <div class="service-btn" onclick="selectService(${s.id})">
                <div style="background: ${s.cor}; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">${s.icone}</div>
                <b>${s.nome}</b>
            </div>
        `).join('');
    }

    function selectService(id) {
        state.servico_id = id;
        nextStep(2);
        loadSlots();
    }

    async function loadSlots() {
        const container = document.getElementById('lista-slots');
        container.innerHTML = '<p style="grid-column: span 3; color: var(--text3);">Buscando horários...</p>';

        const res = await fetch(`api/v1/agenda.php?servico_id=${state.servico_id}&data=${state.data}`);
        const json = await res.json();

        if (json.data.length === 0) {
            container.innerHTML = '<p style="grid-column: span 3; color: var(--danger); font-size: 13px; padding: 20px;">Não há horários disponíveis para esta data.</p>';
            return;
        }

        container.innerHTML = json.data.map(h => `
            <div class="slot-item" onclick="selectSlot('${h}', this)">${h}</div>
        `).join('');
    }

    function selectSlot(hora, el) {
        state.hora = hora;
        document.querySelectorAll('.slot-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        setTimeout(() => nextStep(3), 300);
    }

    function nextStep(n) {
        [1,2,3, 'success'].forEach(s => document.getElementById('step-'+s).classList.add('hidden'));
        document.getElementById('step-'+n).classList.remove('hidden');
    }

    function prevStep(n) { nextStep(n); }

    document.getElementById('data-agenda').onchange = (e) => {
        state.data = e.target.value;
        loadSlots();
    };

    document.getElementById('btnConfirmar').onclick = async () => {
        const nome = document.getElementById('nome_cliente').value.trim();
        const whatsapp = document.getElementById('whatsapp').value.trim();
        if (!nome) return alert("Por favor, informe seu nome.");

        const btn = document.getElementById('btnConfirmar');
        btn.disabled = true;
        btn.innerText = "RESERVANDO...";

        try {
            const res = await fetch('api/v1/agenda.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    ...state,
                    nome_cliente: nome,
                    whatsapp: whatsapp,
                    device_id: localStorage.getItem('bt_device_uuid') || ''
                })
            });
            const json = await res.json();

            if (json.success) {
                document.getElementById('resumo-token').innerText = json.token;
                document.getElementById('resumo-horario').innerText = json.horario;
                document.getElementById('resumo-data').innerText = json.data;

                const cancelUrl = window.location.href.split('?')[0].replace('agendar.php', 'cancelar.php');
                document.getElementById('display-cancel-url').innerText = cancelUrl;

                document.getElementById('btnZap').onclick = () => {
                    const msg = `✅ *AGENDAMENTO CONFIRMADO!*\n\n📍 *Unidade:* ${document.querySelector('h2').innerText}\n📅 *Data:* ${json.data}\n🕒 *Hora:* ${json.horario}\n🔑 *Código:* ${json.token}\n\n━━━━━━━━━━━━━━━\n\n❌ *Para cancelar, acesse:*\n${cancelUrl}?t=${json.token}`;
                    window.open(`https://wa.me/${whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(msg)}`);
                };

                nextStep('success');
                document.getElementById('rules-box').classList.add('hidden');
            } else {
                alert(json.message);
                btn.disabled = false;
                btn.innerText = "CONFIRMAR AGENDAMENTO";
            }
        } catch (e) { alert("Erro ao agendar."); btn.disabled = false; }
    };

    loadServices();
</script>

</body>
</html>
