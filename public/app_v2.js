/**
 * BT Queue Enterprise - Controlador de Interface do Operador V2 (NOC Style)
 * Focado em alta visibilidade e resposta em tempo real.
 */

document.addEventListener('DOMContentLoaded', () => {
    const $dom = {
        selectServico: document.getElementById('servico'),
        selectGuiche: document.getElementById('guiche'),
        btnChamar: document.getElementById('btnChamar'),
        btnRechamar: document.getElementById('btnRechamar'),
        btnFinalizar: document.getElementById('btnFinalizar'),
        lblGuiche: document.getElementById('guicheAtual'),
        lblSenhaAtual: document.getElementById('senhaAtual'),
        listaFila: document.getElementById('fila'),
        listaAgenda: document.getElementById('agenda')
    };

    let atendimentoAtual = null;

    function obterParametrosAtivos() {
        return {
            servico_id: $dom.selectServico && $dom.selectServico.value ? parseInt($dom.selectServico.value, 10) : null,
            guiche_id: $dom.selectGuiche && $dom.selectGuiche.value ? parseInt($dom.selectGuiche.value, 10) : null
        };
    }

    async function atualizarPainel() {
        const params = obterParametrosAtivos();
        if (!params.servico_id) return;

        try {
            const resultado = await BT.api.estado(params.servico_id, params.guiche_id);
            if (!resultado || !resultado.success) return;

            const dados = resultado.data;

            if ($dom.lblGuiche) $dom.lblGuiche.textContent = dados.guiche_codigo || 'N/A';

            if (dados.chamando) {
                atendimentoAtual = dados.chamando;
                $dom.lblSenhaAtual.textContent = dados.chamando.codigo;
                $dom.btnChamar.disabled = true;
                $dom.btnFinalizar.disabled = false;
                $dom.btnRechamar.disabled = false;
            } else {
                atendimentoAtual = null;
                $dom.lblSenhaAtual.textContent = '---';
                $dom.btnChamar.disabled = (!params.guiche_id);
                $dom.btnFinalizar.disabled = true;
                $dom.btnRechamar.disabled = true;
            }

            $dom.listaFila.innerHTML = '';
            if (dados.fila && dados.fila.length > 0) {
                dados.fila.forEach(senha => {
                    const div = document.createElement('div');
                    div.className = 'op-next-item';

                    const isFrozen = (senha.status === 'CONGELADA');
                    const badgeClass = isFrozen ? 'badge-info' : 'badge-warning';
                    const badgeText = isFrozen ? 'Congelada ❄️' : 'Fila';
                    const opacity = isFrozen ? '0.6' : '1';

                    div.style.opacity = opacity;
                    div.innerHTML = `<div><b>${senha.codigo}</b><br><small>${senha.servico_nome}</small></div><span class="badge ${badgeClass}">${badgeText}</span>`;
                    $dom.listaFila.appendChild(div);
                });
            } else {
                $dom.listaFila.innerHTML = '<div class="text-center p-4">Fila vazia</div>';
            }

            // --- RENDERIZAÇÃO DA AGENDA DO DIA (HÍBRIDA) ---
            if ($dom.listaAgenda) {
                $dom.listaAgenda.innerHTML = '';
                if (dados.agendados && dados.agendados.length > 0) {
                    dados.agendados.forEach(agd => {
                        const div = document.createElement('div');
                        div.className = 'op-next-item';
                        div.style.borderLeft = '4px solid var(--warning)';

                        // Extrai apenas a hora do agendamento
                        const hora = agd.data_agendamento.split(' ')[1].substring(0, 5);
                        const label = dados.label_cliente || 'Paciente';

                        // Limpeza inteligente: Remove o texto "ATENDIMENTO" se ele vier no título do Google
                        let nomeLimpo = agd.nome_cliente.replace(/ATENDIMENTO/gi, '').trim();

                        div.innerHTML = `
                            <div style="display:flex; align-items:center; gap:15px; width:100%;">
                                <div style="background:var(--warning); color:#000; padding:5px 10px; border-radius:8px; font-weight:900; font-size:14px; min-width:60px; text-align:center;">
                                    ${hora}
                                </div>
                                <div style="flex:1; text-align:left;">
                                    <span style="font-size:10px; color:var(--text3); text-transform:uppercase; display:block;">${label}</span>
                                    <b style="color:#fff; font-size:14px;">${nomeLimpo}</b>
                                </div>
                                <button class="btn-chamar-agd" onclick="BT_OP.chamarAgendado(${agd.id})" style="background:rgba(255, 193, 7, 0.1); color:var(--warning); border:1px solid var(--warning); padding:8px 15px; border-radius:8px; font-size:11px; font-weight:bold; cursor:pointer; transition:0.3s;">
                                    CHAMAR
                                </button>
                            </div>
                        `;
                        $dom.listaAgenda.appendChild(div);
                    });
                } else {
                    $dom.listaAgenda.innerHTML = '<div class="text-center text-muted py-2" style="font-size:12px;">Nenhum agendamento para hoje.</div>';
                }
            }
        } catch (e) { console.error(e); }
    }

    // Expõe a função globalmente para o onclick
    window.BT_OP = {
        chamarAgendado: async (id) => {
            const p = obterParametrosAtivos();
            if (!p.guiche_id) return alert("Selecione seu guichê primeiro.");

            const res = await BT.api.chamarAgendado(id, p.guiche_id);
            if (res.success) {
                BT.toast.sucesso("Agendado chamado!");
                atualizarPainel();
            } else {
                alert(res.message);
            }
        }
    };

    $dom.btnChamar.onclick = async () => {
        const p = obterParametrosAtivos();
        const res = await BT.api.chamar(p.servico_id, p.guiche_id);
        if (res.success) { BT.toast.sucesso("Chamado: " + res.codigo); atualizarPainel(); }
        else BT.toast.aviso(res.message);
    };

    $dom.btnRechamar.onclick = async () => {
        if (!atendimentoAtual) return;
        const res = await BT.api.rechamar(atendimentoAtual.id);
        if (res.success) BT.toast.info("Rechamando...");
    };

    $dom.btnFinalizar.onclick = async () => {
        if (!atendimentoAtual) return;
        const res = await BT.api.finalizar(atendimentoAtual.id);
        if (res.success) { BT.toast.sucesso("Finalizado."); atualizarPainel(); }
    };

    $dom.selectServico.onchange = atualizarPainel;
    $dom.selectGuiche.onchange = atualizarPainel;

    atualizarPainel();
    setInterval(atualizarPainel, 3000);
});
