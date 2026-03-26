# PHP image
FROM php:8.4-fpm

# instalacija zavisnosti
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    nodejs \
    npm

RUN docker-php-ext-install pdo pdo_pgsql pgsql

# instaliraj composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# set workdir
WORKDIR /app

# kopiraj sve
COPY . .

# install PHP deps
RUN composer install --no-dev --optimize-autoloader

RUN npm install
RUN npm run build

# permissions
RUN chmod -R 775 storage bootstrap/cache

# start
CMD php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=10000
