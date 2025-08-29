# Requirements Document

## Introduction

This feature addresses a critical issue in the setup instructions page where the queue worker status check provides misleading feedback to users. Currently, when users click the "Check Status" button, the queue worker step shows "Queue worker is functioning properly" even when the queue worker is not actually completing jobs. This creates a false impression that the setup is complete when it may not be. The fix involves separating the queue worker status check from the general status refresh and ensuring that the queue worker status only shows as "functioning properly" when a test job has actually been completed successfully.

## Requirements

### Requirement 1

**User Story:** As an administrator setting up the application, I want the queue worker status to accurately reflect whether jobs are being processed so that I don't get false positive feedback about my setup.

#### Acceptance Criteria

1. WHEN I click the "Check Status" button at the top of the page THEN the queue worker step SHALL NOT be automatically checked as part of the general status refresh
2. WHEN the queue worker status has not been tested THEN the status text SHALL display "Click the Test Queue Worker button below"
3. WHEN I click the "Test Queue Worker" button THEN the system SHALL dispatch a test job and only update the queue worker status based on the actual completion of that job
4. WHEN the test job completes successfully THEN the queue worker status SHALL show "Queue worker is functioning properly" with a green indicator

### Requirement 2

**User Story:** As an administrator, I want the "Check Status" button to also trigger a queue worker test so that I can verify all setup steps including queue functionality with a single action.

#### Acceptance Criteria

1. WHEN I click the "Check Status" button at the top of the page THEN the system SHALL trigger the same queue worker test that would be triggered by clicking the "Test Queue Worker" button
2. WHEN the general status check is running THEN the queue worker status SHALL show appropriate loading/testing indicators
3. WHEN the queue worker test completes as part of the general status check THEN the queue worker status SHALL be updated based on the actual test results
4. WHEN other status checks complete but the queue worker test is still running THEN the queue worker status SHALL continue to show testing indicators until the test completes

### Requirement 3

**User Story:** As an administrator, I want clear visual feedback about the queue worker testing process so that I understand what the system is doing and can wait appropriately for results.

#### Acceptance Criteria

1. WHEN a queue worker test is initiated THEN the status SHALL show "Testing queue worker..." or similar loading message
2. WHEN the test job is dispatched but not yet processed THEN the status SHALL indicate "Test job queued, waiting for worker..."
3. WHEN the test job is being processed THEN the status SHALL indicate "Test job is being processed..."
4. WHEN the test completes successfully THEN the status SHALL show "Queue worker is functioning properly" with processing time
5. WHEN the test fails or times out THEN the status SHALL show appropriate error messages with troubleshooting guidance

### Requirement 4

**User Story:** As an administrator, I want the queue worker status to persist between page refreshes so that I don't have to re-test the queue worker every time I reload the page.

#### Acceptance Criteria

1. WHEN I successfully complete a queue worker test THEN the positive status SHALL persist when I refresh the page
2. WHEN I refresh the page after a successful test THEN the status SHALL show "Queue worker is functioning properly" without requiring a new test
3. WHEN the cached test result is older than a reasonable time period THEN the system SHALL prompt for a new test
4. WHEN I refresh the page and no recent test has been completed THEN the status SHALL show "Click the Test Queue Worker button below"

### Requirement 5

**User Story:** As an administrator, I want the system to handle queue worker test failures gracefully so that I can understand what went wrong and how to fix it.

#### Acceptance Criteria

1. WHEN the queue worker is not running THEN the test SHALL timeout after a reasonable period and show "Queue worker may not be running"
2. WHEN the test job fails due to an error THEN the status SHALL show the specific error message
3. WHEN the test cannot be dispatched THEN the status SHALL show "Failed to dispatch test job" with technical details
4. WHEN multiple test attempts fail THEN the system SHALL provide troubleshooting guidance and manual verification steps
5. WHEN a test failure occurs THEN the status SHALL include a "Retry Test" option for easy re-testing