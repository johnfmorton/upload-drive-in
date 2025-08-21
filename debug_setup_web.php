<?php

// Web-accessible debug script for setup status
// Access this via: https://your-domain.com/debug_setup_web.php

header('Content-Type: text/plain');

echo "=== Web Setup Debug ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "URL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\n\n";

try {
    require_once '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    
    echo "✓ Laravel bootstrap successful\n";
    
    // Check environment variables
    echo "\n=== Environment Variables ===\n";
    $setupVars = [
        'APP_ENV' => env('APP_ENV'),
        'APP_DEBUG' => env('APP_DEBUG') ? 'true' : 'false',
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'SETUP_BOOTSTRAP_CHECKS' => env('SETUP_BOOTSTRAP_CHECKS') ? 'true' : 'false',
        'SETUP_CACHE_STATE' => env('SETUP_CACHE_STATE') ? 'true' : 'false',
    ];
    
    foreach ($setupVars as $key => $value) {
        echo "{$key}: {$value}\n";
    }
    
    // Test database connection
    echo "\n=== Database Test ===\n";
    try {
        $pdo = DB::connection()->getPdo();
        echo "✓ Database connection successful\n";
        
        // Check if users table exists
        $hasUsersTable = Schema::hasTable('users');
        echo "Users table exists: " . ($hasUsersTable ? 'YES' : 'NO') . "\n";
        
        if ($hasUsersTable) {
            // Check if admin user exists
            $adminCount = DB::table('users')->where('role', 'admin')->count();
            echo "Admin users count: {$adminCount}\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    // Check setup service
    echo "\n=== Setup Service Test ===\n";
    try {
        $setupService = $app->make(\App\Services\SetupService::class);
        
        $isSetupRequired = $setupService->isSetupRequired();
        echo "Setup Required: " . ($isSetupRequired ? 'YES' : 'NO') . "\n";
        
        $isSetupComplete = $setupService->isSetupComplete();
        echo "Setup Complete: " . ($isSetupComplete ? 'YES' : 'NO') . "\n";
        
        $currentStep = $setupService->getSetupStep();
        echo "Current Step: {$currentStep}\n";
        
    } catch (Exception $e) {
        echo "✗ Setup service error: " . $e->getMessage() . "\n";
    }
    
    // Check middleware
    echo "\n=== Middleware Test ===\n";
    try {
        $middleware = $app->make(\App\Http\Middleware\RequireSetupMiddleware::class);
        echo "✓ RequireSetupMiddleware instantiated\n";
        
        // Create a fake request to test middleware logic
        $request = Illuminate\Http\Request::create('/', 'GET');
        echo "Test request created for: " . $request->path() . "\n";
        
    } catch (Exception $e) {
        echo "✗ Middleware error: " . $e->getMessage() . "\n";
    }
    
    // Check routes
    echo "\n=== Routes Test ===\n";
    try {
        $router = $app->make('router');
        $routes = $router->getRoutes();
        
        $setupRoutes = [];
        foreach ($routes as $route) {
            if (str_starts_with($route->getName() ?? '', 'setup.')) {
                $setupRoutes[] = $route->getName();
            }
        }
        
        echo "Setup routes found: " . count($setupRoutes) . "\n";
        if (count($setupRoutes) > 0) {
            echo "Routes: " . implode(', ', array_slice($setupRoutes, 0, 5)) . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Routes error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Critical error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Recommendations ===\n";
echo "1. If 'Setup Required' shows 'NO', check why setup service thinks setup is complete\n";
echo "2. If database connection fails, verify DB credentials\n";
echo "3. If admin users count > 0, that's why setup isn't required\n";
echo "4. Try accessing /setup directly\n";
echo "5. Check storage/logs/laravel.log for errors\n";