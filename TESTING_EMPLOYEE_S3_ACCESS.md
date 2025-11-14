# Testing Employee Cloud Storage Access Control

## Quick Test Guide

### Setup

1. Ensure you have both admin and employee users created
2. Have access to the `.env` file to switch providers

### Test Scenario 1: Google Drive (OAuth Provider)

```bash
# 1. Set provider to Google Drive
echo "CLOUD_STORAGE_DEFAULT=google-drive" >> .env
ddev artisan config:clear
ddev artisan view:clear

# 2. Login as employee user
# Navigate to: https://upload-drive-in.ddev.site/employee/{username}/dashboard

# 3. Check navigation
# ✅ EXPECTED: "Cloud Storage" link should appear in user dropdown menu

# 4. Click "Cloud Storage" link
# ✅ EXPECTED: Should navigate to cloud storage page showing Google Drive options

# 5. Test API endpoints (optional - use browser dev tools)
# GET /employee/{username}/cloud-storage/status
# ✅ EXPECTED: Returns status data (200 OK)
```

### Test Scenario 2: Amazon S3 (API Key Provider)

```bash
# 1. Set provider to Amazon S3
sed -i '' 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT=amazon-s3/' .env
ddev artisan config:clear
ddev artisan view:clear

# 2. Login as employee user (or refresh page if already logged in)
# Navigate to: https://upload-drive-in.ddev.site/employee/{username}/dashboard

# 3. Check navigation
# ✅ EXPECTED: "Cloud Storage" link should NOT appear in user dropdown menu

# 4. Try to access cloud storage page directly
# Navigate to: https://upload-drive-in.ddev.site/employee/{username}/cloud-storage
# ✅ EXPECTED: Should redirect to dashboard with message:
#    "Cloud storage is managed by the administrator for the current provider."

# 5. Test API endpoints (use curl or Postman)
curl -X GET "https://upload-drive-in.ddev.site/employee/{username}/cloud-storage/status" \
  -H "Cookie: your-session-cookie"
# ✅ EXPECTED: Returns 403 error with message:
#    "Cloud storage status is not available for the current provider."
```

### Test Scenario 3: Mobile/Responsive View

```bash
# 1. Set provider to Google Drive
echo "CLOUD_STORAGE_DEFAULT=google-drive" >> .env
ddev artisan config:clear

# 2. Open browser in mobile view (or resize to < 640px)
# 3. Login as employee user
# 4. Click hamburger menu
# ✅ EXPECTED: "Cloud Storage" link appears in mobile menu

# 5. Switch to S3
sed -i '' 's/CLOUD_STORAGE_DEFAULT=.*/CLOUD_STORAGE_DEFAULT=amazon-s3/' .env
ddev artisan config:clear

# 6. Refresh page and open hamburger menu
# ✅ EXPECTED: "Cloud Storage" link does NOT appear in mobile menu
```

## Verification Checklist

### When Provider = Google Drive
- [ ] Cloud Storage link visible in desktop dropdown
- [ ] Cloud Storage link visible in mobile menu
- [ ] Can access `/employee/{username}/cloud-storage` page
- [ ] Page shows Google Drive connection options
- [ ] API endpoints return data (not 403)

### When Provider = Amazon S3
- [ ] Cloud Storage link NOT visible in desktop dropdown
- [ ] Cloud Storage link NOT visible in mobile menu
- [ ] Cannot access `/employee/{username}/cloud-storage` (redirects to dashboard)
- [ ] Redirect shows info message about admin management
- [ ] API endpoints return 403 errors

### Admin User (Should Not Be Affected)
- [ ] Admin can always see Cloud Storage in navigation
- [ ] Admin can access cloud storage page regardless of provider
- [ ] Admin can configure S3 settings

## Common Issues

### Issue: Navigation still shows after switching to S3
**Solution:** Clear config and view cache
```bash
ddev artisan config:clear
ddev artisan view:clear
```

### Issue: Getting 500 error instead of redirect
**Solution:** Check logs for PHP errors
```bash
ddev artisan pail
# or
ddev logs
```

### Issue: Changes not taking effect
**Solution:** Clear all caches
```bash
ddev artisan optimize:clear
```

## Automated Testing (Future)

Consider adding these test cases:

```php
// tests/Feature/Employee/CloudStorageAccessControlTest.php

public function test_employee_can_access_cloud_storage_with_google_drive()
{
    config(['cloud-storage.default' => 'google-drive']);
    
    $response = $this->actingAs($this->employee)
        ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));
    
    $response->assertOk();
}

public function test_employee_cannot_access_cloud_storage_with_s3()
{
    config(['cloud-storage.default' => 'amazon-s3']);
    
    $response = $this->actingAs($this->employee)
        ->get(route('employee.cloud-storage.index', ['username' => $this->employee->username]));
    
    $response->assertRedirect(route('employee.dashboard', ['username' => $this->employee->username]));
    $response->assertSessionHas('info');
}

public function test_employee_cloud_storage_api_returns_403_with_s3()
{
    config(['cloud-storage.default' => 'amazon-s3']);
    
    $response = $this->actingAs($this->employee)
        ->getJson(route('employee.cloud-storage.status', ['username' => $this->employee->username]));
    
    $response->assertStatus(403);
}
```

## Notes

- The implementation checks `auth_type` in provider config, not the provider name
- This means any future OAuth providers will automatically show the Cloud Storage page
- Any future API key providers will automatically hide it
- No code changes needed when adding new providers
