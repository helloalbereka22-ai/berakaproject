#!/bin/bash

##############################################################################
# Al-Bareka Deployment Script for Yegara Host
# This script automates the deployment process
##############################################################################

set -e  # Exit on error

echo "================================"
echo "Al-Bareka Deployment Script"
echo "================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Make sure you're in the project root."
    exit 1
fi

echo "✅ Found Laravel project"
echo ""

# Step 1: Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader
echo "✅ Dependencies installed"
echo ""

# Step 2: Generate app key if needed
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
    echo "✅ Application key generated"
else
    echo "✅ Application key already exists"
fi
echo ""

# Step 3: Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force
echo "✅ Migrations completed"
echo ""

# Step 4: Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "✅ Caches cleared"
echo ""

# Step 5: Cache for production
echo "⚡ Caching for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Production caches built"
echo ""

# Step 6: Fix permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod 644 .env
echo "✅ Permissions set"
echo ""

# Step 7: Storage link
echo "🔗 Creating storage link..."
php artisan storage:link
echo "✅ Storage link created"
echo ""

echo "================================"
echo "✅ Deployment Complete!"
echo "================================"
echo ""
echo "Your application should be ready at:"
echo "https://albareka.com"
echo ""
echo "Next steps:"
echo "1. Visit https://albareka.com"
echo "2. Login with your admin credentials"
echo "3. Verify all features work correctly"
echo "4. Monitor logs for any errors"
echo ""
