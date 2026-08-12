document.addEventListener("DOMContentLoaded", async () => {

    const empresa = document.getElementById("empresa");
    const label_cliente = document.getElementById("label_cliente");
    const wa_enabled = document.getElementById("whatsapp_enabled");
    const wa_url = document.getElementById("whatsapp_api_url");
    const wa_token = document.getElementById("whatsapp_api_token");
    const prio_mode = document.getElementById("priority_mode");
    const prio_ratio = document.getElementById("priority_ratio");
    const prio_selection = document.getElementById("feature_priority_selection");
    const logo = document.getElementById("logo");
    const botao = document.getElementById("btnSalvar");
    const preview = document.getElementById("empresaLogoPreview");

    // Carrega configurações existentes
    try {
        const resposta = await fetch("api/configuracoes.php");
        const json = await resposta.json();

        if (json.success && json.data) {
            empresa.value = json.data.empresa || "";
            if (label_cliente) label_cliente.value = json.data.label_cliente || "Paciente";
            if (wa_enabled) wa_enabled.value = json.data.whatsapp_enabled || "0";
            if (wa_url) wa_url.value = json.data.whatsapp_api_url || "";
            if (wa_token) wa_token.value = json.data.whatsapp_api_token || "";
            if (prio_mode) prio_mode.value = json.data.priority_mode || "STRICT";
            if (prio_ratio) prio_ratio.value = json.data.priority_ratio || "3";
            if (prio_selection) prio_selection.value = json.data.feature_priority_selection || "1";

            // Toggle inicial da proporção
            const ratioContainer = document.getElementById('priority_ratio_container');
            if (ratioContainer && prio_mode) {
                ratioContainer.style.display = (prio_mode.value === 'BALANCED') ? 'block' : 'none';
            }

            if (preview) preview.src = BT.api.url('uploads/logo.png?v=' + Date.now());
        }
    } catch (e) {
        console.error("Erro ao carregar configurações.", e);
    }

    if (!botao) return;

    if (prio_mode) {
        prio_mode.addEventListener('change', () => {
            const container = document.getElementById('priority_ratio_container');
            if (container) container.style.display = (prio_mode.value === 'BALANCED') ? 'block' : 'none';
        });
    }

    botao.addEventListener("click", async () => {
        try {
            // 1. Salva Nome da Empresa, Rótulo e WhatsApp (v6.6)
            const resposta = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    empresa: empresa.value.trim(),
                    label_cliente: label_cliente ? label_cliente.value.trim() : "Paciente",
                    whatsapp_enabled: wa_enabled ? wa_enabled.value : "0",
                    whatsapp_api_url: wa_url ? wa_url.value.trim() : "",
                    whatsapp_api_token: wa_token ? wa_token.value.trim() : "",
                    priority_mode: prio_mode ? prio_mode.value : "STRICT",
                    priority_ratio: prio_ratio ? prio_ratio.value : "3",
                    feature_priority_selection: prio_selection ? prio_selection.value : "1"
                })
            });

            const json = await resposta.json();
            if (!json.success) return alert(json.message || "Erro ao salvar empresa.");

            // 2. Upload da Logo Admin
            if (logo.files.length > 0) {
                const form = new FormData();
                form.append("logo", logo.files[0]);
                form.append("tipo", "admin");

                const upload = await fetch("api/upload_logo.php", {
                    method: "POST",
                    body: form
                });

                const resultado = await upload.json();
                if (!resultado.success) return alert(resultado.message);
            }

            alert("Configurações salvas com sucesso!");
            location.reload();

        } catch (e) {
            console.error(e);
            alert("Erro ao salvar configurações.");
        }
    });

    // --- LÓGICA DE BACKUP ---
    const btnBackup = document.getElementById("btnFazerBackup");
    if (btnBackup) {
        btnBackup.addEventListener("click", async () => {
            if (!confirm("Isso enviará uma cópia do banco de dados para a Master. Continuar?")) return;

            btnBackup.disabled = true;
            btnBackup.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENVIANDO...';

            try {
                const res = await fetch("backup.php");
                const json = await res.json();

                if (json.success) {
                    alert("✅ Backup realizado com sucesso!");
                } else {
                    alert("❌ Erro no backup: " + json.message);
                }
            } catch (err) {
                alert("❌ Falha crítica na conexão de backup.");
            } finally {
                btnBackup.disabled = false;
                btnBackup.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> REALIZAR BACKUP AGORA';
                location.reload();
            }
        });
    }
});
