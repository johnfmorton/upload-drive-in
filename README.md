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
