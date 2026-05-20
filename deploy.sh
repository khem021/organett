#!/bin/bash
# =============================================================================
#  Organett — Hostinger VPS Setup Script
#  Ubuntu 22.04 / 24.04 LTS
#
#  USAGE (run as root on your VPS):
#    bash deploy.sh
#
#  BEFORE RUNNING:
#    1. Edit the CONFIG section below (domain, email)
#    2. Point your domain's A record to this server's IP
# =============================================================================
set -e

# ── CONFIG — edit these before running ───────────────────────────────────────
APP_DOMAIN=""          # your domain or IP, e.g. organett.yourdomain.com
ADMIN_EMAIL=""         # your email for SSL certificate (Let's Encrypt)
REPO_URL="https://github.com/khem021/organett.git"
APP_DIR="/var/www/organett"
DB_NAME="organett"
DB_USER="organett_user"
DB_PASS="$(openssl rand -base64 20 | tr -d '/+=' | head -c 24)"

# ── MAIL (Gmail SMTP) — optional but needed for Forgot Password emails ────────
# To enable: fill in your Gmail address and a Gmail App Password.
# Get an App Password at: https://myaccount.google.com/apppasswords
# (Requires 2-Step Verification to be ON for your Google account)
MAIL_USERNAME=""       # your Gmail address, e.g. yourname@gmail.com
MAIL_PASSWORD=""       # 16-character Gmail App Password, e.g. abcd efgh ijkl mnop
# ─────────────────────────────────────────────────────────────────────────────

# Colors
R='\033[0;31m' G='\033[0;32m' Y='\033[1;33m' B='\033[0;34m' N='\033[0m'

step() { echo -e "\n${B}━━━ $1 ${N}"; }
ok()   { echo -e "${G}✓ $1${N}"; }
warn() { echo -e "${Y}⚠ $1${N}"; }

# Guard: must run as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${R}Run this script as root: sudo bash deploy.sh${N}"; exit 1
fi

# Guard: must set domain
if [ -z "$APP_DOMAIN" ]; then
  echo -e "${R}Set APP_DOMAIN at the top of this script before running.${N}"; exit 1
fi

echo -e "${B}"
echo "  ╔════════════════════════════════════════╗"
echo "  ║   Organett — VPS Deployment Setup     ║"
echo "  ║   Server: $(hostname -I | awk '{print $1}')                ║"
echo "  ╚════════════════════════════════════════╝"
echo -e "${N}"

# ── 1. System update ──────────────────────────────────────────────────────────
step "1/10 — Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq curl git unzip zip software-properties-common gnupg2
ok "System updated"

# ── 2. PHP 8.4 ───────────────────────────────────────────────────────────────
step "2/10 — Installing PHP 8.4"
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt-get update -qq
apt-get install -y -qq \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-opcache \
    php8.4-pcntl \
    php8.4-tokenizer
ok "PHP $(php8.4 -r 'echo PHP_VERSION;') installed"

# ── 3. Nginx ─────────────────────────────────────────────────────────────────
step "3/10 — Installing Nginx"
apt-get install -y -qq nginx
systemctl enable nginx
ok "Nginx installed"

# ── 4. MySQL 8 ───────────────────────────────────────────────────────────────
step "4/10 — Installing MySQL 8"
apt-get install -y -qq mysql-server
systemctl start mysql
systemctl enable mysql
ok "MySQL installed"

# ── 5. Composer ──────────────────────────────────────────────────────────────
step "5/10 — Installing Composer"
curl -sS https://getcomposer.org/installer | php8.4 -- --quiet
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
ok "Composer $(composer --version --no-ansi | awk '{print $3}') installed"

# ── 6. Node.js 20 ────────────────────────────────────────────────────────────
step "6/10 — Installing Node.js 20"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - > /dev/null 2>&1
apt-get install -y -qq nodejs
ok "Node $(node -v) / npm $(npm -v) installed"

# ── 7. MySQL database ─────────────────────────────────────────────────────────
step "7/10 — Creating MySQL database & user"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
ok "Database '${DB_NAME}' ready"

# ── 8. Deploy application ────────────────────────────────────────────────────
step "8/10 — Deploying Organett"

# Clone or pull
if [ -d "$APP_DIR/.git" ]; then
    warn "Existing install found — pulling latest code"
    cd "$APP_DIR" && git pull origin main
else
    git clone "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"

