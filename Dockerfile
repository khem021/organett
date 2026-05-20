# ── Base image: PHP 8.4 FPM on Alpine ──────────────────────────────────────
FROM php:8.4-fpm-alpine

# ── System dependencies ─────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev

# ── PHP extensions ──────────────────────────────────────────────────────────
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

# ── OPcache tuning ──────────────────────────────────────────────────────────
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── PHP dependencies (cached layer — only re-runs if composer files change) ─
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ── Vite assets (cached layer — only re-runs if JS/CSS source changes) ──────
COPY package.json package-lock.json vite.config.js ./
COPY resources/ resources/
RUN npm ci && npm run build

# ── Full application source ──────────────────────────────────────────────────
COPY . .
RUN composer run-script post-autoload-dump

# ── Storage & bootstrap permissions ─────────────────────────────────────────
RUN mkdir -p \
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
