# Development Workflow & Testing

This project uses specific development workflows and testing patterns optimized for the DDEV environment.

## Development Commands

### Preferred Command Patterns
- **Artisan**: `ddev artisan` instead of `php artisan`
- **Composer**: `ddev composer` instead of `composer`
- **NPM**: `ddev npm` instead of `npm`
- **Database**: `ddev mysql` for direct database access
- **Logs**: `ddev logs` for container logs

### Development Server
- **Primary**: Use DDEV environment (`ddev start`)
- **Vite**: Development server on port 3000
- **Queue Worker**: `ddev artisan queue:work` for background jobs
- **Pail**: `ddev artisan pail` for real-time log monitoring

### Composer Scripts
- **dev**: Runs concurrent services (server, queue, logs, vite)
- Use `ddev composer dev` for full development environment

## File Upload Development

### Local Testing Workflow
1. Start DDEV environment: `ddev start`
2. Run migrations (automatic on start)
3. Configure Google Drive credentials in `.env`
4. Test upload flow via `https://upload-drive-in.ddev.site`
5. Monitor queue jobs: `ddev artisan queue:work`
6. Check logs: `ddev artisan pail` or `ddev logs`

### Storage Locations
- **Temporary**: `storage/app/public/uploads/`
- **Cleanup**: Files removed after successful cloud upload
- **Logs**: `storage/logs/laravel.log`

## Testing Strategy

### Test Types
- **Unit Tests**: Service classes and models
- **Feature Tests**: HTTP endpoints and workflows
- **Integration Tests**: Google Drive API interactions

### Testing Commands
- **Run Tests**: `ddev artisan test`
- **With Coverage**: `ddev artisan test --coverage`
- **Specific Test**: `ddev artisan test --filter=TestName`

### Mock Patterns
- Mock Google API client for unit tests
- Use test folders for integration tests
- Queue fake for job testing
- Mail fake for notification testing

## Queue Management

### Development Queue
- **Driver**: Database (configured in `.env`)
- **Worker**: `ddev artisan queue:work --tries=1`
- **Failed Jobs**: `ddev artisan queue:failed`
- **Retry**: `ddev artisan queue:retry all`

### Job Monitoring
- Monitor `UploadToGoogleDrive` job execution
- Check for failed uploads in admin dashboard
- Verify file cleanup after successful uploads

## Debugging & Troubleshooting

### Common Issues
- **Token Expiry**: Check Google Drive token refresh
- **Queue Failures**: Monitor failed job table
- **File Permissions**: Verify storage directory permissions
- **API Limits**: Handle Google Drive API rate limits

### Debug Tools
- **Laravel Pail**: Real-time log streaming
- **Telescope**: Application debugging (if installed)
- **Tinker**: `ddev artisan tinker` for interactive debugging