# Write .env
cat > .env << ENV
APP_NAME=Organett
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${APP_DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

BCRYPT_ROUNDS=10

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=file

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
ENV

# Append mail config — use Gmail SMTP if credentials were provided, otherwise log driver
if [ -n "$MAIL_USERNAME" ] && [ -n "$MAIL_PASSWORD" ]; then
cat >> .env << ENV
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="${MAIL_USERNAME}"
MAIL_FROM_NAME="Organett"
ENV
  ok "Mail configured → Gmail SMTP (${MAIL_USERNAME})"
else
cat >> .env << ENV
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@${APP_DOMAIN}"
MAIL_FROM_NAME="Organett"
ENV
  warn "Mail not configured — Forgot Password emails will NOT be sent."
  warn "To enable later: edit /var/www/organett/.env and set MAIL_MAILER=smtp with Gmail credentials."
fi

# Generate app key
php8.4 artisan key:generate --force
ok ".env configured"

# PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction --quiet
ok "Composer packages installed"

# Build front-end assets
npm ci --silent && npm run build --silent
ok "Vite assets built"

# Storage symlink
php8.4 artisan storage:link --force > /dev/null
ok "Storage symlink created"

# Database migrations + seed
php8.4 artisan migrate --force
php8.4 artisan db:seed --force
ok "Database migrated and seeded"

# Laravel production caches
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
ok "Laravel caches warmed"

# File permissions
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permissions set"

# ── 9. Nginx virtual host ─────────────────────────────────────────────────────
step "9/10 — Configuring Nginx"
cat > /etc/nginx/sites-available/organett << NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN};
    root ${APP_DIR}/public;
    index index.php;

    charset utf-8;
    client_max_body_size 10M;

    # Laravel front-controller
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Block dotfiles
    location ~ /\.(?!well-known).* { deny all; }

    # Cache static assets forever (Vite fingerprints filenames)
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    gzip on;
    gzip_types text/plain text/css application/javascript application/json image/svg+xml;
    gzip_min_length 1024;

    access_log /var/log/nginx/organett.access.log;
    error_log  /var/log/nginx/organett.error.log;
}
NGINX

ln -sf /etc/nginx/sites-available/organett /etc/nginx/sites-enabled/organett
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
ok "Nginx configured for ${APP_DOMAIN}"

# ── 10. SSL — Let's Encrypt ──────────────────────────────────────────────────
step "10/10 — Setting up HTTPS (Let's Encrypt)"
apt-get install -y -qq certbot python3-certbot-nginx

if [ -n "$ADMIN_EMAIL" ]; then
    certbot --nginx \
        -d "$APP_DOMAIN" \
        --non-interactive \
        --agree-tos \
        --email "$ADMIN_EMAIL" \
        --redirect
    ok "SSL certificate installed — HTTPS enabled"
    APP_URL="https://${APP_DOMAIN}"
else
    warn "ADMIN_EMAIL not set — skipping SSL. Run manually:"
    warn "  certbot --nginx -d ${APP_DOMAIN} --email your@email.com"
    APP_URL="http://${APP_DOMAIN}"
fi

# ── Summary ──────────────────────────────────────────────────────────────────
echo ""
echo -e "${G}╔══════════════════════════════════════════════╗${N}"
echo -e "${G}║        Deployment Complete! 🎉               ║${N}"
echo -e "${G}╚══════════════════════════════════════════════╝${N}"
echo ""
echo -e "  ${B}App URL:${N}      ${APP_URL}"
echo -e "  ${B}App folder:${N}   ${APP_DIR}"
echo ""
echo -e "  ${Y}Database credentials (save these!):${N}"
echo -e "    Database : ${DB_NAME}"
echo -e "    Username : ${DB_USER}"
echo -e "    Password : ${DB_PASS}"
echo ""
echo -e "  ${Y}Default logins:${N}"
echo -e "    Admin  →  admin@organett.local  /  admin123"
echo -e "    Staff  →  staff@organett.local  /  staff123"
echo ""
if [ -n "$MAIL_USERNAME" ] && [ -n "$MAIL_PASSWORD" ]; then
echo -e "  ${G}Mail:${N}         Gmail SMTP enabled (${MAIL_USERNAME})"
echo -e "                Forgot Password emails will be sent."
else
echo -e "  ${Y}Mail:${N}         Not configured — Forgot Password emails are disabled."
echo -e "                To enable: set MAIL_* in ${APP_DIR}/.env"
fi
echo ""
echo -e "  ${B}To deploy future updates, run:${N}"
echo -e "    bash ${APP_DIR}/update.sh"
echo ""
