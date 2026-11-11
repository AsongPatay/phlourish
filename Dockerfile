FROM php:8.2-apache
ENV APP_ENV=production

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql zip

RUN a2enmod rewrite

COPY . /var/www/html/
WORKDIR /var/www/html
RUN composer install --no-dev --classmap-authoritative --no-interaction --working-dir=/var/www/html

CMD ["sh", "-c", "php bin/migrate.php && exec apache2-foreground"]
