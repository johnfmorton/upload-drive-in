# Design Document

## Overview

This design addresses the queue worker status checking issue in the setup instructions page. The current implementation incorrectly shows "Queue worker is functioning properly" when only checking if jobs can be queued, not if they're actually being processed. The fix involves modifying the status checking logic to separate queue worker testing from general status checks and ensuring accurate feedback based on actual job completion.

## Architecture

### Current Problem Analysis

The existing implementation has these issues:
1. The `refreshAllStatuses()` method includes queue worker in the general status check
2. The queue worker status check only verifies if jobs can be added to the queue, not if they're processed
3. Users get false positive feedback about queue worker functionality

### Proposed Solution Architecture

```mermaid
graph TB
    A[Check Status Button] --> B[Modified refreshAllStatuses()]
    B --> C[Check Database]
    B --> D[Check Mail]
    B --> E[Check Google Drive]
    B --> F[Check Migrations]
    B --> G[Check Admin User]
    B --> H[Trigger Queue Worker Test]
    
    I[Test Queue Worker Button] --> H
    H --> J[QueueTestService.dispatchTestJob()]
    J --> K[TestQueueJob Execution]
    K --> L[Poll Test Results]
    L --> M[Update Queue Worker Status]
    
    N[Page Load] --> O[Check Cached Queue Status]
    O --> P{Recent Test Result?}
    P -->|Yes| Q[Show Cached Status]
    P -->|No| R[Show 'Click Test Button' Message]
```

## Components and Interfaces

### 1. Modified SetupStatusService

**Enhanced Methods:**
```php
public function refreshAllStatuses(): array
{
    // Remove queue_worker from automatic status checks
    // Return statuses for all steps except queue_worker
    // Queue worker status will be handled separately
}

public function getQueueWorkerStatus(): array
{
    // Check for recent test results in cache
    // Return cached status or prompt for new test
}
```

### 2. Enhanced JavaScript Status Manager

**Modified Methods:**
```javascript
async refreshAllStatuses() {
    // 1. Refresh standard status checks (excluding queue_worker)
    // 2. Trigger queue worker test simultaneously
    // 3. Update UI for both standard checks and queue test progress
}

async testQueueWorker() {
    // Enhanced to work both standalone and as part of general refresh
    // Update queue worker status based on actual test completion
}
```

### 3. Queue Worker Status Caching Strategy

**Cache Structure:**
```php
[
    'status' => 'completed|failed|testing|not_tested',
    'message' => 'Human readable status message',
    'test_completed_at' => Carbon timestamp,
    'processing_time' => float seconds,
    'error_message' => string|null,
    'test_job_id' => string|null
]
```

**Cache Key:** `setup_queue_worker_status`
**TTL:** 1 hour (configurable)

## Data Models

### Queue Worker Status States

```php
class QueueWorkerStatus
{
    public const STATUS_NOT_TESTED = 'not_tested';
    public const STATUS_TESTING = 'testing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TIMEOUT = 'timeout';
    
    public string $status;
    public string $message;
    public ?Carbon $testCompletedAt;
    public ?float $processingTime;
    public ?string $errorMessage;
    public ?string $testJobId;
}
```

### Status Response Structure

```php
// For general status refresh (excluding queue_worker)
[
    'database' => [...],
    'mail' => [...],
    'google_drive' => [...],
    'migrations' => [...],
    'admin_user' => [...],
    // queue_worker intentionally excluded
]

// For queue worker specific status
[
    'queue_worker' => [
        'status' => 'not_tested|testing|completed|failed',
        'message' => 'Click the Test Queue Worker button below',
        'details' => 'Additional context or error information',
        'test_completed_at' => '2025-01-01T12:00:00Z',
        'processing_time' => 1.23,
        'can_retry' => true|false
    ]
]
```

## Error Handling

### Queue Worker Test Failure Scenarios

1. **Test Job Dispatch Failure**
   - Status: `failed`
   - Message: "Failed to dispatch test job"
   - Action: Show retry button with technical details

2. **Queue Worker Not Running**
   - Status: `timeout`
   - Message: "Queue worker may not be running - test timed out after 30 seconds"
   - Action: Provide troubleshooting guidance

3. **Test Job Execution Failure**
   - Status: `failed`
   - Message: "Test job failed: [error details]"
   - Action: Show specific error and retry option

4. **Network/AJAX Failure**
   - Status: `error`
   - Message: "Unable to check queue status"
   - Action: Retry with exponential backoff

### Graceful Degradation

- If cache is unavailable, default to "not_tested" status
- If test service is unavailable, show manual verification instructions
- If polling fails, provide fallback status check options

## Testing Strategy

### Unit Tests

**SetupStatusService Tests:**
```php
// Test that refreshAllStatuses() excludes queue_worker
public function test_refresh_all_statuses_excludes_queue_worker()

// Test queue worker status caching
public function test_queue_worker_status_caching()

// Test cache expiration handling
public function test_expired_cache_handling()
```

