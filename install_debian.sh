#!/usr/bin/bash
#-------------------------------
#|          Settings           |
#-------------------------------

#-------------------------------
# The hostname used to identify this machine on the local network.
#-------------------------------
HOSTNAME="captivenet"

#-------------------------------
# The static IP address assigned to this device for network communication.
#-------------------------------
HOST_ADDRESS="192.168.4.1"

#-------------------------------
# The maximum number of clients that can be addressed in the subnet (e.g., /24 = 254)
#-------------------------------
MAX_HOSTS=100

#-------------------------------
# The SSID name broadcasted by the Wi-Fi access point.
#-------------------------------
HOTSPOT_SSID="CaptiveNet Hotspot"

#-------------------------------
# The wireless band used for the hotspot, typically 'bg' for 2.4GHz or 'a' for 5GHz.
#-------------------------------
HOTSPOT_BAND="bg"

#-------------------------------
# The wireless channel number used for the hotspot signal.
#-------------------------------
HOTSPOT_CHANNEL=11

#-------------------------------
# The MariaDB username used by the application to access the database.
#-------------------------------
MARIADB_APP_USER="app"
#-------------------------------
# The name of the MariaDB database used by the application.
#-------------------------------
MARIADB_APP_DATABASE="app_database"

#-------------------------------
# Which path should the passwords database be stored in ?
#-------------------------------
PASSWORDS_DATABASE_PATH="/root"

#-------------------------------
#|          Functions          |
#-------------------------------
set -e
set -u

BASE_PATH=$(dirname "$(readlink -f "$0")")

FG_GRAY="\033[1;90m"
FG_RED="\033[1;31m"
FG_GREEN="\033[1;32m"
FG_PURPLE="\033[35m"
FG_CYAN="\033[1;36m"
BOLD="\033[1m"
UNDERLINE="\033[4m"
RESET="\033[0m"

