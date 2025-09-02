# Google Drive Status Messaging Migration Guide

## Overview

This guide helps you migrate from the legacy Google Drive status messaging system to the new consolidated status approach. The migration eliminates contradictory status messages and provides clearer user experience.

## Migration Timeline

### Phase 1: New System Active (Current)
- ✅ New consolidated status system is live
- ✅ Legacy API responses still supported with compatibility headers
- ✅ Dashboard shows new status messages
- ✅ All new integrations use consolidated status

### Phase 2: Legacy Deprecation (3 months from release)
- 🔄 Legacy API responses marked as deprecated
- 🔄 Warning messages for legacy API usage
- 🔄 Migration tools and documentation available
- 🔄 Support for legacy format continues

### Phase 3: Legacy Removal (6 months from release)
- ⏳ Legacy API responses removed
- ⏳ Only consolidated status format supported
- ⏳ Migration must be completed by this phase

## Breaking Changes

### 1. API Response Format Changes

#### Status Check Endpoints

**Before (Legacy)**:
```json
{
  "is_healthy": true,
  "token_expiring_soon": false,
  "token_expired": false,
  "last_success": "2025-08-31T10:30:00Z",
  "warnings": [],
  "provider": "google-drive"
}
```

**After (New)**:
```json
{
  "status": "healthy",
  "message": "Connection is working properly",
  "last_success": "2025-08-31T10:30:00Z",
  "provider": "google-drive",
  "requires_action": false,
  "action_url": null
}
```

#### Test Connection Endpoints

**Before (Legacy)**:
```json
{
  "success": true,
  "message": "Connection successful",
  "token_refreshed": true
}
```

**After (New)**:
```json
{
  "success": true,
  "status": "healthy",
  "message": "Connection test successful",
  "timestamp": "2025-08-31T10:30:00Z",
  "tests": {
    "token_validation": {"passed": true, "message": "Token is valid"},
    "api_connectivity": {"passed": true, "message": "API accessible"}
  }
}
```

### 2. Database Schema Changes

#### New Fields Added
```sql
-- Added to cloud_storage_health_statuses table
ALTER TABLE cloud_storage_health_statuses 
ADD COLUMN consolidated_status ENUM('healthy', 'authentication_required', 'connection_issues', 'not_connected') DEFAULT 'not_connected',
ADD COLUMN last_token_refresh_attempt_at TIMESTAMP NULL,
ADD COLUMN token_refresh_failures INT DEFAULT 0,
ADD COLUMN operational_test_result JSON NULL;
```

#### Legacy Fields (Deprecated)
These fields are maintained for backward compatibility but will be removed in Phase 3:
- `is_healthy` (boolean)
- `token_expiring_soon` (boolean) 
- `token_expired` (boolean)
- `warnings` (JSON array)

### 3. Frontend Component Changes

#### Dashboard Widget

**Before (Legacy)**:
```blade
<!-- Multiple status indicators -->
<div class="status-healthy">✓ Healthy</div>
@if($status['token_expiring_soon'])
  <div class="status-warning">⚠ Token refresh needed</div>
@endif
@if($status['token_expired'])
  <div class="status-error">✗ Token expired</div>
@endif
```

**After (New)**:
```blade
<!-- Single consolidated status -->
<div class="status-indicator status-{{ $status['status'] }}">
  {{ $status['message'] }}
</div>
@if($status['requires_action'])
  <a href="{{ $status['action_url'] }}" class="btn btn-primary">
    Take Action
  </a>
@endif
```

## Migration Steps

### Step 1: Update Frontend Code

#### JavaScript/TypeScript Applications

**Legacy Code Pattern**:
```javascript
function handleStatusResponse(response) {
  if (response.is_healthy && !response.token_expired) {
    showStatus('healthy', 'Connection is working');
  } else if (response.token_expired) {
    showStatus('error', 'Token expired - please reconnect');
  } else if (response.token_expiring_soon) {
    showStatus('warning', 'Token refresh needed');
  } else {
    showStatus('error', 'Connection issues');
  }
}
```

**New Code Pattern**:
```javascript
function handleStatusResponse(response) {
  const statusConfig = {
    'healthy': { type: 'success', icon: '✓' },
    'authentication_required': { type: 'error', icon: '✗' },
    'connection_issues': { type: 'warning', icon: '⚠' },
    'not_connected': { type: 'info', icon: 'ℹ' }
  };
  
  const config = statusConfig[response.status];
  showStatus(config.type, response.message, config.icon);
  
  if (response.requires_action) {
    showActionButton(response.action_url);
  }
}
```

#### Vue.js Components

**Legacy Component**:
```vue
<template>
  <div class="status-widget">
    <div v-if="status.is_healthy" class="status-healthy">
      ✓ Healthy
    </div>
    <div v-if="status.token_expiring_soon" class="status-warning">
      ⚠ Token refresh needed
    </div>
    <div v-if="status.token_expired" class="status-error">
      ✗ Token expired
    </div>
  </div>
</template>
```

