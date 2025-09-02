# Cloud Storage Status API Documentation

## Overview

This document describes the API endpoints for checking and managing cloud storage status, specifically for Google Drive integration. The API has been updated to provide consolidated status information that eliminates contradictory messages.

## Authentication

All endpoints require authentication:
- **Admin endpoints**: Admin user session or API token
- **Employee endpoints**: Employee user session or API token

## Endpoints

### Admin Status Check

Get the current Google Drive connection status for admin users.

**Endpoint**: `GET /admin/cloud-storage/status`

**Response Format**:
```json
{
  "status": "healthy",
  "message": "Connection is working properly",
  "last_success": "2025-08-31T10:30:00Z",
  "provider": "google-drive",
  "requires_action": false,
  "action_url": null,
  "details": {
    "token_valid": true,
    "api_accessible": true,
    "last_token_refresh": "2025-08-31T09:15:00Z",
    "consecutive_failures": 0
  }
}
```

**Status Values**:
- `healthy`: Connection working properly
- `authentication_required`: User needs to reconnect
- `connection_issues`: Network or API problems
- `not_connected`: Integration not set up

**Example Responses**:

*Healthy Connection*:
```json
{
  "status": "healthy",
  "message": "Connection is working properly",
  "last_success": "2025-08-31T10:30:00Z",
  "provider": "google-drive",
  "requires_action": false,
  "action_url": null,
  "details": {
    "token_valid": true,
    "api_accessible": true,
    "last_token_refresh": "2025-08-31T09:15:00Z",
    "consecutive_failures": 0
  }
}
```

*Authentication Required*:
```json
{
  "status": "authentication_required",
  "message": "Please reconnect your account",
  "last_success": "2025-08-30T14:20:00Z",
  "provider": "google-drive",
  "requires_action": true,
  "action_url": "/admin/cloud-storage/google-drive/connect",
  "details": {
    "token_valid": false,
    "api_accessible": false,
    "last_token_refresh": "2025-08-30T14:20:00Z",
    "consecutive_failures": 3
  }
}
```

*Connection Issues*:
```json
{
  "status": "connection_issues",
  "message": "Experiencing connectivity problems",
  "last_success": "2025-08-31T08:45:00Z",
  "provider": "google-drive",
  "requires_action": false,
  "action_url": null,
  "details": {
    "token_valid": true,
    "api_accessible": false,
    "last_token_refresh": "2025-08-31T10:00:00Z",
    "consecutive_failures": 1,
    "error_type": "network_timeout"
  }
}
```

### Employee Status Check

Get the current Google Drive connection status for employee users.

**Endpoint**: `GET /employee/cloud-storage/status`

**Response Format**: Same as admin endpoint but with employee-specific permissions and actions.

**Example Response**:
```json
{
  "status": "healthy",
  "message": "Connection is working properly",
  "last_success": "2025-08-31T10:30:00Z",
  "provider": "google-drive",
  "requires_action": false,
  "action_url": null,
  "details": {
    "token_valid": true,
    "api_accessible": true,
    "upload_enabled": true,
    "folder_configured": true
  }
}
```

### Test Connection

Test the Google Drive connection and return detailed results.

**Endpoint**: `POST /admin/cloud-storage/test-connection`

**Request Body**:
```json
{
  "provider": "google-drive"
}
```

**Response Format**:
```json
{
  "success": true,
  "status": "healthy",
  "message": "Connection test successful",
  "timestamp": "2025-08-31T10:30:00Z",
  "tests": {
    "token_validation": {
      "passed": true,
      "message": "Token is valid",
      "duration_ms": 150
    },
    "token_refresh": {
      "passed": true,
      "message": "Token refresh successful",
      "duration_ms": 800
    },
    "api_connectivity": {
      "passed": true,
      "message": "API accessible",
      "duration_ms": 300
    },
    "folder_access": {
      "passed": true,
      "message": "Root folder accessible",
      "duration_ms": 250
    }
  }
}
```

**Failed Test Response**:
```json
{
  "success": false,
  "status": "authentication_required",
  "message": "Connection test failed",
  "timestamp": "2025-08-31T10:30:00Z",
  "tests": {
    "token_validation": {
      "passed": false,
      "message": "Token is expired",
      "duration_ms": 100
    },
    "token_refresh": {
      "passed": false,
      "message": "Refresh token is invalid",
      "duration_ms": 500,
      "error": "invalid_grant"
    },
    "api_connectivity": {
      "passed": false,
      "message": "Cannot access API",
      "duration_ms": 0
    },
    "folder_access": {
      "passed": false,
      "message": "Cannot access folder",
      "duration_ms": 0
    }
  }
}
```

### Refresh Status

Force a refresh of the connection status.

**Endpoint**: `POST /admin/cloud-storage/refresh-status`

**Request Body**:
```json
{
  "provider": "google-drive"
}
```

**Response Format**:
```json
{
  "success": true,
  "message": "Status refreshed successfully",
  "previous_status": "connection_issues",
  "current_status": "healthy",
  "timestamp": "2025-08-31T10:30:00Z"
}
```

### Get Connection History

Retrieve historical connection status data.

**Endpoint**: `GET /admin/cloud-storage/history`

**Query Parameters**:
- `provider`: Filter by provider (optional, defaults to all)
- `days`: Number of days to retrieve (optional, defaults to 7, max 30)
- `status`: Filter by status (optional)

**Response Format**:
```json
{
  "data": [
    {
      "timestamp": "2025-08-31T10:30:00Z",
      "status": "healthy",
      "message": "Connection is working properly",
      "provider": "google-drive"
    },
    {
      "timestamp": "2025-08-31T09:15:00Z",
      "status": "connection_issues",
      "message": "Experiencing connectivity problems",
      "provider": "google-drive"
    }
  ],
  "meta": {
    "total": 48,
    "days": 7,
    "provider": "google-drive"
  }
}
```

