# Upload Drive-In Documentation

Upload Drive-In is a Laravel-based web application that enables businesses to receive files from their clients directly into their Google Drive accounts. The system provides a streamlined file upload experience with email validation and organized storage.

## Table of Contents
- [Upload Drive-In Documentation](#upload-drive-in-documentation)
  - [Table of Contents](#table-of-contents)
  - [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
    - [Initial Setup](#initial-setup)
  - [Development Environment](#development-environment)
  - [Available Commands](#available-commands)
    - [Make Commands](#make-commands)
    - [Artisan Commands](#artisan-commands)
  - [Features](#features)
    - [Public Upload Page](#public-upload-page)
    - [Google Drive Integration](#google-drive-integration)
    - [Admin Dashboard](#admin-dashboard)
  - [Configuration](#configuration)
    - [Environment Variables](#environment-variables)
    - [Google Drive Setup](#google-drive-setup)
  - [API Documentation](#api-documentation)
    - [Authentication Endpoints](#authentication-endpoints)
    - [Upload Endpoints](#upload-endpoints)
    - [Admin Endpoints](#admin-endpoints)
  - [Troubleshooting](#troubleshooting)
    - [Common Issues](#common-issues)
    - [Debug Tools](#debug-tools)

## Getting Started

### Prerequisites

Before setting up Upload Drive-In, ensure you have the following installed:
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- [Docker](https://www.docker.com/get-started)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (v16 or higher)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/upload-drive-in.git
cd upload-drive-in
```

2. Initialize the project using the make command:
```bash
make init
```

This command will:
- Create `.env` file from `.env.example`
- Install Composer dependencies
- Generate application key
- Install NPM packages
- Build frontend assets
- Run database migrations and seeders

3. Configure your Google Drive credentials:
- Set up a Google Cloud Project
- Enable the Google Drive API
- Create OAuth 2.0 credentials
- Add the credentials to your `.env` file

### Initial Setup

After installation, you'll need to:
1. Configure your `.env` file with:
   - Database credentials
   - Mail server settings
   - Google Drive API credentials
   - Other environment-specific settings

2. Create an admin user:
```bash
ddev exec php artisan user:set-role your@email.com admin
```

## Development Environment

The project uses DDEV for local development. Here are some key commands:

- Start the development environment:
```bash
make dev
```

- Build production assets:
```bash
make build
```

- Access MailHog (for email testing):
```bash
make mailhog
```

- Run the queue worker:
```bash
make queue
```

## Available Commands

### Make Commands
- `make dev`: Launches the development environment with hot-reloading
- `make build`: Builds the project for production
- `make mailhog`: Opens MailHog for email testing
- `make queue`: Starts the queue worker
- `make init`: Initializes the project with all dependencies

### Artisan Commands
- `ddev exec php artisan migrate`: Run database migrations
- `ddev exec php artisan db:seed`: Seed the database
- `ddev exec php artisan queue:work`: Start the queue worker
- `ddev exec php artisan user:set-role`: Set user roles
- `ddev exec php artisan cache:clear`: Clear application cache

## Features

### Public Upload Page
- Email validation system
- Multiple file upload support
- Optional message field
- Pre-validated token links

### Google Drive Integration
- Automatic file organization
- Folder creation by email
- Secure OAuth 2.0 authentication
- Token management

### Admin Dashboard
- Upload monitoring
- User management
- File organization
- Activity logs

## Configuration

### Environment Variables
Key environment variables in `.env`:

```env
APP_NAME=UploadDriveIn
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db

GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_ID=
```

### Google Drive Setup
1. Create a Google Cloud Project
2. Enable the Google Drive API
3. Configure OAuth 2.0 credentials
4. Set up the redirect URI
5. Generate and store refresh tokens

## API Documentation

### Authentication Endpoints
- POST `/api/auth/email-verification`
- POST `/api/auth/validate-token`
- POST `/api/auth/logout`

### Upload Endpoints
- POST `/api/upload`
- GET `/api/uploads`
- GET `/api/uploads/{id}`

### Admin Endpoints
- GET `/api/admin/users`
- POST `/api/admin/users`
- PUT `/api/admin/users/{id}`
- DELETE `/api/admin/users/{id}`

## Troubleshooting

### Common Issues

1. **DDEV Connection Issues**
   - Ensure Docker is running
   - Try restarting DDEV: `ddev restart`
   - Check port conflicts: `ddev describe`

2. **Google Drive Integration**
   - Verify OAuth credentials
   - Check token expiration
   - Ensure proper scopes are enabled

3. **Queue Worker**
   - Verify Redis is running
   - Check queue worker logs
   - Restart queue: `make queue`

### Debug Tools
- MailHog for email testing
- Laravel Telescope for request monitoring
- Laravel Log Viewer for error logs

For additional support or bug reports, please visit our [GitHub Issues](https://github.com/yourusername/upload-drive-in/issues) page.