**New Component**:
```vue
<template>
  <div class="status-widget">
    <div :class="statusClass">
      {{ statusIcon }} {{ status.message }}
    </div>
    <button v-if="status.requires_action" 
            @click="handleAction"
            class="btn btn-primary">
      {{ actionButtonText }}
    </button>
  </div>
</template>

<script>
export default {
  computed: {
    statusClass() {
      return `status-${this.status.status}`;
    },
    statusIcon() {
      const icons = {
        'healthy': '✓',
        'authentication_required': '✗',
        'connection_issues': '⚠',
        'not_connected': 'ℹ'
      };
      return icons[this.status.status];
    },
    actionButtonText() {
      return this.status.status === 'authentication_required' 
        ? 'Reconnect' 
        : 'Configure';
    }
  }
}
</script>
```

### Step 2: Update Backend Code

#### PHP/Laravel Applications

**Legacy Service Usage**:
```php
class DashboardController extends Controller
{
    public function getCloudStorageStatus()
    {
        $status = $this->cloudStorageService->getHealthStatus();
        
        return response()->json([
            'is_healthy' => $status->is_healthy,
            'token_expiring_soon' => $status->token_expiring_soon,
            'token_expired' => $status->token_expired,
            'warnings' => $status->warnings,
        ]);
    }
}
```

**New Service Usage**:
```php
class DashboardController extends Controller
{
    public function getCloudStorageStatus()
    {
        $status = $this->cloudStorageService->getConsolidatedStatus();
        
        return response()->json([
            'status' => $status->consolidated_status,
            'message' => $status->getConsolidatedStatusMessage(),
            'requires_action' => $status->requiresUserAction(),
            'action_url' => $status->getActionUrl(),
            'last_success' => $status->last_success_at,
        ]);
    }
}
```

#### Model Updates

**Legacy Model Usage**:
```php
// Check if connection is working
if ($healthStatus->is_healthy && !$healthStatus->token_expired) {
    // Proceed with operation
}
```

**New Model Usage**:
```php
// Check if connection is working
if ($healthStatus->consolidated_status === 'healthy') {
    // Proceed with operation
}
```

### Step 3: Update Tests

#### Unit Tests

**Legacy Test Pattern**:
```php
public function test_healthy_connection_status()
{
    $status = $this->cloudStorageService->getHealthStatus();
    
    $this->assertTrue($status->is_healthy);
    $this->assertFalse($status->token_expired);
    $this->assertFalse($status->token_expiring_soon);
    $this->assertEmpty($status->warnings);
}
```

**New Test Pattern**:
```php
public function test_healthy_connection_status()
{
    $status = $this->cloudStorageService->getConsolidatedStatus();
    
    $this->assertEquals('healthy', $status->consolidated_status);
    $this->assertEquals('Connection is working properly', $status->getConsolidatedStatusMessage());
    $this->assertFalse($status->requiresUserAction());
}
```

#### Integration Tests

**Legacy API Test**:
```php
public function test_status_endpoint_returns_healthy_status()
{
    $response = $this->get('/admin/cloud-storage/status');
    
    $response->assertJson([
        'is_healthy' => true,
        'token_expired' => false,
        'token_expiring_soon' => false,
    ]);
}
```

**New API Test**:
```php
public function test_status_endpoint_returns_consolidated_status()
{
    $response = $this->get('/admin/cloud-storage/status');
    
    $response->assertJson([
        'status' => 'healthy',
        'message' => 'Connection is working properly',
        'requires_action' => false,
    ]);
}
```

### Step 4: Update Configuration

#### Environment Variables

No changes required for environment variables. The following continue to work:
- `GOOGLE_DRIVE_CLIENT_ID`
- `GOOGLE_DRIVE_CLIENT_SECRET`
- `CLOUD_STORAGE_DEFAULT`

#### Config Files

Update any custom configuration that references legacy status fields:

**Legacy Config**:
```php
// config/monitoring.php
'alerts' => [
    'token_expired' => true,
    'token_expiring_soon' => true,
],
```

**New Config**:
```php
// config/monitoring.php
'alerts' => [
    'authentication_required' => true,
    'connection_issues' => true,
],
```

## Backward Compatibility

### Temporary Legacy Support

During the migration period, you can request legacy format responses using headers:

```javascript
// Request legacy format
fetch('/admin/cloud-storage/status', {
  headers: {
    'Accept': 'application/vnd.api.legacy+json'
  }
})
```

### Gradual Migration Approach

You can migrate gradually by updating components one at a time:

1. **Start with new endpoints**: Create new API endpoints alongside legacy ones
2. **Update frontend components**: Migrate UI components to use new format
3. **Update backend services**: Modify services to return consolidated status
4. **Remove legacy code**: Clean up legacy endpoints and code

