FROM node:22-alpine AS frontend

WORKDIR /frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
RUN npx vite build

FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        nginx \
        gettext-base \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql bcmath intl zip pcntl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/render/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/render/env.sh /usr/local/bin/render-env.sh
COPY docker/render/web-entrypoint.sh /usr/local/bin/web-entrypoint.sh
COPY docker/render/queue-entrypoint.sh /usr/local/bin/queue-entrypoint.sh

RUN sed -i 's/^listen = .*/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/\r$//' /usr/local/bin/render-env.sh /usr/local/bin/web-entrypoint.sh /usr/local/bin/queue-entrypoint.sh \
    && chmod +x /usr/local/bin/render-env.sh /usr/local/bin/web-entrypoint.sh /usr/local/bin/queue-entrypoint.sh

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY backend/ ./
ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

COPY --from=frontend /frontend/dist /tmp/frontend-dist
RUN cp /tmp/frontend-dist/index.html /var/www/html/public/spa.html \
    && cp -a /tmp/frontend-dist/assets /var/www/html/public/ \
    && for file in favicon.ico icons.svg; do \
         if [ -f "/tmp/frontend-dist/$file" ]; then cp "/tmp/frontend-dist/$file" /var/www/html/public/; fi; \
       done \
    && rm -rf /tmp/frontend-dist

ENV APP_ENV=production
ENV LOG_CHANNEL=stderr

EXPOSE 8080
ENTRYPOINT ["web-entrypoint.sh"]
