#!/bin/bash

docker stop forus-backend-db
docker stop forus-backend
docker stop forus-backend-phpmyadmin
docker network rm forus-network
