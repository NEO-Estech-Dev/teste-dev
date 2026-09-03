FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        sqlite-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        zip \
        intl \
        bcmath \
        pcntl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]

