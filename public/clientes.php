<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;
use BTQueue\Core\Database;

Auth::protegerPagina('ADMIN');

// v7.6.0: Busca Lista de Fornecedores/Parceiros
$clientes = Database::fetchAll("
    SELECT
        c.*,
        (SELECT COUNT(*) FROM senhas s WHERE s.cliente_id = c.id AND s.status = 'FINALIZADA') as total_visitas,
        (SELECT MAX(created_at) FROM senhas s WHERE s.cliente_id = c.id) as ultima_visita
    FROM clientes c
    ORDER BY c.nome ASC
");

$pageTitle = 'Gestão de Fornecedores';
include __DIR__ . '/includes/header.php';
?>

<style>
    .bt-button i { min-width: 18px; text-align: center; margin-right: 8px; display: inline-block; vertical-align: middle; }
    .status-table button i { margin-right: 5px; }
    .bt-button { display: inline-flex !important; align-items: center; justify-content: center; text-transform: uppercase; letter-spacing: 0.5px; }
    .hidden { display: none !important; }

    .bt-modal {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .bt-modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 0;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        overflow: hidden;
    }

    .bt-modal-header {
        padding: 20px;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
    }

    .bt-modal-body { padding: 25px; }
    .bt-modal-footer { padding: 20px; border-top: 1px solid var(--border); background: rgba(0,0,0,0.1); }

    .bt-close {
        background: transparent !important;
        border: none !important;
        color: #888 !important;
        width: auto !important;
        height: auto !important;
        min-width: 0 !important;
        min-height: 0 !important;
        flex: none !important;
        cursor: pointer;
        font-size: 20px;
        padding: 5px !important;
    }
</style>

<main class="bt-main">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h2><i class="fa-solid fa-truck-ramp-box"></i> Gestão de Fornecedores</h2>
            <p style="color:var(--text2); font-size:14px;">Controle de identidades e fluxo de visitas de parceiros.</p>
        </div>
        <div style="display:flex; gap:10px;">
             <button onclick="gerarQrCheckin()" class="bt-button" style="background:var(--primary);">
                <i class="fa-solid fa-qrcode"></i> QR CHECK-IN
            </button>
             <button onclick="abrirModalCliente()" class="bt-button bt-success">
                <i class="fa-solid fa-user-plus"></i> NOVO CADASTRO
            </button>
        </div>
    </div>

    <div class="bt-card">
        <table class="status-table">
            <thead>
                <tr>
                    <th>Fornecedor / Visitante</th>
                    <th>Empresa / Distribuidora</th>
                    <th>WhatsApp</th>
                    <th style="text-align:center;">Total Visitas</th>
                    <th>Última Visita</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <b style="color:#fff;"><?= htmlspecialchars($c['nome']) ?></b>
                    </td>
                    <td>
                        <span class="badge" style="background:rgba(29, 180, 255, 0.1); color:var(--secondary); font-weight:bold;">
                            <?= htmlspecialchars($c['empresa'] ?: 'AVULSO') ?>
                        </span>
                    </td>
                    <td>
                        <a href="https://wa.me/55<?= $c['whatsapp'] ?>" target="_blank" style="color:var(--success); text-decoration:none;">
                            <i class="fa-brands fa-whatsapp"></i> <?= $c['whatsapp'] ?>
                        </a>
                    </td>
                    <td align="center"><b><?= $c['total_visitas'] ?></b></td>
                    <td>
                        <small style="color:var(--text3);">
                            <?= $c['ultima_visita'] ? date('d/m/Y H:i', strtotime($c['ultima_visita'])) : '---' ?>
                        </small>
                    </td>
                    <td align="right">
                        <button onclick='abrirModalCliente(<?= json_encode($c) ?>)' class="bt-button" style="padding:5px 10px; font-size:11px; background:var(--sidebar); border:1px solid var(--border);">
                            <i class="fa-solid fa-pen-to-square"></i> EDITAR
                        </button>
                        <button onclick="excluirCliente(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>')" class="bt-button" style="padding:5px 10px; font-size:11px; background:rgba(255,77,77,0.1); border:1px solid var(--danger); color:var(--danger);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="6" align="center" style="padding:50px; color:var(--text3);">Nenhum fornecedor cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- MODAL: CADASTRAR/EDITAR -->
<div id="modalCliente" class="bt-modal hidden">
    <div class="bt-modal-content">
        <div class="bt-modal-header">
            <h3 id="modalClienteTitle"><i class="fa-solid fa-user-plus"></i> Novo Cadastro</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <input type="hidden" id="cli_id">
            <div class="form-group">
                <label>Nome Completo do Visitante</label>
                <input type="text" id="cli_nome" class="form-control" placeholder="Ex: Marcio Ricardo">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>Empresa / Distribuidora</label>
                <input type="text" id="cli_empresa" class="form-control" placeholder="Ex: Brandão Tech">
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label>WhatsApp</label>
                <input type="tel" id="cli_whatsapp" class="form-control" placeholder="71900000000">
            </div>
        </div>
        <div class="bt-modal-footer">
            <button onclick="salvarCliente()" id="btnSalvarCliente" class="bt-button bt-success" style="width:100%;">SALVAR CADASTRO</button>
        </div>
    </div>
</div>

<!-- MODAL: QR CODE CHECK-IN -->
<div id="modalQrCheckin" class="bt-modal hidden">
    <div class="bt-modal-content" style="text-align:center;">
        <div class="bt-modal-header">
            <h3><i class="fa-solid fa-qrcode"></i> QR Code de Check-in</h3>
            <button onclick="fecharModais()" class="bt-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="bt-modal-body">
            <p style="font-size:13px; color:var(--text2); margin-bottom:20px;">Exponha este código para que fornecedores façam o check-in pelo celular.</p>
            <div id="checkin-qr-area" style="background:#fff; padding:20px; border-radius:15px; display:inline-block; margin-bottom:15px;"></div>
            <div style="background:rgba(0,0,0,0.3); padding:10px; border-radius:10px; font-size:11px; color:var(--secondary); font-family:monospace; margin-bottom:15px;">
                URL: <span id="checkin-url-debug">--</span>
            </div>
            <button onclick="window.print()" class="bt-button" style="width:100%; background:var(--sidebar); border:1px solid var(--border);">
                <i class="fa-solid fa-print"></i> IMPRIMIR
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    function abrirModalCliente(dados = null) {
        if (dados) {
            document.getElementById('modalClienteTitle').innerHTML = '<i class="fa-solid fa-user-pen"></i> Editar Cadastro';
            document.getElementById('cli_id').value = dados.id;
            document.getElementById('cli_nome').value = dados.nome;
            document.getElementById('cli_empresa').value = dados.empresa || '';
            document.getElementById('cli_whatsapp').value = dados.whatsapp;
            document.getElementById('btnSalvarCliente').innerText = 'ATUALIZAR CADASTRO';
        } else {
            document.getElementById('modalClienteTitle').innerHTML = '<i class="fa-solid fa-user-plus"></i> Novo Cadastro';
            document.getElementById('cli_id').value = '';
            document.getElementById('cli_nome').value = '';
            document.getElementById('cli_empresa').value = '';
            document.getElementById('cli_whatsapp').value = '';
            document.getElementById('btnSalvarCliente').innerText = 'FINALIZAR CADASTRO';
        }
        document.getElementById('modalCliente').classList.remove('hidden');
    }

    function fecharModais() {
        document.querySelectorAll('.bt-modal').forEach(m => m.classList.add('hidden'));
    }

    async function salvarCliente() {
        const id = document.getElementById('cli_id').value;
        const nome = document.getElementById('cli_nome').value;
        const empresa = document.getElementById('cli_empresa').value;
        const whatsapp = document.getElementById('cli_whatsapp').value;

        if(!nome || !whatsapp) return alert("Nome e WhatsApp são obrigatórios.");

        try {
            const res = await fetch('api/v1/cliente.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nome, empresa, whatsapp, uuid: null }) // Deixa o UUID ser gerado no server se for novo
            });
            const json = await res.json();
            if (json.success) {
                alert("Cadastro salvo com sucesso!");
                location.reload();
            } else {
                alert(json.message);
            }
        } catch (e) { alert("Erro ao conectar com o servidor."); }
    }

    async function excluirCliente(id, nome) {
        if (!confirm(`Deseja realmente remover o cadastro de ${nome}? Esta aÃ§Ã£o nÃ£o pode ser desfeita.`)) return;

        try {
            const res = await fetch(`api/v1/cliente.php?id=${id}`, {
                method: 'DELETE'
            });
            const json = await res.json();

            if (json.success) {
                alert(json.message);
                location.reload();
            } else {
                alert(json.message);
            }
        } catch (e) {
            alert("Falha ao comunicar com o servidor.");
        }
    }

    function gerarQrCheckin() {
        const qrArea = document.getElementById('checkin-qr-area');
        const debugUrl = document.getElementById('checkin-url-debug');

        const pathCheckin = window.location.pathname.replace('clientes.php', 'fidelidade/');
        const currentUrl = window.location.origin + pathCheckin;

        debugUrl.innerText = currentUrl;

        try {
            const qr = qrcode(0, 'H');
            qr.addData(currentUrl);
            qr.make();
            qrArea.innerHTML = qr.createImgTag(8);
            document.getElementById('modalQrCheckin').classList.remove('hidden');
        } catch (e) { alert("Erro ao gerar QR Code."); }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
