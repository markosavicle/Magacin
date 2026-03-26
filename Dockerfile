# PHP + Composer
FROM php:8.4-fpm

# sistemske zavisnosti + node
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    build-essential

# Instaliraj Node 18.x
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set radni direktorijum
WORKDIR /app

# Kopiraj fajlove
COPY . .

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Node dependencies & build
RUN npm install
RUN npm run build

# Permissions
RUN chmod -R 775 storage bootstrap/cache

# Migrate i serve
CMD php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=10000
