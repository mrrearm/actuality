FROM php:8.3-apache

# Estensioni necessarie:
# - curl        -> richiesta dal driver Turso (db/TursoPdo.php) per parlare con l'API HTTP
# - pdo_mysql   -> richiesta solo se DB_DRIVER=mysql (inclusa comunque per flessibilità)
# - mbstring    -> gestione corretta di accenti/maiuscole nei testi italiani
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install pdo pdo_mysql curl mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Abilita AllowOverride All: senza questo il file .htaccess (URL leggibili degli
# articoli, protezioni delle cartelle sql/ e assets/uploads/) verrebbe ignorato
RUN { \
        echo '<Directory /var/www/html/>'; \
        echo '    AllowOverride All'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/z-allow-override.conf \
    && a2enconf z-allow-override

WORKDIR /var/www/html
COPY . /var/www/html/

# La cartella upload deve essere scrivibile da Apache (www-data)
RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 775 /var/www/html/assets/uploads

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
