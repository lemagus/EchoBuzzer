FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash git curl zip unzip icu-dev oniguruma-dev libzip-dev openssl-dev icu-libs freetype-dev libpng-dev libjpeg-turbo-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql intl mbstring zip pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY docker/app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Stubs are copied into the image and overlaid onto the app on startup
COPY stubs/ /stubs/

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]
