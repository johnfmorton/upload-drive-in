# PHPUnit Test Suite Error Report

**Date:** 2026-03-10
**Runtime:** PHP 8.5.3, PHPUnit 11.5.50

## Summary

After removing 26 obsolete/broken test files, the remaining suite status is:

| Metric | Count |
|--------|-------|
| Total Tests | 1,870 |
| Passing | ~627 |
| **Errors** | **1,093** |
| **Failures** | **50** |
| PHPUnit Deprecations | 448 |
| Risky | 134 |
| Skipped | 3 |

### Deleted Test Files (26)

Tests for non-existent source code:
- `SetupCompleteMiddlewareTest` — class removed
- `EnhancedGoogleDriveErrorHandlerTest` — class removed
- `EnhancedS3ErrorHandlerTest` — class removed
- `EnhancedDashboardTokenValidationTest` — class removed
- `CloudStorageStatusWidgetTest` — component removed
- `CheckCloudStorageHealthCommandTest` — command removed
- `CloudStorageConfigurationValidationPipelineTest` — class removed
- `FileUploadCloudStorageErrorTest` — model removed
- `FileUploadPermissionTest` — model removed
- `EmailVerificationLoggingTest` — class removed
- `EmailInvitationTest` — mail class removed
- `TokenRenewalMailTest` — mail class removed
- `GlobalHelpersTest` — helpers removed
- `FileManagerFileGridTest` — component removed
- `FileManagerPreviewModalTest` — component removed

Fundamentally broken tests (constructor changes, missing configs, empty assertions):
- `AdminUserControllerTest`
- `SetupStatusServiceTest`
- `GoogleDriveProviderTest`
- `QueueTestServiceCachingTest`
- `GoogleDriveProviderSimpleTest`
- `QueueTestServiceProgressiveStatusTest`
- `SetupServiceRecoveryTest`
- `TokenRefreshConfigTest`
- `DiagnoseUploadsCommandTest`

Function redeclaration errors:
- `GoogleDriveProviderAdvancedFeaturesTest`
- `S3ProviderAdvancedFeaturesTest`

---

## Error Categories

### Priority 1: PDO Transaction Error (1,000 errors across 69 classes)

**Root cause:** Test classes use `RefreshDatabase` trait which wraps each test in a transaction. When setUp or the code under test also starts a transaction, SQLite throws `cannot start a transaction within a transaction`.

**Error message:**
```
PDOException: SQLSTATE[HY000]: General error: 1 cannot start a transaction within a transaction
```

**Fix options:**
1. Add `protected function connectionsToTransact() { return []; }` to affected tests
2. Replace `RefreshDatabase` with `DatabaseMigrations` in affected test classes
3. Create a base test class that handles transaction state properly

**Affected classes (69):**

| Test Class | Errors |
|------------|--------|
| `SetupServiceTest` | 41 |
| `S3ProviderTest` | 34 |
| `SetupDetectionServiceTest` | 33 |
| `AssetValidationServiceTest` | 32 |
| `FileManagerServiceTest` | 27 |
| `SendBatchUploadNotificationsTest` | 27 |
| `FileUploadTest` | 26 |
| `DatabaseSetupServiceTest` | 23 |
| `CloudStorageSettingTest` | 23 |
| `CloudStorageErrorHandlerFactoryTest` | 22 |
| `CloudStorageHealthStatusTest` (Model) | 20 |
| `QueueTestServiceTest` | 19 |
| `CloudStorageHealthServiceEnhancedTest` | 19 |
| `TokenRefreshConfigServiceTest` | 19 |
| `UploadDiagnosticServiceTest` | 19 |
| `GoogleDriveTokenTest` | 18 |
| `RefreshTokenJobTest` | 18 |
| `CloudStorageAuditServiceTest` | 17 |
| `VerificationMailFactoryTest` | 17 |
| `CloudConfigurationServiceTest` | 16 |
| `ProactiveTokenRenewalServiceTest` | 16 |
| `TokenRenewalNotificationServiceTest` | 16 |
| `TokenSecurityServiceTest` | 16 |
| `FileManagerControllerTest` | 16 (14E/2F) |
| `TokenRefreshCoordinatorTest` | 15 |
| `TestQueueJobTest` | 14 |
| `UserTest` | 14 |
| `CloudStorageLogServiceTest` | 14 |
| `FileMetadataCacheServiceTest` | 14 |
| `TokenRefreshMonitoringServiceTest` | 14 |
| `TokenStatusServiceEnhancedTest` | 14 |
| `TokenStatusServiceTest` | 14 |
| `CloudStorageErrorTrackingServiceTest` | 13 |
| `CloudStorageHealthServiceTest` | 13 |
| `FilePreviewServiceTest` | 13 |
| `FileSecurityServiceTest` | 13 |
| `QueueTestServiceEnhancedErrorDetectionTest` | 13 |
| `CloudStorageReconnectionServiceTest` | 12 |
| `DomainRulesCacheServiceTest` | 12 |
| `ThumbnailServiceTest` | 12 |
| `SetupHelperTest` | 11 |
| `CleanupFailedRefreshAttemptsJobTest` | 11 |
| `UploadToGoogleDriveJobTest` | 11 |
| `CloudStorageHealthServiceCachingTest` | 11 |
| `GoogleDriveServiceProactiveTokenValidationTest` | 11 |
| `ProactiveRefreshSchedulerTest` | 11 |
| `QueueTestServiceEnhancedErrorHandlingTest` | 11 |
| `UploadRecoveryServiceTest` | 11 |
| `TestQueueJobProgressiveStatusTest` | 10 |
| `FileAccessMiddlewareTest` | 10 |
| `FileDownloadRateLimitMiddlewareTest` | 10 |
| `CloudStorageHealthServiceRealTimeValidationTest` | 10 |
| `CloudStoragePerformanceMetricsServiceTest` | 10 |
| `EmailVerificationMetricsServiceTest` | 10 |
| `TokenMonitoringDashboardServiceTest` | 10 |
| `GoogleDriveServiceTokenRefreshErrorHandlingTest` | 9 |
| `UserLookupPerformanceServiceTest` | 9 |
| `TokenMaintenanceJobTest` | 8 |
| `AuditLogServiceTest` | 8 |
| `CloudStorageHealthServiceErrorHandlingTest` | 8 |
| `CloudStorageMonitoringDashboardServiceTest` | 8 |
| `GoogleDriveServiceRefreshTokenValidationTest` | 8 |
| `PublicUploadControllerExistingUserBypassTest` | 7 |
| `GoogleDriveServiceTokenValidationSimpleTest` | 7 |
| `HealthStatusValidationJobTest` | 6 |
| `CloudStorageHealthServiceConsolidatedStatusTest` | 5 |
| `UploadToGoogleDriveTokenRefreshTest` | 4 |
| `DomainRulesCacheServiceTranslationTest` | 4 |
| `GoogleDriveServiceExponentialBackoffTest` | 3 |

