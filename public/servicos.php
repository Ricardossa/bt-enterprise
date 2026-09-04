<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Auth;

Auth::protegerPagina('ADMIN');

$pageTitle = 'Serviços';

include __DIR__ . '/includes/header.php';
?>

<main class="bt-main">

<h2>👨‍💼 Gerenciar Serviços</h2>

<div class="bt-card">

<table class="status-table">

<thead>

<tr>

<th>Código</th>
<th>Serviço</th>
<th>Valor</th>
<th>Ações</th>

</tr>

</thead>

<tbody id="listaServicos">

</tbody>

</table>

<br>

<button id="btnNovo" class="bt-button bt-primary">

➕ Novo Serviço

</button>

</div>

</main>


<script src="assets/js/api.js?v=1"></script>
<script src="assets/js/config.js?v=1"></script>
<script src="assets/js/modal.js?v=1"></script>
<script src="assets/js/servicos.js?v=2"></script>

<?php
include __DIR__ . '/includes/footer.php';
?>
