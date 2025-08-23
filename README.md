# Upload Drive-In

## Getting Started

## Local Development Setup

### 1. Clone the Repository

```bash
git clone <repo-url>
cd <repo-directory>
```

### 2. Install Dependencies

Ensure you have Composer and Node.js installed:

```bash
composer install
npm install
```

### 3. Configure Environment

Copy `.env.example` to `.env` and edit it:

```bash
cp .env.example .env
```

Update values such as database settings and API keys.

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Start Queue Worker (if used)

```bash
php artisan queue:work
```

### 7. Start the Development Server

```bash
php artisan serve
```

Visit: http://127.0.0.1:8000

### 8. Run Vite (JS/CSS)

```bash
npm run dev
```

### Optional: Production Asset Build

```bash
npm run build
```

---

## Additional Notes

- Monitor `storage/logs/laravel.log` for errors.
- Keep your `.env` file up to date with token and permission details.
- Match your logic between dashboard and CLI when checking for pending uploads.


## CLI and cron jobs

The ClearOldUploads command has been created and implemented.
You can now run it from your terminal like this:

```sh
php artisan uploads:clear-old
```

To clear files older than 24 hours (default):

```sh
php artisan uploads:clear-old --hours=48
```

This command will look in the storage/app/public/uploads directory and delete any files whose last modified time is older than the specified threshold. You can add this command to your cron job scheduler for regular cleanup. For example, to run it daily:


## Google API Credentials Setup

This application uses Google Drive to store uploaded files. To enable this functionality, you need to configure Google API credentials.

**1. Create a Google Cloud Project:**

*   Go to the [Google Cloud Console](https://console.cloud.google.com/).
*   If you don't have a project, create a new one.

**2. Enable the Google Drive API:**

*   In your project, navigate to "APIs & Services" > "Library".
*   Search for "Google Drive API" and enable it.

**3. Create OAuth 2.0 Credentials:**

*   Go to "APIs & Services" > "Credentials".
*   Click "+ CREATE CREDENTIALS" and select "OAuth client ID".
*   If prompted, configure the "OAuth consent screen" first.
    *   Choose "External" for User Type unless you have a Google Workspace account and only internal users will connect.
    *   Fill in the required app information (App name, User support email, Developer contact information). You can leave scopes blank for now.
    *   Add your domain under "Authorized domains" if applicable (e.g., `yourdomain.com`).
*   Return to "Credentials" and create the OAuth client ID:
    *   Select "Web application" as the Application type.
    *   Give it a name (e.g., "Upload Drive-In Web Client").
    *   Under **Authorized redirect URIs**, click "+ ADD URI". This is crucial:
        *   The URI must be the **full URL** to the application's callback endpoint. The path is `/google-drive/callback`.
        *   You need to combine your application's base URL (defined as `APP_URL` in your `.env` file) with this path.
        *   **Examples:**
            *   If using `ddev` and your URL is `https://upload-drive-in.ddev.site`, the redirect URI is: `https://upload-drive-in.ddev.site/google-drive/callback`
            *   If running locally with `php artisan serve` on port 8000 (`APP_URL=http://127.0.0.1:8000`), the redirect URI is: `http://127.0.0.1:8000/google-drive/callback`
            *   If deployed to `https://myapp.com`, the redirect URI is: `https://myapp.com/google-drive/callback`
        *   **Enter the exact URI corresponding to your setup.** You can add multiple URIs if you have different environments (like development and production).
*   Click "CREATE".

**4. Configure Your `.env` File:**

*   After creating the credentials, Google will show you your **Client ID** and **Client Secret**.
*   Copy these values into your project's `.env` file:

    ```dotenv
    GOOGLE_DRIVE_CLIENT_ID=YOUR_CLIENT_ID_HERE
    GOOGLE_DRIVE_CLIENT_SECRET=YOUR_CLIENT_SECRET_HERE
    ```

*   **Note**: Google Drive root folder configuration is now managed per-user through the admin and employee dashboards. You no longer need to set a global root folder ID in your environment variables.

**5. Connect Google Drive in the Application:**

*   After configuring your `.env` file, log in to the application as an admin.
*   Navigate to the admin dashboard.
*   Click the "Connect Google Drive" button. This will redirect you to Google to authorize the application.
*   Once authorized, you'll be redirected back, and the connection should be active.

## Environment Configuration

For comprehensive configuration of caching, queues, and background job processing, see the **[Environment Configuration Guide](docs/environment-configuration-guide.md)**.

This guide covers:
- File, database, and Redis caching setup
- Queue configuration and background job processing
- Production recommendations and validation procedures
- Environment cleanup and optimization

## Redis Configuration (Optional)

Redis can be used for improved caching and queue performance. For DDEV environments, Redis can be easily added as a service.

**For detailed Redis setup instructions, see:** [DDEV Redis Setup Guide](docs/ddev-redis-setup.md)

**Quick Setup:**

1. Ensure Redis configuration files exist (they should be included in the project)
2. Restart DDEV: `ddev restart`
3. Update your `.env` file:
   ```env
   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   REDIS_CLIENT=predis
   
   # Optional: Use Redis for caching
   CACHE_STORE=redis
   
   # Optional: Use Redis for queues
   QUEUE_CONNECTION=redis
   ```
4. Test the connection: `ddev artisan validate:cache-configuration`

Redis provides better performance for caching and queue operations compared to file-based or database alternatives.
