# syntax=docker/dockerfile:1.7

FROM php:8.1-fpm AS builder

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    curl \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql zip exif pcntl

COPY composer.lock composer.json ./

RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --ignore-platform-req=php

COPY . .

RUN composer dump-autoload --no-dev --optimize

FROM php:8.1-fpm AS runtime

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    libjpeg62-turbo \
    libpng16-16 \
    libzip5 \
    libxml2 \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

RUN docker-php-ext-enable gd pdo_mysql zip exif pcntl \
    && groupadd -g 1000 www \
    && useradd -u 1000 -ms /bin/bash -g www www

COPY --from=builder --chown=www:www /var/www /var/www

USER www

EXPOSE 9000
CMD ["php-fpm"]
