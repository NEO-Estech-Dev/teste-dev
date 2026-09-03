#!/usr/bin/env sh
set -eu

if [ -z "${APP_KEY:-}" ] && ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --force --no-ansi
fi

php artisan config:clear
php artisan migrate --force

exec php artisan octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers="${OCTANE_WORKERS:-auto}" \
    --max-requests="${OCTANE_MAX_REQUESTS:-500}"
