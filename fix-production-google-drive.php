<?php

/**
 * Production Google Drive Fix Script
 * 
 * Run this script on your production server to diagnose and fix
 * Google Drive authentication issues.
 * 
 * Usage: php fix-production-google-drive.php
 */

// Ensure we're in the Laravel root directory
if (!file_exists('artisan')) {
    echo "❌ Error: This script must be run from the Laravel root directory.\n";
    exit(1);
}

echo "🔧 Google Drive Production Fix Script\n";
echo "=====================================\n\n";

// Step 1: Check environment
echo "1. Checking environment configuration...\n";
$clientId = env('GOOGLE_DRIVE_CLIENT_ID');
$clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
$appUrl = env('APP_URL');

if (empty($clientId)) {
    echo "❌ GOOGLE_DRIVE_CLIENT_ID is not set\n";
} else {
    echo "✅ GOOGLE_DRIVE_CLIENT_ID is set\n";
}

if (empty($clientSecret)) {
    echo "❌ GOOGLE_DRIVE_CLIENT_SECRET is not set\n";
} else {
    echo "✅ GOOGLE_DRIVE_CLIENT_SECRET is set\n";
}

echo "ℹ️  APP_URL: {$appUrl}\n";
echo "ℹ️  Expected callback URL: {$appUrl}/google-drive/callback\n\n";

// Step 2: Clear caches
echo "2. Clearing caches...\n";
exec('php artisan config:clear', $output, $return);
if ($return === 0) {
    echo "✅ Config cache cleared\n";
} else {
    echo "❌ Failed to clear config cache\n";
}

exec('php artisan cache:clear', $output, $return);
if ($return === 0) {
    echo "✅ Application cache cleared\n";
} else {
    echo "❌ Failed to clear application cache\n";
}

exec('php artisan route:clear', $output, $return);
if ($return === 0) {
    echo "✅ Route cache cleared\n";
} else {
    echo "❌ Failed to clear route cache\n";
}

echo "\n";

// Step 3: Check database connection
echo "3. Checking database connection...\n";
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    echo "✅ Database connection successful\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Check admin user
echo "\n4. Checking admin user...\n";
$checkAdminScript = "
require_once 'bootstrap/app.php';
\$app = \$app ?? require_once 'bootstrap/app.php';

