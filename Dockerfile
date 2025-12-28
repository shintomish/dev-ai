# PHP-FPM イメージ
FROM php:8.2-fpm

# 必要なパッケージ
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo_mysql

# 作業ディレクトリ
WORKDIR /var/www/html
