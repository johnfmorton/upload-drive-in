# Implementation Plan

- [x] 1. Enhance User model with primary contact methods
  - Add `primaryContactClients()` method to get clients where user is primary contact
  - Add `isPrimaryContactFor(User $client)` method to check primary contact status
  - Write unit tests for new model methods
  - _Requirements: 3.1, 3.2_

- [x] 2. Create primary contact confirmation modal component
  - Create `resources/views/components/primary-contact-confirmation-modal.blade.php`
  - Implement Alpine.js functionality for modal state management
  - Add confirmation dialog with clear explanation of primary contact impact
  - Include proper z-index layering following modal development standards
  - _Requirements: 2.2_

- [x] 3. Create dashboard primary contact statistics component
  - Create `resources/views/components/dashboard/primary-contact-stats.blade.php`
  - Display count of clients where user is primary contact
  - Add link to filtered client list showing only primary contact clients
  - Include proper styling and responsive design
  - _Requirements: 3.1_

- [x] 4. Enhance admin user show view with improved primary contact UI
  - Update `resources/views/admin/users/show.blade.php` team access tab
  - Add explanatory section about primary contact functionality
  - Enhance current primary contact display with visual prominence
  - Improve team member selection layout with better visual indicators
  - Integrate confirmation modal for primary contact changes
  - _Requirements: 1.1, 1.2, 1.3, 2.1_

- [x] 5. Enhance AdminUserController validation and error handling
  - Update `updateTeamAssignments()` method with enhanced validation rules
  - Add custom validation messages for better user experience
  - Ensure primary contact is required and must be among selected team members
  - Implement proper error handling with user-friendly messages
  - _Requirements: 2.4, 4.1, 4.2, 4.3_

- [x] 6. Add client list filtering for primary contact status
  - Update admin users index view to support primary contact filtering
  - Add filter parameter handling in AdminUserController
  - Update employee clients index view with same filtering capability
  - Ensure filtered results show clear indication of primary contact status
  - _Requirements: 3.3_

- [x] 7. Integrate primary contact stats into user dashboards
  - Update admin dashboard to include primary contact statistics component
  - Update employee dashboard to include primary contact statistics component
  - Ensure statistics are accurate and performant
  - Add proper responsive design for different screen sizes
  - _Requirements: 3.1, 3.2_

- [x] 8. Add database indexes for performance optimization
  - Create migration to add index on `client_user_relationships(client_user_id, is_primary)`
  - Create migration to add index on `client_user_relationships(company_user_id, is_primary)`
  - Test query performance improvements with indexes
  - _Requirements: Performance optimization_

- [x] 9. Write comprehensive tests for primary contact enhancements
  - Write unit tests for new User model methods
  - Write feature tests for enhanced AdminUserController validation
  - Write browser tests for modal interactions and UI workflows
  - Write integration tests for dashboard statistics and filtering
  - Test edge cases like removing last team member or primary contact
  - _Requirements: All requirements validation_

- [ ] 10. Update language files with new UI text
  - Add new translation keys for primary contact explanations
  - Add validation error messages for enhanced form validation
  - Add dashboard statistics labels and descriptions
  - Ensure all new UI text is properly internationalized
  - _Requirements: 1.1, 1.3_
