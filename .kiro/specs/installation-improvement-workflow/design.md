# Design Document

## Overview

This design extends the existing Laravel setup wizard to handle the initial installation experience more gracefully. The current system assumes assets are built and database is configured, but new users encounter 500 errors when these prerequisites aren't met. The solution introduces pre-setup checks and asset build verification before entering the existing setup flow.

## Architecture

### Current Setup Flow
The existing setup system has these components:
- `SetupService` - Manages setup state and checks
- `SetupController` - Handles setup wizard steps
- `RequireSetupMiddleware` - Redirects to setup when needed
- Setup routes with steps: welcome → database → admin → storage → complete

### Enhanced Installation Flow
The new flow adds pre-setup validation:
1. **Asset Check** - Verify Vite manifest exists
2. **Database Check** - Test database connectivity
3. **Existing Setup Flow** - Continue with current wizard

### Component Integration
- Extend `SetupService` with asset validation methods
- Add new controller methods for pre-setup steps
- Enhance middleware to handle asset-missing scenarios
- Create new views for asset build instructions

## Components and Interfaces

### Enhanced SetupService

```php
class SetupService
{
    // New methods for asset validation
    public function isViteManifestPresent(): bool
    public function getAssetBuildInstructions(): array
    public function validateAssetRequirements(): array
    
    // Enhanced setup requirement check
    public function isSetupRequired(): bool // Modified to include asset checks
}
```

### Enhanced SetupController

```php
class SetupController
{
    // New pre-setup methods
    public function showAssetBuildInstructions(): View
    public function checkAssetBuildStatus(): JsonResponse
    
    // Enhanced welcome method
    public function welcome(): View // Modified to handle asset checks
}
```

### New AssetValidationService

```php
class AssetValidationService
{
    public function validateViteManifest(): bool
    public function getManifestPath(): string
    public function getBuildInstructions(): array
    public function checkNodeEnvironment(): array
}
```

### Enhanced RequireSetupMiddleware

```php
class RequireSetupMiddleware
{
    // Enhanced to handle asset-missing scenarios
    public function handle(Request $request, Closure $next): Response
    
    // New method for asset-specific routing
    private function handleAssetMissing(Request $request): Response
}
```

## Data Models

### Setup State Enhancement

The existing setup state JSON structure will be enhanced:

```json
{
    "setup_complete": false,
    "current_step": "assets", // New step
    "started_at": "2025-01-15T10:00:00Z",
    "steps": {
        "assets": { // New step
            "completed": false,
            "completed_at": null
        },
        "welcome": {
            "completed": false,
            "completed_at": null
        },
        // ... existing steps
    },
    "asset_checks": { // New section
        "vite_manifest_exists": false,
        "node_environment_ready": false,
        "build_instructions_shown": true
    }
}
```

### Configuration Updates

Extend `config/setup.php`:

```php
'steps' => [
    'assets',    // New first step
    'welcome',
    'database',
    'admin',
    'storage',
    'complete',
],

'asset_checks' => [
    'vite_manifest_required' => true,
    'node_environment_check' => true,
    'build_instructions_enabled' => true,
],

'asset_paths' => [
    'vite_manifest' => 'public/build/manifest.json',
    'build_directory' => 'public/build',
],
```

## Error Handling

### Asset Missing Scenarios

1. **Vite Manifest Missing**
   - Display build instructions screen
   - Provide copy-paste commands
   - Include troubleshooting tips

2. **Build Directory Missing**
   - Show directory creation instructions
   - Verify file permissions
   - Guide through npm installation

3. **Node Environment Issues**
   - Check for package.json
   - Verify npm/node installation
   - Provide installation links

### Database Connection Failures

Enhanced error handling for database setup:

1. **Connection Timeout**
   - Provide network troubleshooting
   - Suggest firewall/port checks
   - Offer alternative connection methods

2. **Authentication Failures**
   - Clear credential validation messages
   - Suggest privilege checks
   - Provide MySQL user creation examples

3. **Database Not Found**
   - Offer database creation instructions
   - Provide SQL commands
   - Suggest hosting provider steps

### Graceful Degradation

- If asset checks fail, show instructions instead of 500 error
- If database checks fail, provide configuration form
- If admin creation fails, show validation with helpful hints
- Maintain setup state across failures for resumability

## Testing Strategy

### Unit Tests

1. **AssetValidationService Tests**
   - Test manifest detection
   - Test build directory validation
   - Test instruction generation

2. **Enhanced SetupService Tests**
   - Test asset requirement checks
   - Test setup flow with asset validation
   - Test state management with new steps

3. **Controller Tests**
   - Test asset instruction display
   - Test AJAX asset status checks
   - Test error handling scenarios

### Integration Tests

1. **Setup Flow Tests**
   - Test complete installation flow
   - Test resumability after failures
   - Test middleware behavior with missing assets

2. **Error Scenario Tests**
   - Test 500 error prevention
   - Test graceful error display
   - Test recovery from various failure states

3. **Asset Build Tests**
   - Test with missing manifest
   - Test with corrupted build files
   - Test with partial build completion

### Feature Tests

1. **User Experience Tests**
   - Test first-time installation flow
   - Test error message clarity
   - Test instruction effectiveness

2. **Environment Tests**
   - Test on fresh Laravel installation
   - Test with various Node.js versions
   - Test with different hosting environments

## Implementation Phases

### Phase 1: Asset Validation Foundation
- Create `AssetValidationService`
- Add asset validation methods to `SetupService`
- Create asset instruction views
- Add asset step to setup configuration

### Phase 2: Enhanced Setup Flow
- Modify `RequireSetupMiddleware` for asset handling
- Add asset instruction controller methods
- Create AJAX endpoints for build status checking
- Update setup state management

### Phase 3: Enhanced Error Handling
- Improve database error messages
- Add troubleshooting guides
- Enhance validation feedback
- Add recovery mechanisms

### Phase 4: User Experience Polish
- Add progress indicators
- Improve instruction clarity
- Add copy-to-clipboard functionality
- Enhance visual feedback

## Security Considerations

### File System Access
- Validate file paths to prevent directory traversal
- Use Laravel's secure file handling methods
- Limit file system operations to necessary directories

### Environment File Updates
- Preserve existing configuration values
- Validate input before writing to .env
- Use secure file writing methods
- Log configuration changes for audit

### Setup State Security
- Store setup state in secure location
- Prevent unauthorized setup state manipulation
- Clear sensitive data from setup state after completion
- Validate setup state integrity

### Input Validation
- Sanitize all user inputs
- Validate database credentials securely
- Prevent SQL injection in connection tests
- Use Laravel's validation rules consistently

## Performance Considerations

### Asset Checking
- Cache asset validation results
- Use efficient file existence checks
- Minimize file system operations
- Implement lazy loading for instructions

### Setup State Management
- Use efficient JSON encoding/decoding
- Cache setup state when appropriate
- Minimize database queries during setup
- Use Laravel's caching mechanisms

### Error Recovery
- Implement efficient retry mechanisms
- Use exponential backoff for connection tests
- Cache error states to prevent repeated failures
- Optimize setup state persistence

## Monitoring and Logging

### Setup Analytics
- Track setup completion rates
- Monitor common failure points
- Log setup duration metrics
- Track user drop-off points

### Error Tracking
- Log all setup errors with context
- Track asset build failures
- Monitor database connection issues
- Alert on repeated setup failures

### Audit Trail
- Log all setup state changes
- Track configuration modifications
- Record user actions during setup
- Maintain setup completion history