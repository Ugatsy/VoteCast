#!/bin/bash
set -e

echo "Fixing permissions..."
mkdir -p /var/www/storage/logs
touch /var/www/storage/logs/laravel.log
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
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

echo "Starting scheduler..."
(while true; do php /var/www/artisan schedule:run >> /dev/null 2>&1; sleep 60; done) &

echo "Starting Nginx..."
nginx -g 'daemon off;'
