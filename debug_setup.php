<?php

// Debug script to check setup status on remote server
echo "=== Setup Debug Information ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Try to bootstrap Laravel to check setup service
try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    echo "✓ Laravel bootstrap successful\n";
    
    // Check if we can access the setup service
    try {
        $setupService = $app->make(\App\Services\SetupService::class);
        echo "✓ SetupService instantiated\n";
        
        // Check if setup is required
        $isSetupRequired = $setupService->isSetupRequired();
        echo "Setup Required: " . ($isSetupRequired ? 'YES' : 'NO') . "\n";
        
        // Check if setup is complete
        $isSetupComplete = $setupService->isSetupComplete();
        echo "Setup Complete: " . ($isSetupComplete ? 'YES' : 'NO') . "\n";
        
        // Get current setup step
        $currentStep = $setupService->getSetupStep();
        echo "Current Step: " . $currentStep . "\n";
        
        // Check assets
        $assetService = $app->make(\App\Services\AssetValidationService::class);
        $assetsValid = $assetService->areAssetRequirementsMet();
        echo "Assets Valid: " . ($assetsValid ? 'YES' : 'NO') . "\n";
        
    } catch (Exception $e) {
        echo "✗ Error checking setup service: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Laravel bootstrap failed: " . $e->getMessage() . "\n";
}

// Check if .env file exists
if (file_exists('.env')) {
    echo "✓ .env file exists\n";
} else {
    echo "✗ .env file missing\n";
    exit(1);
}

// Check database configuration
$envContent = file_get_contents('.env');
if (strpos($envContent, 'DB_HOST=127.0.0.1') !== false) {
    echo "✓ Database host looks correct (127.0.0.1)\n";
} else {
    echo "⚠ Database host might be incorrect\n";
}

// Check if setup state file exists
$setupStateFile = 'storage/app/setup/setup-state.json';
if (file_exists($setupStateFile)) {
    echo "⚠ Setup state file exists: " . $setupStateFile . "\n";
    echo "Content: " . file_get_contents($setupStateFile) . "\n";
} else {
    echo "✓ No setup state file found (setup should be required)\n";
}

// Check if build assets exist
if (file_exists('public/build/manifest.json')) {
    echo "✓ Vite manifest exists\n";
} else {
    echo "✗ Vite manifest missing - this could prevent setup\n";
}

// Check storage permissions
if (is_writable('storage')) {
    echo "✓ Storage directory is writable\n";
} else {
    echo "✗ Storage directory is not writable\n";
}

if (is_writable('.env')) {
    echo "✓ .env file is writable\n";
} else {
    echo "✗ .env file is not writable\n";
}

// Check setup configuration
if (file_exists('config/setup.php')) {
    echo "✓ Setup config file exists\n";
} else {
    echo "✗ Setup config file missing\n";
}

echo "\n=== Environment Variables Check ===\n";

// Check for setup-related environment variables
$setupVars = [
    'SETUP_BOOTSTRAP_CHECKS',
    'SETUP_CACHE_STATE', 
    'SETUP_CACHE_TTL',
    'SETUP_ASSET_MANIFEST_REQUIRED',
    'SETUP_NODE_ENVIRONMENT_CHECK',
    'SETUP_BUILD_INSTRUCTIONS_ENABLED'
];

foreach ($setupVars as $var) {
    if (strpos($envContent, $var) !== false) {
        // Extract the value
        if (preg_match("/^{$var}=(.*)$/m", $envContent, $matches)) {
            echo "✓ {$var}={$matches[1]}\n";
        } else {
            echo "⚠ {$var} found but value unclear\n";
        }
    } else {
        echo "✗ {$var} missing\n";
    }
}

echo "\n=== Recommendations ===\n";
echo "1. Add SETUP_BOOTSTRAP_CHECKS=true to .env file\n";
echo "2. Set SETUP_CACHE_STATE=false temporarily\n";
echo "3. Clear all caches: php artisan config:clear && php artisan cache:clear\n";
echo "4. Remove setup state: rm -f storage/app/setup/setup-state.json\n";
echo "5. Try visiting /setup directly in your browser\n";
echo "6. Check Laravel logs in storage/logs/laravel.log\n";

// Try to check if we can connect to database (basic check)
try {
    $host = '127.0.0.1';
    $port = 3306;
    
    // Try to create a socket connection to check if database is reachable
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        echo "✓ Database server appears to be reachable\n";
        fclose($connection);
    } else {
        echo "✗ Cannot reach database server: $errstr ($errno)\n";
    }
} catch (Exception $e) {
    echo "⚠ Could not test database connection: " . $e->getMessage() . "\n";
}