# Implementation Plan

- [x] 1. Enhance GoogleDriveToken model with tracking fields
  - Add database migration for new token tracking fields (last_refresh_attempt_at, refresh_failure_count, last_successful_refresh_at, proactive_refresh_scheduled_at, health_check_failures, requires_user_intervention, last_notification_sent_at, notification_failure_count)
  - Update GoogleDriveToken model with new fillable fields and cast types
  - Add helper methods: isExpiringSoon(), canBeRefreshed(), shouldScheduleProactiveRefresh(), markRefreshFailure(), markRefreshSuccess()
  - Write unit tests for new model methods and field validation
  - _Requirements: 1.1, 1.2, 5.1_

- [x] 2. Create TokenRefreshErrorType enum and classification system
  - Create TokenRefreshErrorType enum with cases: NETWORK_TIMEOUT, INVALID_REFRESH_TOKEN, EXPIRED_REFRESH_TOKEN, API_QUOTA_EXCEEDED, SERVICE_UNAVAILABLE, UNKNOWN_ERROR
  - Add methods to enum: isRecoverable(), requiresUserIntervention(), getRetryDelay(), getMaxRetryAttempts()
  - Implement error classification logic in GoogleDriveErrorHandler to map Google API errors to TokenRefreshErrorType
  - Write unit tests for error classification and enum methods
  - _Requirements: 1.3, 1.4, 5.2_

- [x] 3. Implement TokenRefreshCoordinator with mutex locking
  - Create TokenRefreshCoordinator class with coordinateRefresh() method using Cache::lock()
  - Implement duplicate refresh prevention using Redis locks with 30-second TTL
  - Add logic to check if token was already refreshed by another process
  - Create RefreshResult value object to return detailed refresh outcomes
  - Write unit tests for concurrent refresh coordination and lock behavior
  - _Requirements: 1.1, 1.5_

- [x] 4. Enhance GoogleDriveService with proactive token validation
  - Update validateAndRefreshToken() method to use TokenRefreshCoordinator
  - Implement proactive refresh logic (refresh 15 minutes before expiration)
  - Add comprehensive error handling with TokenRefreshErrorType classification
  - Update refreshToken() method to use exponential backoff retry logic (1s, 2s, 4s, 8s, 16s)
  - Add detailed logging for all token refresh operations with operation IDs
  - Write unit tests for proactive refresh timing and retry logic
  - _Requirements: 1.1, 1.2, 1.4, 5.1_

- [x] 5. Create ProactiveTokenRenewalService
  - Implement ProactiveTokenRenewalService class with refreshTokenIfNeeded() and schedulePreemptiveRefresh() methods
  - Add logic to schedule token refresh 15 minutes before expiration using Laravel queues
  - Implement handleRefreshFailure() method with error classification and notification triggering
  - Create RefreshTokenJob for background token refresh processing
  - Write unit tests for scheduling logic and failure handling
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 6. Implement RealTimeHealthValidator for accurate status reporting
  - Create RealTimeHealthValidator class with validateConnectionHealth() and performLiveApiTest() methods
  - Implement three-tier validation: token validation → API connectivity → operational capability
  - Add caching strategy with different TTL for healthy (30s) vs error (10s) status
  - Update testApiConnectivity() to use Google Drive about.get API call for lightweight testing
  - Create HealthStatus value object to represent validation results
  - Write unit tests for validation logic and caching behavior
  - _Requirements: 2.1, 2.2, 2.3, 6.1, 6.2_

- [x] 7. Enhance CloudStorageHealthService with real-time validation
  - Update determineConsolidatedStatus() method to use RealTimeHealthValidator
  - Implement live validation that performs actual API calls instead of relying on cached status
  - Add logic to detect and auto-correct inconsistent health status (healthy status with expired tokens)
  - Update getHealthSummary() to include live validation timestamps and results
  - Add rate limiting for health checks to prevent API abuse
  - Write unit tests for status determination accuracy and auto-correction logic
  - _Requirements: 2.1, 2.2, 2.5, 6.1, 6.5_

- [x] 8. Create email notification system for token renewal failures
  - Create TokenRenewalNotificationService class with notification methods for different error types
  - Implement notification templates: token expired, refresh failure, connection restored
  - Add notification throttling logic (max 1 email per error type per 24 hours per user)
  - Create Mail classes: TokenExpiredMail, TokenRefreshFailedMail, ConnectionRestoredMail
  - Implement escalation to admin users when employee notifications fail
  - Write unit tests for notification logic, throttling, and template rendering
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 9. Update UploadToGoogleDrive job with automatic token refresh
  - Modify job handle() method to call ensureValidToken() before any Google Drive operations
  - Add automatic retry logic when token refresh succeeds after initial failure
  - Update error handling to trigger appropriate notifications based on TokenRefreshErrorType
  - Implement job retry coordination with TokenRefreshCoordinator
  - Add detailed logging for token refresh attempts during upload jobs
  - Write unit tests for upload job token refresh integration and retry behavior
  - _Requirements: 1.1, 4.1, 4.2, 4.3_

