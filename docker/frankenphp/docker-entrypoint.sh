#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --prefer-dist --no-progress --no-interaction
fi

exec docker-php-entrypoint "$@"
