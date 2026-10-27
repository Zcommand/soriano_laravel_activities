#!/bin/sh
set -eu

: "${APP_KEY:?Set APP_KEY in the Render environment.}"
: "${DB_HOST:?Set the Aiven database hostname in Render.}"
: "${DB_PASSWORD:?Set the Aiven database password in Render.}"
: "${MYSQL_SSL_CA:?Set the Aiven CA certificate in MYSQL_SSL_CA.}"

if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
    export ASSET_URL="$RENDER_EXTERNAL_URL"
fi

umask 077
printf '%s\n' "$MYSQL_SSL_CA" > /tmp/aiven-ca.pem
chmod 644 /tmp/aiven-ca.pem
export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan migrate --force --no-interaction
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

# Render forwards HTTPS traffic to Apache on its assigned internal port.
sed -i "s/^Listen 80$/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
