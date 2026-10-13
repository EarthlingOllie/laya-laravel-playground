#!/bin/sh
# Prepares a fresh container: an .env with an app key, and Laravel's discovered packages.
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction >/dev/null
fi

php artisan package:discover --ansi >/dev/null

exec "$@"
