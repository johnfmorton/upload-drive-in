# Requirements Document

## Introduction

This feature enhances the admin dashboard welcome message by changing its display logic and adding a permanent dismissal mechanism. Currently, the welcome message only appears within 5 minutes of setup completion and uses session-based tracking, which is unreliable. The new implementation will show the message on every admin dashboard visit until the admin explicitly dismisses it forever.

## Glossary

- **Admin Dashboard**: The main dashboard page accessible to administrator users after login
- **Welcome Message**: The blue informational banner that displays "🎉 Welcome to Upload Drive-in!" with setup instructions
- **Dismissal Mechanism**: A user interface control that allows permanent hiding of the welcome message
- **User Preferences**: Database-stored settings specific to individual users
- **Setup Completion**: The state when the initial application setup wizard has been completed

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to see the welcome message every time I visit the dashboard, so that I have easy access to important setup instructions until I'm ready to dismiss it.

#### Acceptance Criteria

1. WHEN the administrator visits the admin dashboard, THE System SHALL display the welcome message if it has not been permanently dismissed
2. THE System SHALL display the welcome message regardless of how much time has passed since setup completion
3. THE System SHALL NOT use session-based tracking to determine welcome message visibility
4. THE System SHALL store the dismissal preference in the database for persistence across sessions

### Requirement 2

**User Story:** As an administrator, I want to permanently dismiss the welcome message when I no longer need it, so that my dashboard is cleaner and more focused on my daily tasks.

#### Acceptance Criteria

1. THE System SHALL display a "Dismiss this message" button in the top-right corner of the welcome message
2. WHEN the administrator clicks the dismiss button, THE System SHALL permanently hide the welcome message
3. THE System SHALL store the dismissal preference in the users table
4. WHEN the administrator dismisses the message, THE System SHALL update the database without requiring a page refresh
5. THE System SHALL provide visual feedback when the dismissal action is successful

### Requirement 3

**User Story:** As an administrator, I want the dismissal to be permanent and user-specific, so that the message doesn't reappear after I've dismissed it, even if I log out and log back in.

#### Acceptance Criteria

1. THE System SHALL store the dismissal preference as a boolean field in the users table
2. WHEN checking whether to display the welcome message, THE System SHALL query the user's dismissal preference from the database
3. THE System SHALL maintain the dismissal preference across all user sessions
4. THE System SHALL NOT display the welcome message to users who have dismissed it, even after application restarts

### Requirement 4

**User Story:** As a developer, I want the dismissal mechanism to use proper AJAX patterns with CSRF protection, so that the feature is secure and follows Laravel best practices.

#### Acceptance Criteria

1. THE System SHALL implement the dismissal action as an AJAX POST request
2. THE System SHALL include CSRF token validation for the dismissal request
3. THE System SHALL return a JSON response indicating success or failure
4. WHEN the dismissal request fails, THE System SHALL display an error message to the user
5. THE System SHALL use Alpine.js for client-side state management and interactions

### Requirement 5

**User Story:** As an administrator, I want the dismiss button to be visually clear and accessible, so that I can easily find and use it when I'm ready to hide the message.

#### Acceptance Criteria

1. THE System SHALL position the dismiss button in the top-right corner of the welcome message container
2. THE System SHALL style the dismiss button with an "X" icon or clear dismiss indicator
3. THE System SHALL apply hover effects to the dismiss button for better user feedback
4. THE System SHALL ensure the dismiss button is keyboard accessible
5. THE System SHALL maintain proper z-index layering so the button is always clickable
