FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql bcmath zip intl gd opcache \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh

RUN composer dump-autoload --optimize \
    && php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x /usr/local/bin/render-entrypoint.sh

RUN a2dismod mpm_event || true \
    && a2enmod mpm_prefork

ENV PORT=10000

EXPOSE 10000

ENTRYPOINT ["render-entrypoint.sh"]
CMD ["apache2-foreground"]
