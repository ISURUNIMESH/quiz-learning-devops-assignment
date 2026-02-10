FROM php:8.2-apache

WORKDIR /var/www/html

COPY . .

RUN docker-php-ext-install mysqli pdo pdo_mysql

EXPOSE 80
