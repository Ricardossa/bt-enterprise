<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * Motor de Integração de Inteligência Artificial - BT Diamond AI
 * Conecta a Enterprise ao cérebro local no TrueNAS Xeon.
 */
final class AIService
{
    private string $ollamaUrl;
    private string $model = 'llama3.2';

    public function __construct()
    {
        // IP Real do seu TrueNAS Xeon (v6.6.1 Fix)
        $this->ollamaUrl = 'http://192.168.100.250:11434/api/generate';
    }

    /**
     * Envia uma pergunta ou conjunto de dados para a IA e recebe um Insight.
     */
    public function ask(string $prompt, bool $isDataAnalysis = false): string
    {
        try {
            $context = $isDataAnalysis
                ? "Você é o analista de produtividade da Brandão Tech. Analise os seguintes dados de fila e seja conciso no diagnóstico: "
                : "";

            $payload = json_encode([
                'model' => $this->model,
                'prompt' => $context . $prompt,
                'stream' => false // Queremos a resposta completa de uma vez
            ]);

            $ch = curl_init($this->ollamaUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // O Xeon pode demorar um pouco mais que uma GPU

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("IA fora de área (HTTP $httpCode)");
            }

            $json = json_decode($response, true);
            return $json['response'] ?? "O cérebro não soube responder.";

        } catch (\Throwable $e) {
            Logger::error("Falha na ponte AI: " . $e->getMessage());
            return "Erro ao consultar a Inteligência Artificial.";
        }
    }

    /**
     * Gera um resumo automático de produtividade do dia para o Gerente.
     */
    public function generateDailyReport(array $stats): string
    {
        $prompt = "Dados de hoje: Total de senhas: {$stats['emitidas']}, Atendidas: {$stats['finalizadas']}, Faltas: " . ($stats['emitidas'] - $stats['finalizadas']) . ". Qual sua análise?";
        return $this->ask($prompt, true);
    }
}
