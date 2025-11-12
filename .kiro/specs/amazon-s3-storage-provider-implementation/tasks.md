# Implementation Plan: Amazon S3 Storage Provider

## Task Overview

This implementation plan breaks down the Amazon S3 storage provider feature into discrete coding tasks. Each task builds incrementally on previous work and includes specific requirements references.

---

## Phase 1: Core S3Provider Implementation

- [x] 1. Complete S3Provider core methods
  - Implement the remaining methods in `app/Services/S3Provider.php` to fully satisfy CloudStorageProviderInterface
  - Complete `uploadFile()` method with proper S3 key generation and metadata handling
  - Complete `deleteFile()` method with error handling
  - Complete `getConnectionHealth()` method with system-level credential checking
  - Implement helper methods: `ensureInitialized()`, `getSystemConfig()`, `getBucket()`, `getRegion()`, `generateS3Key()`
  - Add support for custom endpoints (S3-compatible services)
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3, 4.4, 4.5, 14.2, 14.3, 14.5_

- [x] 2. Implement S3Provider configuration and validation methods
  - Complete `validateConfiguration()` method with AWS credential format validation
  - Implement access key ID regex validation (20 uppercase alphanumeric characters)
  - Implement secret access key length validation (40 characters)
  - Implement region format validation
  - Implement bucket name validation according to S3 naming rules
  - Implement custom endpoint URL validation for S3-compatible services
  - Complete `initialize()` method to create S3Client with configuration
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 14.1, 14.4_

- [x] 3. Implement S3Provider capability methods
  - Complete `getCapabilities()` method returning accurate S3 feature flags
  - Implement `getAuthenticationType()` returning 'api_key'
  - Implement `getStorageModel()` returning 'flat'
  - Implement `getMaxFileSize()` returning 5TB limit
  - Implement `getSupportedFileTypes()` returning ['*']
  - Implement `supportsFeature()` method for feature checking
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 4. Implement S3Provider OAuth stub methods
  - Implement `handleAuthCallback()` to throw CloudStorageException with FEATURE_NOT_SUPPORTED
  - Implement `getAuthUrl()` to throw CloudStorageException with FEATURE_NOT_SUPPORTED
  - Implement `disconnect()` to clear system-level credentials from database
  - Implement `hasValidConnection()` to check system-level configuration
  - _Requirements: 13.4, 13.5, 13.1, 13.2, 13.3_

---

## Phase 2: Advanced S3 Features

- [x] 5. Implement presigned URL generation
  - Complete `generatePresignedUrl()` method for download, upload, and delete operations
  - Implement S3Client createPresignedRequest for each operation type
  - Add configurable expiration time support
  - Add error handling and logging
  - _Requirements: 10.1, 10.4_

- [x] 6. Implement storage class management
  - Complete `setStorageClass()` method using S3 copyObject
  - Implement `getAvailableStorageClasses()` returning S3 storage tiers with descriptions
  - Add validation for storage class names
  - Include Standard, IA, Glacier, Deep Archive options
  - _Requirements: 10.2, 10.5_

- [x] 7. Implement file metadata operations
  - Complete `getFileMetadata()` method to retrieve S3 object metadata
  - Complete `setFileMetadata()` method to update S3 object metadata
  - Implement `addFileTags()` method for S3 object tagging
  - Implement `getFileTags()` method to retrieve object tags
  - _Requirements: 10.1_

- [x] 8. Implement upload optimization
  - Complete `optimizeUpload()` method for large file handling
  - Add multipart upload support for files > 50MB
  - Implement configurable chunk size
  - Add progress tracking capability
  - _Requirements: 10.3_

---

## Phase 3: Error Handling and Logging

- [x] 9. Complete S3ErrorHandler implementation
  - Implement `classifyError()` method mapping AWS exceptions to CloudStorageErrorType
  - Add mapping for InvalidAccessKeyId → INVALID_CREDENTIALS
  - Add mapping for NoSuchBucket → BUCKET_NOT_FOUND
  - Add mapping for AccessDenied → BUCKET_ACCESS_DENIED
  - Add mapping for InvalidBucketName → INVALID_BUCKET_NAME
  - Add mapping for NoSuchKey → FILE_NOT_FOUND
  - Add mapping for RequestTimeout → NETWORK_ERROR
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 10. Add new CloudStorageErrorType enum cases
  - Add BUCKET_NOT_FOUND case to CloudStorageErrorType enum
  - Add BUCKET_ACCESS_DENIED case
  - Add INVALID_BUCKET_NAME case
  - Add INVALID_REGION case
  - Add FILE_NOT_FOUND case (if not already present)
  - Add STORAGE_CLASS_NOT_SUPPORTED case
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 11. Add S3 error messages to CloudStorageErrorMessageService ✅
  - Add user-friendly messages for BUCKET_NOT_FOUND ✅
  - Add admin messages for BUCKET_ACCESS_DENIED ✅
  - Add technical messages for INVALID_BUCKET_NAME ✅
  - Add messages for INVALID_REGION ✅
  - Add messages for FILE_NOT_FOUND ✅
  - _Requirements: 7.2, 7.3, 7.4_
  - **Note**: All S3-specific error messages have been added to CloudStorageErrorMessageService.php and translated to all supported languages (English, German, Spanish, French)

