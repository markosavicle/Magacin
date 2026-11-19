# syntax=docker/dockerfile:1.7
#
# Magacin: one Dockerfile, three runtime targets.
#   app    PHP-FPM 8.4 with the code, production vendor/ and built assets (also used by the scheduler)
#   web    Nginx serving public/ and passing PHP to the app container
#   test   app + dev dependencies, for `php artisan test` inside a container
#
# Build context is the repository root:  docker build -f docker/php/Dockerfile --target app .

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22
ARG NGINX_VERSION=1.28

############################ base: PHP runtime + extensions ############################
FROM php:${PHP_VERSION}-fpm-trixie AS base

# postgresql-client: pg_dump for spatie/laravel-backup (same major version as the db image, 17).
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      postgresql-client \
      libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
      unzip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip gd intl bcmath opcache pcntl \
 && apt-get purge -y --auto-remove libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
 && apt-get install -y --no-install-recommends libpq5 libzip5 libicu76 libpng16-16t64 libjpeg62-turbo libfreetype6 \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-magacin.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-magacin.conf

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1

############################ vendor: production composer deps ############################
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

############################ assets: Vite build ############################
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

############################ app: the image that runs ############################
FROM base AS app
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
 && php artisan package:discover --ansi \
 && mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
             storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

COPY --chmod=0755 docker/php/entrypoint.sh /usr/local/bin/magacin-entrypoint
USER www-data
ENTRYPOINT ["magacin-entrypoint"]
CMD ["php-fpm"]

############################ test: app + dev deps ############################
# `php artisan test` expects a .env file, as in CI; APP_KEY comes from the environment.
FROM app AS test
USER root
RUN composer install --prefer-dist --no-progress --no-scripts \
 && php artisan package:discover --ansi \
 && cp .env.example .env \
 && chown -R www-data:www-data vendor bootstrap/cache .env
USER www-data
ENTRYPOINT []
CMD ["php", "artisan", "test"]

############################ web: Nginx in front of PHP-FPM ############################
FROM nginx:${NGINX_VERSION}-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
