#!/bin/bash
# 🚀 BRANDÃO TECH - LINUX APPLIANCE PROVISIONER v1.1
# Padrão Industrial Diamond - Fix Port 8080 & Path

COL_BLUE="\033[0;34m"
COL_GREEN="\033[0;32m"
COL_RESET="\033[0m"

echo -e "${COL_BLUE}🏗️  INICIANDO PROVISIONAMENTO BRANDÃO TECH APPLIANCE...${COL_RESET}"

# 1. Instalação de Motores
echo "📦 Instalando motores e dependências..."
apt update -y > /dev/null
apt install -y apache2 php libapache2-mod-php php-sqlite3 php-curl php-mbstring php-gd php-zip php-xml git curl unzip > /dev/null

# 2. Estrutura de Diretórios
echo "📂 Organizando pastas industriais..."
rm -rf /var/www/html/btqueue
mkdir -p /var/www/html/btqueue

# 3. Download do Código (Via Fábrica VM na porta 8080)
echo "📥 Baixando código fonte Diamond..."
curl -L http://192.168.100.245:8080/bt-enterprise.zip -o /tmp/source.zip

if [ -s /tmp/source.zip ] && [ $(stat -c%s /tmp/source.zip) -gt 1000 ]; then
    unzip -o /tmp/source.zip -d /var/www/html/btqueue/ > /dev/null
    echo "✅ Código extraído com sucesso."
else
    echo "⚠️  Fonte local não encontrada na porta 8080, tentando via Git..."
    git clone -b develop https://github.com/Ricardossa/bt-enterprise.git /var/www/html/btqueue/ > /dev/null
fi

# 4. Configuração do Servidor Web (Raiz Limpa)
echo "🎯 Configurando VirtualHost (Totem Mobile Ready)..."
echo "<VirtualHost *:80>
    DocumentRoot /var/www/html/btqueue/public
    <Directory /var/www/html/btqueue/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# 5. Permissões de Segurança
echo "🔐 Selando permissões de diretórios..."
chown -R www-data:www-data /var/www/html/btqueue
chmod -R 755 /var/www/html/btqueue
chmod -R 775 /var/www/html/btqueue/database
chmod -R 775 /var/www/html/btqueue/logs
chmod -R 775 /var/www/html/btqueue/public/uploads

# 6. Reset para Modo Setup
echo "🧹 Resetando estado para ativação de hardware..."
if [ -f /var/www/html/btqueue/database/banco_template.db ]; then
    cp /var/www/html/btqueue/database/banco_template.db /var/www/html/btqueue/database/banco.db
    chown www-data:www-data /var/www/html/btqueue/database/banco.db
    chmod 775 /var/www/html/btqueue/database/banco.db
    rm -f /var/www/html/btqueue/database/.installed
fi

# 7. Ativação de Serviços
echo "⚙️  Ligando motores de sincronismo..."
/usr/sbin/a2enmod rewrite > /dev/null
/usr/sbin/service apache2 restart

echo -e "${COL_GREEN}✅  APPLIANCE INSTALADO COM SUCESSO!${COL_RESET}"
echo "👉 Acesse: http://$(hostname -I | awk '{print $1}')/ para inserir o PIN."
