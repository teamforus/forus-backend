#!/bin/bash

# copy .env
cp -n .env.docker .env
rm -f public/storage

docker-compose up -d

echo "Composer install"
docker-compose exec app composer install

echo "Generate key"
docker-compose exec app php artisan key:generate

echo "Storage link"
docker-compose exec app php artisan storage:link