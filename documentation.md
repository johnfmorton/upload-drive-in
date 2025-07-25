# Upload Drive-In Documentation

Upload Drive-In is a Laravel-based web application that enables businesses to receive files from their clients directly into their Google Drive accounts. The system provides a streamlined file upload experience with email validation and organized storage.

## Table of Contents

- [Upload Drive-In Documentation](#upload-drive-in-documentation)
  - [Table of Contents](#table-of-contents)
  - [Getting Started](#getting-started)
    - [Development Installation (DDEV)](#development-installation-ddev)
      - [Prerequisites](#prerequisites)
      - [Installation Steps](#installation-steps)
      - [Initial Setup](#initial-setup)
    - [Production Installation (Server)](#production-installation-server)
      - [Prerequisites](#prerequisites-1)
      - [Installation Steps](#installation-steps-1)
    - [Docker Installation](#docker-installation)
      - [Prerequisites](#prerequisites-2)
      - [Installation Steps](#installation-steps-2)
      - [Docker Management Commands](#docker-management-commands)
    - [Updating to Latest Version](#updating-to-latest-version)
      - [Update Checklist](#update-checklist)
      - [Rollback Process](#rollback-process)
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

Upload Drive-In can be installed in three different ways depending on your needs:

1. **Development Environment** - Using DDEV for local development
2. **Production Server** - Direct installation on a server
3. **Docker Container** - Containerized deployment for production

### Development Installation (DDEV)

#### Prerequisites

- [DDEV](https://ddev.readthedocs.io/en/stable/)
- [Docker](https://www.docker.com/get-started)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (v16 or higher)

#### Installation Steps

1. Clone the repository:

```bash
git clone https://github.com/yourusername/upload-drive-in.git
cd upload-drive-in
```

2. Start the DDEV environment:

```bash
ddev start
```

3. Initialize the project using the make command:

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

4. Configure your Google Drive credentials:

- Set up a Google Cloud Project
- Enable the Google Drive API
- Create OAuth 2.0 credentials
- Add the credentials to your `.env` file

#### Initial Setup

After installation, you'll need to:

1. Configure your `.env` file with:
   - Database credentials
   - Mail server settings
   - Google Drive API credentials
   - Other environment-specific settings

2. Create an admin user:

```bash
ddev artisan user:set-role your@email.com admin
```

### Production Installation (Server)

For production deployment on a bare-bones server (Ubuntu/Debian):

#### Prerequisites

- Ubuntu 20.04+ or Debian 11+
- PHP 8.3+
- MySQL 8.0+ or MariaDB 10.11+
- Redis
- Nginx or Apache
- Composer
- Node.js 18+

#### Installation Steps

1. **Update system and install dependencies**:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-redis php8.3-gd \
    php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl php8.3-intl \
    mysql-server redis-server nginx composer nodejs npm git unzip
```

2. **Clone and setup application**:

```bash
cd /var/www
sudo git clone https://github.com/yourusername/upload-drive-in.git
sudo chown -R www-data:www-data upload-drive-in
cd upload-drive-in
```

3. **Install dependencies**:

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build
```

4. **Configure environment**:

```bash
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
```

5. **Setup database**:

```bash
# Create database and user
sudo mysql -e "CREATE DATABASE upload_drive_in;"
sudo mysql -e "CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON upload_drive_in.* TO 'laravel'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

6. **Configure `.env` file**:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=upload_drive_in
DB_USERNAME=laravel
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add your Google Drive credentials
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REDIRECT_URI=https://yourdomain.com/admin/cloud-storage/google-drive/callback
GOOGLE_DRIVE_ROOT_FOLDER_ID=your_folder_id
```

7. **Run migrations and optimize**:

```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

8. **Configure Nginx**:

```bash
sudo tee /etc/nginx/sites-available/upload-drive-in << 'EOF'
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/upload-drive-in/public;
    index index.php;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

sudo ln -s /etc/nginx/sites-available/upload-drive-in /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

9. **Setup queue worker service**:

```bash
sudo tee /etc/systemd/system/upload-drive-in-queue.service << 'EOF'
[Unit]
Description=Upload Drive-In Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/upload-drive-in
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable upload-drive-in-queue
sudo systemctl start upload-drive-in-queue
```

10. **Create admin user**:

```bash
sudo -u www-data php artisan user:set-role your@email.com admin
```

### Docker Installation

For containerized deployment using Docker:

#### Prerequisites

- Docker 20.10+
- Docker Compose 2.0+

#### Installation Steps

1. **Clone repository**:

```bash
git clone https://github.com/yourusername/upload-drive-in.git
cd upload-drive-in
```

2. **Configure environment**:

```bash
cp .env.example .env
```

Edit `.env` with your production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (will connect to MySQL container)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=upload_drive_in
DB_USERNAME=laravel
DB_PASSWORD=secure_password
DB_ROOT_PASSWORD=rootpassword

# Redis (will connect to Redis container)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis

# Google Drive credentials
GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REDIRECT_URI=https://yourdomain.com/admin/cloud-storage/google-drive/callback
GOOGLE_DRIVE_ROOT_FOLDER_ID=your_folder_id
```

3. **Build and start containers**:

```bash
docker-compose up -d --build
```

4. **Run initial setup**:

```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate --force

# Create admin user
docker-compose exec app php artisan user:set-role your@email.com admin

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### Docker Management Commands

```bash
# View logs
docker-compose logs -f app

# Access application container
docker-compose exec app sh

# Restart services
docker-compose restart

# Stop services
docker-compose down

# Update application
git pull origin main
docker-compose down
docker-compose up -d --build
```

### Updating to Latest Version

Update instructions vary depending on your installation method:

#### Development Environment (DDEV)

1. **Backup your data** (recommended):

```bash
# Backup database
ddev export-db --file=backup-$(date +%Y%m%d).sql.gz

# Backup uploaded files (if any)
cp -r storage/app/public/uploads/ ~/upload-backup-$(date +%Y%m%d)/
```

2. **Pull the latest changes**:

```bash
git fetch origin
git pull origin main
```

3. **Update dependencies**:

```bash
# Update Composer dependencies
ddev composer update

# Update NPM packages
ddev npm update
```

4. **Run database migrations**:

```bash
ddev artisan migrate
```

5. **Clear caches**:

```bash
ddev artisan optimize:clear
ddev artisan config:cache
ddev artisan route:cache
ddev artisan view:cache
```

6. **Rebuild frontend assets**:

```bash
ddev npm run build
```

7. **Restart queue workers** (if running):

```bash
ddev artisan queue:restart
```

#### Production Server

1. **Backup your data**:

```bash
# Backup database
mysqldump -u laravel -p upload_drive_in > backup-$(date +%Y%m%d).sql

# Backup uploaded files
sudo cp -r /var/www/upload-drive-in/storage/app/public/uploads/ ~/upload-backup-$(date +%Y%m%d)/
```

2. **Update application**:

```bash
cd /var/www/upload-drive-in
sudo git pull origin main
```

3. **Update dependencies**:

```bash
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build
```

4. **Run migrations and optimize**:

```bash
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

5. **Restart services**:

```bash
sudo systemctl restart upload-drive-in-queue
sudo systemctl reload nginx
```

#### Docker Installation

1. **Backup your data**:

```bash
# Backup database
docker-compose exec mysql mysqldump -u laravel -p upload_drive_in > backup-$(date +%Y%m%d).sql

# Backup uploaded files
docker cp $(docker-compose ps -q app):/var/www/html/storage/app/public/uploads/ ~/upload-backup-$(date +%Y%m%d)/
```

2. **Update and rebuild**:

```bash
git pull origin main
docker-compose down
docker-compose up -d --build
```

3. **Run migrations and optimize**:

```bash
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### Update Checklist

After updating, verify these components are working:

- [ ] Application loads without errors
- [ ] Database migrations completed successfully
- [ ] Google Drive integration still works
- [ ] File uploads function properly
- [ ] Queue jobs process correctly
- [ ] Admin dashboard is accessible

#### Rollback Process

If you encounter issues after updating, follow the appropriate rollback process:

**Development Environment (DDEV):**

```bash
# Restore database
ddev import-db --file=backup-YYYYMMDD.sql.gz

# Revert code
git checkout <previous-commit-hash>

# Reinstall dependencies
ddev composer install
ddev npm install
ddev npm run build
```

**Production Server:**

```bash
# Restore database
mysql -u laravel -p upload_drive_in < backup-YYYYMMDD.sql

# Revert code
cd /var/www/upload-drive-in
sudo git checkout <previous-commit-hash>

# Reinstall dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Restart services
sudo systemctl restart upload-drive-in-queue
sudo systemctl reload nginx
```

**Docker Installation:**

```bash
# Restore database
docker-compose exec mysql mysql -u laravel -p upload_drive_in < backup-YYYYMMDD.sql

# Revert and rebuild
git checkout <previous-commit-hash>
docker-compose down
docker-compose up -d --build
```

## Development Environment

The project uses DDEV for local development. Here are some key commands:

- Start the full development environment (recommended):

```bash
ddev composer dev
```

This runs all development services concurrently: server, queue worker, logs, and Vite dev server.

- Alternative individual commands:

```bash
make dev          # Start development with Vite hot-reloading
make build        # Build production assets
make mailhog      # Access MailHog for email testing
make queue        # Run the queue worker only
```

- Monitor logs in real-time:

```bash
ddev artisan pail
```

## Available Commands

### Make Commands

- `make dev`: Launches the development environment with hot-reloading
- `make build`: Builds the project for production
- `make mailhog`: Opens MailHog for email testing
- `make queue`: Starts the queue worker
- `make init`: Initializes the project with all dependencies

### Artisan Commands

- `ddev artisan migrate`: Run database migrations
- `ddev artisan db:seed`: Seed the database
- `ddev artisan queue:work`: Start the queue worker
- `ddev artisan user:set-role`: Set user roles
- `ddev artisan cache:clear`: Clear application cache

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
- Pending uploads management (see [Pending Uploads Documentation](docs/pending-uploads.md))

## Configuration

### Environment Variables

Key environment variables in `.env`:

#### Development Environment (DDEV)

```env
APP_NAME=UploadDriveIn
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=https://upload-drive-in.ddev.site

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db

GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REDIRECT_URI=https://upload-drive-in.ddev.site/admin/cloud-storage/google-drive/callback
GOOGLE_DRIVE_ROOT_FOLDER_ID=
CLOUD_STORAGE_DEFAULT=google-drive

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

#### Production Environment

```env
APP_NAME=UploadDriveIn
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=upload_drive_in
DB_USERNAME=laravel
DB_PASSWORD=secure_password

GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REDIRECT_URI=https://yourdomain.com/admin/cloud-storage/google-drive/callback
GOOGLE_DRIVE_ROOT_FOLDER_ID=your_folder_id
CLOUD_STORAGE_DEFAULT=google-drive

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
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
   - Verify OAuth credentials in `.env`
   - Check token expiration and refresh
   - Ensure proper scopes are enabled (`drive.file` and `drive`)
   - Verify redirect URI matches Google Cloud Console settings

3. **Queue Worker Issues**
   - Check failed jobs: `ddev artisan queue:failed`
   - Retry failed jobs: `ddev artisan queue:retry all`
   - Monitor queue in real-time: `ddev artisan queue:work --tries=1`
   - Verify Redis is running for queue driver

4. **File Upload Problems**
   - Check storage directory permissions
   - Verify temporary upload directory exists
   - Monitor background job processing
   - Check Google Drive API rate limits

5. **Frontend Build Issues**
   - Clear Node modules: `ddev exec rm -rf node_modules && ddev npm install`
   - Rebuild assets: `ddev npm run build`
   - Check Vite configuration for Tailwind CSS 4.x compatibility

### Debug Tools

- **Laravel Pail**: Real-time log streaming (`ddev artisan pail`)
- **Laravel Tinker**: Interactive REPL (`ddev artisan tinker`)
- **MailHog**: Email testing (accessible via `make mailhog`)
- **DDEV Logs**: Container logs (`ddev logs`)
- **Queue Monitoring**: Failed jobs table and retry mechanisms
- **Laravel Pint**: Code formatting (`ddev composer pint`)

For additional support or bug reports, please visit our [GitHub Issues](https://github.com/yourusername/upload-drive-in/issues) page.
