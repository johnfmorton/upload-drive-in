# Implementation Plan

- [ ] 1. Add folder_path configuration to cloud-storage config
  - Add 'folder_path' key to amazon-s3 provider config in `config/cloud-storage.php`
  - Set default value to empty string using `env('AWS_FOLDER_PATH', '')`
  - Ensure configuration follows existing pattern for other S3 settings
  - _Requirements: 1.1, 1.2, 1.3_

- [ ] 2. Update S3Provider service with folder path support
  - [ ] 2.1 Add getFolderPath() private method
    - Retrieve folder_path from config array
    - Trim leading/trailing slashes and whitespace
    - Return empty string if not configured
    - _Requirements: 1.4, 4.3_

  - [ ] 2.2 Add validateFolderPathFormat() private method
    - Validate alphanumeric, hyphens, underscores, slashes, periods only
    - Check for consecutive slashes
    - Check for leading/trailing slashes
    - Return array of validation errors
    - Allow empty folder path
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ] 2.3 Modify generateS3Key() method
    - Call getFolderPath() to retrieve configured path
    - Prepend folder path to S3 key when not empty
    - Ensure single slash separator between folder path and client email
    - Maintain existing key generation logic for filename
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ] 2.4 Update validateConfiguration() method
    - Add folder_path validation using validateFolderPathFormat()
    - Merge folder path errors with existing validation errors
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 2.5 Update initialize() method logging
    - Add folder_path to initialization log output
    - Include folder path in configuration logging
    - _Requirements: 8.4_

- [ ] 3. Update CloudStorageController for folder path
  - [ ] 3.1 Modify getS3EnvironmentSettings() method
    - Add 'folder_path' key to returned array
    - Check for AWS_FOLDER_PATH environment variable
    - Return boolean indicating if folder path is from environment
    - _Requirements: 3.1, 3.2_

  - [ ] 3.2 Update storeS3Configuration() method
    - Add 'aws_folder_path' to validation rules
    - Use regex pattern: `/^[a-zA-Z0-9\-_\/\.]+$/`
    - Make field nullable
    - Store folder_path in cloud_storage_settings table when not from environment
    - Trim slashes before storing
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ] 3.3 Update index() method
    - Pass folder_path value to view from config or database
    - Include folder_path in $s3Config array
    - _Requirements: 2.4_


- [ ] 4. Add helper methods to CloudStorageConfigHelper
  - [ ] 4.1 Create getS3FolderPath() static method
    - Check AWS_FOLDER_PATH environment variable first
    - Fall back to database CloudStorageSetting lookup
    - Trim slashes from returned value
    - Return empty string if not configured
    - _Requirements: 3.4, 6.1_

  - [ ] 4.2 Create generateExampleS3Key() static method
    - Accept folder path parameter
    - Generate example key with sample client email and filename
    - Return format: `{folder_path}/{client_email}/{filename}` or `{client_email}/{filename}`
    - Use for UI display purposes
    - _Requirements: 6.2, 6.3_

- [ ] 5. Update S3 configuration view with folder path field
  - [ ] 5.1 Add folder path form field
    - Add field after bucket name field in configuration form
    - Use x-label component with "Folder Path (Optional)" label
    - Set placeholder to "uploads/client-files"
    - Add pattern attribute: `[a-zA-Z0-9\-_\/\.]+`
    - Bind to Alpine.js formData.folder_path
    - _Requirements: 2.1, 2.4_

  - [ ] 5.2 Implement conditional read-only rendering
    - Check $s3EnvSettings['folder_path'] for environment configuration
    - Display read-only field with gray background when from environment
    - Display environment variable value in read-only field
    - Add helper text: "This value is configured via environment variables"
    - Display editable field when not from environment
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ] 5.3 Add dynamic example key display
    - Display helper text with example S3 key format
    - Use <code> tag with monospace styling for example
    - Bind example to Alpine.js exampleKey property
    - Update example dynamically as user types
    - Show format: "Files will be uploaded to: {example_key}"
    - _Requirements: 6.2, 6.3, 6.4_

  - [ ] 5.4 Add validation helper text
    - Add text: "Leave blank to upload to bucket root"
    - Add text: "Do not include leading or trailing slashes"
    - Display validation errors using x-input-error component
    - _Requirements: 5.5, 6.5_