- [x] 12. Implement S3 operation logging
  - Add logging for upload operations (start, success, failure)
  - Add logging for delete operations
  - Add logging for health checks
  - Add logging for configuration changes
  - Include user context and operation duration in logs
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

---

## Phase 4: Configuration Storage and Retrieval

- [x] 13. Enhance CloudStorageSetting model
  - Add `getDecryptedValueAttribute()` accessor for encrypted values
  - Add `scopeSystemLevel()` scope for system-level settings (user_id IS NULL)
  - Add `scopeForProvider()` scope for filtering by provider
  - Add static `getProviderSettings()` helper method
  - Ensure proper encryption/decryption of sensitive fields
  - _Requirements: 1.4, 2.1, 2.2_

- [x] 14. Create CloudStorageSettingsService for S3 configuration management
  - Create service class to handle S3 settings CRUD operations
  - Implement `storeS3Configuration()` method to save encrypted credentials
  - Implement `getS3Configuration()` method to retrieve and decrypt credentials
  - Implement `deleteS3Configuration()` method to remove credentials
  - Implement `validateS3Configuration()` method using S3Provider validation
  - _Requirements: 1.3, 1.4, 1.5, 6.1, 6.2, 6.3, 6.4, 6.5_

---

## Phase 5: Provider Registration and Factory Integration

- [x] 15. Register S3Provider in CloudStorageServiceProvider
  - Add S3Provider registration in CloudStorageServiceProvider boot method
  - Register S3ErrorHandler with CloudStorageFactory
  - Ensure S3Provider is available in factory's registered providers list
  - Add initialization with configuration from cloud-storage.php
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [x] 16. Update cloud-storage.php configuration
  - Change amazon-s3 provider availability from 'coming_soon' to 'fully_available'
  - Verify all S3 configuration keys are present
  - Ensure error_handler is set to S3ErrorHandler::class
  - Verify features array is complete and accurate
  - _Requirements: 1.1, 5.1, 5.2, 5.3, 5.4, 5.5_

---

## Phase 6: Admin UI Implementation

- [x] 17. Create S3 configuration blade component
  - Create `resources/views/admin/cloud-storage/amazon-s3/configuration.blade.php`
  - Add form fields for AWS Access Key ID (text input with validation)
  - Add form fields for AWS Secret Access Key (password input)
  - Add region dropdown with common AWS regions
  - Add bucket name input with validation hints
  - Add optional custom endpoint field for S3-compatible services
  - Add connection status badge (Connected/Not Connected)
  - Include Alpine.js for dynamic form behavior
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 14.1_

- [x] 18. Update main cloud storage index view
  - Update `resources/views/admin/cloud-storage/index.blade.php`
  - Add x-show condition for amazon-s3 provider
  - Include the S3 configuration component
  - Ensure provider dropdown shows Amazon S3 as selectable
  - Update Alpine.js data to handle S3 provider selection
  - _Requirements: 1.1, 1.2, 9.1, 9.2_

- [x] 19. Add S3 configuration form validation
  - Add client-side validation for AWS Access Key ID format (20 chars, uppercase alphanumeric)
  - Add client-side validation for AWS Secret Access Key (40 chars)
  - Add client-side validation for bucket name format
  - Add client-side validation for custom endpoint URL format
  - Add visual feedback for validation errors
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 14.4_

---

## Phase 7: Controller Implementation

- [x] 20. Add S3 configuration routes
  - Add PUT route for `admin.cloud-storage.amazon-s3.update`
  - Add POST route for `admin.cloud-storage.amazon-s3.test-connection`
  - Add DELETE route for `admin.cloud-storage.amazon-s3.disconnect`
  - Update routes/web.php with proper middleware and naming
  - _Requirements: 1.3, 1.5, 9.5_

- [x] 21. Implement CloudStorageController S3 methods
  - Create `updateAmazonS3()` method in CloudStorageController
  - Implement request validation with custom rules for AWS credentials
  - Call CloudStorageSettingsService to store configuration
  - Perform health check after configuration save
  - Return appropriate success/error messages
  - _Requirements: 1.3, 1.4, 1.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 22. Implement S3 connection testing endpoint
  - Create `testAmazonS3Connection()` method in CloudStorageController
  - Initialize S3Provider with provided credentials
  - Perform health check without saving configuration
  - Return JSON response with connection status and details
  - _Requirements: 1.5, 8.1, 8.2, 8.3, 8.4_

- [x] 23. Implement S3 disconnect functionality
  - Create `disconnectAmazonS3()` method in CloudStorageController
  - Call S3Provider disconnect method
  - Remove S3 credentials from cloud_storage_settings table
  - Log the disconnection event
  - Return success message
  - _Requirements: 9.5, 12.5_

---

## Phase 8: Health Status Integration

