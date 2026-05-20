#!/bin/sh
set -e

cd /var/www/html

# Coolify: Redis ACL часто ломает session driver=redis; file стабилен для Sanctum SPA
if [ "${APP_ENV:-local}" = "production" ]; then
    export SESSION_DRIVER=file
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
fi

# Убрать кавычки и пробелы по краям APP_KEY из UI Coolify
if [ -n "${APP_KEY:-}" ]; then
    APP_KEY=$(printf '%s' "$APP_KEY" | tr -d '\r\n' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    export APP_KEY
fi

mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Не кешируем config по умолчанию: Coolify инжектит APP_KEY/DB_* только в runtime,
# и config:cache в entrypoint запекает пустые значения → crypto/login 500.
php artisan config:clear

if php -r '
$key = getenv("APP_KEY") ?: "";
exit(preg_match("/^base64:[A-Za-z0-9+\/=]+$/", $key) && strlen($key) >= 32 ? 0 : 1);
'; then
    if [ "${CONFIG_CACHE:-false}" = "true" ]; then
        php artisan config:cache
    fi
else
    echo "wbooster: APP_KEY missing or invalid — set Runtime-only APP_KEY=base64:... (php artisan key:generate --show)" >&2
fi

if ls resources/views/*.blade.php >/dev/null 2>&1; then
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
