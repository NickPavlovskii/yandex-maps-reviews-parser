FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libsqlite3-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pdo_sqlite bcmath intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php/queue-entrypoint.sh /usr/local/bin/queue-entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh /usr/local/bin/queue-entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/queue-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