- [x] 10. Create ConnectionRecoveryService for automatic remediation
  - Implement ConnectionRecoveryService class with attemptAutomaticRecovery() and retryPendingUploads() methods
  - Add logic to identify and retry pending uploads when connection is restored
  - Implement automatic recovery strategies for different error types (network, quota, service unavailable)
  - Create PendingUploadRetryJob for processing failed uploads after connection recovery
  - Add notification logic for successful recovery and failed recovery attempts
  - Write unit tests for recovery logic and pending upload retry coordination
  - _Requirements: 4.1, 4.2, 4.4, 4.5_

- [x] 11. Implement background maintenance jobs for proactive monitoring
  - Create TokenMaintenanceJob to find and refresh tokens expiring within 30 minutes
  - Implement HealthStatusValidationJob to periodically validate connection health for active users
  - Add CleanupFailedRefreshAttemptsJob to clean up old failed refresh records
  - Create ProactiveRefreshScheduler to schedule token refreshes based on expiration times
  - Add job scheduling in Laravel's task scheduler for regular maintenance
  - Write unit tests for maintenance job logic and scheduling
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [x] 12. Update dashboard controllers with live health validation and detailed token status
  - Modify admin dashboard controller to use RealTimeHealthValidator for connection status
  - Update "Test Connection" button to perform same validation as dashboard status display
  - Add comprehensive token status display: token issued date, expiration date, next automated renewal time
  - Implement token health indicators: time until expiration, renewal countdown, last refresh status
  - Add real-time status updates without requiring page refresh
  - Implement consistent status messaging between dashboard display and test button results
  - Add loading states and error handling for live validation requests
  - Create token status component showing: "Token issued: [date]", "Expires: [date] ([time remaining])", "Auto-renewal scheduled: [date/time]"
  - Write feature tests for dashboard status accuracy, test button consistency, and token status display
  - _Requirements: 2.1, 2.2, 2.5, 7.1_

- [x] 13. Add comprehensive logging and monitoring
  - Implement structured logging for all token refresh operations with operation IDs
  - Add performance metrics tracking: refresh success rate, average refresh time, health validation accuracy
  - Create log analysis queries for troubleshooting token refresh issues
  - Implement alerting thresholds: refresh failure rate > 10%, health cache miss rate > 50%
  - Add monitoring dashboard for token health metrics and system performance
  - Write unit tests for logging format consistency and metric calculation
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 14. Implement rate limiting and security measures
  - Add rate limiting for token refresh attempts (max 5 attempts per hour per user)
  - Implement security logging for all authentication-related operations
  - Add token rotation on successful refresh to improve security
  - Create audit trail for token refresh failures and user interventions
  - Implement IP-based r ate limiting for token refresh endpoints
  - Write security tests for rate limiting and audit logging
  - _Requirements: 1.4, 1.5, 5.1_

- [x] 15. Create integration tests for end-to-end token lifecycle
  - Write integration tests for complete token refresh flow from expiration to renewal
  - Test upload job behavior with expired tokens and automatic refresh
  - Create tests for dashboard status accuracy during token refresh scenarios
  - Implement tests for notification delivery and throttling behavior
  - Add tests for concurrent user scenarios and race condition handling
  - Test automatic recovery of pending uploads after connection restoration
  - _Requirements: 1.1, 2.1, 3.1, 4.1_

- [x] 16. Add configuration and feature flags for gradual rollout
  - Create configuration options for token refresh timing (default 15 minutes before expiration)
  - Add feature flags for proactive refresh, live validation, and automatic recovery
  - Implement configuration for notification throttling and retry limits
  - Add environment-specific settings for development, staging, and production
  - Create admin interface for managing token refresh settings
  - Write tests for configuration validation and feature flag behavior
  - _Requirements: 6.1, 6.2, 8.1_

- [x] 17. Implement performance optimizations and caching
  - Add Redis caching for health status validation results with appropriate TTL
  - Implement database query optimization for token expiration lookups
  - Add connection pooling for Google API client instances
  - Create cache warming strategies for frequently accessed health status
  - Implement batch processing for multiple token refresh operations
  - Write performance tests for caching effectiveness and query optimization
  - _Requirements: 6.5, 6.6_

- [x] 18. Enhance dashboard token status widget with comprehensive information display
  - Create TokenStatusWidget component showing detailed token lifecycle information
  - Display token issued timestamp with relative time ("Issued 3 days ago on March 15, 2025")
  - Show token expiration with countdown timer ("Expires in 2 hours 15 minutes on March 18, 2025 at 3:30 PM")
  - Display next automated renewal schedule ("Auto-renewal scheduled for March 18, 2025 at 3:15 PM")
  - Add visual indicators: green for healthy tokens, yellow for expiring soon, red for expired/failed
  - Show last refresh attempt status and timestamp ("Last refreshed successfully 2 hours ago")
  - Display refresh failure count and last error if applicable
  - Add manual refresh button for immediate token renewal testing
  - Implement real-time updates for countdown timers and status changes
  - Write unit tests for token status calculations and display formatting
  - _Requirements: 2.1, 2.2, 7.1_

- [x] 19. Create comprehensive documentation and deployment guide
  - Write deployment guide with step-by-step migration instructions
  - Create troubleshooting documentation for common token refresh issues
  - Document configuration options and recommended settings for different environments
  - Add monitoring and alerting setup instructions
  - Create user guide for understanding connection status, token information, and notifications
  - Document rollback procedures and emergency recovery steps
  - Add screenshots and examples of the enhanced dashboard token status widget
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
