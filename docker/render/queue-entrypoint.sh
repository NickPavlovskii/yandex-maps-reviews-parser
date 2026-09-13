#!/bin/sh
set -e
. /usr/local/bin/render-env.sh

exec php artisan queue:work redis --sleep=1 --tries=3 --timeout=360 --max-time=3600
