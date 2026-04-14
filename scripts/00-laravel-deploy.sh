#!/usr/bin/env bash
set -e
cd /var/www/html

echo "Running composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Storage permissions..."
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force