echo_frame() {
    local LINES=("$@")
    local MAX_LINE_WIDTH=0
    local ANSI_REGEX=$'\033\[[0-9;]*[mK]'

    for LINE in "${LINES[@]}"; do 
        local STRIPPED_LINE
        STRIPPED_LINE=$(printf "$LINE" | sed "s/$ANSI_REGEX//g")
        local LINE_WIDTH=${#STRIPPED_LINE}
        (( LINE_WIDTH > MAX_LINE_WIDTH )) && MAX_LINE_WIDTH=$LINE_WIDTH
    done

    printf "${FG_GRAY}╭%s╮${RESET}\n" "$(printf "%${MAX_LINE_WIDTH}s" | tr ' ' "-")"

    for LINE in "${LINES[@]}"; do   
        local STRIPPED_LINE
        STRIPPED_LINE=$(printf "$LINE" | sed "s/$ANSI_REGEX//g")
        local LINE_WIDTH=${#STRIPPED_LINE}
        local PADDING=$((MAX_LINE_WIDTH - LINE_WIDTH))
        printf "${FG_GRAY}│${RESET}" 
        printf "$LINE${RESET}"
        printf "%*s" "$PADDING" ""
        printf "${FG_GRAY}│${RESET}\n"
    done
    
    printf "${FG_GRAY}╰%s╯${RESET}\n" "$(printf "%${MAX_LINE_WIDTH}s" | tr ' ' "-")"
}

PASSWORDS_VAULT_FILE=$(realpath "$PASSWORDS_DATABASE_PATH/passwords.db")

init_password_vault() { 
    printf "${FG_PURPLE}Setting up passwords table...${RESET}\n" 
    local TABLE_NAME=
    TABLE_NAME=$(sudo sqlite3 "$PASSWORDS_VAULT_FILE" "SELECT name FROM sqlite_master WHERE type='table' AND name='passwords';" 2>/dev/null)
    if [[ "$TABLE_NAME" != "passwords" ]]; then
        sudo sqlite3 "$PASSWORDS_VAULT_FILE" <<EOF
CREATE TABLE IF NOT EXISTS passwords (
    key TEXT PRIMARY KEY,
    password TEXT NOT NULL
);
PRAGMA journal_mode=WAL;
PRAGMA synchronous=NORMAL;
PRAGMA locking_mode=EXCLUSIVE;
EOF
        printf "${FG_GREEN}Passwords table created${RESET}\n" 
    else
        printf "${FG_PURPLE}Passwords table exists, skipping creation...${RESET}\n" 
    fi
}

get_or_add_password_in_password_vault() {
    local PASSWORD_KEY="$1"
    local ESCAPED_PASSWORD_KEY
    ESCAPED_PASSWORD_KEY="${PASSWORD_KEY//\'/\'\'}"
    local PASSWORD_VALUE
    PASSWORD_VALUE=$(sudo sqlite3 -batch -noheader "$PASSWORDS_VAULT_FILE" "SELECT password FROM passwords WHERE key = '$ESCAPED_PASSWORD_KEY'")
    if [[ -z "$PASSWORD_VALUE" ]]; then
        PASSWORD_VALUE=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9' | head -c20)
        local ESCAPED_PASSWORD_VALUE
        ESCAPED_PASSWORD_VALUE="${PASSWORD_VALUE//\'/\'\'}"
        sudo sqlite3 "$PASSWORDS_VAULT_FILE" "INSERT INTO passwords (key, password) VALUES ('$ESCAPED_PASSWORD_KEY', '$ESCAPED_PASSWORD_VALUE');"
    fi
    echo "$PASSWORD_VALUE"
}

add_cron_job() {
    local CRON_JOB_ENTRY="$1"
    sudo crontab -l 2>/dev/null | grep -Fxq "$CRON_JOB_ENTRY" && return
    (sudo crontab -l 2>/dev/null; echo "$CRON_JOB_ENTRY") | sudo crontab -
}

#-------------------------------
#|             Code            |
#-------------------------------

printf "${FG_CYAN}\n"
echo " ____             _   _           _   _      _   "
echo "/ ___|__  _ _ __ | |_(_)_   _____| \ | | ___| |_ "
echo "| |  /  _\` | '_ \| __| \ \ / / _ \  \| |/ _ \ __|"
echo "| |__| (_| | |_) | |_| |\ V /  __/ |\  |  __/ |_ "
echo "\____\___,_| .__/ \__|_| \_/ \___|_| \_|\___|\__|"
echo "           |_|                                   "
printf "${FG_PURPLE}==========Installation Setup for Debian==========\n"
printf "${RESET}\n"

OS_ID=""
OS_VERSION_ID=""
PRETTY_NAME=""

if [ -f /etc/os-release ]; then
    while IFS= read -r line; do
        case "$line" in
            ID=*) OS_ID=$(echo "$line" | cut -d= -f2 | tr -d '"') ;;
            VERSION_ID=*) OS_VERSION_ID=$(echo "$line" | cut -d= -f2 | tr -d '"') ;;
            PRETTY_NAME=*) PRETTY_NAME=$(echo "$line" | cut -d= -f2- | tr -d '"') ;;
        esac
    done < /etc/os-release
    if [ "$OS_ID" != "debian" ] || [ "$OS_VERSION_ID" != "12" ]; then
        printf "${FG_RED}${FG_BOLD}This script is only compatible with Debian 12 (detected: $PRETTY_NAME). Aborting...${RESET}\n"
        exit 1
    fi
else
    printf "${FG_RED}${FG_BOLD}Cannot detect your OS version (missing /etc/os-release). Aborting...${RESET}\n"
    exit 1
fi

ping -q -c 1 8.8.8.8 >/dev/null || {
  printf "${FG_RED}Internet connection not available. Aborting...${RESET}\n"
  exit 1
}

if ! getent hosts deb.debian.org >/dev/null; then
    printf "${FG_RED}DNS resolution failed (cannot resolve deb.debian.org). Aborting...${RESET}\n"
    exit 1
fi

if ! iw list | grep -A 10 "Supported interface modes" | grep -q "\bAP\b"; then
    printf "${FG_RED}AP mode is not supported by your Wi-Fi card. Aborting...${RESET}\n"
    exit 1
