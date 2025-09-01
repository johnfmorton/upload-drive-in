#!/bin/bash

# Laravel Forge Deployment Script
# This script should be used in your Forge deployment configuration

cd /home/forge/uploads.morton.dev

# Enable maintenance mode
php artisan down --retry=60 --secret="deployment-secret-key"

# Pull latest changes
git pull origin main

# Install/update Composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear all caches BEFORE optimization
echo "Clearing all caches..."
php artisan config:clear
php artisan route:clear  
php artisan view:clear
php artisan cache:clear

# Run database migrations
php artisan migrate --force

# Install NPM dependencies and build assets
npm ci
npm run build

# Cache configuration and routes for production
echo "Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

# Disable maintenance mode
php artisan up

echo "Deployment completed successfully!"