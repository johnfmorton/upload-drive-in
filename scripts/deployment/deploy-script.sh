#!/bin/bash

# Laravel Deployment Script
# This script ensures proper cache clearing and optimization for production deployments

echo "🚀 Starting Laravel deployment..."

# Step 1: Clear all caches
echo "📝 Clearing application caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Step 2: Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Step 3: Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 4: Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Step 5: Build frontend assets (if using Vite)
echo "🎨 Building frontend assets..."
npm ci
npm run build

# Step 6: Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Step 7: Queue restart (if using queue workers)
echo "🔄 Restarting queue workers..."
php artisan queue:restart

echo "✅ Deployment completed successfully!"