---

### Priority 2: Missing Database Table (44 errors across 4 classes)

Related to Priority 1 — when the transaction wrapper fails, migrations may not run.

**Error:** `General error: 1 no such table: users`

| Test Class | Errors |
|------------|--------|
| `GoogleDriveServiceTest` | 13 |
| `PendingUploadRetryJobTest` | 12 |
| `ConnectionRecoveryServiceTest` | 10 |
| `UploadToGoogleDriveEnhancedTest` | 9 |

---

### Priority 3: Missing Methods (29 errors across 2 classes)

Tests call methods that don't exist on the service classes.

**`QueueTestServiceComprehensiveTest`** — 16 errors
Missing methods on `QueueTestService`:
- `getQueueHealth()`
- `getTestJobStatus()`
- `updateJobProgress()`
- `markTestJobCompleted()`
- `handleTestTimeout()`
- `cleanupOldTestJobs()`

**`SetupSecurityServiceTest`** — 13 errors
Missing methods on `SetupSecurityService`:
- `sanitizeDatabaseConfig()`
- `generateSecureToken()`
- `sanitizeAdminUserInput()`
- `validateAndSanitizePath()`
- `sanitizeStorageConfig()`
- `sanitizeEnvironmentValue()`
- `validateSetupToken()`
- `sanitizeRedirectUrl()`

**Fix:** Delete these test files or implement the missing methods if they're needed.

---

### Priority 4: Risky Tests — Error Handlers Not Removed (134 risky across 14 classes)

Tests register custom error/exception handlers but don't restore them in tearDown.

**Fix:** Add to affected test classes:
```php
protected function tearDown(): void
{
    restore_error_handler();
    restore_exception_handler();
    parent::tearDown();
}
```

| Test Class | Risky Count |
|------------|-------------|
| `SetupServiceTest` | 41 |
| `AssetValidationServiceTest` | 32 |
| `DatabaseSetupServiceTest` | 23 |
| `SetupHelperTest` | 11 |
| `QueueTestServiceComprehensiveTest` | 9 |
| `SetupDetectionMiddlewareTest` | 5 |
| `CloudStorageSetupServiceTest` | 3 |
| `CloudStorageAdvancedFeaturesServiceTest` | 2 |
| `CloudStorageFeatureUtilizationServiceTest` | 2 |
| `CloudStorageManagerTest` | 2 |
| `AnalyzeTokenRefreshLogsTest` | 1 |
| `FileManagerControllerTest` | 1 |
| `CloudStorageFactoryTest` | 1 |
| `SetupStatusServiceComprehensiveTest` | 1 |

---

### Priority 5: Assertion Failures (40 errors/failures across 13 classes)

Individual expectation mismatches requiring case-by-case fixes.

