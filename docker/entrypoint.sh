#!/bin/sh
# Prepares a fresh container: an .env with an app key, and Laravel's discovered packages.
set -e

# Laravel writes sessions, cache, compiled views and logs here; make sure the directories exist
# even if storage is a fresh volume.
mkdir -p storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction >/dev/null
fi

php artisan package:discover --ansi >/dev/null

exec "$@"
