#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

composer install --no-interaction --prefer-dist

APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env | cut -d '=' -f2- | tr -d '[:space:]')
if [ -z "$APP_KEY_VALUE" ]; then
  php artisan key:generate --force --ansi
fi

mkdir -p storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R 777 storage bootstrap/cache || true

php artisan migrate --seed --force
php artisan config:clear

exec docker-php-entrypoint "$@"
