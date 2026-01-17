FROM serversideup/php:8.4-fpm-nginx as base
WORKDIR /var/www/html

USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip zip \
 && rm -rf /var/lib/apt/lists/*

USER www-data

COPY composer.json composer.lock ./

FROM base AS dev
RUN composer install --no-interaction --prefer-dist
COPY . .

FROM base AS stage
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
COPY . .
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

FROM base AS prod
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
COPY . .
USER root
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
USER www-data
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

