# Implementation Plan

- [x] 1. Enhance CloudStorageErrorMessageService with specific error contexts
  - Add rate limiting message handling for "Too many token refresh attempts" scenarios
  - Implement context-aware message generation that considers error types and consecutive failures
  - Create priority-based message resolution to show most relevant error first
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Create centralized status message configuration
  - Create `CloudStorageStatusMessages` class with constant message definitions
  - Implement dynamic retry time message generation for rate limiting scenarios
  - Add message validation and consistency checking methods
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 3. Update CloudStorageHealthService to provide structured error context
  - Modify `getProviderHealth` methods to return detailed error context instead of generic messages
  - Add rate limiting detection logic using token refresh attempt tracking
  - Implement error context data structure with all necessary fields for message generation
  - _Requirements: 1.1, 1.4, 5.4_

- [x] 4. Refactor controller status message generation
  - ✅ Update `Admin/CloudStorageController` to use centralized messaging instead of inline message generation
  - ✅ Update `Employee/CloudStorageController` to use centralized messaging
  - ✅ Update `CloudStorageDashboardController` to use centralized messaging
  - ✅ Remove duplicate "Connection issues detected" message generation from all controllers
  - _Requirements: 2.1, 2.2, 3.1, 3.2_

- [x] 5. Update cloud storage status widget frontend logic
  - ✅ Modify status display logic to use single message source from backend (already implemented)
  - ✅ Remove redundant status message generation in JavaScript (already implemented)
  - ✅ Implement consistent error display that doesn't contradict connection status badges (already implemented)
  - ✅ Add proper handling for rate limiting scenarios with retry time display (already implemented)
  - _Requirements: 2.1, 2.2, 2.3, 4.1, 4.2_

- [x] 6. Create comprehensive unit tests for message consistency
  - Test rate limiting message takes priority over generic connection issues
  - Test that healthy status doesn't show contradictory error messages
  - Test message generation for all error types and contexts
  - Test priority resolution when multiple errors exist
  - _Requirements: 3.3, 5.1, 5.2_

- [x] 7. Add integration tests for cross-component message consistency
  - Test that dashboard and modal components show identical messages for same errors
  - Test that admin and employee interfaces show consistent messaging
  - Test that status refresh maintains message consistency
  - Verify no redundant or contradictory messages appear in any interface
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 8. Implement rate limiting protection for test connection buttons
  - Add client-side button disabling after test attempts to prevent rapid clicking (already implemented in widget)
  - Implement server-side rate limiting for test connection endpoints (token.refresh.rate.limit middleware)
  - Show countdown timer when rate limit is reached instead of generic error (handled by centralized messaging)
  - Add visual feedback showing remaining cooldown time before next test is allowed (implemented in status widget)
  - _Requirements: 4.2, 4.4_

- [x] 9. Update factory and test data to use realistic error scenarios
  - Update `CloudStorageHealthStatusFactory` to generate realistic error contexts
  - Remove generic "Connection issues detected" from test data
  - Add factory states for rate limiting and specific error scenarios
  - _Requirements: 5.3, 5.4_
