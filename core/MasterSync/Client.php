<?php

declare(strict_types=1);

namespace BTQueue\Core\MasterSync;

use Exception;

/**
 * Abstração de comunicação HTTP para a Platform Master.
 */
final class Client
{
    private string $url;
    private int $timeout;

    public function __construct(string $url, int $timeout = 10)
    {
        $this->url = $url;
        $this->timeout = $timeout;
    }

    /**
     * Envia um payload JSON e retorna a resposta decodificada.
     */
    public function post(array $payload): array
    {
        $json = json_encode($payload);

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Falha na conexão com a Platform Master: $error");
        }

        if ($httpCode >= 400) {
            throw new Exception("Platform Master retornou erro HTTP $httpCode: $response");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Platform Master retornou um JSON inválido.");
        }

        return $data;
    }
}