## Common Migration Issues

### Issue 1: Multiple Status Indicators

**Problem**: Legacy code shows multiple conflicting status messages
**Solution**: Replace with single consolidated status display

**Before**:
```html
<div class="status-healthy">✓ Healthy</div>
<div class="status-warning">⚠ Token refresh needed</div>
```

**After**:
```html
<div class="status-healthy">✓ Connection is working properly</div>
```

### Issue 2: Complex Status Logic

**Problem**: Complex conditional logic for determining status
**Solution**: Use simple switch statement on consolidated status

**Before**:
```javascript
if (status.is_healthy && !status.token_expired && !status.token_expiring_soon) {
  return 'all_good';
} else if (status.token_expired || (!status.is_healthy && status.token_expiring_soon)) {
  return 'needs_reconnection';
} else {
  return 'has_issues';
}
```

**After**:
```javascript
switch (status.status) {
  case 'healthy': return 'all_good';
  case 'authentication_required': return 'needs_reconnection';
  default: return 'has_issues';
}
```

### Issue 3: Test Failures

**Problem**: Tests fail due to changed response format
**Solution**: Update test assertions to match new format

**Before**:
```php
$response->assertJson(['is_healthy' => true]);
```

**After**:
```php
$response->assertJson(['status' => 'healthy']);
```

## Migration Tools

### Automated Migration Script

Run the migration script to update database records:

```bash
# Migrate existing status records to new format
ddev artisan cloud-storage:migrate-status

# Verify migration results
ddev artisan cloud-storage:verify-migration
```

### Status Comparison Tool

Compare legacy and new status responses:

```bash
# Compare status formats for debugging
ddev artisan cloud-storage:compare-status --user=1
```

### Migration Validation

Validate that migration is complete:

```bash
# Check for any remaining legacy usage
ddev artisan cloud-storage:validate-migration

# Generate migration report
ddev artisan cloud-storage:migration-report
```

## Testing Your Migration

### Checklist

- [ ] All API endpoints return new consolidated status format
- [ ] Frontend components display single status message
- [ ] No contradictory status messages appear
- [ ] Action buttons appear when user intervention is required
- [ ] Test connection functionality works with new format
- [ ] All unit tests pass with new assertions
- [ ] Integration tests validate new API responses
- [ ] Legacy API requests still work (during compatibility period)

### Manual Testing Scenarios

1. **Healthy Connection**: Verify single "healthy" status shows
2. **Expired Token**: Verify "authentication required" with reconnect button
3. **Network Issues**: Verify "connection issues" with appropriate message
4. **Not Connected**: Verify "not connected" with setup instructions

### Automated Testing

```bash
# Run full test suite
ddev artisan test

# Run specific migration tests
ddev artisan test --filter=Migration

# Run browser tests for UI changes
ddev artisan dusk --filter=StatusMigration
```

## Rollback Plan

### Emergency Rollback

If issues arise, you can temporarily rollback to legacy format:

```bash
# Enable legacy mode globally
ddev artisan cloud-storage:enable-legacy-mode

# Disable new consolidated status
ddev artisan cloud-storage:disable-consolidated-status
```

### Partial Rollback

Rollback specific components while keeping others on new system:

```bash
# Rollback specific endpoints
ddev artisan cloud-storage:rollback-endpoint --endpoint=status

# Rollback specific users
ddev artisan cloud-storage:rollback-user --user=1
```

## Support During Migration

### Getting Help

1. **Documentation**: Review this migration guide and API documentation
2. **Testing Tools**: Use provided migration validation tools
3. **Support Team**: Contact development team with specific migration issues
4. **Community**: Check project discussions for common migration patterns

### Common Questions

**Q: Can I use both formats simultaneously?**
A: Yes, during the compatibility period (3 months) both formats are supported.

**Q: Will my existing integrations break?**
A: No, legacy format support continues during the migration period.

**Q: How do I know if migration is complete?**
A: Run `ddev artisan cloud-storage:validate-migration` to check.

**Q: What if I find bugs in the new system?**
A: Report issues immediately and use rollback procedures if necessary.

## Post-Migration

### Cleanup Tasks

After successful migration:

1. Remove legacy API endpoints
2. Clean up deprecated database fields
3. Update documentation references
4. Remove compatibility code
5. Update monitoring and alerting rules

### Performance Benefits

The new system provides:
- Reduced API calls through better caching
- Clearer user experience with single status messages
- Improved error handling and recovery
- Better monitoring and debugging capabilities

### Future Enhancements

With the new consolidated status system, future enhancements become easier:
- Multi-provider status aggregation
- Enhanced error recovery mechanisms
- Improved user guidance and troubleshooting
- Better integration with monitoring systems