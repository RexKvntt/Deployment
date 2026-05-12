FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite headers
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN php -v \
    && php -m \
    && composer --version \
    && composer validate --no-check-publish \
    && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

COPY . .
RUN mkdir -p uploads/class_posts \
    && chown -R www-data:www-data uploads

EXPOSE 10000

CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
