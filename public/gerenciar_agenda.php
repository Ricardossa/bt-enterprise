<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::protegerPagina('ADMIN');

$servicos = Database::fetchAll("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC");

$pageTitle = 'Gerenciar Agenda';
include __DIR__ . '/includes/header.php';
?>

<style>
    .agenda-manager-card { background: var(--card); border-radius: 15px; border: 1px solid var(--border); padding: 25px; margin-top: 20px; }
    .day-row { display: grid; grid-template-columns: 150px 1fr 1fr 100px 80px; gap: 15px; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .day-name { font-weight: bold; color: var(--secondary); text-transform: uppercase; font-size: 13px; }
    .form-label-small { font-size: 11px; color: var(--text2); display: block; margin-bottom: 5px; }
</style>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2><i class="fa-solid fa-calendar-check"></i> Gestão de Agendamentos</h2>
            <p style="color:var(--text2); font-size:14px;">Configure as janelas de horário e duração dos atendimentos.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="agendar.php" target="_blank" class="bt-button" style="background:var(--sidebar); border:1px solid var(--border); text-decoration:none;">
                <i class="fa-solid fa-external-link"></i> VER PÁGINA PÚBLICA
            </a>
            <button id="btnSalvar" class="bt-button bt-success">
                <i class="fa-solid fa-floppy-disk"></i> SALVAR CONFIGURAÇÃO
            </button>
        </div>
    </div>

    <div class="agenda-manager-card">
        <div class="form-group" style="max-width: 400px; margin-bottom: 30px;">
            <label>Selecione o Serviço para Configurar</label>
            <select id="select-servico" class="form-control">
                <option value="">Escolha um serviço...</option>
                <?php foreach ($servicos as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="regras-container" class="hidden">
            <div class="day-row" style="border-bottom: 2px solid var(--border); padding-bottom: 10px; opacity: 0.6;">
                <div class="day-name">Dia da Semana</div>
                <div>Horário Início</div>
                <div>Horário Fim</div>
                <div>Duração (min)</div>
                <div>Ativo</div>
            </div>

            <?php
            $dias = ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
            foreach ($dias as $index => $nome): ?>
                <div class="day-row" data-dia="<?= $index ?>">
                    <div class="day-name"><?= $nome ?></div>
                    <div>
                        <input type="time" class="form-control start-time" value="08:00">
                    </div>
                    <div>
                        <input type="time" class="form-control end-time" value="18:00">
                    </div>
                    <div>
                        <input type="number" class="form-control slot-duration" value="30" min="5" max="240">
                    </div>
                    <div style="text-align:center;">
                        <input type="checkbox" class="is-active" checked style="transform: scale(1.5);">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-selection" style="padding: 60px; text-align: center; color: var(--text3);">
            <i class="fa-solid fa-mouse-pointer" style="font-size: 40px; margin-bottom: 20px;"></i>
            <p>Selecione um serviço acima para começar a configurar a agenda.</p>
        </div>
    </div>
</main>

<script>
    const $dom = {
        select: document.getElementById('select-servico'),
        container: document.getElementById('regras-container'),
        noSelection: document.getElementById('no-selection'),
        btnSalvar: document.getElementById('btnSalvar'),
        rows: document.querySelectorAll('.day-row[data-dia]')
    };

    $dom.select.onchange = async () => {
        const id = $dom.select.value;
        if (!id) {
            $dom.container.classList.add('hidden');
            $dom.noSelection.classList.remove('hidden');
            return;
        }

        $dom.container.classList.remove('hidden');
        $dom.noSelection.classList.add('hidden');

        // Carrega regras existentes
        try {
            const res = await fetch(`api/v1/agenda.php?action=get_regras&servico_id=${id}`);
            const json = await res.json();

            // Reseta para o padrão antes de aplicar os dados do banco
            resetFields();

            if (json.success && json.data.length > 0) {
                json.data.forEach(regra => {
                    const row = document.querySelector(`.day-row[data-dia="${regra.dia_semana}"]`);
                    if (row) {
                        row.querySelector('.start-time').value = regra.hora_inicio;
                        row.querySelector('.end-time').value = regra.hora_fim;
                        row.querySelector('.slot-duration').value = regra.duracao_slot;
                        row.querySelector('.is-active').checked = parseInt(regra.ativo) === 1;
                    }
                });
            }
        } catch (e) { console.error(e); }
    };

    function resetFields() {
        $dom.rows.forEach(row => {
            row.querySelector('.start-time').value = "08:00";
            row.querySelector('.end-time').value = "18:00";
            row.querySelector('.slot-duration').value = "30";
            row.querySelector('.is-active').checked = false;
        });
    }

    $dom.btnSalvar.onclick = async () => {
        const servicoId = $dom.select.value;
        if (!servicoId) return alert("Selecione um serviço primeiro.");

        const regras = [];
        $dom.rows.forEach(row => {
            regras.push({
                dia_semana: row.getAttribute('data-dia'),
                hora_inicio: row.querySelector('.start-time').value,
                hora_fim: row.querySelector('.end-time').value,
                duracao_slot: row.querySelector('.slot-duration').value,
                ativo: row.querySelector('.is-active').checked ? 1 : 0
            });
        });

        $dom.btnSalvar.disabled = true;
        $dom.btnSalvar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SALVANDO...';

        try {
            const res = await fetch('api/v1/agenda.php?action=save_regras', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ servico_id: servicoId, regras })
            });
            const json = await res.json();

            if (json.success) {
                BT.toast.sucesso("Agenda atualizada com sucesso!");
            } else {
                alert(json.message);
            }
        } catch (e) { alert("Erro ao salvar."); }

        $dom.btnSalvar.disabled = false;
        $dom.btnSalvar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> SALVAR CONFIGURAÇÃO';
    };
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
