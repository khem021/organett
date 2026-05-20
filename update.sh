#!/bin/bash
# =============================================================================
#  Organett — Quick Update Script
#  Run this on your VPS to deploy new code changes from GitHub
#
#  USAGE (run as root on your VPS):
#    bash /var/www/organett/update.sh
# =============================================================================
set -e

APP_DIR="/var/www/organett"

# Colors
R='\033[0;31m' G='\033[0;32m' Y='\033[1;33m' B='\033[0;34m' N='\033[0m'

step() { echo -e "\n${B}━━━ $1 ${N}"; }
ok()   { echo -e "${G}✓ $1${N}"; }
warn() { echo -e "${Y}⚠ $1${N}"; }

# Guard: must run as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${R}Run this script as root: sudo bash update.sh${N}"; exit 1
fi

echo -e "${B}"
echo "  ╔════════════════════════════════════════╗"
echo "  ║   Organett — Code Update               ║"
echo "  ║   $(date '+%Y-%m-%d %H:%M:%S')                    ║"
echo "  ╚════════════════════════════════════════╝"
echo -e "${N}"

cd "$APP_DIR"

# ── 1. Pull latest code ───────────────────────────────────────────────────────
step "1/6 — Pulling latest code from GitHub"
git pull origin main
ok "Code updated"

# ── 2. PHP dependencies ───────────────────────────────────────────────────────
step "2/6 — Updating Composer packages"
composer install --no-dev --optimize-autoloader --no-interaction --quiet
ok "Composer packages updated"

# ── 3. Front-end assets ───────────────────────────────────────────────────────
step "3/6 — Rebuilding front-end assets"
npm ci --silent && npm run build --silent
ok "Vite assets rebuilt"

# ── 4. Database migrations ────────────────────────────────────────────────────
step "4/6 — Running database migrations"
php8.4 artisan migrate --force
ok "Migrations complete"

# ── 5. Clear & rebuild caches ─────────────────────────────────────────────────
step "5/6 — Clearing and rebuilding Laravel caches"
php8.4 artisan config:cache
php8.4 artisan route:cache
php8.4 artisan view:cache
ok "Caches refreshed"

# ── 6. File permissions ───────────────────────────────────────────────────────
step "6/6 — Fixing file permissions"
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permissions set"

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${G}╔══════════════════════════════════════════════╗${N}"
echo -e "${G}║        Update Complete! 🎉                   ║${N}"
echo -e "${G}╚══════════════════════════════════════════════╝${N}"
echo ""
echo -e "  ${B}Organett is now running the latest code.${N}"
echo ""
