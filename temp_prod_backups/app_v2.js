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
        listaFila: document.getElementById('fila')
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
                    div.innerHTML = `<div><b>${senha.codigo}</b><br><small>${senha.servico_nome}</small></div><span class="badge warning">Fila</span>`;
                    $dom.listaFila.appendChild(div);
                });
            } else {
                $dom.listaFila.innerHTML = '<div class="text-center p-4">Fila vazia</div>';
            }
        } catch (e) { console.error(e); }
    }

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
