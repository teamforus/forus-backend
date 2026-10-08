#!/bin/bash

cp -n .env.docker .env
rm -f public/storage
rm -rf vendor

docker compose --profile phpmyadmin up -d

# This directory is created as root by Docker, but needs to be owned
# by the forus user in the container for composer install to work.
docker compose exec -u root app chown -R forus:forus /var/www/vendor

echo "Composer install"
docker compose exec app composer install

echo "Generate key"
docker compose exec app php artisan key:generate

echo "Storage link"
docker compose exec app php artisan storage:link