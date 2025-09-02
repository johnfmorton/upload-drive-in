# Implementation Plan

- [x] 1. Enhance GoogleDriveService with proactive token validation
  - Add `validateAndRefreshToken()` method that attempts token refresh without performing file operations
  - Add `testApiConnectivity()` method that performs lightweight API call to verify connection
  - Update existing token refresh logic to return success/failure status
  - Write unit tests for token validation and refresh scenarios
  - _Requirements: 2.1, 2.2, 7.1, 7.2_

- [x] 2. Add database fields for consolidated status tracking
  - Create migration to add `consolidated_status`, `last_token_refresh_attempt_at`, `token_refresh_failures`, and `operational_test_result` fields to `cloud_storage_health_statuses` table
  - Update `CloudStorageHealthStatus` model with new fillable fields and casts
  - Add helper methods `getConsolidatedStatusMessage()` and `isTokenRefreshWorking()` to model
  - Write unit tests for new model methods and field handling
  - _Requirements: 6.4, 7.3_

- [x] 3. Implement enhanced status determination logic in CloudStorageHealthService
  - Add `ensureValidToken()` method that proactively attempts token refresh during status checks
  - Add `testApiConnectivity()` method that validates actual operational capability
  - Modify `checkConnectionHealth()` to use proactive token validation and API testing
  - Implement `determineConsolidatedStatus()` method that prioritizes operational capability over token age
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 6.1, 6.2_

- [x] 4. Update status determination to eliminate contradictory messages
  - Modify `getHealthSummary()` to return consolidated status instead of separate token warnings
  - Remove separate `token_expiring_soon` and `token_expired` flags when connection is operational
  - Ensure status shows "Healthy" when token refresh works automatically
  - Add logic to show "Authentication Required" only when refresh token is invalid/expired
  - _Requirements: 1.3, 1.4, 3.1, 3.2, 3.3_

- [x] 5. Update dashboard widget to display consolidated status messages
  - Modify cloud storage status widget template to show single status message
  - Remove separate "Token refresh needed" and "Token will refresh soon" warnings
  - Update JavaScript status display logic to use consolidated status
  - Ensure "Test Connection" button results are consistent with displayed status
  - _Requirements: 4.1, 4.2, 4.3, 5.1, 5.2_

- [x] 6. Implement comprehensive error handling for token refresh scenarios
  - Add specific error handling for expired refresh tokens
  - Add handling for network issues during token refresh
  - Add handling for API quota exceeded scenarios
  - Implement exponential backoff for failed refresh attempts
  - _Requirements: 2.3, 2.4, 6.3_

- [x] 7. Write integration tests for complete status flow
  - Test end-to-end status check with automatic token refresh
  - Test that successful token refresh results in "Healthy" status without warnings
  - Test that failed token refresh results in "Authentication Required" status
  - Test that API connectivity issues show "Connection Issues" status
  - Verify dashboard display consistency with backend status determination
  - _Requirements: 4.4, 7.4, 7.5_

- [x] 8. Add caching and performance optimizations
  - Implement caching for successful token refresh results (5 minutes)
  - Implement caching for API connectivity test results (2 minutes)
  - Add rate limiting for token refresh attempts to prevent API quota exhaustion
  - Track and limit frequency of connectivity tests
  - _Requirements: 6.5_

- [x] 9. Update controller endpoints to use consolidated status
  - Modify admin and employee cloud storage status endpoints to return consolidated status
  - Update test connection endpoints to use new proactive validation logic
  - Ensure status refresh endpoints trigger comprehensive status checks
  - Add proper error handling and user-friendly error messages
  - _Requirements: 3.4, 4.1, 4.2_

- [x] 10. Create comprehensive test suite for refresh token validation
  - Write tests that verify refresh token mechanism works with expired access tokens
  - Write tests that verify system handles expired refresh tokens gracefully
  - Write tests that verify automatic refresh during various operations
  - Write browser tests that verify dashboard shows consistent status messages
  - Create manual testing scenarios for token expiration edge cases
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 11. Add logging and monitoring for token refresh operations
  - Add detailed logging for all token refresh attempts and outcomes
  - Add logging for status determination decisions and reasoning
  - Implement metrics tracking for token refresh success/failure rates
  - Add monitoring for frequency of different status states
  - _Requirements: 5.3, 5.4_

- [x] 12. Update documentation and create migration guide
  - Document new consolidated status approach and elimination of contradictory messages
  - Create troubleshooting guide for Google Drive connection issues
  - Update API documentation for status endpoints
  - Create migration notes for any breaking changes to status responses
  - _Requirements: 6.4_
