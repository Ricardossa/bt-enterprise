#!/bin/bash
# ==========================================================
# 🚀 BRANDÃO TECH APPLIANCE INSTALLER v2.0 (Diamond Elite)
# ==========================================================

set -e

APP_NAME="BT Queue Enterprise"
INSTALL_DIR="/var/www/html/btqueue"
# Fábrica local (VM Master)
MASTER_IP="192.168.100.245"
DOWNLOAD_URL="http://$MASTER_IP:8080/bt-enterprise.zip"
ZIP_FILE="/tmp/bt-enterprise.zip"

COL_RED="\033[0;31m"
COL_GREEN="\033[0;32m"
COL_YELLOW="\033[1;33m"
COL_BLUE="\033[0;34m"
COL_RESET="\033[0m"

msg() { echo -e "${COL_BLUE}$1${COL_RESET}"; }
ok()  { echo -e "${COL_GREEN}✔ $1${COL_RESET}"; }
warn(){ echo -e "${COL_YELLOW}⚠ $1${COL_RESET}"; }
erro(){ echo -e "${COL_RED}✖ $1${COL_RESET}"; exit 1; }

banner() {
    clear
    echo -e "${COL_BLUE}==================================================="
    echo "      BRANDÃO TECH APPLIANCE INSTALLER v2.0"
    echo "==================================================="
    echo " Produto     : $APP_NAME"
    echo " Plataforma  : Debian 13 / Linux"
    echo "===================================================${COL_RESET}"
}

check_root() { [ "$EUID" -ne 0 ] || return 0; erro "Execute como root."; }

check_network() {
    msg "🌐 Testando conectividade com a Fábrica..."
    if ping -c1 $MASTER_IP >/dev/null 2>&1; then
        ok "Conexão com a Fábrica estabelecida."
    else
        erro "Não foi possível acessar a VM Master ($MASTER_IP)."
    fi
}

install_dependencies() {
    msg "📦 Instalando motores..."
    apt update -y > /dev/null
    apt install -y apache2 php libapache2-mod-php php-sqlite3 php-curl php-mbstring php-gd php-zip php-xml sqlite3 curl unzip rsync > /dev/null
    ok "Dependências instaladas."
}

prepare_installation() {
    msg "📂 Preparando ambiente..."
    if [ -d "$INSTALL_DIR/public" ]; then
        warn "Instalação existente encontrada."
        echo -e "1) Atualizar\n2) Reparar\n3) Reinstalar (LIMPA)\n4) Cancelar"
        read -p "Escolha: " OPTION
        case $OPTION in
            1) INSTALL_MODE="UPDATE" ;;
            2) INSTALL_MODE="REPAIR" ;;
            3) INSTALL_MODE="NEW"; rm -rf "$INSTALL_DIR"; mkdir -p "$INSTALL_DIR" ;;
            *) erro "Operação cancelada." ;;
        esac
    else
        INSTALL_MODE="NEW"
        mkdir -p "$INSTALL_DIR"
    fi
}

download_package() {
    msg "📥 Baixando pacote oficial da Fábrica..."
    curl -L --fail --progress-bar "$DOWNLOAD_URL" -o "$ZIP_FILE"
    ok "Pacote baixado."
}

extract_package() {
    msg "📦 Extraindo arquivos..."
    unzip -oq "$ZIP_FILE" -d "$INSTALL_DIR"
    ok "Arquivos extraídos."
}

configure_system() {
    msg "🌐 Configurando VirtualHost (Standard)..."
    if [ -f "$INSTALL_DIR/install/apache.conf" ]; then
        cp "$INSTALL_DIR/install/apache.conf" /etc/apache2/sites-available/000-default.conf
    else
        warn "Arquivo install/apache.conf não encontrado. Usando fallback."
    fi
    /usr/sbin/a2enmod rewrite > /dev/null

    msg "🔐 Aplicando permissões industriais..."
    chown -R www-data:www-data "$INSTALL_DIR"
    find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
    find "$INSTALL_DIR" -type f -exec chmod 644 {} \;
    chmod -R 775 "$INSTALL_DIR/database" "$INSTALL_DIR/logs" "$INSTALL_DIR/public/uploads"

    msg "🗄️ Inicializando banco template..."
    DB="$INSTALL_DIR/database/banco.db"
    if [ "$INSTALL_MODE" = "NEW" ] && [ ! -f "$DB" ]; then
        cp "$INSTALL_DIR/database/banco_template.db" "$DB"
        rm -f "$INSTALL_DIR/database/.installed"
    fi
    chown www-data:www-data "$DB"
    chmod 775 "$DB"

    msg "🤖 Instalando BT Doctor..."
    ln -sf "$INSTALL_DIR/scripts/bt-doctor.php" /usr/local/bin/bt-doctor
    chmod +x "$INSTALL_DIR/scripts/bt-doctor.php"

    msg "🛰️ Configurando MasterSync Service..."
    echo "[Unit]
Description=BT Queue MasterSync Service
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php $INSTALL_DIR/public/pulse.php
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target" > /etc/systemd/system/bt-sync.service
    systemctl daemon-reload
    systemctl enable bt-sync
    systemctl start bt-sync
}

main() {
    check_root
    banner
    check_network
    install_dependencies
    prepare_installation
    download_package
    extract_package
    configure_system
    systemctl restart apache2
    ok "INSTALAÇÃO CONCLUÍDA!"
    msg "👉 Acesse: http://$(hostname -I | awk '{print $1}')/"
    msg "👉 Suporte: Digite 'bt-doctor' no terminal."
}

main