- [x] 24. Update CloudStorageHealthService for S3
  - Ensure `determineConsolidatedStatus()` handles S3 provider correctly
  - Add S3-specific health status logic for system-level credentials
  - Handle S3 error types in status determination
  - Update health status caching for S3
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 25. Update dashboard cloud storage widget for S3
  - Update `resources/views/components/dashboard/cloud-storage-status-widget.blade.php`
  - Display S3 connection status for admin users
  - Show bucket name and region when connected
  - Display appropriate error messages for S3 connection issues
  - Add "Configure S3" link when not connected
  - _Requirements: 8.5, 9.3, 9.4_

---

## Phase 9: File Upload Integration

- [x] 26. Update UploadToGoogleDrive job for S3 support
  - Modify job to detect provider type from configuration
  - Use CloudStorageFactory to get appropriate provider instance
  - Pass system-level credentials for S3 operations
  - Ensure S3 key is stored in file_uploads table
  - Handle S3-specific errors appropriately
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 27. Update FileUpload model for S3 compatibility
  - Ensure `storage_provider` field can store 'amazon-s3'
  - Ensure `google_drive_file_id` field can store S3 keys (or rename to generic `cloud_file_id`)
  - Add accessor/mutator if field renaming is needed
  - Update relationships and scopes as needed
  - _Requirements: 3.4_

---

## Phase 10: Testing Implementation

- [ ] 28. Create S3Provider unit tests
  - Create `tests/Unit/Services/S3ProviderTest.php`
  - Write test for configuration validation
  - Write test for S3 key generation
  - Write test for capability reporting
  - Write test for OAuth method exceptions
  - Write test for custom endpoint support
  - Mock AWS S3Client for isolated testing
  - _Requirements: All requirements_

- [ ] 29. Create S3ErrorHandler unit tests
  - Create `tests/Unit/Services/S3ErrorHandlerTest.php`
  - Write tests for each AWS exception mapping
  - Test InvalidAccessKeyId → INVALID_CREDENTIALS
  - Test NoSuchBucket → BUCKET_NOT_FOUND
  - Test AccessDenied → BUCKET_ACCESS_DENIED
  - Test unknown errors → UNKNOWN_ERROR
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 30. Create S3 integration tests
  - Create `tests/Integration/S3ProviderIntegrationTest.php`
  - Write test for full upload workflow with real S3 (or LocalStack)
  - Write test for file deletion
  - Write test for health check
  - Write test for presigned URL generation
  - Write test for S3-compatible service (if available)
  - Use environment variables for test credentials
  - _Requirements: All requirements_

- [ ] 31. Create S3 configuration feature tests
  - Create `tests/Feature/Admin/CloudStorageS3ConfigurationTest.php`
  - Write test for admin accessing S3 configuration page
  - Write test for saving valid S3 configuration
  - Write test for validation errors on invalid configuration
  - Write test for health check after configuration
  - Write test for credential encryption in database
  - Write test for switching default provider to S3
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 6.1, 6.2, 6.3, 6.4, 6.5_

---

## Phase 11: Documentation and Deployment

- [ ] 32. Create S3 setup documentation
  - Create `docs/cloud-storage/amazon-s3-setup-guide.md`
  - Document AWS account setup requirements
  - Provide IAM policy template for S3 bucket access
  - Document environment variable configuration
  - Include troubleshooting section for common issues
  - Add examples for S3-compatible services (Cloudflare R2, Backblaze B2)
  - _Requirements: All requirements_

- [ ] 33. Update main cloud storage documentation
  - Update `docs/cloud-storage-provider-system.md`
  - Add S3 provider to provider comparison table
  - Document S3 vs Google Drive differences
  - Add S3 configuration examples
  - Update architecture diagrams to include S3
  - _Requirements: All requirements_

- [ ] 34. Create deployment checklist
  - Create deployment checklist for S3 feature
  - Include database migration steps (if any)
  - Include configuration verification steps
  - Include health check verification
  - Include rollback procedures
  - _Requirements: All requirements_

---

## Phase 12: Final Integration and Validation

- [ ] 35. Perform end-to-end testing
  - Test complete file upload flow with S3 as default provider
  - Test file deletion through admin interface
  - Test provider switching (Google Drive ↔ S3)
  - Test health status monitoring
  - Test error handling and recovery
  - Verify logging and audit trails
  - _Requirements: All requirements_

- [ ] 36. Update provider availability status
  - Change S3 provider status from 'coming_soon' to 'fully_available' in config
  - Update UI to show S3 as fully available option
  - Remove any "coming soon" badges or warnings
  - Update provider selection dropdown
  - _Requirements: 1.1_

- [ ] 37. Code review and cleanup
  - Review all S3-related code for consistency
  - Ensure proper error handling throughout
  - Verify logging is comprehensive
  - Check for security issues (credential exposure, etc.)
  - Remove debug code and console.log statements
  - Ensure code follows Laravel conventions
  - _Requirements: All requirements_

---

## Notes

- All tasks including tests are required for comprehensive implementation
- Each task should be completed and tested before moving to the next
- Requirements are referenced using the format from requirements.md
- Some tasks may be combined or split based on implementation complexity
- Health checks should be performed after each major integration point
- Testing tasks ensure quality and prevent regressions
