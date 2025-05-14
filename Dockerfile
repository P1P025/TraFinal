FROM php:8.1-apache

# Instala extensiones para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia tu carpeta public/ al DocumentRoot
COPY public/ /var/www/html/

# (Opcional) Ajusta permisos
RUN chown -R www-data:www-data /var/www/html
