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
    let agendadosAnteriores = [];
    let filaAnterior = [];

    // Solicita permissão para notificações nativas (v5.8.9 Diamond)
    if ("Notification" in window) {
        Notification.requestPermission();
    }

    function notify(title, body) {
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, { body: body, icon: 'assets/img/favicon.ico' });
        }
    }

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

            // --- ALERTA DE NOVA SENHA NA FILA (TOTEM) (DIAMOND v5.8.8) ---
            if (dados.fila && dados.fila.length > filaAnterior.length) {
                const ultima = dados.fila[dados.fila.length - 1];
                BT.toast.sucesso("🎟️ Nova Senha na Fila: " + (ultima.codigo || '---'));
                notify("🎟️ Nova Senha na Fila", (ultima.codigo || '---') + " - " + ultima.servico_nome);
                try { new Audio('assets/audio/alert.mp3').play(); } catch(e){}
            }
            filaAnterior = dados.fila || [];

            if (dados.fila && dados.fila.length > 0) {
                dados.fila.forEach(senha => {
                    const div = document.createElement('div');
                    div.className = 'op-next-item';

                    const isFrozen = (senha.status === 'CONGELADA');
                    const badgeClass = isFrozen ? 'badge-info' : 'badge-warning';
                    const badgeText = isFrozen ? 'Congelada ❄️' : 'Fila';
                    const opacity = isFrozen ? '0.6' : '1';

                    div.style.opacity = opacity;
                    div.innerHTML = `
                        <div style="flex:1">
                            <b>${senha.codigo}</b><br>
                            <small>${senha.servico_nome}</small>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            ${window.BT_USER_NIVEL === 'ADMIN' ? `<button onclick="BT_OP.chamarFuraFila(${senha.id})" class="bt-button" style="padding:4px 8px; font-size:10px; background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2);">CHAMAR</button>` : ''}
                            <span class="badge ${badgeClass}">${badgeText}</span>
                        </div>
                    `;
                    $dom.listaFila.appendChild(div);
                });
            } else {
                $dom.listaFila.innerHTML = '<div class="text-center p-4">Fila vazia</div>';
            }

            // --- RENDERIZAÇÃO DA AGENDA DO DIA (HÍBRIDA) ---
            if ($dom.listaAgenda) {
                $dom.listaAgenda.innerHTML = '';

                // --- ALERTA DE NOVO AGENDAMENTO OU CHECK-IN (v6.2 Diamond) ---
                if (dados.agendados && dados.agendados.length > 0) {
                    dados.agendados.forEach((agd, idx) => {
                        const antigo = agendadosAnteriores.find(a => a.id === agd.id);

                        // 1. Novo agendamento detectado
                        if (!antigo && agendadosAnteriores.length > 0) {
                            BT.toast.aviso("📅 Novo Agendamento: " + agd.nome_cliente);
                            notify("📅 Novo Agendamento", agd.nome_cliente);
                            try { new Audio('assets/audio/alert.mp3').play(); } catch(e){}
                        }

                        // 2. Check-in realizado (Mudou de AGENDADO para PRESENTE)
                        if (antigo && antigo.status === 'AGENDADO' && agd.status === 'PRESENTE') {
                            BT.toast.sucesso("📍 CHEGADA: " + agd.nome_cliente);
                            notify("📍 PACIENTE NA LOJA", agd.nome_cliente + " acaba de fazer check-in.");
                            try { new Audio('assets/audio/checkin.mp3').play(); } catch(e){}
                        }
                    });
                }
                agendadosAnteriores = dados.agendados || [];

                if (dados.agendados && dados.agendados.length > 0) {
                    dados.agendados.forEach(agd => {
                        const div = document.createElement('div');
                        div.className = 'op-next-item';

                        // Visual diferenciado para quem já está na loja (v6.2)
                        const isPresente = (agd.status === 'PRESENTE');
                        const borderColor = isPresente ? 'var(--success)' : 'var(--warning)';
                        const bgIcon = isPresente ? 'var(--success)' : 'var(--warning)';
                        const pulseClass = isPresente ? 'animate__animated animate__pulse animate__infinite' : '';

                        div.style.borderLeft = `4px solid ${borderColor}`;
                        if (isPresente) div.style.background = 'rgba(24, 201, 100, 0.05)';

                        // Extrai apenas a hora do agendamento
                        const hora = agd.data_agendamento.split(' ')[1].substring(0, 5);
                        const label = dados.label_cliente || 'Paciente';

                        // --- LIMPEZA DE ELITE BRANDÃO TECH (Foco no Fornecedor) ---
                        let raw = agd.nome_cliente;
                        let nomeExibir = "";

                        // 1. Tenta extrair o que está dentro dos parênteses (ex: CAIO LIMA)
                        let match = raw.match(/\(([^)]+)\)/);
                        if (match) {
                            nomeExibir = match[1];
                        } else {
                            // 2. Fallback: Limpa lixo e pega Nome + Sobrenome
                            let limpo = raw.replace(/ATENDIMENTO|ZH|NAILTON/gi, '').trim();
                            let partes = limpo.split(' ').filter(p => p.length > 1);
                            nomeExibir = (partes.length >= 2) ? (partes[0] + ' ' + partes[partes.length - 1]) : (partes[0] || label);
                        }

                        div.innerHTML = `
                            <div style="display:flex; align-items:center; gap:15px; width:100%;" class="${pulseClass}">
                                <div style="background:${bgIcon}; color:#000; padding:5px 10px; border-radius:8px; font-weight:900; font-size:14px; min-width:60px; text-align:center;">
                                    ${hora}
                                </div>
                                <div style="flex:1; text-align:left;">
                                    <span style="font-size:10px; color:var(--text2); text-transform:uppercase; display:block;">${isPresente ? '✅ NA UNIDADE' : label}</span>
                                    <b style="color:#fff; font-size:16px; text-transform:uppercase;">${nomeExibir}</b>
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
        },
        chamarFuraFila: async (id) => {
            const p = obterParametrosAtivos();
            if (!p.guiche_id) return alert("Selecione seu guichê primeiro.");
            if (!confirm("Deseja furar a fila e chamar esta senha agora?")) return;

            const res = await fetch('api/chamar.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ senha_id: id, guiche_id: p.guiche_id })
            });
            const json = await res.json();

            if (json.success) {
                BT.toast.sucesso("Fura-fila ativado!");
                atualizarPainel();
            } else {
                alert(json.message);
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
