#!/bin/bash

composer dumpautoload && php artisan migrate:refresh --seed && php artisan db:seed --class LoremDbSeeder
