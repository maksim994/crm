#!/bin/sh
set -e

cd /var/www/html

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache

if compgen -G "resources/views/*.blade.php" > /dev/null; then
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
