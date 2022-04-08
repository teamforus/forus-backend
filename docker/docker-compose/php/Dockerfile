FROM php:7.4-apache

# Arguments
ARG user
ARG uid

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

# Copy commands for database reset
COPY docker/docker-compose/cmd/db-reset.sh /usr/local/bin/db-reset
COPY docker/docker-compose/cmd/start-schedule.sh /usr/local/bin/start-schedule

RUN chmod +x /usr/local/bin/db-reset /usr/local/bin/start-schedule

# Apache2 conf
ENV APACHE_DOCUMENT_ROOT=/var/www/public
COPY docker/docker-compose/apache2/default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# PHP ini config
COPY docker/docker-compose/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Set working directory
WORKDIR /var/www

USER $user

EXPOSE 8000
