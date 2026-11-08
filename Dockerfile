FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql zip

RUN a2enmod rewrite

COPY . /var/www/html/
WORKDIR /var/www/html
