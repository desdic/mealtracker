FROM php:8.2-apache

# Install PHP extensions for MariaDB/MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

