
FROM php:8.2-apache

# Dependencias y extensiones para MySQL y PostgreSQL
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install \
    mysqli \
    pdo_mysql \
    pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Activar mod_rewrite para URLs amigables
RUN a2enmod rewrite

RUN printf '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n' > /etc/apache2/conf-available/rooms-override.conf \
    && a2enconf rooms-override

# Copiar el proyecto al servidor Apache
COPY . /var/www/html/

# Permitir que Apache acceda a los archivos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
