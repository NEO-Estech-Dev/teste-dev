#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
    echo "==> .env missing, copying .env.example"
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    echo "==> installing composer dependencies"
    composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    echo "==> generating application key"
    php artisan key:generate --force
fi

# Compose already gates startup on the MySQL healthcheck; this loop covers
# container restarts, where the dependency condition is not re-evaluated.
echo "==> waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}"
for attempt in $(seq 1 60); do
    if php -r '
        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s", getenv("DB_HOST") ?: "mysql", getenv("DB_PORT") ?: "3306"),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: ""
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' 2>/dev/null; then
        echo "==> MySQL is ready"
        break
    fi

    if [ "$attempt" -eq 60 ]; then
        echo "!!! MySQL did not become ready in time" >&2
        exit 1
    fi

    sleep 1
done

echo "==> running migrations"
php artisan migrate --force

exec "$@"
