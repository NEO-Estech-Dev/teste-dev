#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f .env ]; then
    APP_KEY_VALUE=$(grep '^APP_KEY=' .env | cut -d= -f2- || true)

    if [ -z "$APP_KEY_VALUE" ]; then
        php artisan key:generate --force
    fi
fi

php artisan migrate --force
php artisan db:seed --force

exec php-fpm