fi

# Dependencies
echo_frame "${FG_CYAN}${UNDERLINE}Dependencies${RESET}"

printf "${FG_PURPLE}Updating APT packages...${RESET}\n"
sudo apt update 
sudo apt upgrade -y
sudo apt autopurge -y

printf "${FG_PURPLE}Installing tooling APT packages...${RESET}\n"
sudo apt install -y apt-transport-https ca-certificates gnupg wget

if ! grep -q "packages.sury.org/php" /etc/apt/sources.list.d/sury-php.list 2>/dev/null; then
    printf "${FG_PURPLE}Adding PHP repository...${RESET}\n"
    sudo wget -qO "/usr/share/keyrings/sury-php.gpg" "https://packages.sury.org/php/apt.gpg" >/dev/null
    echo 'deb [signed-by="/usr/share/keyrings/sury-php.gpg"] "https://packages.sury.org/php" bookworm main' | sudo tee "/etc/apt/sources.list.d/sury-php.list" >/dev/null
else
    printf "${FG_PURPLE}PHP repository already configured. Skipping...${RESET}\n"
fi

if ! grep -q "deb.nodesource.com" /etc/apt/sources.list /etc/apt/sources.list.d/* 2>/dev/null; then
    printf "${FG_PURPLE}Adding NodeJS repository...${RESET}\n"
    curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - >/dev/null 2>&1
else
    printf "${FG_PURPLE}NodeJS repository already configured. Skipping...${RESET}\n"
fi

printf "${FG_PURPLE}Refreshing package list...${RESET}\n"
sudo apt update

printf "${FG_PURPLE}Installing required APT packages...${RESET}\n"
sudo apt install --no-upgrade -y \
    git \
    openssl \
    php8.4 \
    php8.4-cli \
    php8.4-fpm \
    php8.4-{common,mbstring,mysql,pdo-mysql} \
    sqlite3 \
    mariadb-server \
    mariadb-client \
    nginx \
    dnsmasq \
    iptables \
    cron \
    conntrack \
    ipset \
    nodejs

# Networking
echo_frame "${FG_CYAN}${UNDERLINE}Networking${RESET}"
printf "${FG_PURPLE}Configuring system-wide network stack${RESET}\n"
sudo tee "/etc/sysctl.d/sysctl-overrides.conf" >/dev/null <<EOF
# Disable IPv6 by default and on all interfaces system-wide
net.ipv6.conf.default.disable_ipv6=1
net.ipv6.conf.all.disable_ipv6=1
# Enable IPv4 packet forwarding system-wide (required for NAT)
net.ipv4.ip_forward=1
EOF
sudo sysctl -p /etc/sysctl.d/sysctl-overrides.conf
printf "${FG_PURPLE}Setting hostname to $HOSTNAME...${RESET}\n"
sudo hostnamectl set-hostname "$HOSTNAME"
printf "${FG_PURPLE}Configuring hosts...${RESET}\n"
HOST_MAPPINGS=(
    "127.0.0.1 localhost"
    "$HOST_ADDRESS $HOSTNAME"
)
for HOST_MAPPING in "${HOST_MAPPINGS[@]}"; do
    if ! grep -qE "^$HOST_MAPPING" /etc/hosts; then
        echo "Mapping $(echo "$HOST_MAPPING" | awk "{print $1}") to $(echo "$HOST_MAPPING" | awk "{print $2}")"
        echo "$HOST_MAPPING" | sudo tee -a /etc/hosts >/dev/null
    fi
done
echo_frame \
    "Host address: $HOST_ADDRESS" \
    "Max allowed hosts: $MAX_HOSTS"
printf "${FG_PURPLE}Calculating best CIDR notation...${RESET}\n"
BEST_CIDR_BITS=32
USABLE_HOSTS=0
while (( BEST_CIDR_BITS > 0 )); do
    USABLE_HOSTS=$((2 ** (32 - BEST_CIDR_BITS) - 2))
    if (( USABLE_HOSTS >= MAX_HOSTS )); then
        break
    fi
    ((BEST_CIDR_BITS--))
done
BEST_CIDR_NOTATION="$HOST_ADDRESS/$BEST_CIDR_BITS"
echo_frame "Best CIDR notation: $BEST_CIDR_NOTATION"
printf "${FG_PURPLE}Creating hotspot...${RESET}\n"
echo_frame \
    "Network SSID: $HOTSPOT_SSID" \
    "Network Band: $HOTSPOT_BAND" \
    "Network Channel: $HOTSPOT_CHANNEL"
sudo nmcli con delete hotspot >/dev/null 2>/dev/null || true
sudo nmcli con add \
    type wifi \
    mode ap \
    autoconnect yes \
    con-name hotspot \
    ifname wlan0 \
    ssid "$HOTSPOT_SSID" \
    wifi.band "$HOTSPOT_BAND" \
    wifi.channel "$HOTSPOT_CHANNEL" \
    ipv4.method manual \
    ipv4.addresses "$BEST_CIDR_NOTATION" \
    ipv6.method disabled \
    >/dev/null
for _ in {1..10}; do
    if ip link show wlan0 | grep -q "state UP"; then
        printf "${FG_GREEN}wlan0 network interface is up.${RESET}\n"
        break
    fi
    printf "${FG_PURPLE}Waiting for wlan0 network interface...${RESET}\n"
    sleep 1
done
if ! ip link show wlan0 | grep -q "state UP"; then
    printf "${FG_RED}${BOLD}wlan0 network interface not found or not a wireless device. Aborting...${RESET}\n"
    exit 1
fi
for _ in {1..10}; do
    if ip addr show wlan0 | grep -q "$HOST_ADDRESS"; then
        printf "${FG_GREEN}$HOST_ADDRESS is now assigned to wlan0.${RESET}\n"
        break
    fi
    printf "${FG_PURPLE}Waiting for IP $HOST_ADDRESS on wlan0...${RESET}\n"
    sleep 1
done
if ! ip addr show wlan0 | grep -q "$HOST_ADDRESS"; then
    printf "${FG_RED}${BOLD}$HOST_ADDRESS was not assigned to wlan0. Aborting...${RESET}\n"
    exit 1
fi
ip link show wlan0
ip addr show wlan0
printf "${FG_PURPLE}Configuring Dnsmasq (DHCP & DNS)...${RESET}\n"
HOST_SUBNET="${HOST_ADDRESS%.*}"
DHCP_RANGE_START_IP=10
DHCP_USUABLE_HOSTS=$((USABLE_HOSTS - (DHCP_RANGE_START_IP - 1)))
if (( DHCP_USUABLE_HOSTS < MAX_HOSTS )); then
    printf "${FG_RED}${BOLD}Not enough usuable hosts for DHCP (${DHCP_USUABLE_HOSTS} available, ${MAX_HOSTS} requested). Terminating...\n"
fi
DHCP_RANGE_END_IP=$((DHCP_RANGE_START_IP + MAX_HOSTS - 1))
DHCP_RANGE_LINE="dhcp-range=${HOST_SUBNET}.${DHCP_RANGE_START_IP},${HOST_SUBNET}.${DHCP_RANGE_END_IP},24h"
sed -i 's/^dhcp-range=.*/'"$DHCP_RANGE_LINE"'/' "$BASE_PATH/config/network/dnsmasq.conf"
sudo ln -sf "$BASE_PATH/config/network/dnsmasq.conf" "/etc/dnsmasq.d/dnsmasq-overrides.conf"
printf "${FG_PURPLE}Scheduling a wlan0 iface state change dispatcher script...${RESET}\n"
sudo tee /etc/NetworkManager/dispatcher.d/99-wlan0-up-dispatch >/dev/null <<EOF
#!/bin/bash
IFACE="\$1"
STATE="\$2"
if [ "\$IFACE" = "wlan0" ] && [ "\$STATE" = "up" ]; then
    DAEMON_PROCESS_NAME="dnsmasq"
    logger -t "dispatcher" "wlan0 is up — restarting \$DAEMON_PROCESS_NAME"
    sudo systemctl restart \$DAEMON_PROCESS_NAME
    for _ in {1..10}; do
        if systemctl is-active --quiet \$DAEMON_PROCESS_NAME; then
            logger -t "dispatcher" "\$DAEMON_PROCESS_NAME is active"
            break
        fi
        logger -t "dispatcher" "Waiting for \$DAEMON_PROCESS_NAME..."
        sleep 1
    done
    if ! systemctl is-active --quiet \$DAEMON_PROCESS_NAME; then
        logger -t "dispatcher" "\$DAEMON_PROCESS_NAME failed to activate. Aborting..."
        exit 1
    fi
    logger -t "dispatcher" "Applying iptables rules (firewall)..."
    sudo chmod +x "$BASE_PATH/config/network/firewall.sh"
    sudo bash "$BASE_PATH/config/network/firewall.sh"
