FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
