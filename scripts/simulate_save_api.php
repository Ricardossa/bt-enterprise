<?php
$url = "http://localhost:8090/api/v1/agenda.php?action=save_regras";
$payload = json_encode([
    'servico_id' => 1,
    'regras' => [
        ['dia_semana' => 0, 'hora_inicio' => '10:00', 'hora_fim' => '20:00', 'duracao_slot' => 30, 'ativo' => 1]
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
// Mock session cookie if needed, but since I'm on CLI, I'll bypass Auth in a temp script if I have to.
// Let's see what it returns without auth first.

$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP CODE: $code\n";
echo "RESPONSE: $res\n";
?>