fi
EOF
sudo chmod +x /etc/NetworkManager/dispatcher.d/99-wlan0-up-dispatch
printf "${FG_PURPLE}Running a wlan0 iface state change dispatcher script (init round)...${RESET}\n"
SCRIPT_START=$(date +"%Y-%m-%d %H:%M:%S")
sudo bash /etc/NetworkManager/dispatcher.d/99-wlan0-up-dispatch wlan0 up
journalctl -t dispatcher --since "$SCRIPT_START" --no-pager
iptables -L -n -v

# MariaDB
echo_frame "${FG_CYAN}${UNDERLINE}MariaDB - Database${RESET}"
init_password_vault
printf "${FG_PURPLE}Configuring MariaDB...${RESET}\n"
sudo ln -sf "$BASE_PATH/config/mariadb/global.conf" "/etc/mysql/conf.d/mariadb-overrides.conf"
printf "${FG_PURPLE}Restarting MariaDB...${RESET}\n"
sudo systemctl restart mariadb
MARIADB_ROOT_PASSWORD=$(get_or_add_password_in_password_vault "mariadb_root")
printf "${FG_PURPLE}Securing MariaDB...${RESET}\n"
sudo mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<EOF
DELETE FROM mysql.user 
    WHERE user='';
DELETE FROM mysql.user 
    WHERE user='root' AND host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db 
    WHERE db='test';
