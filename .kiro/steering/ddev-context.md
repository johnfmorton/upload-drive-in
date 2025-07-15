# DDEV Development Environment

This project uses DDEV for local development with the following configuration:

## Environment Details
- **Project Name**: upload-drive-in
- **Type**: Laravel
- **PHP Version**: 8.3
- **Database**: MariaDB 10.11
- **Web Server**: nginx-fpm
- **Node.js**: 18
- **Document Root**: public/

## URLs
- **Local URL**: https://upload-drive-in.ddev.site
- **Vite Dev Server**: http://upload-drive-in.ddev.site:3000

## Common DDEV Commands
When suggesting commands, prefer DDEV equivalents:
- `ddev start` - Start the environment
- `ddev stop` - Stop the environment
- `ddev ssh` - SSH into web container
- `ddev composer` - Run Composer commands
- `ddev artisan` - Run Laravel Artisan commands
- `ddev npm` - Run npm commands
- `ddev mysql` - Access MySQL/MariaDB CLI
- `ddev logs` - View container logs

## Development Workflow
- Database migrations run automatically on `ddev start`
- Vite development server is exposed on port 3000
- Use `ddev artisan` for Laravel commands instead of direct `php artisan`
- Use `ddev composer` for package management