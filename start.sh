#!/bin/bash
set -e

echo "========================================="
echo "Starting VoteCast Application"
echo "========================================="

# Fix permissions
echo "Setting up permissions..."
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/{sessions,views,cache}
touch /var/www/storage/logs/laravel.log
chmod -R 777 /var/www/storage
chmod -R 777 /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage
chown -R www-data:www-data /var/www/bootstrap/cache

# Install dependencies if vendor doesn't exist
if [ ! -d "/var/www/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Check if .env exists, if not copy from example
if [ ! -f "/var/www/.env" ]; then
    echo "Creating .env file..."
    cp /var/www/.env.example /var/www/.env
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" /var/www/.env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run migrations first (this creates the necessary tables)
echo "Running database migrations..."
php artisan migrate --force

# Clear config cache (safe to do after migrations)
echo "Clearing config cache..."
php artisan config:clear

# Clear route and view cache
php artisan route:clear
php artisan view:clear

# Create storage link
echo "Creating storage link..."
php artisan storage:link --force

# Start PHP-FPM
echo "Starting PHP-FPM..."
php-fpm -D

# Start Laravel scheduler
echo "Starting Laravel scheduler..."
(
    while true; do
        php /var/www/artisan schedule:run --verbose --no-interaction >> /var/www/storage/logs/scheduler.log 2>&1
        sleep 60
    done
) &

# Start Laravel queue worker
echo "Starting queue worker..."
(
    while true; do
        php /var/www/artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> /var/www/storage/logs/queue.log 2>&1
        sleep 1
    done
) &

echo "========================================="
echo "VoteCast is running on port 8000"
echo "Access at: http://localhost:8000"
echo "========================================="

# Start Nginx (foreground)
nginx -g 'daemon off;'