**JavaScript Tests:**
```javascript
// Test modified refreshAllStatuses behavior
test('refreshAllStatuses excludes queue worker and triggers test')

// Test queue worker status persistence
test('queue worker status persists between page loads')

// Test error handling for failed tests
test('handles queue worker test failures gracefully')
```

### Integration Tests

**Setup Instructions Page:**
```php
// Test complete workflow: general status + queue test
public function test_check_status_button_triggers_queue_test()

// Test queue worker status persistence
public function test_queue_worker_status_persists_on_refresh()

// Test error scenarios
public function test_queue_worker_test_timeout_handling()
```

### Feature Tests

**End-to-End Scenarios:**
1. Fresh setup - queue worker shows "Click test button"
2. Successful test - status shows "functioning properly"
3. Failed test - shows error with retry option
4. Page refresh after successful test - status persists
5. Cache expiration - prompts for new test

## Frontend Implementation

### Modified Status Checking Logic

**Current Implementation Issue:**
```javascript
// PROBLEM: This includes queue_worker in general status check
this.statusSteps = [
    "database", "mail", "google_drive", 
    "migrations", "admin_user", "queue_worker"  // ← Remove this
];
```

**Fixed Implementation:**
```javascript
// SOLUTION: Separate queue worker from general status steps
this.generalStatusSteps = [
    "database", "mail", "google_drive", 
    "migrations", "admin_user"
];

async refreshAllStatuses() {
    // 1. Check general statuses
    await this.refreshGeneralStatuses();
    
    // 2. Trigger queue worker test
    await this.triggerQueueWorkerTest();
}

async refreshGeneralStatuses() {
    // Only refresh non-queue-worker steps
    // Update UI for these steps immediately
}

async triggerQueueWorkerTest() {
    // Same logic as testQueueWorker() but integrated
    // Update queue worker status based on actual test results
}
```

### Queue Worker Status Display Logic

**Initial State (Page Load):**
```javascript
// Check for cached queue worker status
const cachedStatus = await this.getCachedQueueWorkerStatus();
if (cachedStatus && !this.isStatusExpired(cachedStatus)) {
    this.updateQueueWorkerStatus(cachedStatus);
} else {
    this.updateQueueWorkerStatus({
        status: 'not_tested',
        message: 'Click the Test Queue Worker button below'
    });
}
```

**During Test:**
```javascript
// Show progressive status updates
this.updateQueueWorkerStatus({
    status: 'testing',
    message: 'Testing queue worker...'
});

// Then during polling:
// "Test job queued, waiting for worker..."
// "Test job is being processed..."
// "Queue worker is functioning properly! (1.23s)"
```

### UI State Management

**Status Indicator Classes:**
```css
.status-not-tested {
    color: #6b7280; /* Gray */
}

.status-testing {
    color: #3b82f6; /* Blue with spinner */
}

.status-completed {
    color: #10b981; /* Green */
}

.status-failed {
    color: #ef4444; /* Red */
}

.status-timeout {
    color: #f59e0b; /* Amber */
}
```

**Button State Management:**
```javascript
// Disable both buttons during testing
const checkStatusBtn = document.getElementById('refresh-status-btn');
const testQueueBtn = document.getElementById('test-queue-worker-btn');

// During test: disable both, show spinners
// After test: re-enable both, update status
```

## Security Considerations

### Rate Limiting
- Limit queue worker tests to prevent abuse
- Implement cooldown period between tests
- Track test attempts per session/IP

### Cache Security
- Use secure cache keys to prevent tampering
- Validate cached data structure before use
- Implement cache invalidation on security events

### Error Information Disclosure
- Sanitize error messages for non-admin users
- Log detailed errors server-side for debugging
- Avoid exposing internal system details

## Performance Considerations

### Caching Strategy
- Cache successful test results for 1 hour
- Use Redis for distributed caching if available
- Implement cache warming for frequently accessed data

### Optimization
- Parallel execution of general status checks and queue test
- Debounce rapid button clicks
- Implement efficient polling with exponential backoff

### Resource Management
- Clean up old test jobs automatically
- Limit concurrent queue tests
- Implement proper timeout handling

## Implementation Phases

### Phase 1: Backend Logic Separation
1. Modify SetupStatusService to exclude queue_worker from general refresh
2. Enhance queue worker status caching logic
3. Update AJAX endpoints to handle separated logic
4. Add proper error handling for all scenarios

### Phase 2: Frontend Status Management
1. Modify JavaScript to separate general status from queue worker testing
2. Implement queue worker status persistence logic
3. Update UI state management for different test states
4. Add proper loading indicators and error handling

### Phase 3: Integration and Testing
1. Test complete workflow with separated logic
2. Verify status persistence across page refreshes
3. Test all error scenarios and recovery
4. Ensure backward compatibility with existing functionality

### Phase 4: Polish and Documentation
1. Add comprehensive error messages and troubleshooting guidance
2. Implement proper accessibility features
3. Update documentation with new behavior
4. Performance testing and optimization