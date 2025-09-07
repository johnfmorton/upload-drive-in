# Implementation Plan

- [x] 1. Refactor PublicUploadController email validation flow
  - Modify validateEmail() method to check for existing users before applying restrictions
  - Move existing user detection to the beginning of the validation process
  - Separate logic for existing users vs new users into distinct code paths
  - _Requirements: 1.2, 2.2, 6.2_

- [x] 2. Create helper methods for user type handling
- [x] 2.1 Create sendVerificationEmailToExistingUser() method
  - Handle verification email sending for existing users
  - Bypass all registration restrictions for existing users
  - Add logging for existing user verification attempts
  - Store intended URL in session if provided
  - _Requirements: 1.1, 1.3, 3.1, 4.1, 5.1_

- [x] 2.2 Create handleNewUserRegistration() method
  - Apply existing security checks (public registration, domain restrictions)
  - Handle new user verification email sending
  - Add logging for new user registration attempts and restrictions
  - Store intended URL in session if provided
  - _Requirements: 6.4, 6.5_

- [x] 2.3 Refactor createVerificationAndSendEmail() method
  - Extract common verification email creation and sending logic
  - Handle both existing and new user scenarios
  - Maintain existing role-based email template selection
  - _Requirements: 3.1, 4.1, 5.1_

- [x] 3. Enhance logging for existing user detection
  - Add structured logging when existing users bypass restrictions
  - Log user ID, role, and which restrictions were bypassed
  - Add context about public registration and domain restriction settings
  - Log new user restriction enforcement for comparison
  - _Requirements: 1.4, 2.3, 3.4, 4.4, 5.4, 6.5_

- [ ] 4. Add error handling for database lookup failures
  - Wrap existing user detection in try-catch blocks
  - Implement fallback behavior when user lookup fails
  - Log database errors without exposing sensitive information
  - Ensure graceful degradation maintains security posture
  - _Requirements: 6.2, 6.3_

- [ ] 5. Update language files with enhanced error messages
  - Add new language keys for existing vs new user error messages
  - Update public registration disabled message to mention existing users
  - Update domain restriction message to mention existing users
  - Add success messages that distinguish existing vs new users
  - _Requirements: 1.4, 2.4_

- [ ] 6. Create unit tests for existing user bypass logic
- [ ] 6.1 Test existing admin user bypasses public registration restrictions
  - Create admin user and disable public registration
  - Verify admin receives verification email despite restrictions
  - Verify admin-specific email template is used
  - Verify appropriate logging occurs
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 3.1, 3.2, 3.3, 3.4_

- [ ] 6.2 Test existing employee user bypasses domain restrictions
  - Create employee user with non-whitelisted domain
  - Set up domain whitelist that excludes employee's domain
  - Verify employee receives verification email despite domain restrictions
  - Verify employee-specific email template is used
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.2, 4.3, 4.4_

- [ ] 6.3 Test existing client user bypasses all restrictions
  - Create client user with restricted domain and disabled public registration
  - Verify client receives verification email despite all restrictions
  - Verify client-specific email template is used
  - Verify appropriate logging occurs
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [ ] 6.4 Test new users are still blocked by restrictions
  - Test new user blocked when public registration is disabled
  - Test new user blocked when domain is not whitelisted
  - Verify no verification emails are sent to blocked new users
  - Verify appropriate error messages are returned
  - _Requirements: 6.4_

- [ ] 6.5 Test database error handling
  - Mock database connection failures during user lookup
  - Verify graceful fallback behavior
  - Verify appropriate error logging
  - Verify security posture is maintained during errors
  - _Requirements: 6.2, 6.3_

- [ ] 7. Create integration tests for complete verification flows
- [ ] 7.1 Test existing admin complete verification flow
  - Test admin email submission with restrictions in place
  - Verify admin verification email is sent
  - Test clicking verification link redirects to admin dashboard
  - Verify admin is logged in after verification
  - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3_

- [ ] 7.2 Test existing employee complete verification flow
  - Test employee email submission with domain restrictions
  - Verify employee verification email is sent
  - Test clicking verification link redirects to employee dashboard
  - Verify employee is logged in after verification
  - _Requirements: 2.1, 2.2, 2.3, 4.1, 4.2, 4.3_

- [ ] 7.3 Test existing client complete verification flow
  - Test client email submission with all restrictions enabled
  - Verify client verification email is sent
  - Test clicking verification link redirects to client upload interface
  - Verify client is logged in after verification
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 8. Create feature tests for edge cases
- [ ] 8.1 Test intended URL handling for existing users
  - Test existing user with intended URL parameter
  - Verify intended URL is stored and used after verification
  - Test both employee upload URLs and general intended URLs
  - _Requirements: 1.3, 2.3, 5.3_

- [ ] 8.2 Test multiple verification attempts by existing users
  - Test existing user requesting multiple verification emails
  - Verify each attempt bypasses restrictions consistently
  - Verify email template selection remains consistent
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 8.3 Test mixed scenarios with domain rules
  - Test existing user with whitelisted domain when public registration disabled
  - Test existing user with blacklisted domain in blacklist mode
  - Verify existing users always bypass regardless of domain rule configuration
  - _Requirements: 2.1, 2.2, 6.1, 6.2_

- [ ] 9. Update existing tests to work with new flow
  - Review existing PublicUploadController tests
  - Update tests that expect specific error behaviors
  - Ensure backward compatibility tests still pass
  - Update any tests that mock user lookup behavior
  - _Requirements: 6.4_

- [ ] 10. Add monitoring and metrics
  - Add metrics for existing user bypass events
  - Add metrics for restriction enforcement on new users
  - Create dashboard queries to monitor bypass patterns
  - Add alerting for unusual bypass patterns
  - _Requirements: 6.5_

- [ ] 11. Update documentation
  - Update API documentation to reflect new behavior
  - Document the existing user bypass logic
  - Add troubleshooting guide for restriction issues
  - Update security documentation to explain bypass rationale
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 12. Performance optimization
  - Optimize user lookup query with proper indexing
  - Consider caching domain rules to reduce database queries
  - Add query performance monitoring for user detection
  - _Requirements: 6.2, 6.3_
