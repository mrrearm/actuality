FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install pdo pdo_mysql curl mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
