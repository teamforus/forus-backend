#!/bin/bash

docker network create forus-network
docker run --rm -d --platform linux/amd64 --name forus-backend-db --network forus-network -p 33061:3306 -e MYSQL_ROOT_PASSWORD=forus -e MYSQL_DATABASE=forus -e MYSQL_PASSWORD=forus -e MYSQL_USER=forus mysql:8.0
docker run --rm -d --name forus-backend --network forus-network -p 8000:80 forus-io/forus-backend
docker run --rm -d --name forus-backend-phpmyadmin --network forus-network -p 8080:80 -e PMA_HOST=forus-backend-db -e MYSQL_ROOT_PASSWORD=forus phpmyadmin/phpmyadmin