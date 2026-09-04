#!/bin/bash

echo "🔧 ATUALIZANDO FUNÇÃO generateDailyReport"
echo "═══════════════════════════════════════════════════════"

# Faz backup
cp core/AIService.php core/AIService.php.bak_$(date +%Y%m%d_%H%M%S)
echo "✅ Backup criado"

# Cria a nova função em um arquivo temporário
cat > /tmp/nova_funcao.txt << 'EOF'
    public function generateDailyReport(array $stats): string
    {
        $emitidas = (int)($stats['emitidas'] ?? 0);
        $chamadas = (int)($stats['chamadas'] ?? 0);
        $finalizadas = (int)($stats['finalizadas'] ?? 0);
        $pendentes = (int)($stats['pendentes'] ?? 0);

        $taxaConclusao = $emitidas > 0 
            ? round(($finalizadas / $emitidas) * 100, 1) 
            : 0;

        if ($pendentes > 0) {
            $situacao = "Há {$pendentes} senha(s) pendente(s) de atendimento.";
        } else {
            $situacao = "Não há senhas pendentes de atendimento.";
        }

        $prompt = <<<PROMPT
Você é o AI Core do BT Queue Enterprise da Brandão Tech.

ANALISE EXCLUSIVAMENTE OS DADOS FORNECIDOS.

DADOS REAIS:
- Senhas emitidas: {$emitidas}
- Senhas chamadas: {$chamadas}
- Senhas finalizadas: {$finalizadas}
- Senhas pendentes: {$pendentes}
- Taxa de conclusão: {$taxaConclusao}%

FATO DETERMINADO PELO SISTEMA:
{$situacao}

REGRAS OBRIGATÓRIAS (NÃO QUEBRE ESTAS REGRAS):
1. USE SOMENTE os dados fornecidos acima
2. NÃO invente números que não estão nos dados
3. NÃO mencione "falta", "faltas" ou "clientes não atendidos"
4. NÃO mencione funcionários, atendentes ou equipe
5. NÃO invente causas para os números
6. NÃO invente tempos de atendimento
7. NÃO invente gargalos ou problemas operacionais
8. NÃO faça recomendações genéricas
9. NÃO calcule nada além do que já foi fornecido
10. NÃO use adjetivos emocionais (ótimo, péssimo, etc)
11. A taxa de conclusão é APENAS um número, não infira problemas
12. Se não houver dados para uma conclusão, diga isso claramente

RESPONDA EXATAMENTE NESTE FORMATO (use os títulos exatamente como estão):

SITUAÇÃO:
[Uma frase objetiva descrevendo APENAS o que os números mostram]

ATENÇÃO:
[Uma frase apontando APENAS o que está pendente ou precisa de acompanhamento]

RECOMENDAÇÃO:
[Uma recomendação prática e direta baseada APENAS nos dados]

EXEMPLO DE RESPOSTA CORRETA:
SITUAÇÃO:
Os dados mostram 2 senhas emitidas, com 1 finalizada e 1 pendente, resultando em 50% de conclusão.

ATENÇÃO:
Há 1 senha pendente aguardando atendimento.

RECOMENDAÇÃO:
Finalizar a senha pendente para completar o atendimento do dia.

SUA RESPOSTA DEVE SEGUIR EXATAMENTE ESTE FORMATO. NÃO INVENTE INFORMAÇÕES.
PROMPT;

        return $this->ask($prompt, true);
    }
EOF

# Substitui a função no arquivo usando Python (mais seguro)
python3 << 'PYTHON_SCRIPT'
import re

# Lê o arquivo
with open('core/AIService.php', 'r') as f:
    content = f.read()

# Lê a nova função
with open('/tmp/nova_funcao.txt', 'r') as f:
    nova_funcao = f.read()

# Padrão para encontrar a função generateDailyReport
pattern = r'public function generateDailyReport\(array \$stats\): string\s*\{.*?\n\s*\}'
 
# Substitui
new_content = re.sub(pattern, nova_funcao, content, flags=re.DOTALL)

# Salva
with open('core/AIService.php', 'w') as f:
    f.write(new_content)

print("✅ Função atualizada com sucesso!")
PYTHON_SCRIPT

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✅ ATUALIZAÇÃO CONCLUÍDA!"

# Testa
echo ""
echo "🧪 Testando..."
php -r "
require_once 'core/AIService.php';
\$service = new BTQueue\Core\AIService();
\$result = \$service->generateDailyReport(['emitidas'=>2,'chamadas'=>1,'finalizadas'=>1,'pendentes'=>1]);
echo \"Resposta:\\n\";
echo \$result . \"\\n\";
"
