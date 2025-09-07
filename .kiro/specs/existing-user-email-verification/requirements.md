# Requirements Document

## Introduction

This feature ensures that existing users can still access the system through the public email verification form even when security settings restrict public registration or limit allowed domains. When public registration is disabled or domain restrictions are in place, existing users with accounts in the database should still be able to use the email form to receive verification emails and log into their accounts.

## Requirements

### Requirement 1

**User Story:** As an existing user, I want to be able to use the public email form to log in even when public registration is disabled, so that I can access my account without needing a separate login interface.

#### Acceptance Criteria

1. WHEN public registration is disabled AND I am an existing user THEN I SHALL still be able to submit my email through the public form
2. WHEN I submit my email AND public registration is disabled THEN the system SHALL check if I am an existing user
3. WHEN I am found to be an existing user THEN the system SHALL send me a verification email regardless of the public registration setting
4. WHEN I am not an existing user AND public registration is disabled THEN the system SHALL reject my email with an appropriate error message
5. WHEN the verification email is sent THEN it SHALL use the appropriate role-based template for my user type

### Requirement 2

**User Story:** As an existing user with an email domain not on the whitelist, I want to still be able to log in through the email form, so that domain restrictions don't prevent me from accessing my existing account.

#### Acceptance Criteria

1. WHEN domain restrictions are in whitelist mode AND my email domain is not whitelisted AND I am an existing user THEN I SHALL still be able to submit my email
2. WHEN I submit my email AND my domain is not whitelisted THEN the system SHALL check if I am an existing user before applying domain restrictions
3. WHEN I am found to be an existing user THEN the system SHALL bypass domain restrictions and send me a verification email
4. WHEN I am not an existing user AND my domain is not whitelisted THEN the system SHALL reject my email with a domain restriction error
5. WHEN the verification email is sent THEN it SHALL use the appropriate role-based template for my user type

### Requirement 3

**User Story:** As an existing admin user, I want to receive an admin-specific verification email when I use the public form during restricted registration periods, so that I can access my administrative dashboard.

#### Acceptance Criteria

1. WHEN I am an existing admin user AND I use the public email form THEN the system SHALL send me an admin verification email
2. WHEN the admin verification email is sent THEN it SHALL use the admin-specific template with administrative messaging
3. WHEN I click the verification link THEN I SHALL be logged in and redirected to the admin dashboard
4. WHEN the verification process completes THEN the system SHALL log the successful admin verification

### Requirement 4

**User Story:** As an existing employee user, I want to receive an employee-specific verification email when I use the public form during restricted registration periods, so that I can access my employee dashboard.

#### Acceptance Criteria

1. WHEN I am an existing employee user AND I use the public email form THEN the system SHALL send me an employee verification email
2. WHEN the employee verification email is sent THEN it SHALL use the employee-specific template with file management messaging
3. WHEN I click the verification link THEN I SHALL be logged in and redirected to my employee dashboard
4. WHEN the verification process completes THEN the system SHALL log the successful employee verification

### Requirement 5

**User Story:** As an existing client user, I want to receive a client-specific verification email when I use the public form during restricted registration periods, so that I can access the file upload interface.

#### Acceptance Criteria

1. WHEN I am an existing client user AND I use the public email form THEN the system SHALL send me a client verification email
2. WHEN the client verification email is sent THEN it SHALL use the client-specific template with upload instructions
3. WHEN I click the verification link THEN I SHALL be logged in and redirected to the client upload interface
4. WHEN the verification process completes THEN the system SHALL log the successful client verification

### Requirement 6

**User Story:** As a system administrator, I want the security restrictions to apply only to new user registration while allowing existing users to always access their accounts, so that security policies don't lock out legitimate users.

#### Acceptance Criteria

1. WHEN security restrictions are in place THEN existing users SHALL always be able to use the email verification form
2. WHEN an email is submitted THEN the system SHALL first check for existing users before applying registration restrictions
3. WHEN an existing user is found THEN all registration restrictions SHALL be bypassed for that user
4. WHEN no existing user is found THEN normal registration restrictions SHALL apply
5. WHEN the system processes the request THEN it SHALL log whether restrictions were bypassed due to existing user status