FROM php:7.4-apache

# Arguments
ARG user=forus
ARG uid=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libjpeg-dev \
    libwebp-dev \
    zip \
    unzip \
    nano \
    locales \
    libzip-dev \
    libfreetype6-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Copy commands
COPY docker/docker-compose/cmd/start-schedule.sh /usr/local/bin/start-schedule

RUN chmod +x /usr/local/bin/start-schedule

# Apache2 conf
ENV APACHE_DOCUMENT_ROOT=/var/www/public
COPY docker/docker-compose/apache2/default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# PHP ini config
COPY docker/docker-compose/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Copy data
COPY --chown=$user:www-data . /var/www

# Set working directory
WORKDIR /var/www

# Set permissions for project files
RUN find . -type f -exec chmod 664 {} \; | find . -type d -exec chmod 775 {} \;
RUN chgrp -R www-data ./storage ./bootstrap/cache | chmod -R ug+rwx ./storage ./bootstrap/cache

# Set user
USER $user

# Install composer dependency, copy env and set app key
RUN composer install && cp -n .env.docker .env && php artisan key:generate && php artisan storage:link

EXPOSE 8000