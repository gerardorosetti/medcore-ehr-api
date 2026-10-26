FROM php:8.5-fpm-alpine

# Install system dependencies and build libraries for Laravel 13, PostgreSQL 16, and Redis
RUN apk add --no-cache \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash \
    autoconf \
    g++ \
    make \
    linux-headers \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        pcntl \
        bcmath \
        intl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make linux-headers \
    && rm -rf /tmp/pear

# Copy Composer binary from official Composer 2 image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy hardened PHP and PHP-FPM configuration
COPY docker/php/99-hardened.ini /usr/local/etc/php/conf.d/99-hardened.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy container entrypoint script
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
