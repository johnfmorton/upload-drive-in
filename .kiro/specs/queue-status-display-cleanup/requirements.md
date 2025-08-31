# Requirements Document

## Introduction

The current admin dashboard includes a "Queue Status" display element with a gray background that shows queue health information (Healthy, Error, Warning, etc.). This element is causing confusion for users who don't understand what it means or how to interpret it. The requirement is to eliminate this confusing status display entirely while preserving the "Test Queue Worker" button and its associated testing functionality, which provides clear, actionable feedback to users.

## Requirements

### Requirement 1

**User Story:** As an admin user, I want the confusing "Queue Status" display element removed from the dashboard so that I'm not confused by unclear status information.

#### Acceptance Criteria

1. WHEN I visit the admin dashboard THEN I SHALL NOT see the gray background "Queue Status" element that displays "Healthy", "Error", "Warning", or other status text
2. WHEN I visit the admin dashboard THEN I SHALL NOT see the queue health overview section with status icons and metrics
3. WHEN I visit the admin dashboard THEN I SHALL NOT see any automatic queue health polling or status updates

### Requirement 2

**User Story:** As an admin user, I want to keep the "Test Queue Worker" button so that I can still test my queue functionality when needed.

#### Acceptance Criteria

1. WHEN I visit the admin dashboard THEN I SHALL see the "Test Queue Worker" button in the Queue Worker Status section
2. WHEN I click the "Test Queue Worker" button THEN the system SHALL dispatch a test job and show progress as it currently does
3. WHEN the test completes THEN I SHALL see the test results displayed as they currently are
4. WHEN the test fails THEN I SHALL see appropriate error messages and failure information as currently implemented

### Requirement 3

**User Story:** As an admin user, I want the Queue Worker Status section to have a cleaner, simpler interface focused only on testing functionality.

#### Acceptance Criteria

1. WHEN I view the Queue Worker Status section THEN I SHALL see only the section title, description, help text, and the "Test Queue Worker" button
2. WHEN I view the Queue Worker Status section THEN I SHALL NOT see any persistent status displays, metrics, or health indicators
3. WHEN I view the Queue Worker Status section THEN the layout SHALL be clean and focused on the testing functionality
4. WHEN I test the queue worker THEN the test results SHALL still be displayed in the existing "Test Results" section

### Requirement 4

**User Story:** As a developer, I want the JavaScript code cleaned up to remove unused queue health monitoring functionality while preserving test functionality.

#### Acceptance Criteria

1. WHEN the page loads THEN the system SHALL NOT automatically load or poll queue health status
2. WHEN the page loads THEN the system SHALL NOT update any queue status displays or metrics
3. WHEN the "Test Queue Worker" button is clicked THEN all existing test functionality SHALL work exactly as before
4. WHEN test results are displayed THEN they SHALL appear and function exactly as they currently do