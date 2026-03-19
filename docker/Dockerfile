FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite

RUN mkdir -p /var/www/html/res && chown www-data:www-data /var/www/html/res

ENV APACHE_DOCUMENT_ROOT=/var/www/html
