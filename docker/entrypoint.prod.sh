#!/bin/sh
set -e

cd /var/www/html

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache
php artisan route:cache

if [ -d resources/views ] && [ -n "$(ls -A resources/views 2>/dev/null)" ]; then
    php artisan view:cache
fi

exec "$@"
