# Google Drive Status Quick Reference

## Status Values

| Status | Meaning | User Action | Technical State |
|--------|---------|-------------|-----------------|
| `healthy` | Working properly | None | Token valid, API accessible |
| `authentication_required` | Need to reconnect | Click "Reconnect" | Refresh token expired/invalid |
| `connection_issues` | Temporary problems | Wait/retry | Token valid, API inaccessible |
| `not_connected` | Not set up | Follow setup | No tokens stored |

## API Response Format

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

## Common Code Patterns

### Frontend Status Display
```javascript
const statusConfig = {
  'healthy': { class: 'text-green-600', icon: '✓' },
  'authentication_required': { class: 'text-red-600', icon: '✗' },
  'connection_issues': { class: 'text-yellow-600', icon: '⚠' },
  'not_connected': { class: 'text-gray-600', icon: 'ℹ' }
};
```

### Backend Status Check
```php
$status = $cloudStorageService->getConsolidatedStatus();
if ($status->consolidated_status === 'healthy') {
    // Proceed with operation
}
```

### Vue.js Component
```vue
<div :class="statusClass">
  {{ statusIcon }} {{ status.message }}
</div>
<button v-if="status.requires_action" @click="handleAction">
  {{ actionText }}
</button>
```

## Troubleshooting Commands

```bash
# Quick status check
ddev artisan cloud-storage:diagnose

# Clear status cache
ddev artisan cloud-storage:cache --clear

# Fix status inconsistencies
ddev artisan cloud-storage:fix-status

# Test token refresh
ddev artisan cloud-storage:sync-tokens
```

## Migration Checklist

- [ ] Update API response handling
- [ ] Replace multiple status indicators with single display
- [ ] Update test assertions
- [ ] Remove legacy status logic
- [ ] Test all status scenarios

## Key Benefits

- ✅ No more contradictory messages
- ✅ Clear user action guidance
- ✅ Automatic token refresh handling
- ✅ Consistent status across interfaces
- ✅ Better error recovery