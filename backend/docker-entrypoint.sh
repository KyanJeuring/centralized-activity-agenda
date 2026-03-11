#!/bin/sh
set -e

cd /var/www

# If Symfony is not installed yet
if [ ! -f "public/index.php" ]; then
    echo "Symfony project not found. Creating project..."

    composer create-project symfony/skeleton /tmp/symfony

    cp -r /tmp/symfony/. /var/www
    rm -rf /tmp/symfony

    echo "Installing API dependencies..."

    composer require symfony/orm-pack symfony/serializer-pack symfony/validator symfony/security-bundle
    composer require --dev symfony/maker-bundle
    composer require nelmio/cors-bundle
fi

# On fresh clones vendor/ is absent because it's gitignored.
if [ ! -f "vendor/autoload_runtime.php" ]; then
    echo "Composer dependencies not found. Installing..."
    composer install --no-interaction --prefer-dist
fi

echo "Starting Symfony server..."

exec php -S 0.0.0.0:${BACKEND_PORT} -t public public/index.php
