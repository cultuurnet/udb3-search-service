# --------------------------------------------------
# Runtime base: PHP-FPM + extensions
# --------------------------------------------------
FROM php:8.1-fpm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        netcat-openbsd \
        libicu-dev \
        libtidy-dev \
        zlib1g-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        bcmath \
        tidy \
        sockets \
        zip \
        intl \
        pcntl \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear /var/lib/apt/lists/*

EXPOSE 9000

# --------------------------------------------------
# Build stage: install deps and build app
# --------------------------------------------------
FROM runtime AS build

COPY --from=composer:2.6.6 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist

COPY . .

RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    && mkdir -p log

# --------------------------------------------------
# Production image: runtime base + built app, no composer
# --------------------------------------------------
FROM runtime

WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /var/www/html .

USER www-data
