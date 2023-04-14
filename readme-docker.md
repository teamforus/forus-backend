# Dockerize Forus

- [Installation](#installation)
- [Docker compose](#docker-compose)
- [Docker image](#docker-image)

# Installation

**Repositories:**  
Frontend: [https://github.com/teamforus/forus-frontend](https://**github**.com/teamforus/forus-frontend)  
Backend: [https://github.com/teamforus/forus-backend](https://github.com/teamforus/forus-backend) 

## Get the project from github
First, you need to clone the project from GitHub:  
```bash
git clone git@github.com:teamforus/forus-backend.git forus-backend
```

### Checkout to the branch you want to test:
Go to the newly created folder `forus-backend` and checkout to the branch you want to test:
```
cd forus-backend
git checkout <BRANCH_NAME>
```

## Docker compose
___

Using docker compose you can start all the necessary services to run the backend (`PHP`, `apache2`, `MySQL` and `PhpMyAdmin`), and mount the project directory as a container volume. This will allow you to edit the files within the container for easier development and testing.

Run `docker-compose` command to build the images:
``` 
docker-compose build
```

Next, you can run a bash script, to perform the next operations:
- Create the .env file from .env.example (if it doesn't already exist).
- Start (PHP, apache2, MySQL and PhpMyAdmin) containers.
- Connect your project directory as container volume.
- Remove the existing `vendor` directory (to ensure clean composer install).
- Install composer dependencies.
- Set generate app key and create the storage soft link.

```bash
./docker/cmd/start-docker-compose.sh
```
Please check the `start-docker-compose.sh` for more details.

## Links
If everything fine, you should be able to find the `backend` and `phpMyAdmin` at following urls:
___
[http://localhost:8000](http://localhost:8000) - backend   
[http://localhost:8080](http://localhost:8080) - phpmyadmin 

PhpMyAdmin login and password: 
```
user: forus 
password: forus
```

## Initial setup
___

If you are running the project for the first time, you will need to adjust the `.env` file to set all the necessary API keys and other configs and files (like certificates for push notifications or SMTP credentials to send auth emails and user notifications).

### Migrations
___
When you are ready, you can use the next command to run the migrations (this will create the database structure):
``` 
docker-compose exec app bash -c "composer dumpautoload && php artisan migrate"
```

### Seeders
___
Then you need to run the seeder to fill the base system tables like: `product-categories`, `record-types` and other system tables (which are needed to run the project): 
```bash 
docker-compose exec app bash -c "php artisan db:seed"
```

Now you should be able to use the backend to create organizations, funds and products.

### Seed test data
___
On the previous step we prepared a clean project without any organizations, funds or products. Now you can go ahead and create a user account, register sponsors and providers, then create funds and products and so on.  

Or you can use the next command to generate test data. 

But first you need to configure the data you will generate.  
Please run the following command to create a custom config file.
```bash
cp ./config/forus/test_data/configs/custom.example.php ./config/forus/test_data/configs/custom.php
```

Now you need to adjust the config file you just created.
```bash
nano ./config/forus/test_data/configs/custom.example.php
```

The file from the example only has ```primary_email``` key which represents the default email used to create all test organizations.  

Please set this email to an email address you have access to, you will use it to log in into the dashboards and webshops.  
Example:
```php
<?php

return [
    'primary_email' => 'your-email@example.com',
];
```

Please note there are more variables that can be changed.  
To see the full list, please read the default config file and copy all the keys you want to overwrite to your ```custom.php``` file.  

Run the following command to read the default config file:  
```bash
nano ./config/forus/test_data/configs/default.php
```

To seed the test data after you finished editing your config file run:
```bash
docker-compose exec app php artisan test-data:seed
```

## Update composer dependencies
___
Since you just initiated the project, your composer dependencies should be up-to-date. 

However, if you switch to another branch or pull new commits your installed composer dependencies might get outdated, and you might have to manually install the update. For that, please run the following command to make sure your dependencies are up to date. 
``` 
docker-compose exec app composer install
```

## Stopping the containers
___
To stop the containers please run:
```bash
docker-compose down
```

## Restarting the containers
___
To start again existing container, without `composer install` run:
```bash
docker-compose up -d
```

# Docker image
Another way to run the project, is to use a prebuilt docker image.

You can either build the forus-backend image yourself locally or download the image from docker-hub (will be available later).

## Build docker image:
___
First build docker image:
```bash
docker build -t forus-io/forus-backend .
```

## Start the containers:
The next command will start the containers (`php`, `apache2`, `mysql` and `phpmyadmin`)
```bash
./docker/cmd/start.sh
```

## Edit the `.env` file
___
If you want to make changes to the `.env` file from the container - adjust the `.env` file from the project directory and run the following command to update the `.env` file within the container.
``` 
docker exec -it forus-backend nano .env
```

## Database
___

To run the migrations (create db structure):
```bash
docker exec -it forus-backend bash -c "composer dumpautoload && php artisan migrate" 
```

And base seeders (bare minimum):
```bash
docker exec -it forus-backend bash -c "php artisan db:seed"
```

To generate test data, please run:
```bash
docker exec -it forus-backend bash -c "php artisan test-data:seed"
```

To reset the database run:  
**WARNING!** this will drop all existing tables
```bash
docker exec -it forus-backend bash -c "php artisan migrate:reset"
```

## Links
___
[http://localhost:8000](http://localhost:8000) - backend   
[http://localhost:8080](http://localhost:8080) - phpmyadmin 

PhpMyAdmin login and password: 
```
user: forus 
password: forus
```

## Stop containers
To stop containers:
```bash
docker stop forus-backend-db
docker stop forus-backend
docker stop forus-backend-phpmyadmin
docker network rm forus-network
```

Or 
```bash
./docker/cmd/stop.sh
```
