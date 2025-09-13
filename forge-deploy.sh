#!/bin/bash

set -e

echo "🚀 Starting deployment..."

# Change to the application directory
cd /home/forge/margflow

# Put application in maintenance mode
echo "🔧 Enabling maintenance mode..."
php artisan down --refresh=15 || true

# Pull the latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install/Update Composer dependencies (optimized for production)
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install/Update NPM dependencies and build assets
echo "🎨 Building frontend assets..."
npm ci --production
npm run build

# Clear various caches before migration
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Database migrations in correct order
echo "🗃️  Running database migrations..."

# 1. RBAC Database First (foundational)
echo "   → Migrating RBAC database..."
php artisan migrate --database=rbac --force

# 2. Main Database Second (core application)
echo "   → Migrating main database..."
php artisan migrate --database=mysql --force

# 3. Business Database Last (supplementary)
echo "   → Migrating business database..."
php artisan migrate --database=business_db --force

# Cache configuration and routes for performance
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue restart (if using queue workers)
echo "🔄 Restarting queue workers..."
php artisan queue:restart || true

# Clear opcache if available
if command -v php-fpm &> /dev/null; then
    echo "🧹 Clearing OPcache..."
    php artisan opcache:clear || true
fi

# Take application out of maintenance mode
echo "✅ Disabling maintenance mode..."
php artisan up

# Run health checks
echo "🏥 Running health checks..."
php artisan health:check || echo "⚠️  Health check failed - please investigate"

echo "🎉 Deployment completed successfully!"

# Optional: Send deployment notification
# curl -X POST -H 'Content-type: application/json' --data '{"text":"🚀 Deployment completed for margflow"}' YOUR_SLACK_WEBHOOK_URL || true