FLUSH PRIVILEGES;
EOF
printf "${FG_PURPLE}Setting up MariaDB root user...${RESET}\n"
sudo mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<EOF
ALTER USER \`root\`@\`localhost\` IDENTIFIED BY '$MARIADB_ROOT_PASSWORD';
EOF
printf "${FG_PURPLE}Setting up MariaDB app user...${RESET}\n"
MARIADB_APP_PASSWORD=$(get_or_add_password_in_password_vault "mariadb_app")
sudo mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<EOF
CREATE USER IF NOT EXISTS \`$MARIADB_APP_USER\`@\`localhost\`;
ALTER USER \`$MARIADB_APP_USER\`@\`localhost\` IDENTIFIED BY '$MARIADB_APP_PASSWORD';
EOF
printf "${FG_PURPLE}Setting up MariaDB app database...${RESET}\n"
sudo mariadb -u root -p"$MARIADB_ROOT_PASSWORD" <<EOF
CREATE DATABASE IF NOT EXISTS \`$MARIADB_APP_DATABASE\`;
REVOKE ALL PRIVILEGES, GRANT OPTION FROM \`$MARIADB_APP_USER\`@\`localhost\`;
REVOKE SHOW DATABASES ON *.* FROM \`$MARIADB_APP_USER\`@\`localhost\`;
GRANT ALL PRIVILEGES ON \`$MARIADB_APP_DATABASE\`.* TO \`$MARIADB_APP_USER\`@\`localhost\`;
FLUSH PRIVILEGES;
EOF
printf "${FG_PURPLE}Gathering SQL migrations to apply...${RESET}\n"
sudo mariadb -u "$MARIADB_APP_USER" -p"$MARIADB_APP_PASSWORD" "$MARIADB_APP_DATABASE" <<EOF
CREATE TABLE IF NOT EXISTS applied_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
EOF
shopt -s nullglob
for MIGRATION_SQL_FILE in "$BASE_PATH"/config/mariadb/migrations/*.sql; do
    [[ -f "$MIGRATION_SQL_FILE" ]] || continue
    MIGRATION_SQL_FILENAME="$(basename "$MIGRATION_SQL_FILE" .sql)"
    MIGRATION_SQL_EXISTS=$(sudo mariadb -N -u "$MARIADB_APP_USER" -p"$MARIADB_APP_PASSWORD" "$MARIADB_APP_DATABASE" -e "SELECT 1 FROM applied_migrations WHERE name = '$MIGRATION_SQL_FILENAME' LIMIT 1;")
    if [[ "$MIGRATION_SQL_EXISTS" != "1" ]]; then
        printf "${FG_PURPLE}Applying SQL migration: $MIGRATION_SQL_FILENAME${RESET}\n"
        sudo bash -c "mariadb -u "$MARIADB_APP_USER" -p'$MARIADB_APP_PASSWORD' '$MARIADB_APP_DATABASE' < '$MIGRATION_SQL_FILE'"
        sudo mariadb -u "$MARIADB_APP_USER" -p"$MARIADB_APP_PASSWORD" "$MARIADB_APP_DATABASE" -e "INSERT INTO applied_migrations (name) VALUES ('$MIGRATION_SQL_FILENAME');"
    else
        printf "${FG_PURPLE}Skipping already applied migration: $MIGRATION_SQL_FILENAME${RESET}\n"
    fi
done
echo_frame \
    "${FG_CYAN}MariaDB${RESET}" \
    "${RESET}[${FG_CYAN}Users${RESET}]${RESET}" \
    "root -> $MARIADB_ROOT_PASSWORD" \
    "$MARIADB_APP_USER -> $MARIADB_APP_PASSWORD" \
    "${RESET}[${FG_CYAN}Databases${RESET}]${RESET}" \
    "$MARIADB_APP_DATABASE"

# Nginx
echo_frame "${FG_CYAN}${UNDERLINE}Nginx - Web Server${RESET}"
printf "${FG_PURPLE}Generating self-signed SSL certificate...${RESET}\n"
openssl req -x509 -nodes -days 365 \
    -newkey rsa:2048 \
    -keyout /etc/ssl/private/captivenet.key \
    -out /etc/ssl/certs/captivenet.crt \
    -subj "/CN=captivenet.local"
printf "${FG_PURPLE}Configuring Nginx...${RESET}\n"
SITE_AVAILABLE_DIR_PATH="/etc/nginx/sites-available"
SITE_ENABLED_DIR_PATH="/etc/nginx/sites-enabled"
SITE_WWW_DIR_PATH="/var/www"
sudo rm -rf \
    "$SITE_AVAILABLE_DIR_PATH" \
    "$SITE_ENABLED_DIR_PATH" \
    "$SITE_WWW_DIR_PATH"
sudo mkdir -p \
    "$SITE_AVAILABLE_DIR_PATH" \
    "$SITE_ENABLED_DIR_PATH" \
    "$SITE_WWW_DIR_PATH"
printf "${FG_PURPLE}Gathering Nginx sites...${RESET}\n"
shopt -s nullglob
for SITE_CONF_FILE in "$BASE_PATH"/config/nginx/sites/*.conf; do
    [[ -f "$SITE_CONF_FILE" ]] || continue
    SITE_CONF_FILENAME="$(basename "$SITE_CONF_FILE" .conf)"
    printf "${FG_GREEN}Loading site: $SITE_CONF_FILENAME${RESET}\n"
    sudo ln -sf "$SITE_CONF_FILE" "$SITE_AVAILABLE_DIR_PATH/$SITE_CONF_FILENAME"
    printf "${FG_GREEN}Enabling site: $SITE_CONF_FILENAME${RESET}\n"
    sudo ln -sf "$SITE_AVAILABLE_DIR_PATH/$SITE_CONF_FILENAME" "$SITE_ENABLED_DIR_PATH/$SITE_CONF_FILENAME"
    sudo mkdir -p "$SITE_WWW_DIR_PATH/$SITE_CONF_FILENAME"
done
printf "${FG_PURPLE}Restarting Nginx...${RESET}\n"
sudo systemctl restart nginx

# PHP 
echo_frame "${FG_CYAN}${UNDERLINE}PHP - Scripting${RESET}"
printf "${FG_PURPLE}Configuring PHP 8.4...${RESET}\n"
sudo ln -sf "$BASE_PATH/config/php/cli/php.ini" "/etc/php/8.4/cli/php.ini"
sudo ln -sf "$BASE_PATH/config/php/fpm/php.ini" "/etc/php/8.4/fpm/php.ini"
sudo ln -sf "$BASE_PATH/config/php/cli/php.ini" "/etc/php/8.4/cli/conf.d/php.ini"
sudo ln -sf "$BASE_PATH/config/php/fpm/www.conf" "/etc/php/8.4/fpm/pool.d/www.conf"
printf "${FG_PURPLE}Clearing PHP 8.4 session cache...${RESET}\n"
sudo rm -rf /var/lib/php/sessions/*
sudo mkdir -p /var/lib/php/sessions
sudo chown www-data:www-data /var/lib/php/sessions
sudo chmod 775 /var/lib/php/sessions
printf "${FG_PURPLE}Installing PHP 8.4 dependencies with Composer...${RESET}\n"
sudo php -r "copy('https://getcomposer.org/installer', 'php://stdout');" | sudo php -- --install-dir=/usr/local/bin --filename=composer >/dev/null 2>&1
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install -d "$BASE_PATH/app" --no-interaction >/dev/null 2>&1
sudo COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload -d "$BASE_PATH/app" --no-interaction  >/dev/null 2>&1
printf "${FG_PURPLE}Restarting PHP 8.4...${RESET}\n"
sudo systemctl restart php8.4-fpm
printf "${FG_PURPLE}Installing Adminer...${RESET}\n"
sudo wget -qO "$SITE_WWW_DIR_PATH/adminer/index.php" https://www.adminer.org/latest.php
printf "${FG_PURPLE}Publishing app files to www...${RESET}\n"
sudo cp "$BASE_PATH/app/.env.local" "$BASE_PATH/app/.env"
sudo sed -i "s/^DATABASE_NAME=$/DATABASE_NAME=$MARIADB_APP_DATABASE/" "$BASE_PATH/app/.env"
sudo sed -i "s/^DATABASE_USERNAME=$/DATABASE_USERNAME=$MARIADB_APP_USER/" "$BASE_PATH/app/.env"
sudo sed -i "s/^DATABASE_PASSWORD=$/DATABASE_PASSWORD=$MARIADB_APP_PASSWORD/" "$BASE_PATH/app/.env"
sudo cp -r "$BASE_PATH/app" "$SITE_WWW_DIR_PATH"
printf "${FG_PURPLE}Running app seeders...${RESET}\n"
sudo php "$BASE_PATH/app/bin/seed.php"
printf "${FG_PURPLE}Scheduling PHP 8.4 scripts...${RESET}\n"
sudo chmod +x "$SITE_WWW_DIR_PATH/app/bin/scheduler.php"
add_cron_job "* * * * * /usr/bin/php $SITE_WWW_DIR_PATH/app/bin/scheduler.php"

# NodeJS 
echo_frame "${FG_CYAN}${UNDERLINE}NodeJS${RESET}"
cd "$BASE_PATH/app"
printf "${FG_PURPLE}Installing NodeJS app dependencies...${RESET}\n"
sudo npm install >/dev/null
printf "${FG_GREEN}NodeJS dependencies dependencies installed${RESET}\n" 
printf "${FG_PURPLE}Building Vite dependencies assets...${RESET}\n"
sudo npm run build >/dev/null
printf "${FG_GREEN}Vite dependencies assets built${RESET}\n" 
cd "$BASE_PATH"

printf "${FG_GREEN}Done${RESET}\n"
