FROM php:8.1-fpm

# Copy dependency manifests first to keep the Composer layer cacheable.
COPY composer.lock composer.json /var/www/

# Set working directory
WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libxml2

# RUN pecl install xdebug-2.9.2 \
# 	&& docker-php-ext-enable xdebug \
#     && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql zip exif pcntl
RUN docker-php-ext-install gd && docker-php-ext-enable gd

# Install Composer and production dependencies.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --ignore-platform-req=php

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing application directory contents
COPY --chown=www:www . /var/www

# Generate the optimized autoloader after the application is present.
RUN composer dump-autoload --no-dev --optimize

USER www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]