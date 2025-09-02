#!/bin/bash

# Post-Deployment Fixes Script
# Run this after deploying code changes to production

echo "🚀 Running post-deployment fixes..."

# Clear all caches
echo "📦 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix cloud storage health status records
echo "🔧 Fixing cloud storage health status..."
php artisan cloud-storage:fix-health-status --dry-run

# Ask for confirmation before applying fixes
read -p "Apply the health status fixes? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan cloud-storage:fix-health-status
    echo "✅ Health status fixes applied"
else
    echo "⏭️  Health status fixes skipped"
fi

# Restart queue workers (if using supervisor)
if command -v supervisorctl &> /dev/null; then
    echo "🔄 Restarting queue workers..."
    sudo supervisorctl restart all
fi

echo "✅ Post-deployment fixes completed!"