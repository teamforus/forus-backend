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
    zip \
    unzip \
    nano \
    locales \
    libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip

# Install gd
RUN apt-get update && apt-get install -y  \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libwebp-dev \
    --no-install-recommends \
    && docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Copy commands for database reset
COPY docker-compose/cmd/db-reset.sh /usr/local/bin/db-reset
COPY docker-compose/cmd/start-schedule.sh /usr/local/bin/start-schedule

RUN chmod +x /usr/local/bin/db-reset
RUN chmod +x /usr/local/bin/start-schedule

# Apache2 conf
ENV APACHE_DOCUMENT_ROOT=/var/www/public
COPY docker-compose/apache2/default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# PHP ini config
COPY docker-compose/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Copy data
COPY --chown=$user:www-data . /var/www

# Set working directory
WORKDIR /var/www

# Set permissions for project files
RUN find . -type f -exec chmod 664 {} \;
RUN find . -type d -exec chmod 775 {} \;
RUN chgrp -R www-data ./storage ./bootstrap/cache
RUN chmod -R ug+rwx ./storage ./bootstrap/cache

# Set user
USER $user

# Install composer dependency
RUN composer install

# Copy env and set app key
RUN cp -n .env.docker .env
RUN php artisan key:generate && php artisan storage:link

EXPOSE 8000