try {
    \$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
    if (\$user) {
        echo '✅ Admin user found: ' . \$user->email . PHP_EOL;
        
        \$token = \App\Models\GoogleDriveToken::where('user_id', \$user->id)->first();
        if (\$token) {
            echo '✅ Google Drive token exists' . PHP_EOL;
            if (\$token->expires_at && \$token->expires_at->isPast()) {
                echo '⚠️  Token is expired' . PHP_EOL;
            } else {
                echo '✅ Token is valid' . PHP_EOL;
            }
        } else {
            echo '❌ No Google Drive token found' . PHP_EOL;
        }
    } else {
        echo '❌ No admin user found' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Error checking admin user: ' . \$e->getMessage() . PHP_EOL;
}
";

$tempFile = tempnam(sys_get_temp_dir(), 'check_admin');
file_put_contents($tempFile, "<?php\n" . $checkAdminScript);
exec("php {$tempFile}", $output, $return);
echo implode("\n", $output) . "\n";
unlink($tempFile);

// Step 5: Fix health status
echo "\n5. Fixing health status records...\n";
$fixHealthScript = "
require_once 'bootstrap/app.php';
\$app = \$app ?? require_once 'bootstrap/app.php';

try {
    \$users = \App\Models\User::whereIn('role', [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::EMPLOYEE])->get();
    \$fixed = 0;
    
    foreach (\$users as \$user) {
        \$healthStatus = \App\Models\CloudStorageHealthStatus::where('user_id', \$user->id)
            ->where('provider', 'google-drive')
            ->first();
        
        if (\$healthStatus && \$healthStatus->consolidated_status === 'not_connected') {
            \$healthService = app(\App\Services\CloudStorageHealthService::class);
            \$newStatus = \$healthService->determineConsolidatedStatus(\$user, 'google-drive');
            \$healthStatus->update(['consolidated_status' => \$newStatus]);
            echo '✅ Updated status for ' . \$user->email . ' to: ' . \$newStatus . PHP_EOL;
            \$fixed++;
        }
    }
    
    if (\$fixed === 0) {
        echo 'ℹ️  No health status records needed fixing' . PHP_EOL;
    } else {
        echo '✅ Fixed ' . \$fixed . ' health status records' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Error fixing health status: ' . \$e->getMessage() . PHP_EOL;
}
";

$tempFile = tempnam(sys_get_temp_dir(), 'fix_health');
file_put_contents($tempFile, "<?php\n" . $fixHealthScript);
exec("php {$tempFile}", $output, $return);
echo implode("\n", $output) . "\n";
unlink($tempFile);

// Step 6: Test services
echo "\n6. Testing services...\n";
$testServicesScript = "
require_once 'bootstrap/app.php';
\$app = \$app ?? require_once 'bootstrap/app.php';

try {
    // Test GoogleDriveService
    \$driveService = app(\App\Services\GoogleDriveService::class);
    echo '✅ GoogleDriveService instantiated successfully' . PHP_EOL;
    
    // Test CloudStorageLogService
    \$logService = app(\App\Services\CloudStorageLogService::class);
    echo '✅ CloudStorageLogService instantiated successfully' . PHP_EOL;
    
    // Test CloudStorageHealthService
    \$healthService = app(\App\Services\CloudStorageHealthService::class);
    echo '✅ CloudStorageHealthService instantiated successfully' . PHP_EOL;
    
    // Test job instantiation
    \$upload = \App\Models\FileUpload::first();
    if (\$upload) {
        \$job = new \App\Jobs\UploadToGoogleDrive(\$upload);
        echo '✅ UploadToGoogleDrive job instantiated successfully' . PHP_EOL;
    } else {
        echo 'ℹ️  No FileUpload records to test job with' . PHP_EOL;
    }
    
} catch (Exception \$e) {
    echo '❌ Service test failed: ' . \$e->getMessage() . PHP_EOL;
}
";

$tempFile = tempnam(sys_get_temp_dir(), 'test_services');
file_put_contents($tempFile, "<?php\n" . $testServicesScript);
exec("php {$tempFile}", $output, $return);
echo implode("\n", $output) . "\n";
unlink($tempFile);

// Step 7: Check file permissions
echo "\n7. Checking file permissions...\n";
$storageWritable = is_writable('storage');
$logsWritable = is_writable('storage/logs');
$appStorageWritable = is_writable('storage/app');

echo $storageWritable ? "✅ storage/ is writable\n" : "❌ storage/ is not writable\n";
echo $logsWritable ? "✅ storage/logs/ is writable\n" : "❌ storage/logs/ is not writable\n";
echo $appStorageWritable ? "✅ storage/app/ is writable\n" : "❌ storage/app/ is not writable\n";

// Final summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 SUMMARY\n";
echo str_repeat("=", 50) . "\n";

if (empty($clientId) || empty($clientSecret)) {
    echo "❌ CRITICAL: Google Drive credentials not configured\n";
    echo "   Please set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET in your .env file\n";
}

if (!$storageWritable || !$logsWritable || !$appStorageWritable) {
    echo "❌ CRITICAL: Storage permissions issue\n";
    echo "   Run: sudo chown -R www-data:www-data storage/ && sudo chmod -R 775 storage/\n";
}

echo "\n📋 NEXT STEPS:\n";
echo "1. Ensure Google Drive credentials are set in .env\n";
echo "2. Add your production callback URL to Google Cloud Console:\n";
echo "   {$appUrl}/google-drive/callback\n";
echo "3. Test the connection by logging in as admin and going to Cloud Storage settings\n";
echo "4. If issues persist, check storage/logs/laravel.log for detailed errors\n";

echo "\n✅ Fix script completed!\n";