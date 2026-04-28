# Production stage
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . /var/www
COPY .env.ci .env
COPY backup.txt composer.json

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --chown=www-data:www-data . /var/www

RUN composer install --no-scripts --no-autoloader

RUN chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY php.ini $PHP_INI_DIR/conf.d/

EXPOSE 9000
CMD ["php-fpm"]
