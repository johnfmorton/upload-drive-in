# Implementation Plan

- [x] 1. Create database migration for welcome message dismissal field
  - Create migration file to add `welcome_message_dismissed` boolean field to users table
  - Set default value to `false`
  - Position field after `preferred_cloud_provider`
  - _Requirements: 1.4, 3.1, 3.2_

- [x] 2. Update User model for welcome message dismissal
  - Add `welcome_message_dismissed` to `$fillable` array
  - Add `welcome_message_dismissed` to `$casts` array with 'boolean' type
  - _Requirements: 3.1, 3.2_

- [x] 3. Implement controller logic for welcome message display and dismissal
- [x] 3.1 Refactor checkFirstTimeLogin to shouldShowWelcomeMessage
  - Remove time-based and session-based logic from `checkFirstTimeLogin()` method
  - Create new `shouldShowWelcomeMessage()` method that checks user's `welcome_message_dismissed` field
  - Return `false` for non-admin users
  - Return `true` for admin users where `welcome_message_dismissed` is `false` or `null`
  - Update `index()` method to use new method and pass `$showWelcomeMessage` to view
  - _Requirements: 1.1, 1.2, 1.3, 3.2_

- [x] 3.2 Create dismissWelcomeMessage controller method
  - Implement `dismissWelcomeMessage()` method in DashboardController
  - Verify user is authenticated and is admin (return 403 if not)
  - Update user's `welcome_message_dismissed` field to `true`
  - Log the dismissal action with user details
  - Return JSON response with success/failure status
  - Implement proper error handling and logging
  - _Requirements: 2.2, 2.3, 3.3, 4.1, 4.2, 4.3, 4.4_

- [x] 4. Add route for welcome message dismissal
  - Add POST route `/admin/dismiss-welcome` in admin routes group
  - Map route to `DashboardController::dismissWelcomeMessage`
  - Ensure route is protected by `auth` and `admin` middleware
  - Name route as `admin.dismiss-welcome`
  - _Requirements: 2.2, 4.1, 4.2_

- [x] 5. Update dashboard view with dismissible welcome message
- [x] 5.1 Update welcome message conditional logic
  - Change `@if ($isFirstTimeLogin)` to `@if ($showWelcomeMessage)`
  - Add Alpine.js `x-data` directive with `welcomeMessageHandler()` function
  - Add `x-show` directive bound to `dismissed` state
  - Add `x-transition` directives for smooth fade-out animation
  - Add `relative` class to container for absolute positioning of dismiss button
  - _Requirements: 1.1, 2.5_

- [x] 5.2 Implement dismiss button UI
  - Add dismiss button in top-right corner with absolute positioning
  - Style button with X icon using SVG
  - Add hover and focus states for accessibility
  - Bind click event to `dismissMessage()` Alpine.js method
  - Add `:disabled` binding to `isProcessing` state
  - Include `aria-label` and `title` attributes for accessibility
  - _Requirements: 2.1, 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 5.3 Create Alpine.js welcomeMessageHandler component
  - Create `welcomeMessageHandler()` function with `dismissed` and `isProcessing` state
  - Implement `dismissMessage()` async method
  - Make AJAX POST request to `/admin/dismiss-welcome` with CSRF token
  - Handle success response by setting `dismissed = true` to trigger animation
  - Handle error responses with user-friendly alert messages
  - Add console logging for debugging
  - Prevent double-clicks by checking `isProcessing` state
  - _Requirements: 2.2, 2.4, 2.5, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 6. Run migration and verify database changes
  - Execute migration using `ddev artisan migrate`
  - Verify `welcome_message_dismissed` field exists in users table
  - Verify default value is `false` for existing users
  - _Requirements: 3.1_

- [ ] 7. Manual testing and verification
  - Test welcome message displays on admin dashboard for users with `welcome_message_dismissed = false`
  - Test dismiss button is visible and clickable
  - Test clicking dismiss button hides message with animation
  - Test message stays hidden after page refresh
  - Test message stays hidden after logout and login
  - Test non-admin users don't see the welcome message
  - Test error handling when network request fails
  - Test keyboard accessibility of dismiss button
  - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 2.4, 2.5, 3.3, 5.4_
