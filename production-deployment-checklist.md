# Production Deployment Checklist for Google Drive Fixes

## Files That Need to be Updated on Production

### 1. Core Fix Files
- ✅ `app/Jobs/UploadToGoogleDrive.php` - Fixed TypeError in failed() method
- ✅ `app/Services/CloudStorageLogService.php` - Added missing methods

### 2. Deployment Steps

#### Step 1: Deploy Code Changes
```bash
# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

#### Step 2: Verify Environment Configuration
```bash
# Check Google Drive credentials are set
php artisan config:show services.google

# Verify the redirect URI matches your production domain
# Should be: https://yourdomain.com/google-drive/callback
```

#### Step 3: Database Health Status Fix
```bash
# Run the health status fix command
php artisan tinker --execute="
\$users = \App\Models\User::whereIn('role', [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::EMPLOYEE])->get();
foreach (\$users as \$user) {
    \$healthStatus = \App\Models\CloudStorageHealthStatus::where('user_id', \$user->id)
        ->where('provider', 'google-drive')
        ->first();
    
    if (\$healthStatus && \$healthStatus->consolidated_status === 'not_connected') {
        \$healthService = app(\App\Services\CloudStorageHealthService::class);
        \$newStatus = \$healthService->determineConsolidatedStatus(\$user, 'google-drive');
        \$healthStatus->update(['consolidated_status' => \$newStatus]);
        echo 'Updated status for user ' . \$user->email . ' to: ' . \$newStatus . PHP_EOL;
    }
}
echo 'Health status update complete' . PHP_EOL;
"
```

#### Step 4: Test the Fixes
```bash
# Test admin user Google Drive connection
php artisan tinker --execute="
\$user = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
if (\$user) {
    echo 'Testing admin user: ' . \$user->email . PHP_EOL;
    
    // Test auth URL generation
    try {
        \$driveService = app(\App\Services\GoogleDriveService::class);
        \$authUrl = \$driveService->getAuthUrl(\$user, false);
        echo '✅ Auth URL generation: SUCCESS' . PHP_EOL;
    } catch (Exception \$e) {
        echo '❌ Auth URL generation: FAILED - ' . \$e->getMessage() . PHP_EOL;
    }
    
    // Test health status
    try {
        \$healthService = app(\App\Services\CloudStorageHealthService::class);
        \$health = \$healthService->getAllProvidersHealth(\$user);
        \$googleHealth = collect(\$health)->firstWhere('provider', 'google-drive');
        echo '✅ Health status: ' . \$googleHealth['consolidated_status'] . PHP_EOL;
    } catch (Exception \$e) {
        echo '❌ Health status: FAILED - ' . \$e->getMessage() . PHP_EOL;
    }
}
"
```

#### Step 5: Restart Services
```bash
# Restart queue workers (if using supervisor)
sudo supervisorctl restart all

# Or if using systemd
sudo systemctl restart your-queue-worker

# Restart web server (if needed)
sudo systemctl reload nginx
# or
sudo systemctl reload apache2
```

## Common Production Issues and Solutions

### Issue 1: "Class not found" errors
**Solution**: Run `composer dump-autoload --optimize`

### Issue 2: Config cache issues
**Solution**: 
```bash
php artisan config:clear
php artisan config:cache
```

### Issue 3: Route not found errors
**Solution**:
```bash
php artisan route:clear
php artisan route:cache
```

### Issue 4: Permission errors
**Solution**:
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Fix bootstrap cache permissions
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 bootstrap/cache/
```

### Issue 5: Google OAuth redirect URI mismatch
**Problem**: The redirect URI in your Google Cloud Console doesn't match production URL

**Solution**: 
1. Go to Google Cloud Console
2. Navigate to APIs & Services > Credentials
3. Edit your OAuth 2.0 Client ID
4. Add your production callback URL: `https://yourdomain.com/google-drive/callback`

### Issue 6: Environment variables not loading
**Solution**:
```bash
# Check if .env file exists and has correct permissions
ls -la .env
cat .env | grep GOOGLE_DRIVE

# If using Laravel Forge or similar, check environment variables in the panel
```

## Verification Commands

After deployment, run these commands to verify everything is working:

```bash
# 1. Check if all routes are available
php artisan route:list | grep -E "(google-drive|cloud-storage)"

# 2. Test Google Drive service instantiation
php artisan tinker --execute="
try {
    \$service = app(\App\Services\GoogleDriveService::class);
    echo '✅ GoogleDriveService: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ GoogleDriveService: ' . \$e->getMessage() . PHP_EOL;
}
"

# 3. Test CloudStorageLogService methods
php artisan tinker --execute="
try {
    \$service = app(\App\Services\CloudStorageLogService::class);
    \$user = \App\Models\User::first();
    if (\$user) {
        \$operationId = \$service->logOperationStart('test', 'google-drive', \$user);
        echo '✅ CloudStorageLogService: OK' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ CloudStorageLogService: ' . \$e->getMessage() . PHP_EOL;
}
"

# 4. Test job instantiation
php artisan tinker --execute="
try {
    \$upload = \App\Models\FileUpload::first();
    if (\$upload) {
        \$job = new \App\Jobs\UploadToGoogleDrive(\$upload);
        echo '✅ UploadToGoogleDrive Job: OK' . PHP_EOL;
    } else {
        echo 'ℹ️  No FileUpload records to test with' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ UploadToGoogleDrive Job: ' . \$e->getMessage() . PHP_EOL;
}
"
```

## Post-Deployment Testing

1. **Login as admin user**
2. **Navigate to Cloud Storage settings**
3. **Try to connect Google Drive**
4. **Upload a test file**
5. **Check dashboard shows "Connected" status**

If any step fails, check the logs and run the diagnostic commands from the debug guide.