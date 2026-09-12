#!/bin/sh
set -e
cd /var/www/html

until [ -f vendor/autoload.php ] && [ -f .env ]; do
  echo "Waiting for application bootstrap..."
  sleep 2
done

exec php artisan queue:work redis --sleep=1 --tries=3 --timeout=360
