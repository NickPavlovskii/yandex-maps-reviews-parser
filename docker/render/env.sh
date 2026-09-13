#!/bin/sh
set -e
cd /var/www/html

if [ -n "$DATABASE_PRIVATE_URL" ]; then
  export DATABASE_URL="$DATABASE_PRIVATE_URL"
fi

if [ -n "$PGHOST" ] && [ -n "$PGPASSWORD" ]; then
  export DB_CONNECTION=pgsql
  export DB_HOST="$PGHOST"
  export DB_PORT="${PGPORT:-5432}"
  export DB_DATABASE="${PGDATABASE:-railway}"
  export DB_USERNAME="${PGUSER:-postgres}"
  export DB_PASSWORD="$PGPASSWORD"
  unset DB_URL
  unset DATABASE_URL
elif [ -n "$DATABASE_URL" ]; then
  export DB_CONNECTION=pgsql
  export DB_URL="$DATABASE_URL"
fi

if [ -z "$REDIS_URL" ] && [ -n "$REDIS_PRIVATE_URL" ]; then
  export REDIS_URL="$REDIS_PRIVATE_URL"
fi

if [ -z "$REDIS_HOST" ] && [ -n "$REDISHOST" ]; then
  export REDIS_HOST="$REDISHOST"
fi

if [ -z "$REDIS_PORT" ] && [ -n "$REDISPORT" ]; then
  export REDIS_PORT="$REDISPORT"
fi

if [ -z "$REDIS_PASSWORD" ] && [ -n "$REDISPASSWORD" ]; then
  export REDIS_PASSWORD="$REDISPASSWORD"
fi

if [ -z "$REDIS_USERNAME" ] && [ -n "$REDISUSER" ]; then
  export REDIS_USERNAME="$REDISUSER"
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

# Railway often leaves RAILWAY_PRIVATE_DOMAIN empty when referenced from
# another service. Private DNS `{service}.railway.internal` still works.
if [ -z "$PARSER_URL" ] && [ -n "${RAILWAY_ENVIRONMENT:-}${RAILWAY_ENVIRONMENT_ID:-}${RAILWAY_PROJECT_ID:-}" ]; then
  parser_service="${PARSER_SERVICE:-otklik-parser}"
  parser_port="${PARSER_PRIVATE_PORT:-3000}"
  export PARSER_URL="http://${parser_service}.railway.internal:${parser_port}"
fi

# Railway ${{service.PORT}} is empty when the reference uses the DNS
# name instead of the service name, producing http://host: or http://host.
PARSER_URL="${PARSER_URL%:}"
if [ -n "$PARSER_URL" ]; then
  parser_hostport="${PARSER_URL#http://}"
  parser_hostport="${parser_hostport#https://}"
  parser_hostport="${parser_hostport%%/*}"
  case "$parser_hostport" in
    *:*) ;;
    *)
      export PARSER_URL="${PARSER_URL%/}:${PARSER_PRIVATE_PORT:-3000}"
      ;;
  esac
fi

if [ -z "$PARSER_URL" ]; then
  echo "WARNING: PARSER_URL is empty; Laravel will try local node" >&2
else
  echo "PARSER_URL=${PARSER_URL}" >&2
fi

mkdir -p storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R 777 storage bootstrap/cache || true
