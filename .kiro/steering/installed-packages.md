# Installed Packages & Dependencies

This project has specific packages installed that should be leveraged before adding new dependencies.

## Core Laravel Packages

### Authentication & UI
- **laravel/breeze**: Simple authentication scaffolding (login, registration, password reset)
- **laravel/ui**: Additional UI scaffolding and frontend presets
- **laravel/sanctum**: API token authentication (for future API endpoints)
- **upload-drive-in/laravel-admin-2fa**: Custom 2FA package (local development)

### Development & Debugging
- **laravel/tinker**: Interactive REPL for Laravel
- **laravel/pail**: Real-time log streaming (`ddev artisan pail`)
- **laravel/pint**: Code style fixer (Laravel's opinionated PHP CS Fixer)
- **laravel/sail**: Docker development environment (though we use DDEV)

## Specialized Packages

### File Upload & Processing
- **pion/laravel-chunk-upload**: Chunked file upload handling
  - Use for large file uploads to improve user experience
  - Handles resumable uploads and progress tracking
  - Already configured for the upload system

### Google Integration
- **google/apiclient**: Official Google API client
  - Used in GoogleDriveService for Drive API interactions
  - Handles OAuth 2.0 authentication flow
  - Supports token refresh and API calls

### Database & Caching
- **doctrine/dbal**: Database abstraction layer
  - Required for advanced database operations
  - Schema introspection and migrations
  - Cross-database compatibility

- **predis/predis**: Redis client for PHP
  - Used for caching and session storage
  - Queue driver support
  - Configure via REDIS_* environment variables

### Utility Packages
- **simplesoftwareio/simple-qrcode**: QR code generation
  - Generate QR codes for upload links or tokens
  - Multiple output formats (PNG, SVG, etc.)
  - Use: `QrCode::size(300)->generate('text')`

- **spatie/color**: Color manipulation utilities
  - Handle color conversions and manipulations
  - Useful for UI theming or file categorization
  - Use: `Color::hex('#ff0000')->rgb()`

## Custom Helpers

### Available Helper Functions
- **format_bytes()**: Convert bytes to human-readable format (KB, MB, GB, etc.)
  - Use instead of creating custom byte formatting
  - Example: `format_bytes(1024)` returns "1.00 KB"

## Package Usage Guidelines

### Before Adding New Packages
1. **Check existing packages** for similar functionality
2. **Review if current packages** can be extended or configured differently
3. **Consider removing unused packages** when adding new ones
4. **Update steering rules** when package changes are made

### Preferred Usage Patterns

#### File Uploads
- Use `pion/laravel-chunk-upload` for large files
- Leverage existing upload job queue system
- Utilize `format_bytes()` helper for file size display

#### Authentication
- Extend Laravel Breeze for additional auth features
- Use custom 2FA package for admin security
- Leverage Sanctum for future API authentication

#### Google Drive Integration
- Always use `google/apiclient` through service layer
- Don't add additional Google packages without review
- Extend existing GoogleDriveService for new features

#### Code Quality
- Use Laravel Pint for code formatting: `ddev composer pint`
- Leverage Tinker for debugging: `ddev artisan tinker`
- Monitor with Pail: `ddev artisan pail`

### Package Removal Considerations
When adding new packages, review if these can be removed:
- **laravel/sail**: Since we use DDEV, this might be removable
- **laravel/ui**: If Breeze covers all UI needs, consider removal
- Unused Spatie or utility packages if functionality isn't used

## Development Commands

### Package Management
- **Install**: `ddev composer require package/name`
- **Remove**: `ddev composer remove package/name`
- **Update**: `ddev composer update`
- **Autoload**: `ddev composer dump-autoload`

### Code Quality
- **Format Code**: `ddev composer pint` or `ddev artisan pint`
- **Run Tests**: `ddev artisan test`
- **Clear Cache**: `ddev artisan optimize:clear`