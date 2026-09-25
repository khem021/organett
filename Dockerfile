# ── Stage 1: PHP dependencies ───────────────────────────────────────────────
# Platform reqs are checked for real when the autoloader is dumped in the runtime stage.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ── Stage 2: Vite assets ────────────────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc vite.config.js ./
RUN npm ci
COPY resources/ resources/
# Tailwind scans Laravel's pagination views for class names
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ── Stage 3: Runtime — PHP 8.4 FPM + Nginx on Alpine ────────────────────────
# No node, npm, git or curl in the final image: less for an attacker to use.
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        intl

# ── PHP runtime tuning & hardening ──────────────────────────────────────────
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
        echo 'expose_php=Off'; \
        echo 'display_errors=Off'; \
        echo 'display_startup_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'allow_url_include=Off'; \
    } > /usr/local/etc/php/conf.d/security.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor vendor/
COPY . .
COPY --from=assets /app/public/build public/build/

# Composer is only needed to build the autoloader; remove it afterwards.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm /usr/bin/composer \
    && rm -f public/hot

# ── Storage & bootstrap permissions ─────────────────────────────────────────
RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/logs \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Nginx virtual host & startup script ─────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
CMD ["/start.sh"]
