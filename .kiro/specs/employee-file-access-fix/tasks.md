# Implementation Plan

- [x] 1. Add service dependencies to employee controller
  - Copy the exact same service injections from admin controller constructor
  - Add FileManagerService, FilePreviewService, FileSecurityService, and AuditLogService dependencies
  - _Requirements: 1.1, 2.1, 2.2_

- [x] 2. Create employee access control helper method
  - Create `checkEmployeeAccess()` method equivalent to admin's `checkAdminAccess()`
  - Implement employee-specific file access validation
  - _Requirements: 1.4, 2.2_

- [x] 3. Copy admin error handling helper methods
  - Copy `handleSecurityViolation()`, `handleFileAccessException()`, and `handleGeneralException()` methods from admin controller
  - Adapt error messages for employee context where needed
  - _Requirements: 2.4, 4.2, 4.3_

- [x] 4. Replace employee download method with admin implementation
  - Remove existing Google Drive redirect code in employee download method
  - Copy the complete admin `download()` method implementation
  - Replace `checkAdminAccess()` call with `checkEmployeeAccess()`
  - _Requirements: 1.2, 2.1, 3.2_

- [x] 5. Replace employee preview method with admin implementation
  - Remove existing Google Drive redirect code in employee preview method
  - Copy the complete admin `preview()` method implementation
  - Ensure identical security validation and audit logging
  - _Requirements: 1.1, 2.1, 3.1_

- [x] 6. Write unit tests for employee file access methods
  - Copy admin controller test patterns for employee controller
  - Test download method with mocked services
  - Test preview method with mocked services
  - Test error handling scenarios
  - _Requirements: 1.1, 1.2, 2.4_

- [x] 7. Write integration tests for employee file serving
  - Test complete employee file download flow
  - Test complete employee file preview flow
  - Verify audit logging is working correctly
  - Test access control enforcement
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 8. Add security validation tests
  - Test that employees cannot access files outside their scope
  - Test security violation logging for unsafe file types
  - Test authentication requirements
  - Verify consistent security behavior with admin controller
  - _Requirements: 1.4, 2.2, 3.3, 3.4_

- [x] 9. Update employee controller routes if needed
  - Verify existing routes still work with new method signatures
  - Ensure route model binding is properly configured
  - Test URL generation in frontend components
  - _Requirements: 1.1, 1.2_

- [x] 10. Test frontend integration
  - Verify employee preview modal works with new backend endpoints
  - Test download functionality from employee interface
  - Ensure error messages display correctly in employee UI
  - Verify no more direct Google Drive URLs appear in browser network tab
  - _Requirements: 4.1, 4.2, 4.3_
