# Dockerize Forus

- [Docker compose](#docker-compose)
- [Docker image](#docker-image)

## Git
### Get project from git
First you need to get project from git
``` 
git clone git@github.com:teamforus/forus-backend.git forus-backend
```

### Checkout branch
Go to the new created folder `forus-backend` and pull project from any branch you want
```
git checkout <BRANCH_NAME>
```

## Docker compose
Using docker compose service you can start all necessary services for backend (php, apache2, mysql, phpmyadmin) from one config file and also you can edit all project files locally (and see changes in docker container immediately), so for example you can set some env variables or add some files in folder, that in gitignore (like certificates or config files).  
Docker compose will build images, create three containers (php, database, phpmyadmin).  
Run command to build docker images
``` 
docker-compose build
```

Next you can run sh command, that will copy env file (if it's not exists), start images in containers and connect your folder with project to container (volume).
Also install composer dependencies, set app key and links.
``` 
./docker/cmd/start-docker-compose.sh
```

### Database
If you running project first time - maybe you need to set some configs (variables in .env or other files).  
Now you can run database migrations and seeders  
**WARNING!** this will refresh all migrations (drop all tables if they exist)
``` 
docker-compose exec app bash -c "composer dumpautoload && php artisan migrate:refresh --seed"
```

### Database lorem ipsum seeder
You can prefill with test data
``` 
docker-compose exec app php artisan db:seed --class LoremDbSeeder
```

### Links
```
http://localhost:8000 - backend   
http://localhost:8080 - phpmyadmin with user `forus` and password `forus`
```

### Composer dependencies (optional)
If you have some updates in composer.json, to install them use next command
``` 
docker-compose exec app composer install
```

### Stop containers
To stop containers:
``` 
docker-compose down
```

## Docker image
You can use docker image for forus backend, build it (later it will be available on docker hub) and start it

### Build docker image
First you build docker image (later it will be available form docker hub)
``` 
docker build -t forus-io/forus-backend .
```

### Start docker containers
After you can run command to start docker containers (for php, apache2, mysql and phpmyadmin)
``` 
./docker/cmd/start.sh
```

### Edit env file
If you want to set some variables in .env - go to the editor and change what you need
``` 
docker exec -it forus-backend nano .env
```

### Database
Now you can run database migrations and seeders (and prefill with test data)  
**WARNING!** this will refresh all migrations (drop all tables if they exist)
``` 
docker exec -it forus-backend bash -c "composer dumpautoload && php artisan migrate:refresh --seed"
```

### Links
```
http://localhost:8000 - backend   
http://localhost:8080 - phpmyadmin with user `forus` and password `forus`
```

### Stop containers
To stop containers:
``` 
docker stop forus-backend-db
docker stop forus-backend
docker stop forus-backend-phpmyadmin
docker network rm forus-network
```

Or 
``` 
./docker/cmd/stop.sh
```
