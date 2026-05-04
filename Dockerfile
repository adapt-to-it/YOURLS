# Local-only image for testing the Filament Skin plugin against YOURLS.
# Not intended for production use.

FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libzip-dev \
        unzip git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql mbstring zip \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# PHP runtime tweaks suitable for local dev
RUN { \
        echo 'display_errors=On'; \
        echo 'display_startup_errors=On'; \
        echo 'error_reporting=E_ALL'; \
        echo 'log_errors=On'; \
        echo 'upload_max_filesize=16M'; \
        echo 'post_max_size=16M'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/zz-yourls-dev.ini

# Mount point — the project is bind-mounted from the host via docker-compose
WORKDIR /var/www/html
EXPOSE 80
