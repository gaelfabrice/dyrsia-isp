#!/bin/bash
set -e

PORT="${PORT:-80}"

if [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

APP_ROOT="/var/www/html"
for dir in system/uploads system/cache ui/compiled ui/cache; do
    mkdir -p "${APP_ROOT}/${dir}"
done
if [ -f "${APP_ROOT}/system/uploads/notifications.default.json" ]; then
    :
elif [ -f "${APP_ROOT}/system/uploads/notifications.default.json.dist" ]; then
    cp "${APP_ROOT}/system/uploads/notifications.default.json.dist" "${APP_ROOT}/system/uploads/notifications.default.json"
fi
chown -R www-data:www-data "${APP_ROOT}/system/uploads" "${APP_ROOT}/system/cache" "${APP_ROOT}/ui/compiled" "${APP_ROOT}/ui/cache" 2>/dev/null || true
chmod -R 775 "${APP_ROOT}/system/uploads" "${APP_ROOT}/system/cache" "${APP_ROOT}/ui/compiled" "${APP_ROOT}/ui/cache" 2>/dev/null || true

exec "$@"
