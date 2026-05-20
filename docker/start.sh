#!/bin/sh
set -e

cd /var/www/html

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

# ── 4. Seed demo data (safe to run multiple times — uses insertOrIgnore) ─────
echo "--> Seeding initial data..."
php artisan db:seed --force

# ── 5. Start PHP-FPM as a background daemon ──────────────────────────────────
echo "--> Starting PHP-FPM..."
php-fpm -D

# Give PHP-FPM a moment to bind on port 9000
sleep 2

# ── 6. Start Nginx in the foreground (PID 1) ─────────────────────────────────
echo "--> Nginx listening on :8080"
exec nginx -g 'daemon off;'