## Error Responses

### Standard Error Format
```json
{
  "error": true,
  "message": "Error description",
  "code": "ERROR_CODE",
  "timestamp": "2025-08-31T10:30:00Z"
}
```

### Common Error Codes

#### Authentication Errors
- `UNAUTHENTICATED`: User not logged in
- `UNAUTHORIZED`: Insufficient permissions
- `TOKEN_EXPIRED`: API token has expired

#### Validation Errors
- `INVALID_PROVIDER`: Unsupported cloud storage provider
- `MISSING_PARAMETERS`: Required parameters not provided
- `INVALID_PARAMETERS`: Parameters have invalid values

#### System Errors
- `SERVICE_UNAVAILABLE`: Cloud storage service temporarily unavailable
- `RATE_LIMITED`: Too many requests, try again later
- `INTERNAL_ERROR`: Unexpected server error

### Error Response Examples

*Authentication Required*:
```json
{
  "error": true,
  "message": "Authentication required",
  "code": "UNAUTHENTICATED",
  "timestamp": "2025-08-31T10:30:00Z"
}
```

*Invalid Provider*:
```json
{
  "error": true,
  "message": "Provider 'dropbox' is not supported",
  "code": "INVALID_PROVIDER",
  "timestamp": "2025-08-31T10:30:00Z"
}
```

*Rate Limited*:
```json
{
  "error": true,
  "message": "Too many requests. Please try again in 60 seconds.",
  "code": "RATE_LIMITED",
  "timestamp": "2025-08-31T10:30:00Z",
  "retry_after": 60
}
```

## Migration from Legacy API

### Breaking Changes

The API response format has changed significantly from the legacy version:

#### Legacy Response Format (Deprecated)
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

#### New Response Format
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

### Migration Guide

#### For Frontend Applications

**Old Code**:
```javascript
// Legacy approach
if (response.is_healthy && !response.token_expired) {
  showHealthyStatus();
} else if (response.token_expired || response.token_expiring_soon) {
  showTokenWarning();
} else {
  showErrorStatus();
}
```

**New Code**:
```javascript
// New consolidated approach
switch (response.status) {
  case 'healthy':
    showHealthyStatus(response.message);
    break;
  case 'authentication_required':
    showAuthenticationRequired(response.message, response.action_url);
    break;
  case 'connection_issues':
    showConnectionIssues(response.message);
    break;
  case 'not_connected':
    showNotConnected(response.message, response.action_url);
    break;
}
```

#### For Backend Integrations

**Old Code**:
```php
// Legacy status check
$status = $cloudStorageService->getStatus();
if ($status['is_healthy'] && !$status['token_expired']) {
    // Proceed with upload
}
```

**New Code**:
```php
// New status check
$status = $cloudStorageService->getStatus();
if ($status['status'] === 'healthy') {
    // Proceed with upload
}
```

### Backward Compatibility

During the migration period (3 months), both response formats are supported:

**Request Header**: `Accept: application/json` (new format)
**Request Header**: `Accept: application/vnd.api.legacy+json` (legacy format)

After the migration period, only the new format will be supported.

## Rate Limiting

### Limits
- **Status checks**: 60 requests per minute per user
- **Test connection**: 10 requests per minute per user
- **Refresh status**: 20 requests per minute per user

### Headers
Rate limit information is included in response headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1693478400
```

### Rate Limit Exceeded Response
```json
{
  "error": true,
  "message": "Rate limit exceeded. Try again in 30 seconds.",
  "code": "RATE_LIMITED",
  "timestamp": "2025-08-31T10:30:00Z",
  "retry_after": 30
}
```

## Caching

### Cache Headers
Responses include appropriate cache headers:
```
Cache-Control: private, max-age=120
ETag: "abc123def456"
Last-Modified: Wed, 31 Aug 2025 10:30:00 GMT
```

### Cache Invalidation
Status cache is automatically invalidated when:
- Connection test is performed
- Status refresh is requested
- Token refresh occurs
- Configuration changes are made

## Webhooks (Future Enhancement)

### Status Change Notifications
Future versions will support webhooks for status changes:

**Webhook Payload**:
```json
{
  "event": "status_changed",
  "timestamp": "2025-08-31T10:30:00Z",
  "data": {
    "user_id": 1,
    "provider": "google-drive",
    "previous_status": "connection_issues",
    "current_status": "healthy",
    "message": "Connection is working properly"
  }
}
```

## Testing

### Test Endpoints
Development and staging environments include additional test endpoints:

**Force Status**: `POST /admin/cloud-storage/test/force-status`
```json
{
  "provider": "google-drive",
  "status": "authentication_required"
}
```

**Simulate Error**: `POST /admin/cloud-storage/test/simulate-error`
```json
{
  "provider": "google-drive",
  "error_type": "quota_exceeded"
}
```

### Example Test Scenarios

#### Test Token Refresh
```bash
curl -X POST /admin/cloud-storage/test-connection \
  -H "Content-Type: application/json" \
  -d '{"provider": "google-drive"}' \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Test Status History
```bash
curl -X GET "/admin/cloud-storage/history?days=1&provider=google-drive" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Support

### Documentation
- [Google Drive Status Messaging Improvement](../google-drive-status-messaging-improvement.md)
- [Troubleshooting Guide](../troubleshooting/google-drive-connection-issues.md)
- [Cloud Storage Caching Guide](../cloud-storage-caching-guide.md)

### Contact
For API support or questions:
1. Check the troubleshooting guide
2. Review application logs
3. Contact the development team with specific error details