#!/bin/sh
set -e

cd /var/www/html

# Render's generated 256-bit value is base64 without Laravel's required prefix.
# Normalize only 44-character generated values before Laravel caches config.
if [ -n "${APP_KEY:-}" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ] && [ "${#APP_KEY}" -eq 44 ]; then
    APP_KEY="base64:$APP_KEY"
    export APP_KEY
fi

echo "=========================================="
echo "  Organett — Starting up"
echo "=========================================="

# ── 1. Cache config, routes, and views for production performance ────────────
echo "--> Warming Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 2. Create storage symlink (public/storage → storage/app/public) ──────────
echo "--> Setting up storage link..."
php artisan storage:link --force 2>/dev/null || true

# ── 3. Run all pending database migrations ───────────────────────────────────
echo "--> Running database migrations..."
php artisan migrate --force

# Start PHP-FPM as a background daemon
echo "--> Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM a moment to bind on port 9000
sleep 2

# Start Nginx in the foreground (PID 1)
echo "--> Nginx listening on :8080"
exec nginx -g 'daemon off;'
