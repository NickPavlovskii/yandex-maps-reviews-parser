#!/bin/sh
set -e
cd /var/www/html

if [ -z "$DB_URL" ] && [ -n "$DATABASE_URL" ]; then
  export DB_URL="$DATABASE_URL"
fi

if [ -z "$APP_KEY" ] && [ -n "$APP_KEY_MATERIAL" ]; then
  export APP_KEY="base64:${APP_KEY_MATERIAL}"
fi

if [ -z "$APP_URL" ] && [ -n "$RENDER_EXTERNAL_URL" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi

if [ -z "$APP_URL" ] && [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
  export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

if [ -z "$FRONTEND_URL" ] && [ -n "$APP_URL" ]; then
  export FRONTEND_URL="$APP_URL"
fi

if [ -z "$SANCTUM_STATEFUL_DOMAINS" ] && [ -n "$RENDER_EXTERNAL_HOSTNAME" ]; then
  export SANCTUM_STATEFUL_DOMAINS="$RENDER_EXTERNAL_HOSTNAME"
fi

if [ -z "$SANCTUM_STATEFUL_DOMAINS" ] && [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
  export SANCTUM_STATEFUL_DOMAINS="$RAILWAY_PUBLIC_DOMAIN"
fi

if [ -z "$PARSER_URL" ] && [ -n "$PARSER_HOSTPORT" ]; then
  export PARSER_URL="http://${PARSER_HOSTPORT}"
fi

if [ -z "$PARSER_URL" ] && [ -n "$PARSER_PRIVATE_HOST" ]; then
  export PARSER_URL="http://${PARSER_PRIVATE_HOST}:${PARSER_PRIVATE_PORT:-3000}"
fi

mkdir -p storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R 777 storage bootstrap/cache || true
