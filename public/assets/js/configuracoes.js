document.addEventListener("DOMContentLoaded", async () => {

    const empresa = document.getElementById("empresa");
    const label_cliente = document.getElementById("label_cliente");
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
            if (preview) preview.src = BT.api.url('uploads/logo.png?v=' + Date.now());
        }
    } catch (e) {
        console.error("Erro ao carregar configurações.", e);
    }

    if (!botao) return;

    botao.addEventListener("click", async () => {
        try {
            // 1. Salva Nome da Empresa e Rótulo
            const resposta = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    empresa: empresa.value.trim(),
                    label_cliente: label_cliente ? label_cliente.value.trim() : "Paciente"
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
