# PHP image
FROM php:8.4-fpm

# instalacija zavisnosti
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    nodejs \
    npm

# instaliraj composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# set workdir
WORKDIR /app

# kopiraj sve
COPY . .

# install PHP deps
RUN composer install --no-dev --optimize-autoloader

# 🔥 OVO TI FALI:
RUN npm install
RUN npm run build

# permissions
RUN chmod -R 775 storage bootstrap/cache

# start
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
