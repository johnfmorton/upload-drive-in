# Laravel Project Conventions

This project follows specific Laravel conventions and coding standards.

## Coding Standards

### File Naming
- **Files**: Use kebab-case (e.g., `my-class-file.php`)
- **Classes/Enums**: Use PascalCase (e.g., `MyClass`, `UserRole`)
- **Methods**: Use camelCase (e.g., `myMethod`, `validateEmail`)
- **Variables/Properties**: Use snake_case (e.g., `my_variable`, `user_id`)
- **Constants/Enum Cases**: Use SCREAMING_SNAKE_CASE (e.g., `MY_CONSTANT`, `ADMIN_ROLE`)

### PHP Version & Features
- **Target**: PHP 8.3+
- Use modern PHP features (typed properties, match expressions, etc.)
- Leverage Laravel 12 features and conventions
- Prefer helpers over facades when possible

### Code Quality
- **Styling**: Laravel Pint configuration for consistent formatting
- **Documentation**: Comprehensive docblocks for better DX and autocompletion
- **Type Safety**: Use strict typing where possible
- **Developer Experience**: Focus on excellent autocompletion and IDE support

## Package Development
- Uses `spatie/laravel-package-tools` as boilerplate foundation
- Custom package: `upload-drive-in/laravel-admin-2fa` (local development)
- Follow Laravel package development best practices

## Architecture Patterns

### Service Layer
- Business logic in dedicated service classes
- Dependency injection for testability
- Single responsibility principle

### Job Queue System
- Background processing for file uploads
- Retry logic with exponential backoff
- Proper error handling and logging

### Event-Driven Architecture
- Events: `FileUploaded`, `BatchUploadComplete`
- Listeners for notifications and cleanup
- Decoupled components for maintainability