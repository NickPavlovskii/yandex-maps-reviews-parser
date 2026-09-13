#!/bin/sh
set -e
. /usr/local/bin/render-env.sh

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

php-fpm -D

(
  i=1
  while [ "$i" -le 12 ]; do
    if php artisan migrate --seed --force; then
      break
    fi

    echo "Waiting for database... (${i}/12)"
    i=$((i + 1))
    sleep 5
  done
) &

exec nginx -g 'daemon off;'
