document.addEventListener("DOMContentLoaded", async () => {

    const empresa = document.getElementById("empresa");
    const logo = document.getElementById("logo");
    const botao = document.getElementById("btnSalvar");
    const preview = document.getElementById("empresaLogoPreview");

    // Carrega configurações existentes
    try {
        const resposta = await fetch("api/configuracoes.php");
        const json = await resposta.json();

        if (json.success && json.data) {
            empresa.value = json.data.empresa || "";
            if (preview) preview.src = BT.api.url('uploads/logo.png?v=' + Date.now());
        }
    } catch (e) {
        console.error("Erro ao carregar configurações.", e);
    }

    if (!botao) return;

    botao.addEventListener("click", async () => {
        try {
            // 1. Salva Nome da Empresa
            const resposta = await fetch("api/configuracoes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    empresa: empresa.value.trim()
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
});
