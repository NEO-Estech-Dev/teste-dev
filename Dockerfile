FROM php:8.3-cli-bookworm

# install-php-extensions resolves the build dependencies for each extension.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        pcntl \
        opcache \
        redis \
        swoole

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Match the host UID so files written from inside the container (composer, logs,
# cache) stay editable on the bind mount.
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app || true \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash app

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER app

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=4"]
