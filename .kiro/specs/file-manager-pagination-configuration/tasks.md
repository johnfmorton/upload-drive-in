# Implementation Plan

- [x] 1. Create file manager configuration file
  - Create `config/file-manager.php` with pagination settings
  - Define default, minimum, and maximum items per page values
  - Include environment variable binding for FILE_MANAGER_ITEMS_PER_PAGE
  - _Requirements: 1.2, 1.3, 2.3_

- [x] 2. Update environment configuration documentation
  - Add FILE_MANAGER_ITEMS_PER_PAGE to `.env.example` with documentation
  - Include comments explaining the purpose and valid range
  - Set example value to demonstrate usage
  - _Requirements: 2.1_

- [x] 3. Update Admin FileManagerController pagination logic
  - Replace hardcoded perPage value (15) with configuration-based value
  - Implement validation for per_page request parameter
  - Add min/max boundary enforcement using configuration values
  - Maintain backward compatibility with existing per_page parameter
  - _Requirements: 1.1, 1.4, 3.1_

- [x] 4. Update Employee FileManagerController pagination logic
  - Replace hardcoded paginate(20) with configuration-based pagination
  - Apply same pagination configuration logic as admin controller
  - Ensure consistent behavior between admin and employee interfaces
  - _Requirements: 3.1, 3.2, 3.4_

- [x] 5. Update other controllers with hardcoded pagination values
  - Update Employee/DashboardController.php (paginate(10))
  - Update Employee/ClientManagementController.php (paginate(15))
  - Update Admin/EmployeeController.php (paginate(15))
  - Update Admin/AdminUserController.php (paginate(15) and paginate(10))
  - Update Admin/DashboardController.php (paginate(10))
  - Update FileUploadController.php (paginate(10))
  - _Requirements: 3.1, 3.4_

- [x] 6. Add configuration validation and logging
  - Implement startup logging for pagination configuration
  - Add validation helper for pagination values
  - Log configuration source (environment vs default)
  - _Requirements: 2.4_

- [x] 7. Write unit tests for configuration validation
  - Test default value behavior when no environment variable is set
  - Test environment variable override functionality
  - Test boundary validation (min/max enforcement)
  - Test invalid value fallback behavior
  - _Requirements: 1.4, 2.2, 2.3_

- [x] 8. Write integration tests for pagination behavior
  - Test admin file manager pagination with custom configuration
  - Test employee file manager pagination consistency
  - Test pagination UI rendering with different page sizes
  - Verify page navigation works correctly with new configuration
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 9. Update existing pagination tests
  - Update FileManagerPerformanceTest.php hardcoded value (line 229: paginate(20))
  - Review and update existing file manager tests that assume specific items per page
  - Ensure tests work with configurable pagination values
  - Add test cases for edge cases (very small/large page sizes)
  - _Requirements: 1.1, 3.3_
