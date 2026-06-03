FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html

RUN if [ ! -f /var/www/html/config.php ]; then cp /var/www/html/config.sample.php /var/www/html/config.php; fi \
    && a2enmod rewrite headers \
    && mkdir -p /var/www/html/system/uploads /var/www/html/system/cache /var/www/html/ui/compiled /var/www/html/ui/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/system/uploads /var/www/html/system/cache /var/www/html/ui/compiled /var/www/html/ui/cache

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
