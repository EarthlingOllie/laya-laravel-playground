# The playground app: Laravel + laya-php, built in three stages so the runtime image carries no build tools.

# 1. PHP dependencies. The composer image has git and unzip; the autoloader is dumped in the runtime stage.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

# 2. Front-end assets (Vite + Tailwind + Alpine).
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# 3. Runtime: plain PHP 8.4 CLI. `php artisan serve` with a few workers is plenty for a local playground.
FROM php:8.4-cli AS app
WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# The app runs as www-data: it writes its .env (for the app key), storage and the bootstrap cache.
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && cp .env.example .env \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache .env \
    && chown www-data:www-data /app

USER www-data

ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1:8080/up') === false ? 1 : 0);"

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080", "--no-reload"]
