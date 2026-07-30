<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

// Se já estiver logado, redireciona para o destino certo
if (Auth::autenticado()) {
    $op = Auth::operador();
    $destino = ($op['nivel'] === 'ADMIN') ? 'dashboard.php' : 'operador.php';
    header("Location: $destino");
    exit;
}

$pageTitle = 'Login do Sistema';
include __DIR__ . '/includes/header.php';
?>

<main class="bt-main">
    <section class="bt-card" style="max-width:420px; margin:60px auto; border-radius: 30px;">
        <div style="text-align:center; margin-bottom:30px;">
            <i class="fa-solid fa-shield-halved" style="font-size: 40px; color: var(--secondary); margin-bottom:15px;"></i>
            <h2 style="font-weight:900;">Acesso Restrito</h2>
            <p style="color:var(--text2); font-size:14px;">Identifique-se para acessar o painel.</p>
        </div>

        <div class="form-group">
            <label>Login</label>
            <input type="text" id="login" class="form-control" autocomplete="username" placeholder="Seu usuário">
        </div>

        <div class="form-group" style="margin-top:20px;">
            <label>Senha</label>
            <input type="password" id="senha" class="form-control" autocomplete="current-password" placeholder="••••••••">
        </div>

        <button id="btnEntrar" class="bt-button bt-primary" style="width:100%; margin-top:30px; border-radius: 15px; font-weight:bold; padding:18px;">
            <i class="fa-solid fa-right-to-bracket"></i> ENTRAR NO SISTEMA
        </button>

        <div id="msg" style="margin-top:25px; text-align:center; color:var(--danger); font-weight:bold; font-size:13px;"></div>
    </section>
</main>

<script>
document.getElementById('btnEntrar').onclick = async () => {
    const login = document.getElementById('login').value.trim();
    const senha = document.getElementById('senha').value;
    const btn = document.getElementById('btnEntrar');

    if (!login || !senha) {
        document.getElementById('msg').innerText = "Informe login e senha.";
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> AUTENTICANDO...';

    try {
        const resposta = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ login, senha })
        });

        const json = await resposta.json();

        if (json.success) {
            // REDIRECIONAMENTO INTELIGENTE POR PERFIL
            const nivel = json.operador.nivel;
            window.location = (nivel === 'ADMIN') ? 'dashboard.php' : 'operador.php';
        } else {
            document.getElementById('msg').innerText = json.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> ENTRAR NO SISTEMA';
        }
    } catch (e) {
        document.getElementById('msg').innerText = "Falha crítica de comunicação.";
        btn.disabled = false;
    }
};

// Enter key trigger
document.addEventListener('keypress', (e) => {
    if(e.key === 'Enter') document.getElementById('btnEntrar').click();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
