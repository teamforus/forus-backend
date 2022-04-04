## Dockerize Forus

- [Docker compose](#docker-compose)
- [Docker image](#docker-image)

## Docker compose

Using docker compose service you can start all necessary services for backend (php, apache2, mysql) from one config file and also you can edit all project files locally (and see changes in docker container immediately), so for example you can set some env variables or add some files in folder, that in gitignore (like certificates or config files).

Docker compose will build images, create two containers (php and database). 
First you need to get project from git

``` 
git clone git@github.com:teamforus/forus-backend.git forus-backend
```
go to the new created folder `forus-backend` and pull project from any branch you want 

```
git checkout <BRANCH_NAME>
```

After you need to run command to build docker images

``` 
docker-compose build
```

Next you can run sh command, that will copy env file (if it's not exists), start images in containers and connect your folder with project to container (volume). 

``` 
./docker-cmd/start-docker-compose.sh
```

After you need to run composer installer, key generate and link to storage app (if you run project first time)

``` 
docker-compose exec app composer install --ignore-platform-reqs
docker-compose exec app php artisan key:generate 
docker-compose exec app php artisan storage:link
```
Or you can use next sh file to run all these commands:

``` 
docker-compose exec app ./docker-cmd/install.sh 
```

If you running project first time - maybe you need to set some configs (variables in .env or other files).  
Now you can run database migrations and seeders

``` 
docker-compose exec app composer dumpautoload
docker-compose exec app php artisan migrate:refresh --seed
```

Also, you can prefill with test data

``` 
docker-compose exec app php artisan db:seed --class LoremDbSeeder
```

Or run next script for these two items (database migrations and test data seeders)

``` 
docker-compose exec app ./docker-cmd/db-reset.sh 
```

If everything done - backend will be available on `http://localhost:8000` .

If you have some updates in composer.json, to install them use next command

``` 
docker-compose exec app composer install
```

To stop containers:

``` 
docker-compose down
```

## Docker image

Same as for docker compose you need to get project from git

``` 
git clone git@github.com:teamforus/forus-backend.git forus-backend
```
go to the new created folder `forus-backend` and pull project from any branch you want

```
git checkout <BRANCH_NAME>
```

Then you need to build docker image (later it will be available form docker hub)

``` 
docker build -t forus-io/forus-backend .
```

After you can run command to start docker containers (for php, apache2 and mysql)

``` 
./docker-cmd/start.sh
```

If you want to set some variables in .env - go to the editor and change what you need

``` 
docker exec -it forus-backend nano .env
```

Now you can run database migrations and seeders (and prefill with test data)

``` 
docker exec -it forus-backend bash db-reset
```

If everything done - backend will be available on `http://localhost:8000` .

To stop containers:

``` 
docker stop forus-backend-db
docker stop forus-backend
docker network rm forus-network
```

Or 

``` 
./docker-cmd/stop.sh
```
