FROM serversideup/php:8.4-fpm-nginx as base
WORKDIR /var/www/html

USER www-data

COPY composer.json composer.lock ./

FROM base AS dev
USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip zip \
 && rm -rf /var/lib/apt/lists/*
USER www-data

RUN composer install --no-interaction --prefer-dist
COPY . .

FROM base AS prod
USER root
RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip zip \
 && rm -rf /var/lib/apt/lists/*
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
COPY . .

USER root
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
USER www-data

