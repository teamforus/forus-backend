#!/bin/bash

# copy .env
cp -n .env.docker .env

docker-compose up -d