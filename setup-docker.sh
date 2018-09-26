#!/usr/bin/env bash

echo "Installing composer dependencies..."
docker run --rm -v $(pwd):/app composer install

docker-compose up --detach

echo "Waiting for 15 seconds to allow MySQL to boot..." 
sleep 15

docker-compose exec app php artisan key:generate
docker-compose exec app php artisan optimize

echo "Seeding database..."
docker-compose exec app php artisan migrate --seed

docker-compose down

echo "You can then run \`docker compose up\` to run the application, accessible on port 3000"
