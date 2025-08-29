# Implementation Plan

- [x] 1. Modify SetupStatusService to exclude queue worker from general status refresh
  - Remove 'queue_worker' from the statusSteps array in refreshAllStatuses() method
  - Create separate getQueueWorkerStatus() method that checks cached test results
  - Update status response structure to exclude queue_worker from general refresh
  - Add cache key constants for queue worker status storage
  - Write unit tests for modified status refresh logic
  - _Requirements: 1.1, 1.2_

- [x] 2. Enhance queue worker status caching and persistence logic
  - Implement QueueWorkerStatus data class with proper status constants
  - Add caching logic in QueueTestService for storing test results with 1-hour TTL
  - Create getCachedQueueWorkerStatus() method to retrieve and validate cached results
  - Add cache invalidation logic for expired or invalid test results
  - Write unit tests for caching behavior and cache expiration handling
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 3. Update AJAX endpoints to handle separated queue worker logic
  - Modify /setup/status/refresh endpoint to exclude queue_worker from response
  - Create new /setup/queue-worker/status endpoint for queue worker status retrieval
  - Update existing queue test endpoints to properly cache results after completion
  - Add proper error handling and response formatting for all endpoints
  - Write feature tests for modified AJAX endpoint behavior
  - _Requirements: 1.1, 2.3, 4.1_

- [x] 4. Modify JavaScript SetupStatusManager to separate general status from queue worker testing
  - Remove 'queue_worker' from the statusSteps array in setup-status.js
  - Create separate generalStatusSteps array excluding queue worker
  - Modify refreshAllStatuses() method to handle general status and queue test separately
  - Add getCachedQueueWorkerStatus() method for checking cached test results on page load
  - Write JavaScript unit tests for modified status management logic
  - _Requirements: 1.1, 2.1, 4.4_

- [x] 5. Implement queue worker status persistence and initial state handling
  - Add logic to check cached queue worker status on page load
  - Implement isStatusExpired() method to determine if cached status is still valid
  - Update initial queue worker status display to show "Click the Test Queue Worker button below" when no recent test
  - Add proper handling for expired cache scenarios
  - Write JavaScript tests for status persistence and initial state logic
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 6. Enhance queue worker test integration with general status refresh
  - Modify refreshAllStatuses() to trigger queue worker test alongside general status checks
  - Implement parallel execution of general status refresh and queue worker testing
  - Add proper coordination between general status completion and ongoing queue test
  - Update UI to show appropriate loading states for both general status and queue test
  - Write integration tests for coordinated status refresh and queue testing
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 7. Implement progressive queue worker test status updates
  - Add status messages for different test phases: "Testing queue worker...", "Test job queued...", "Test job processing..."
  - Update testQueueWorker() method to show progressive status during test execution
  - Implement proper status transitions from testing to completed/failed states
  - Add processing time display for successful tests
  - Write tests for progressive status update functionality
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [ ] 8. Enhance error handling and failure scenarios for queue worker tests
  - Implement specific error messages for different failure types (dispatch failure, timeout, job failure)
  - Add retry functionality for failed tests with proper UI controls
  - Create troubleshooting guidance display for common failure scenarios
  - Add proper timeout handling with configurable timeout periods
  - Write comprehensive tests for all error scenarios and recovery mechanisms
  - _Requirements: 3.5, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 9. Update setup instructions Blade template for separated queue worker status
  - Modify queue worker status indicator to show initial "Click the Test Queue Worker button below" message
  - Update CSS classes and styling for different queue worker status states
  - Add retry button functionality for failed queue worker tests
  - Ensure proper responsive design for new status messages and controls
  - Test template changes across different browsers and device sizes
  - _Requirements: 1.2, 3.1, 5.5_

- [ ] 10. Implement proper button state management during testing
  - Disable both "Check Status" and "Test Queue Worker" buttons during active testing
  - Add loading spinners and visual feedback during test execution
  - Implement proper button re-enabling after test completion or failure
  - Add debouncing to prevent rapid button clicks and multiple concurrent tests
  - Write tests for button state management and user interaction handling
  - _Requirements: 2.2, 3.1, 5.5_

- [ ] 11. Add comprehensive error messaging and troubleshooting guidance
  - Create user-friendly error messages for different queue worker failure scenarios
  - Add troubleshooting tips for common issues (worker not running, configuration problems)
  - Implement expandable error details for technical debugging information
  - Add links to documentation or manual verification steps when appropriate
  - Write tests for error message display and troubleshooting guidance
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 12. Implement security measures and rate limiting for queue worker tests
  - Add rate limiting to prevent abuse of queue worker test functionality
  - Implement cooldown periods between test attempts
  - Add proper CSRF protection for all new AJAX endpoints
  - Validate and sanitize all user inputs and cached data
  - Write security tests for rate limiting and input validation
  - _Requirements: Security considerations from design_

- [ ] 13. Add performance optimizations and resource management
  - Implement efficient caching strategy with proper TTL and invalidation
  - Add cleanup logic for old test jobs and cached results
  - Optimize AJAX polling intervals and implement exponential backoff
  - Add proper timeout handling to prevent resource leaks
  - Write performance tests to verify optimization effectiveness
  - _Requirements: Performance considerations from design_

- [ ] 14. Create comprehensive test coverage for all modified components
  - Write unit tests for all modified service methods and JavaScript functions
  - Create integration tests for complete status refresh workflow with separated logic
  - Add feature tests for queue worker status persistence across page refreshes
  - Write end-to-end tests for all error scenarios and recovery mechanisms
  - Ensure test coverage meets project standards for all modified code
  - _Requirements: All requirements validation_

- [ ] 15. Final integration testing and bug fixes
  - Test complete workflow from fresh setup through successful queue worker testing
  - Verify status persistence works correctly across browser refreshes and sessions
  - Test all error scenarios and ensure proper recovery and user guidance
  - Verify backward compatibility with existing setup instructions functionality
  - Perform cross-browser testing and mobile responsiveness verification
  - _Requirements: All requirements integration_
