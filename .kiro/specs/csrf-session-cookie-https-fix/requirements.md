# Requirements Document

## Introduction

Users intermittently encounter a "419 Page Expired" error when attempting to log in through the `/login` page. This error occurs inconsistently, suggesting a session or CSRF token management issue. The application is configured to use HTTPS (`APP_URL=https://upload-drive-in.ddev.site`) but lacks proper session cookie security configuration for HTTPS environments, which can cause browsers to reject session cookies and invalidate CSRF tokens.

## Glossary

- **CSRF Token**: Cross-Site Request Forgery token used to validate form submissions
- **Session Cookie**: HTTP cookie used to maintain user session state
- **HTTPS**: Secure HTTP protocol using TLS/SSL encryption
- **SameSite**: Cookie attribute controlling cross-site request behavior
- **Secure Cookie**: Cookie that is only transmitted over HTTPS connections
- **Session Driver**: Laravel's mechanism for storing session data (file, database, redis, etc.)

## Requirements

### Requirement 1

**User Story:** As a user, I want to log in reliably every time so that I can access the application without encountering "419 Page Expired" errors.

#### Acceptance Criteria

1. WHEN a user visits the login page THEN the Application SHALL generate a valid CSRF token
2. WHEN a user submits the login form THEN the Application SHALL validate the CSRF token successfully
3. WHEN the application uses HTTPS THEN the Application SHALL configure session cookies with the `secure` flag set to true
4. WHEN a user attempts to log in THEN the Application SHALL accept the session cookie and CSRF token without intermittent failures
5. WHEN session cookies are set THEN the Browser SHALL accept and store them correctly for HTTPS connections

### Requirement 2

**User Story:** As a system administrator, I want session cookies to be properly configured for HTTPS so that the application works reliably in secure environments.

#### Acceptance Criteria

1. WHEN `APP_URL` contains `https://` THEN the Application SHALL automatically set `SESSION_SECURE_COOKIE=true`
2. WHEN session cookies are created THEN the Application SHALL include the `Secure` attribute for HTTPS connections
3. WHEN the application runs in local development with HTTPS THEN the Application SHALL configure cookies appropriately for the DDEV environment
4. WHEN session configuration is missing HTTPS-specific settings THEN the Application SHALL provide sensible defaults based on the `APP_URL` protocol

### Requirement 3

**User Story:** As a developer, I want clear session cookie configuration in the environment file so that I can understand and troubleshoot session-related issues.

#### Acceptance Criteria

1. WHEN reviewing the `.env.example` file THEN it SHALL include `SESSION_SECURE_COOKIE` with appropriate documentation
2. WHEN reviewing the `.env.example` file THEN it SHALL include `SESSION_SAME_SITE` with appropriate documentation
3. WHEN reviewing the `.env.example` file THEN it SHALL include `SESSION_HTTP_ONLY` with appropriate documentation
4. WHEN reviewing the `.env.example` file THEN it SHALL include comments explaining when to use each setting
5. WHEN the setup wizard generates the `.env` file THEN it SHALL include appropriate session cookie settings based on the `APP_URL` protocol

### Requirement 4

**User Story:** As a system administrator, I want the session configuration to automatically detect HTTPS usage so that I don't need to manually configure multiple related settings.

#### Acceptance Criteria

1. WHEN `config/session.php` is loaded THEN it SHALL detect HTTPS from `APP_URL` if `SESSION_SECURE_COOKIE` is not explicitly set
2. WHEN the application uses HTTPS THEN the Session Configuration SHALL default `SESSION_SECURE_COOKIE` to true
3. WHEN the application uses HTTP THEN the Session Configuration SHALL default `SESSION_SECURE_COOKIE` to false
4. WHEN `SESSION_SAME_SITE` is not set THEN the Session Configuration SHALL default to 'lax' for CSRF protection

### Requirement 5

**User Story:** As a user, I want my session to persist correctly across page loads so that I don't get logged out unexpectedly or encounter CSRF errors.

#### Acceptance Criteria

1. WHEN a session is created THEN the Application SHALL store it using the configured session driver
2. WHEN a user navigates between pages THEN the Application SHALL maintain the same session identifier
3. WHEN a CSRF token is generated THEN it SHALL remain valid for the duration of the session
4. WHEN the session driver is set to 'file' THEN the Application SHALL store sessions in the filesystem with proper permissions
5. WHEN the session driver is set to 'database' THEN the Application SHALL store sessions in the database with proper indexing
