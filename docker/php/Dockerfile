FROM php:8.2-fpm-bullseye

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        librabbitmq-dev \
        libssl-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip opcache \
    && pecl install redis amqp \
    && docker-php-ext-enable redis amqp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]
