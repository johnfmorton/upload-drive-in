# Production Google Drive Authentication Debug Commands

Run these commands on your production server to diagnose the Google Drive authentication issues.

## 1. Check Environment Configuration

```bash
# Check if Google Drive credentials are set
php artisan config:show services.google

# Check cloud storage configuration
php artisan config:show cloud-storage.providers.google-drive

# Verify environment variables
php artisan tinker --execute="
echo 'GOOGLE_DRIVE_CLIENT_ID: ' . (env('GOOGLE_DRIVE_CLIENT_ID') ? 'SET' : 'NOT SET') . PHP_EOL;
echo 'GOOGLE_DRIVE_CLIENT_SECRET: ' . (env('GOOGLE_DRIVE_CLIENT_SECRET') ? 'SET' : 'NOT SET') . PHP_EOL;
echo 'APP_URL: ' . env('APP_URL') . PHP_EOL;
"
```

## 2. Check Database and User Status

```bash
# Check admin user exists and Google Drive connection status
php artisan tinker --execute="
\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user) {
    echo 'Admin user found: ' . \$user->email . PHP_EOL;
    echo 'Has Google Drive connected: ' . (\$user->hasGoogleDriveConnected() ? 'Yes' : 'No') . PHP_EOL;
    
    \$token = \App\Models\GoogleDriveToken::where('user_id', \$user->id)->first();
    if (\$token) {
        echo 'Token exists: Yes' . PHP_EOL;
        echo 'Token expires at: ' . (\$token->expires_at ? \$token->expires_at->toISOString() : 'Never') . PHP_EOL;
        echo 'Has refresh token: ' . (\$token->refresh_token ? 'Yes' : 'No') . PHP_EOL;
    } else {
        echo 'Token exists: No' . PHP_EOL;
    }
} else {
    echo 'No admin user found' . PHP_EOL;
}
"
```

## 3. Test Google Drive Service

```bash
# Test auth URL generation
php artisan tinker --execute="
\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user) {
    try {
        \$driveService = app(\App\Services\GoogleDriveService::class);
        \$authUrl = \$driveService->getAuthUrl(\$user, false);
        echo 'Auth URL generated successfully' . PHP_EOL;
        echo 'Auth URL: ' . \$authUrl . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Error generating auth URL: ' . \$e->getMessage() . PHP_EOL;
        echo 'Trace: ' . \$e->getTraceAsString() . PHP_EOL;
    }
}
"
```

## 4. Check Health Status

```bash
# Check current health status
php artisan tinker --execute="
\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user) {
    try {
        \$healthService = app(\App\Services\CloudStorageHealthService::class);
        \$providersHealth = \$healthService->getAllProvidersHealth(\$user);
        
        echo 'Current status:' . PHP_EOL;
        foreach (\$providersHealth as \$provider) {
            echo 'Provider: ' . \$provider['provider'] . PHP_EOL;
            echo 'Status: ' . \$provider['status'] . PHP_EOL;
            echo 'Consolidated Status: ' . \$provider['consolidated_status'] . PHP_EOL;
            echo 'Is Healthy: ' . (\$provider['is_healthy'] ? 'Yes' : 'No') . PHP_EOL;
            echo 'Status Message: ' . \$provider['status_message'] . PHP_EOL;
            echo '---' . PHP_EOL;
        }
    } catch (Exception \$e) {
        echo 'Error getting status: ' . \$e->getMessage() . PHP_EOL;
        echo 'Trace: ' . \$e->getTraceAsString() . PHP_EOL;
    }
}
"
```

## 5. Check Routes and URLs

```bash
# Check if routes are properly registered
php artisan route:list | grep google-drive

# Test callback URL generation
php artisan tinker --execute="
echo 'Google Drive callback URL: ' . route('google-drive.unified-callback') . PHP_EOL;
echo 'Admin cloud storage URL: ' . route('admin.cloud-storage.index') . PHP_EOL;
"
```

## 6. Clear Caches and Reset

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# If using OPcache
php artisan optimize:clear
```

## 7. Check File Permissions

```bash
# Check storage permissions
ls -la storage/
ls -la storage/app/
ls -la storage/logs/

# Check if storage directories are writable
php artisan tinker --execute="
echo 'Storage writable: ' . (is_writable(storage_path()) ? 'Yes' : 'No') . PHP_EOL;
echo 'Logs writable: ' . (is_writable(storage_path('logs')) ? 'Yes' : 'No') . PHP_EOL;
echo 'App storage writable: ' . (is_writable(storage_path('app')) ? 'Yes' : 'No') . PHP_EOL;
"
```

## 8. Check Database Health Status Records

```bash
# Check and fix health status records
php artisan tinker --execute="
\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user) {
    echo 'Checking CloudStorageHealthStatus records...' . PHP_EOL;
    
    \$healthStatus = \App\Models\CloudStorageHealthStatus::where('user_id', \$user->id)
        ->where('provider', 'google-drive')
        ->first();
    
    if (\$healthStatus) {
        echo 'Health Status Record Found:' . PHP_EOL;
        echo 'Status: ' . \$healthStatus->status . PHP_EOL;
        echo 'Consolidated Status: ' . (\$healthStatus->consolidated_status ?? 'NULL') . PHP_EOL;
        
        // Force recalculation of consolidated status
        echo 'Forcing recalculation...' . PHP_EOL;
        \$healthService = app(\App\Services\CloudStorageHealthService::class);
        \$newStatus = \$healthService->determineConsolidatedStatus(\$user, 'google-drive');
        echo 'New Consolidated Status: ' . \$newStatus . PHP_EOL;
        
        // Update the record
        \$healthStatus->update(['consolidated_status' => \$newStatus]);
        echo 'Updated database record' . PHP_EOL;
    } else {
        echo 'No health status record found' . PHP_EOL;
    }
}
"
```

## 9. Test File Upload Job

```bash
# Test if the upload job works
php artisan tinker --execute="
echo 'Testing file upload job...' . PHP_EOL;

\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user && \$user->hasGoogleDriveConnected()) {
    // Create a simple test file
    \$testContent = 'Production test file';
    \$filename = 'prod-test-' . time() . '.txt';
    \$localPath = 'uploads/' . \$filename;
    
    Storage::disk('public')->put(\$localPath, \$testContent);
    echo 'Created test file: ' . \$localPath . PHP_EOL;
    
    // Create FileUpload record
    \$fileUpload = \App\Models\FileUpload::create([
        'filename' => \$filename,
        'original_filename' => 'production-test.txt',
        'mime_type' => 'text/plain',
        'file_size' => strlen(\$testContent),
        'email' => 'test@production.com',
        'message' => 'Production test upload',
        'validation_method' => 'email',
        'uploaded_by_user_id' => \$user->id,
        'company_user_id' => \$user->id,
    ]);
    
    echo 'Created FileUpload record with ID: ' . \$fileUpload->id . PHP_EOL;
    
    // Dispatch the job
    try {
        \App\Jobs\UploadToGoogleDrive::dispatch(\$fileUpload);
        echo 'Job dispatched successfully' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Error dispatching job: ' . \$e->getMessage() . PHP_EOL;
    }
} else {
    echo 'Admin user not found or Google Drive not connected' . PHP_EOL;
}
"
```

## 10. Check Logs

```bash
# Check recent logs for errors
tail -n 50 storage/logs/laravel.log

# Check for Google Drive specific errors
grep -i "google\|drive\|oauth" storage/logs/laravel.log | tail -20
```