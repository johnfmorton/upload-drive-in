# Implementation Plan

- [x] 1. Update session configuration with HTTPS auto-detection
  - Modify `config/session.php` to add HTTPS detection logic for the `secure` setting
  - Implement fallback logic that checks `SESSION_SECURE_COOKIE` env var first, then auto-detects from `APP_URL`
  - Ensure the detection handles edge cases (empty URL, malformed URL, mixed case)
  - Verify all cookie-related settings have proper defaults (same_site, http_only, partitioned)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 4.1, 4.2, 4.3, 4.4_

- [x] 2. Update .env.example with comprehensive session cookie documentation
  - Add `SESSION_SECURE_COOKIE` setting with explanation of when to use it
  - Add `SESSION_SAME_SITE` setting with available options and recommendations
  - Add `SESSION_HTTP_ONLY` setting with security explanation
  - Add `SESSION_PARTITIONED_COOKIE` setting with use case documentation
  - Include comments explaining the relationship between APP_URL and SESSION_SECURE_COOKIE
  - Document the auto-detection behavior
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Update current .env file with proper session cookie settings
  - Add `SESSION_SECURE_COOKIE=true` to the existing `.env` file since it uses HTTPS
  - Add `SESSION_SAME_SITE=lax` for CSRF protection
  - Add `SESSION_HTTP_ONLY=true` for XSS protection
  - Verify the configuration resolves the 419 error
  - Create documentation for the options for `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, and `SESSION_HTTP_ONLY` which detail the available options and why to choose one over the other.
  - _Requirements: 1.3, 1.4, 1.5, 2.1, 2.2, 2.3_

- [x] 4. Enhance setup wizard to generate session cookie configuration
  - Locate the setup wizard's environment file generation code
  - Add logic to detect HTTPS from the provided `APP_URL`
  - Generate appropriate `SESSION_SECURE_COOKIE` value based on protocol detection
  - Include all session cookie settings in the generated `.env` file
  - Add comments to the generated file explaining the settings
  - _Requirements: 2.3, 3.5, 4.1, 4.2, 4.3_

- [x] 5. Add configuration validation and logging
  - Create validation logic to detect session configuration mismatches
  - Warn if HTTPS is used without secure cookies enabled
  - Warn if secure cookies are enabled without HTTPS
  - Add debug logging for session configuration in development mode
  - Log session driver, secure flag, same_site, and http_only settings on boot
  - _Requirements: 2.4, 4.1, 4.2, 4.3_

- [ ]* 6. Add unit tests for HTTPS detection logic
  - Test HTTPS URL detection returns true for secure cookie setting
  - Test HTTP URL detection returns false for secure cookie setting
  - Test empty APP_URL defaults to false
  - Test malformed APP_URL defaults to false
  - Test explicit SESSION_SECURE_COOKIE=true overrides auto-detection
  - Test explicit SESSION_SECURE_COOKIE=false overrides auto-detection
  - _Requirements: 4.1, 4.2, 4.3_

- [ ]* 7. Add integration tests for session cookie behavior
  - Test that session cookies include Secure attribute when using HTTPS
  - Test that session cookies work correctly with HTTP
  - Test SameSite attribute is set to 'lax' by default
  - Test HttpOnly attribute is set to true
  - Test CSRF token validation works with secure cookies
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 5.1, 5.2, 5.3_

- [ ]* 8. Add feature tests for login flow
  - Test successful login with HTTPS configuration
  - Test CSRF token is present in login form
  - Test CSRF token validation succeeds on form submission
  - Test session persists after successful login
  - Test no 419 errors occur during normal login flow
  - Test login works consistently across multiple attempts
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 9. Update deployment and troubleshooting documentation
  - Add session cookie configuration section to deployment documentation
  - Document the HTTPS auto-detection behavior
  - Create troubleshooting guide for 419 CSRF errors
  - Document how to verify session cookie attributes in browser DevTools
  - Add guidance for different deployment environments (DDEV, production, staging)
  - _Requirements: 2.4, 3.4_