- [ ] 6. Update Alpine.js configuration handler
  - [ ] 6.1 Add folder_path to formData
    - Initialize folder_path in formData object
    - Load value from old input or $s3Config
    - Default to empty string
    - _Requirements: 2.4, 2.5_

  - [ ] 6.2 Add exampleKey property
    - Initialize exampleKey property for dynamic display
    - Set initial value in init() method
    - _Requirements: 6.2, 6.3, 6.4_

  - [ ] 6.3 Create validateFolderPath() method
    - Check for empty value (valid)
    - Validate regex pattern for allowed characters
    - Check for consecutive slashes
    - Check for leading/trailing slashes
    - Set/clear errors.folder_path based on validation
    - Call updateExampleKey() after validation
    - Call validateForm() to update form validity
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 6.4 Create updateExampleKey() method
    - Get folder_path from formData
    - Trim whitespace
    - Generate example with format: `{folder_path}/{client_email}/{filename}`
    - Use sample values: "client@example.com" and "document_2024-01-15_abc123.pdf"
    - Handle empty folder path case
    - Update exampleKey property
    - _Requirements: 6.2, 6.3, 6.4_

  - [ ] 6.5 Update init() method
    - Load folder_path from environment if configured
    - Call updateExampleKey() during initialization
    - _Requirements: 3.3, 6.2_

  - [ ] 6.6 Update testConnection() method
    - Include folder_path in test configuration payload
    - Use environment value if present, otherwise form value
    - _Requirements: 7.1, 7.2_


- [ ] 7. Update .env.example with folder path configuration
  - Add AWS_FOLDER_PATH variable to .env.example
  - Add comment explaining folder path purpose
  - Provide example value
  - Place after AWS_BUCKET configuration
  - _Requirements: 1.1_

- [ ] 8. Update S3 connection test to verify folder path
  - Modify test connection logic to use configured folder path
  - Verify write permissions to folder path location
  - Include full S3 key in success message
  - Provide specific error messages for folder path issues
  - Clean up test files after connection test
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ]* 9. Add unit tests for folder path functionality
  - [ ]* 9.1 Test S3Provider getFolderPath() method
    - Test with configured folder path
    - Test with empty folder path
    - Test slash trimming
    - _Requirements: 1.4, 4.3_

  - [ ]* 9.2 Test S3Provider validateFolderPathFormat() method
    - Test valid folder paths
    - Test invalid characters
    - Test consecutive slashes
    - Test leading/trailing slashes
    - Test empty folder path (should be valid)
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [ ]* 9.3 Test S3Provider generateS3Key() with folder path
    - Test key generation without folder path
    - Test key generation with folder path
    - Verify single slash separator
    - Verify correct key format
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ]* 9.4 Test CloudStorageConfigHelper methods
    - Test getS3FolderPath() with environment variable
    - Test getS3FolderPath() with database value
    - Test getS3FolderPath() precedence (env over database)
    - Test generateExampleS3Key() with and without folder path
    - _Requirements: 3.4, 6.1, 6.2, 6.3_

- [ ]* 10. Add feature tests for folder path configuration
  - [ ]* 10.1 Test admin form folder path configuration
    - Test saving folder path via admin form
    - Test validation errors display correctly
    - Test folder path stored in database
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [ ]* 10.2 Test environment variable precedence
    - Test that AWS_FOLDER_PATH overrides database value
    - Test read-only field display when from environment
    - Test editable field when not from environment
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [ ]* 10.3 Test file uploads with folder path
    - Test upload generates correct S3 key with folder path
    - Test upload without folder path uses original format
    - Verify files are accessible at expected location
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 8.1, 8.2, 8.3_

- [ ] 11. Update documentation
  - Add AWS_FOLDER_PATH to environment configuration guide
  - Document folder path configuration in S3 setup guide
  - Add examples of common folder structures
  - Document environment variable precedence
  - Update S3Provider class documentation
  - _Requirements: All requirements (documentation support)_

