#!/bin/bash

docker stop forus-backend-db
docker stop forus-backend
docker network rm forus-network
