#!/bin/bash
# 🚀 BRANDÃO TECH - LINUX APPLIANCE INSTALLER v1.0
# Transforma um Debian/Ubuntu em um Servidor de Fila Diamond em segundos.

echo "🏗️ Iniciando instalação industrial da Brandão Tech..."

# 1. Instala dependências
sudo apt update && sudo apt install -y apache2 php php-sqlite3 php-curl php-mbstring php-gd php-zip git curl

# 2. Prepara diretórios
sudo mkdir -p /var/www/html/btqueue
sudo chown -R www-data:www-data /var/www/html/btqueue

# 3. Clone do Sistema (Troque pelo seu link SSH se preferir)
cd /var/www/html/
sudo git clone -b develop https://github.com/Ricardossa/bt-enterprise.git btqueue

# 4. Ajuste de Permissões Críticas
sudo chmod -R 775 /var/www/html/btqueue/database
sudo chmod -R 775 /var/www/html/btqueue/logs
sudo chmod -R 775 /var/www/html/btqueue/public/uploads
sudo chown -R www-data:www-data /var/www/html/btqueue

# 5. Criação do Serviço de Sincronismo (Systemd)
echo "[Unit]
Description=BT Queue MasterSync Service
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php /var/www/html/btqueue/public/pulse.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target" | sudo tee /etc/systemd/system/bt-sync.service

# 6. Ativa os motores
sudo systemctl daemon-reload
sudo systemctl enable bt-sync
sudo systemctl start bt-sync
sudo systemctl restart apache2

echo "✅ INSTALAÇÃO CONCLUÍDA COM SUCESSO!"
echo "👉 Acesse: http://$(hostname -I | awk '{print $1}')/btqueue/public/"
