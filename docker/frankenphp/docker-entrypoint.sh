#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --prefer-dist --no-progress --no-interaction
fi

if [ "$1" = 'frankenphp' ] || [ "${1#-}" != "$1" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec docker-php-entrypoint "$@"
