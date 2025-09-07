# Implementation Plan

- [x] 1. Create base verification mail infrastructure
  - Create abstract BaseVerificationMail class with shared functionality
  - Define common properties and methods for all verification emails
  - Implement template and subject abstract methods
  - _Requirements: 4.1, 4.2, 5.1, 5.2_

- [x] 2. Create role-specific Mail classes
  - [x] 2.1 Create AdminVerificationMail class
    - Extend BaseVerificationMail with admin-specific template and subject
    - Implement getTemplate() to return admin verification template path
    - Implement getSubject() to return admin-specific subject line
    - _Requirements: 1.1, 1.4_

  - [x] 2.2 Create EmployeeVerificationMail class
    - Extend BaseVerificationMail with employee-specific template and subject
    - Implement getTemplate() to return employee verification template path
    - Implement getSubject() to return employee-specific subject line
    - _Requirements: 2.1, 2.5_

  - [x] 2.3 Create ClientVerificationMail class
    - Extend BaseVerificationMail with client-specific template and subject
    - Implement getTemplate() to return client verification template path
    - Implement getSubject() to return client-specific subject line
    - _Requirements: 3.1, 3.4_

- [x] 3. Create email template files
  - [x] 3.1 Create admin verification template
    - Create resources/views/emails/verification/admin-verification.blade.php
    - Use Laravel mail components for consistent styling
    - Include admin-specific messaging about system management capabilities
    - _Requirements: 1.2, 1.3, 5.1, 5.3_

  - [x] 3.2 Create employee verification template
    - Create resources/views/emails/verification/employee-verification.blade.php
    - Use Laravel mail components for consistent styling
    - Include employee-specific messaging about client file management
    - _Requirements: 2.2, 2.3, 5.1, 5.3_

  - [x] 3.3 Create client verification template
    - Create resources/views/emails/verification/client-verification.blade.php
    - Use Laravel mail components for consistent styling
    - Include client-specific messaging about file upload process
    - _Requirements: 3.2, 3.3, 5.1, 5.3_

- [x] 4. Add role-specific language keys
  - [x] Add admin verification language keys to messages.php
  - [x] Add employee verification language keys to messages.php
  - [x] Add client verification language keys to messages.php
  - [x] Ensure consistent naming convention across all role keys
  - [x] Add translations for German, Spanish, and French language files
  - _Requirements: 6.1, 6.2, 6.3, 6.5_

- [x] 5. Create VerificationMailFactory service
  - Create factory class with role detection logic
  - Implement createForUser() method for existing user contexts
  - Implement createForContext() method for string-based role selection
  - Add logging for template selection debugging
  - _Requirements: 4.1, 4.2, 4.4_

- [x] 6. Update PublicUploadController to use role-based emails
  - Modify validateEmail() method to use VerificationMailFactory
  - Add role detection logic for public upload context
  - Implement fallback to client template for unknown contexts
  - Add logging for email template selection
  - _Requirements: 4.3, 4.4_

- [x] 7. Update admin user creation flows
  - Identify admin user creation endpoints that send verification emails
  - Update controllers to use AdminVerificationMail for admin users
  - Ensure proper role context is passed to mail factory
  - _Requirements: 1.1, 4.1_

- [x] 8. Update employee user creation flows
  - Identify employee user creation endpoints that send verification emails
  - Update controllers to use EmployeeVerificationMail for employee users
  - Ensure proper role context is passed to mail factory
  - _Requirements: 2.1, 4.1_

- [x] 9. Create unit tests for Mail classes
  - [x] 9.1 Test AdminVerificationMail
    - Test template path is correct
    - Test subject line is correct
    - Test mail can be instantiated and rendered
    - _Requirements: 1.1, 1.4_

  - [x] 9.2 Test EmployeeVerificationMail
    - Test template path is correct
    - Test subject line is correct
    - Test mail can be instantiated and rendered
    - _Requirements: 2.1, 2.5_

  - [x] 9.3 Test ClientVerificationMail
    - Test template path is correct
    - Test subject line is correct
    - Test mail can be instantiated and rendered
    - _Requirements: 3.1, 3.4_

- [x] 10. Create unit tests for VerificationMailFactory
  - Test createForUser() returns correct Mail class for admin users
  - Test createForUser() returns correct Mail class for employee users
  - Test createForUser() returns correct Mail class for client users
  - Test createForUser() returns client mail for null user (fallback)
  - Test createForContext() returns correct Mail class for each role string
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 11. Create integration tests for email sending
  - Test admin verification email is sent with correct template
  - Test employee verification email is sent with correct template
  - Test client verification email is sent with correct template
  - Test fallback behavior when role detection fails
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 12. Create feature tests for complete verification flows
  - Test admin user email verification end-to-end flow
  - Test employee user email verification end-to-end flow
  - Test client user email verification end-to-end flow
  - Test public upload email verification uses client template
  - _Requirements: 1.1, 2.1, 3.1, 4.3_

- [x] 13. Update existing tests to work with new system
  - Update tests that expect LoginVerificationMail to work with new factory
  - Update tests that check email content to expect role-specific content
  - Ensure backward compatibility tests still pass
  - _Requirements: 4.3_

- [ ] 14. Clean up unused components
  - Remove unused EmailVerificationMail class
  - Remove duplicate email-verification.blade.php template
  - Update any remaining references to old email classes
  - _Requirements: 5.1_

- [ ] 15. Update email verification notification controller
  - Update EmailVerificationNotificationController to use VerificationMailFactory
  - Add role-based template selection for resend verification emails
  - Add structured logging for template selection and email sending
  - _Requirements: 4.1, 4.2, 4.4_

- [ ] 16. Update user creation controllers to use proper translation keys
  - Update EmployeeController to use proper translation keys instead of hardcoded strings
  - Add employee creation message translations to all language files (en, de, es, fr)
  - Add role-based email verification translations to all language files
  - Add validation message translations to all language files
  - Ensure AdminUserController and ClientManagementController use existing translation keys
  - _Requirements: 6.1, 6.2, 6.3, 6.5_

- [ ] 16. Add logging and monitoring
  - Add structured logging for email template selection
  - Add error logging for role detection failures
  - Add metrics for email verification success rates by role
  - _Requirements: 4.4_
