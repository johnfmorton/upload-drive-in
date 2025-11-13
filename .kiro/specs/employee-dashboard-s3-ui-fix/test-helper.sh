#!/bin/bash

# Manual Testing Helper Script for Employee Dashboard S3 UI Fix
# This script provides quick commands for testing different configurations

set -e

echo "🧪 Employee Dashboard S3 UI Fix - Testing Helper"
echo "=================================================="
echo ""

# Function to display current configuration
show_config() {
    echo "📋 Current Configuration:"
    echo "------------------------"
    grep "CLOUD_STORAGE_DEFAULT" .env || echo "CLOUD_STORAGE_DEFAULT not set"
    echo ""
}

# Function to switch to S3
switch_to_s3() {
    echo "🔄 Switching to Amazon S3..."
    sed -i.bak 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT="amazon-s3"/' .env
    ddev artisan config:clear
    ddev artisan cache:clear
    ddev artisan view:clear
    echo "✅ Switched to Amazon S3"
    echo "🌐 Please refresh your browser: https://upload-drive-in.ddev.site/employee/dashboard"
    echo ""
}

# Function to switch to Google Drive
switch_to_google_drive() {
    echo "🔄 Switching to Google Drive..."
    sed -i.bak 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT="google-drive"/' .env
    ddev artisan config:clear
    ddev artisan cache:clear
    ddev artisan view:clear
    echo "✅ Switched to Google Drive"
    echo "🌐 Please refresh your browser: https://upload-drive-in.ddev.site/employee/dashboard"
    echo ""
}

# Function to test invalid config
test_invalid_config() {
    echo "🔄 Setting invalid provider for error testing..."
    sed -i.bak 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT="invalid-provider"/' .env
    ddev artisan config:clear
    ddev artisan cache:clear
    ddev artisan view:clear
    echo "✅ Set to invalid provider"
    echo "🌐 Please refresh your browser to test error handling"
    echo ""
}

# Function to restore backup
restore_backup() {
    if [ -f .env.bak ]; then
        echo "🔄 Restoring previous configuration..."
        mv .env.bak .env
        ddev artisan config:clear
        ddev artisan cache:clear
        ddev artisan view:clear
        echo "✅ Configuration restored"
    else
        echo "❌ No backup found"
    fi
    echo ""
}

# Function to clear all caches
clear_caches() {
    echo "🧹 Clearing all caches..."
    ddev artisan config:clear
    ddev artisan cache:clear
    ddev artisan view:clear
    ddev artisan route:clear
    echo "✅ All caches cleared"
    echo ""
}

# Function to check logs
check_logs() {
    echo "📜 Recent Laravel logs:"
    echo "----------------------"
    ddev artisan pail --filter="employee" || tail -n 50 storage/logs/laravel.log
    echo ""
}

# Function to create test employee user
create_test_user() {
    echo "👤 Creating test employee user..."
    ddev artisan tinker --execute="
        \$user = App\Models\User::firstOrCreate(
            ['email' => 'employee@test.com'],
            [
                'name' => 'Test Employee',
                'password' => bcrypt('password'),
                'role' => 'employee',
                'email_verified_at' => now(),
            ]
        );
        echo 'User created/found: ' . \$user->email . PHP_EOL;
        echo 'Login at: https://upload-drive-in.ddev.site/login' . PHP_EOL;
        echo 'Email: employee@test.com' . PHP_EOL;
        echo 'Password: password' . PHP_EOL;
    "
    echo ""
}

# Main menu
case "${1:-menu}" in
    "config")
        show_config
        ;;
    "s3")
        switch_to_s3
        ;;
    "google-drive"|"gd")
        switch_to_google_drive
        ;;
    "invalid")
        test_invalid_config
        ;;
    "restore")
        restore_backup
        ;;
    "clear")
        clear_caches
        ;;
    "logs")
        check_logs
        ;;
    "user")
        create_test_user
        ;;
    "menu"|*)
        echo "Available commands:"
        echo "  ./test-helper.sh config          - Show current configuration"
        echo "  ./test-helper.sh s3              - Switch to Amazon S3"
        echo "  ./test-helper.sh google-drive    - Switch to Google Drive"
        echo "  ./test-helper.sh invalid         - Test invalid configuration"
        echo "  ./test-helper.sh restore         - Restore previous configuration"
        echo "  ./test-helper.sh clear           - Clear all caches"
        echo "  ./test-helper.sh logs            - Check recent logs"
        echo "  ./test-helper.sh user            - Create test employee user"
        echo ""
        echo "Current configuration:"
        show_config
        ;;
esac
