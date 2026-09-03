FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libbrotli-dev \
        libicu-dev \
        libssl-dev \
        libzip-dev \
        pkg-config \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl opcache pcntl pdo_mysql sockets \
    && MAKEFLAGS="-j$(nproc)" pecl install swoole \
    && docker-php-ext-enable swoole \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && chmod +x docker/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