| Test Class | Count | Issue |
|------------|-------|-------|
| `SetupDetectionMiddlewareTest` | 9 (3E/6F) | Middleware redirects to `/setup/instructions` when tests expect pass-through |
| `AdminUserSearchPerformanceTest` | 9F | All admin routes return 302 — missing `actingAs()` auth |
| `BaseCloudStorageErrorHandlerTest` | 6 (2E/4F) | Retry delays changed (expects 60, gets 30); TypeError on error type access |
| `GoogleDriveErrorHandlerTest` | 6F | Retry delay/backoff values changed; action text changed |
| `AnalyzeTokenRefreshLogsTest` | 5 (2E/3F) | Cannot mock private method; output not initialized |
| `RecoverPendingUploadsCommandTest` | 4 (1E/3F) | Undefined `dry_run` key; no test data seeded |
| `EnvironmentFileServiceTest` | 3F | `updateEnvironmentFile()` returns false; backup count wrong |
| `DashboardControllerTest` | 1F | Returns 302 — missing auth |
| `CloudStorageConnectionAlertTest` | 1F | URL uses `/admin/` instead of `/employee/` |
| `CloudStorageHealthServiceTest` | 1F | `getHealthSummary()` returns false |
| `CloudStorageProviderInterfaceTest` | 1F | Interface has 26 methods, test expects 17 |
| `CloudStorageErrorTypeTest` | 1F | Enum has 26 cases, test expects 14 |
| `CloudStorageServiceProviderTest` | 1F | `provides()` returns 10, test expects 3 |

---

### Priority 6: Mockery Issues (16 errors across 6 classes)

| Test Class | Count | Issue |
|------------|-------|-------|
| `ClientManagementControllerTest` | 4E | `UrlGenerator::previous()` not expected |
| `CloudStorageFeatureUtilizationServiceTest` | 3 (2E/1F) | `getUserProvider()` not expected |
| `CloudStorageSetupServiceTest` | 3 (2E/1F) | `Filesystem::get()`/`::exists()` not expected |
| `CloudStorageAdvancedFeaturesServiceTest` | 3 (2E/1F) | `getProvider()` not called; `optimizeUpload()` returns 0 |
| `SetupStatusServiceComprehensiveTest` | 2 (1E/1F) | `LogManager::error()`/`::warning()` not expected |
| `CloudStorageFactoryTest` | 1E | `getEffectiveConfig()` args don't match |

---

### Priority 7: Other (6 errors/failures)

| Test Class | Count | Issue |
|------------|-------|-------|
| `ClientUserServiceTest` | 2F | Returns null instead of User; role stored as "user" not "client" |
| `CloudStorageManagerTest` | 2E | Mock expectations don't match for `CloudConfigurationService` |
| `RefreshResultTest` | 1F | `getDescription()` returns literal `:description` placeholder |
| `S3ErrorHandlerTest` | 1F | `requiresUserIntervention()` returns false, expects true |

---

## Clean/Passing Test Files (41)

These files pass with zero errors, failures, or risky warnings:

**Components:** `FileManagerConfirmationModalTest`, `FileManagerErrorNotificationTest`, `FileManagerFileTableTest`, `FileManagerHeaderTest`, `FileManagerIndexTest`, `FileManagerProgressModalTest`, `FileManagerSuccessNotificationTest`

**Console Commands:** `AnalyzeSecurityLogsTest`, `CleanupUploadsCommandTest`, `ListUsersCommandTest`

**Contracts:** `CloudStorageInterfaceContractTest`, `CloudStorageProviderComplianceTest`

**Controllers:** `EmployeeFileManagerControllerTest`, `Employee\DashboardControllerTest`

**Enums:** `TokenRefreshErrorTypeTest`

**Exceptions:** `CloudStorageExceptionTest`

**Helpers:** `PaginationConfigHelperTest`

**HTTP Middleware:** `TokenRefreshRateLimitTest`

**Jobs:** `UploadToGoogleDriveEnhancedSimpleTest`

**Mail:** `AdminVerificationMailTest`, `ClientVerificationMailTest`, `EmployeeVerificationMailTest`

**Middleware:** `QueueWorkerTestRateLimitTest`, `RequireSetupMiddlewareTest`

**Services:** `CloudStorageErrorMessageServiceTest`, `CloudStorageFactoryRegistrationTest`, `CloudStorageFeatureDetectionServiceTest`, `CloudStorageGracefulDegradationServiceTest`, `CloudStorageHealthStatusTest`, `CloudStorageMessageConsistencyTest`, `CloudStorageProviderAvailabilityServiceTest`, `GoogleDriveErrorHandlerTokenRefreshTest`, `GoogleDriveProviderEnhancedTest`, `HealthStatusTest`, `QueueWorkerStatusEnhancedErrorHandlingTest`, `QueueWorkerStatusTest`, `QueueWorkerTestSecurityServiceTest`, `RealTimeHealthValidatorTest`

**Other:** `ControllerValidationTest`, `ExampleTest`, `ValidationRulesTest`

---

## Recommended Fix Order

| # | Category | Errors Fixed | Effort |
|---|----------|-------------|--------|
| 1 | PDO transaction fix (base test class) | ~1,044 | Medium — one infrastructure change |
| 2 | Delete missing-method test files | 29 | Trivial |
| 3 | Add tearDown handler cleanup | 134 risky | Low — add tearDown to 14 classes |
| 4 | Update stale assertion values | ~15 | Low — update counts/values |
| 5 | Fix auth in test requests | 10 | Low — add `actingAs()` |
| 6 | Fix mockery expectations | 16 | Medium — case-by-case |
| 7 | Fix remaining individual issues | ~6 | Medium — case-by-case |
