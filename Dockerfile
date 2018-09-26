FROM php:7

RUN apt-get update -y
RUN apt-get install -y --no-install-recommends openssl zip unzip git libmcrypt-dev mysql-client libmagickwand-dev
RUN pecl install imagick
RUN docker-php-ext-install pdo_mysql mbstring

WORKDIR /app
COPY . /app

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=3000"]