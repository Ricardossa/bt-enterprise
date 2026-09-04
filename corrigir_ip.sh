#!/bin/bash

echo "🔧 CORRIGINDO CONFIGURAÇÃO DA IA"
echo "═══════════════════════════════════════════════════════"

# IP correto
IP="192.168.100.139"
PORTA="11434"
URL="http://${IP}:${PORTA}/api/generate"
MODEL="qwen2.5:3b"

echo ""
echo "📡 IA encontrada em: ${IP}:${PORTA}"
echo "📦 Modelo: ${MODEL}"
echo ""

# Faz backup
echo "📁 Fazendo backup..."
cp core/AIService.php core/AIService.php.bak_$(date +%Y%m%d_%H%M%S)

# Corrige o arquivo
echo "✏️  Corrigindo AIService.php..."
sed -i "s|private string \$model = .*|private string \$model = '$MODEL';|" core/AIService.php
sed -i "s|http://[0-9.]*:11434/api/generate|$URL|g" core/AIService.php

echo "✅ Arquivo corrigido"

# Atualiza o banco
echo ""
echo "📊 Atualizando banco de dados..."
sqlite3 database/bt_queue.db "INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('ai_url', '$URL');"

echo "✅ Banco atualizado"

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✅ CORREÇÃO CONCLUÍDA!"
echo "   URL: $URL"
echo "   Modelo: $MODEL"
echo ""

echo "🧪 Testando..."
php -r "
require_once 'core/AIService.php';
\$service = new BTQueue\Core\AIService();
\$result = \$service->generateDailyReport(['emitidas'=>5,'chamadas'=>4,'finalizadas'=>3,'pendentes'=>2]);
echo \"Resposta:\\n\";
echo \$result . \"\\n\";
"
