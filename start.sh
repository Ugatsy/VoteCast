#!/bin/bash
set -e

echo "Fixing permissions..."
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "Setting trusted proxies..."
php artisan config:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;'
