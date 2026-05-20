#!/bin/sh
set -e

cd /var/www/html

# Coolify: Redis ACL часто ломает session driver=redis; file стабилен для Sanctum SPA
if [ "${APP_ENV:-local}" = "production" ]; then
    export SESSION_DRIVER=file
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
fi

mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache

if compgen -G "resources/views/*.blade.php" > /dev/null; then
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
