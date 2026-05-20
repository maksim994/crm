#!/bin/sh
set -e

cd /var/www/html

# Coolify: Redis ACL часто ломает session driver=redis; file стабилен для Sanctum SPA
if [ "${APP_ENV:-local}" = "production" ]; then
    export SESSION_DRIVER=file
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
fi

# Убрать случайные кавычки в APP_KEY из UI Coolify
if [ -n "${APP_KEY:-}" ]; then
    APP_KEY=$(printf '%s' "$APP_KEY" | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    export APP_KEY
fi

mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:clear
php artisan config:cache

if ls resources/views/*.blade.php >/dev/null 2>&1; then
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
