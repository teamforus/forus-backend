#!/bin/bash

docker network create forus-network
docker run --rm -d --name forus-backend-db --network forus-network -p 33061:3306 -e MYSQL_ROOT_PASSWORD=forus -e MYSQL_DATABASE=forus -e MYSQL_PASSWORD=forus -e MYSQL_USER=forus mysql:5.7
docker run --rm -d --name forus-backend --network forus-network -p 8000:80 forus-io/forus-backend