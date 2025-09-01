# Implementation Plan

- [ ] 1. Create database migration for upload recovery fields
  - Add retry_count, last_error, error_details, last_processed_at, recovery_attempts fields to file_uploads table
  - Create indexes for efficient querying by status and processing timestamps
  - _Requirements: 1.3, 3.1_

- [ ] 2. Update FileUpload model with new status constants and methods
  - Add new status constants (FAILED, MISSING_FILE, RETRY)
  - Create methods for checking stuck uploads and updating recovery status
  - Add relationships and scopes for filtering by recovery status
  - _Requirements: 1.1, 3.1_

- [ ] 3. Create UploadRecoveryService for automatic stuck upload detection
  - Implement detectStuckUploads() method to find uploads pending beyond threshold
  - Create attemptRecovery() method to reprocess individual stuck uploads
  - Add bulkRecovery() method for processing multiple uploads efficiently
  - _Requirements: 1.1, 1.2, 1.4_

- [ ] 4. Create UploadDiagnosticService for system health monitoring
  - Implement performHealthCheck() to verify queue worker, disk space, API connectivity
  - Create analyzeUploadFailure() method for detailed failure analysis
  - Add validateGoogleDriveConnectivity() to check API status and token validity
  - _Requirements: 4.1, 4.3, 5.1_

- [ ] 5. Enhance UploadToGoogleDrive job with better error handling
  - Add retry logic with exponential backoff for transient errors
  - Implement proper error classification (transient vs permanent)
  - Update job to record detailed error information in database
  - _Requirements: 1.2, 3.1, 4.3_

- [ ] 6. Create configuration file for upload recovery settings
  - Define thresholds for stuck uploads, retry attempts, and alert conditions
  - Set batch sizes and processing limits
  - Configure monitoring and alerting parameters
  - _Requirements: 1.1, 1.2, 4.1_

- [ ] 7. Create DiagnoseUploadsCommand for CLI system health checks
  - Implement comprehensive system diagnostics (queue, storage, API, tokens)
  - Add detailed reporting of upload queue status and potential issues
  - Include recommendations for resolving detected problems
  - _Requirements: 5.1, 5.4_

- [ ] 8. Create RecoverPendingUploadsCommand for CLI upload recovery
  - Implement batch processing of stuck uploads with progress reporting
  - Add options for processing specific upload IDs or all pending uploads
  - Include detailed logging and summary reporting
  - _Requirements: 5.2, 5.5_

- [ ] 9. Create CleanupUploadsCommand for maintenance operations
  - Implement orphaned file cleanup and database consistency checks
  - Add options for removing failed upload artifacts
  - Include dry-run mode for safe testing
  - _Requirements: 5.3_

- [ ] 10. Create process-pending-modal component following modal standards
  - Build modal component with proper z-index hierarchy (z-[10000])
  - Implement Alpine.js data structure for modal state management
  - Add loading states and progress indicators during processing
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 11. Update file manager dashboard to use new modal instead of alerts
  - Replace alert-based "Process 1 Pending" button with modal trigger
  - Integrate modal with existing file manager Alpine.js component
  - Add proper error handling and success feedback
  - _Requirements: 2.1, 2.4, 2.5_

- [ ] 12. Create upload failure detail modal for error investigation
  - Build modal to display detailed error information for failed uploads
  - Show retry history, error messages, and diagnostic information
  - Include options for manual retry or marking as resolved
  - _Requirements: 3.2, 3.3_

- [ ] 13. Implement automated monitoring and alerting system
  - Create scheduled command to detect stuck uploads and send alerts
  - Add failure rate monitoring with configurable thresholds
  - Implement Google Drive token expiration alerts
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 14. Add comprehensive logging for upload processing events
  - Enhance existing upload job logging with recovery attempt details
  - Add structured logging for diagnostic and monitoring purposes
  - Include performance metrics and error pattern tracking
  - _Requirements: 1.3, 3.4, 4.4_

- [ ] 15. Create unit tests for upload recovery services
  - Test UploadRecoveryService methods with various failure scenarios
  - Test UploadDiagnosticService health check functionality
  - Test error classification and retry logic
  - _Requirements: 1.1, 1.2, 3.1_

- [ ] 16. Create integration tests for end-to-end recovery workflow
  - Test complete recovery process from detection to resolution
  - Test CLI commands with various upload states and conditions
  - Test modal interface behavior and error handling
  - _Requirements: 2.1, 5.1, 5.2_

- [ ] 17. Update file manager interface to show enhanced status indicators
  - Add visual indicators for Failed, Missing File, and Retry statuses
  - Update status badges with appropriate colors and icons
  - Include hover tooltips with additional status information
  - _Requirements: 3.2, 3.3_

- [ ] 18. Create dashboard metrics and monitoring widgets
  - Add upload success/failure rate displays
  - Create queue health status indicators
  - Include recent error trends and system alerts
  - _Requirements: 4.1, 4.4, 5.4_