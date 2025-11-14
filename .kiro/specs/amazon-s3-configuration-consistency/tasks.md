# Implementation Plan

- [x] 1. Add environment variable detection to controller
  - Add `getS3EnvironmentSettings()` private method to `CloudStorageController`
  - Update `index()` method to pass `$s3EnvSettings` to view
  - Ensure method checks for AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, and AWS_ENDPOINT
  - _Requirements: 1.1, 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 2. Add environment configuration banner to S3 view
  - Add information banner at top of S3 configuration section
  - Display banner only when at least one environment variable is set
  - List specific settings configured via environment variables
  - Use same styling as Google Drive banner (blue background, info icon)
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 3. Implement conditional field rendering for Access Key ID
  - Add conditional logic to check `$s3EnvSettings['access_key_id']`
  - Render read-only field with gray background when environment variable exists
  - Display actual environment value in read-only field
  - Add helper text "This value is configured via environment variables"
  - Render editable field with validation when no environment variable
  - _Requirements: 2.1, 2.6, 2.7, 5.1, 5.2, 5.3, 5.4_

- [x] 4. Implement conditional field rendering for Secret Access Key
  - Add conditional logic to check `$s3EnvSettings['secret_access_key']`
  - Render read-only password field when environment variable exists
  - Display exactly 40 dot characters (••••••••••••••••••••••••••••••••••••••••) for masked display
  - Add helper text "This value is configured via environment variables"
  - Render editable password field with validation when no environment variable
  - _Requirements: 2.2, 3.1, 3.2, 3.3, 3.4, 5.1, 5.2, 5.3, 5.4_

- [x] 5. Implement conditional field rendering for Region
  - Add conditional logic to check `$s3EnvSettings['region']`
  - Render disabled select dropdown with environment value when environment variable exists
  - Display region name from `$awsRegions` array
  - Add helper text "This value is configured via environment variables"
  - Render enabled select dropdown with validation when no environment variable
  - _Requirements: 2.3, 2.6, 2.7, 5.1, 5.2, 5.3, 5.4_

- [ ] 6. Implement conditional field rendering for Bucket Name
  - Add conditional logic to check `$s3EnvSettings['bucket']`
  - Render read-only field with gray background when environment variable exists
  - Display actual environment value in read-only field
  - Add helper text "This value is configured via environment variables"
  - Render editable field with validation when no environment variable
  - _Requirements: 2.4, 2.6, 2.7, 5.1, 5.2, 5.3, 5.4_

- [ ] 7. Implement conditional field rendering for Custom Endpoint
  - Add conditional logic to check `$s3EnvSettings['endpoint']`
  - Render read-only field with gray background when environment variable exists
  - Display actual environment value in read-only field
  - Add helper text "This value is configured via environment variables"
  - Render editable field with validation when no environment variable
  - _Requirements: 2.5, 2.6, 2.7, 5.1, 5.2, 5.3, 5.4_

- [ ] 8. Implement conditional save button display
  - Add PHP logic to check if all required fields are from environment
  - Hide save button when access_key_id, secret_access_key, region, and bucket are all from environment
  - Display save button when at least one field is editable
  - Ensure button follows same pattern as Google Drive credentials form
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 9. Update Alpine.js handler for environment values
  - Add `envSettings` property to `s3ConfigurationHandler()` data structure
  - Initialize form data with environment values in `init()` method
  - Update `testConnection()` to use environment values when present
  - Update `handleSubmit()` to prevent submission when all fields are from environment
  - _Requirements: 6.1, 6.3, 7.5_

- [ ] 10. Verify form submission for database credentials
  - Test saving credentials when no environment variables are set
  - Verify validation works correctly for database-stored credentials
  - Verify success message displays after successful save
  - Verify error messages display when save fails
  - Verify credentials are properly stored in cloud_storage_settings table
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [ ] 11. Test mixed configuration scenarios
  - Test with only access key in environment
  - Test with only secret key in environment
  - Test with access key and secret in environment, region and bucket in database
  - Verify correct fields are read-only in each scenario
  - Verify save button displays correctly in mixed scenarios
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 4.2_

- [ ] 12. Verify test connection functionality
  - Test connection with all credentials from environment
  - Test connection with all credentials from database
  - Test connection with mixed credentials
  - Verify test uses correct credential source
  - Verify test results display correctly
  - _Requirements: 6.1, 6.3_

- [ ] 13. Verify disconnect functionality
  - Test disconnect with environment credentials
  - Test disconnect with database credentials
  - Verify disconnect clears database credentials only
  - Verify disconnect does not modify environment variables
  - Verify appropriate message displays after disconnect
  - _Requirements: 6.2, 6.4_
