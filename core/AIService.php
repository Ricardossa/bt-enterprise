<?php

declare(strict_types=1);

namespace BTQueue\Core;

use Exception;

/**
 * Motor de Integracao de Inteligencia Artificial - BT Diamond AI
 * Conecta a Enterprise ao cerebro local no TrueNAS Xeon.
 */
final class AIService
{
    private string $ollamaUrl;
    private string $model = 'qwen2.5:3b';

    public function __construct()
    {
        try {
            // Busca a URL da IA configurada no banco
            $config = Database::fetch("SELECT valor FROM configuracoes WHERE chave = 'ai_url' LIMIT 1");
            $this->ollamaUrl = $config ? $config['valor'] : 'http://192.168.100.139:11434/api/generate';
        } catch (\Throwable $e) {
            // Fallback se o banco falhar
            $this->ollamaUrl = 'http://192.168.100.139:11434/api/generate';
        }
    }

    /**
     * Envia uma pergunta ou conjunto de dados para a IA e recebe um Insight.
     */
    public function ask(string $prompt, bool $isDataAnalysis = false): string
    {
        try {
            $context = $isDataAnalysis
                ? "Voce e o analista de produtividade da Brandao Tech. Analise os seguintes dados de fila e seja conciso no diagnostico: "
                : "";

            $payload = json_encode([
                'model' => $this->model,
                'prompt' => $context . $prompt,
                'stream' => false,
                'options' => [
                    'num_predict' => 300,
                    'temperature' => 0.3
                ]
            ]);

            $ch = curl_init($this->ollamaUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false || $error !== '') {
                throw new Exception("Falha de comunicacao com a IA: " . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception("IA fora de area (HTTP $httpCode)");
            }

            $json = json_decode($response, true);

            if (!is_array($json)) {
                throw new Exception("Resposta invalida da IA.");
            }

            return $json['response'] ?? "O cerebro nao soube responder.";

        } catch (\Throwable $e) {
            Logger::error("Falha na ponte AI: " . $e->getMessage());
            return "Erro ao consultar a Inteligencia Artificial.";
        }
    }

    /**
     * Gera um resumo automatico de produtividade do dia para o Gerente.
     */
    public function generateDailyReport(array $stats): string
    {
        $emitidas = (int)($stats['emitidas'] ?? 0);
        $chamadas = (int)($stats['chamadas'] ?? 0);
        $finalizadas = (int)($stats['finalizadas'] ?? 0);
        $pendentes = (int)($stats['pendentes'] ?? 0);

        $taxaConclusao = (int)($stats['taxa_conclusao'] ?? (
            $emitidas > 0 ? round(($finalizadas / $emitidas) * 100) : 0
        ));

        $esperaMedia = (int)($stats['espera_media'] ?? 0);
        $pico = $stats['pico_movimento'] ?? 'Não disponível';
        $totalAtendentes = (int)($stats['total_atendentes'] ?? 0);
        $melhorOperador = $stats['melhor_operador'] ?? 'Não disponível';
        $tempoAtendimento = (int)($stats['tempo_atendimento'] ?? 0);
        $servicoLento = $stats['servico_mais_lento'] ?? 'Não disponível';

        $prompt = <<<PROMPT
Você é o AI Core do BT Queue Enterprise da Brandão Tech.

Analise exclusivamente os dados fornecidos.

DADOS:
- Senhas emitidas: {$emitidas}
- Senhas chamadas: {$chamadas}
- Senhas finalizadas: {$finalizadas}
- Senhas pendentes: {$pendentes}
- Taxa de conclusão: {$taxaConclusao}%
- Pico de movimento: {$pico}
- Espera média até a chamada: {$esperaMedia} minutos
- Total de atendentes identificados: {$totalAtendentes}
- Operador com maior quantidade de finalizações: {$melhorOperador}
- Tempo médio de atendimento: {$tempoAtendimento} minutos
- Serviço com maior tempo médio emissão → finalização: {$servicoLento}

REGRAS ABSOLUTAS:

1. Use somente os números e informações fornecidos.
2. Nunca invente fatos, funcionários, faltas, causas, gargalos, horários, tempos ou eventos.
3. Senha pendente NÃO significa falta de funcionário.
4. Taxa de conclusão abaixo de 100% NÃO prova baixa produtividade.
5. Espera elevada NÃO prova gargalo ou problema de equipe.
6. Nunca recomende contratar, adicionar ou retirar funcionários sem dados específicos que comprovem essa necessidade.
7. Nunca atribua uma causa para um problema se a causa não estiver nos dados.
8. "Pico de movimento" representa somente o horário de maior volume de emissões. Não é duração.
9. "Operador com maior quantidade de finalizações" representa somente quantidade. Não significa que seja o melhor profissional.
10. Espera média é o tempo entre emissão e chamada.
11. Tempo médio de atendimento é o tempo entre chamada e finalização.
12. Serviço com maior tempo médio emissão → finalização não informa a causa desse tempo.
13. Não transforme correlação ou possibilidade em fato.
14. Não faça recomendações genéricas.
15. Só recomende uma ação quando existir uma ação diretamente justificável pelos dados.
16. Se os dados não permitirem descobrir a causa, escreva exatamente:
"Os dados disponíveis não permitem determinar a causa."
17. Se não houver base suficiente para recomendar mudança operacional, escreva:
"Os dados disponíveis não são suficientes para recomendar uma mudança operacional."
18. Seja objetivo. Não repita todos os dados desnecessariamente.
19. Não use linguagem alarmista.

FORMATO OBRIGATÓRIO:

SITUACAO:
Descreva somente os fatos observados.

ATENCAO:
Informe somente os indicadores que realmente merecem atenção. Se não houver evidência suficiente para determinar um problema, deixe isso claro.

RECOMENDACAO:
Faça somente uma recomendação diretamente sustentada pelos dados. Caso não seja possível, informe que os dados disponíveis não são suficientes para recomendar uma mudança operacional.

Responda somente nesses três blocos.
PROMPT;

        return $this->ask($prompt, false);
    }
}
