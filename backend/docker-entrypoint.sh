#!/bin/sh
set -e

cd /var/www

if [ ! -f "artisan" ]; then
    # Allow first boot on fresh clones where ./backend is empty.
    NON_BOOTSTRAP_FILES="$(find . -mindepth 1 -maxdepth 1 \
        ! -name vendor \
        ! -name storage \
        ! -name Dockerfile \
        ! -name docker-entrypoint.sh \
        ! -name ENDPOINTS.md \
        ! -name .editorconfig \
        ! -name .env \
        ! -name .env.example \
        ! -name .gitignore \
        ! -name .gitattributes \
        ! -name .dockerignore \
        -print -quit)"
    if [ -n "$NON_BOOTSTRAP_FILES" ]; then
        echo "Laravel project not found, and /var/www contains unexpected files."
        echo "First blocking path: $NON_BOOTSTRAP_FILES"
        echo "Clean ./backend or add project files, then retry."
        exit 1
    fi

    echo "Laravel project not found. Bootstrapping a new project..."
    rm -rf /tmp/laravel-bootstrap
    composer create-project laravel/laravel /tmp/laravel-bootstrap --no-interaction --prefer-dist
    cp -a /tmp/laravel-bootstrap/. /var/www/
    rm -rf /tmp/laravel-bootstrap
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Patch .env with values from Docker environment variables so that
# the PHP built-in server (used by artisan serve) reads the correct
# settings from .env, regardless of what .env.example defaults to.
patch_env() {
    local key="$1" value="$2"
    # Replace existing key (commented or not) with the new value
    if grep -qE "^#?\s*${key}=" .env; then
        sed -i "s|^#\?\s*${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

[ -n "$APP_URL"       ] && patch_env APP_URL        "$APP_URL"
[ -n "$POSTGRES_HOST" ] && patch_env POSTGRES_HOST  "$POSTGRES_HOST"
[ -n "$POSTGRES_PORT" ] && patch_env POSTGRES_PORT  "$POSTGRES_PORT"
[ -n "$POSTGRES_DB"   ] && patch_env POSTGRES_DB    "$POSTGRES_DB"
[ -n "$POSTGRES_USER" ] && patch_env POSTGRES_USER  "$POSTGRES_USER"
[ -n "$POSTGRES_PASSWORD" ] && patch_env POSTGRES_PASSWORD "$POSTGRES_PASSWORD"

# For a stateless API, file-based session/cache is simpler and
# requires no extra database tables.
patch_env SESSION_DRIVER file
patch_env CACHE_STORE    file

if [ ! -f "vendor/autoload.php" ]; then
    echo "Composer dependencies not found. Installing..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# Ensure required storage directories exist (the named volume may be fresh).
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache

php artisan migrate --force

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

echo "Starting Laravel server..."

exec php artisan serve --host=0.0.0.0 --port="${BACKEND_PORT:-8000}"
