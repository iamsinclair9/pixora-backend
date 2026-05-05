#!/bin/bash
set -e

# Run as root

# Fix permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Composer install if no vendor
if [ ! -d "/var/www/vendor" ]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

# Copy .env if missing
if [ ! -f "/var/www/.env" ]; then
  cp .env.example .env
fi

# Laravel setup
php artisan key:generate --force --no-interaction
php artisan migrate --force --no-interaction
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start php-fpm as www-data
exec gosu www-data php-fpm
