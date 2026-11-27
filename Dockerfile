FROM composer:2 AS composer

FROM php:8.4-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        libfcgi-bin \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev

ENV APP_ENV=dev \
    COMPOSER_ALLOW_SUPERUSER=1

RUN pecl install pcov \
    && docker-php-ext-enable pcov

CMD ["php-fpm"]

FROM base AS prod

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock symfony.lock* ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && composer run-script post-install-cmd \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=5 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

CMD ["php-fpm"]

FROM prod AS render

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        gettext-base \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/render/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/render/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK NONE

CMD ["/usr/local/bin/entrypoint.sh"]