# Dockerized Laravel Application Instructions

This document outlines the steps to build and run the containerized Laravel application using Docker Compose. This setup is configured for a production-like environment using Nginx and PHP-FPM, with a persistent SQLite database.

## Prerequisites

1.  **Docker:** Ensure Docker Engine and Docker Compose are installed on your system. Visit the [official Docker documentation](https://docs.docker.com/get-docker/) for installation instructions.
2.  **Application Code:** You need the Laravel application source code in the current directory.
3.  **`.env` File:** A properly configured `.env` file must exist in the project root. It should be based on `.env.example` and contain your application key (`APP_KEY`) and other necessary environment variables.
    *   **Important:** Ensure the following database settings are present for SQLite persistence within the container:
        ```dotenv
        DB_CONNECTION=sqlite
        # This path MUST match the volume mount point in docker-compose.yml
        DB_DATABASE=/var/www/html/database/database.sqlite
        ```
4.  **`Dockerfile`:** A `Dockerfile` for building the PHP application image (referenced as `Dockerfile` in `docker-compose.yml`). This should:
    *   Start from a PHP-FPM base image (e.g., `php:8.2-fpm`).
    *   Install necessary PHP extensions (`pdo_sqlite`, `mbstring`, `curl`, `xml`, etc.).
    *   Install Composer.
    *   Copy application code (`COPY . /var/www/html`).
    *   Install Composer dependencies (`composer install --optimize-autoloader --no-dev`).
    *   Set correct file permissions for `storage` and `bootstrap/cache`.
    *   Run Laravel optimization commands (`php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`).
    *   Expose port 9000 and set the `CMD` to `php-fpm`.
5.  **`Dockerfile.nginx`:** A `Dockerfile` for building the Nginx image (referenced as `Dockerfile.nginx` in `docker-compose.yml`). This should:
    *   Start from an Nginx base image (e.g., `nginx:alpine`).
    *   Remove the default Nginx configuration.
    *   Copy a custom Nginx configuration file (e.g., `nginx.conf`) to `/etc/nginx/conf.d/default.conf`. This config file must proxy PHP requests to the `app` service on port 9000 (`fastcgi_pass app:9000;`) and set the root to `/var/www/html/public`.
    *   Expose port 80 (and 443 if needed).

## Build and Run

1.  **Navigate:** Open your terminal and navigate to the project's root directory (where `docker-compose.yml` is located).
2.  **Build Images:** Build the Docker images defined in the `docker-compose.yml` file.
    ```bash
    docker-compose build
    ```
    *(Note: Depending on your Docker version, you might use `docker compose build`)*
3.  **Run Containers:** Start the services (PHP-FPM and Nginx) in detached mode (-d).
    ```bash
    docker-compose up -d
    ```
    *(Note: Depending on your Docker version, you might use `docker compose up -d`)*

Your Laravel application should now be accessible via your server's IP address or domain name (or `http://localhost` if running locally) on port 80.

## Common Commands

*   **Stop Containers:**
    ```bash
    docker-compose down
    ```
*   **Stop and Remove Volumes (Use with caution - deletes database and storage!):**
    ```bash
    docker-compose down -v
    ```
*   **View Logs:**
    ```bash
    # View logs for all services
    docker-compose logs -f

    # View logs for a specific service (e.g., app)
    docker-compose logs -f app
    ```
*   **Run Artisan Commands:** Execute artisan commands within the running `app` container.
    ```bash
    docker-compose exec app php artisan <your-command>
    # Example: Run migrations
    docker-compose exec app php artisan migrate
    ```
*   **Access Container Shell:**
    ```bash
    docker-compose exec app /bin/sh
    # or /bin/bash if available in the image
    ```

## Persistence

*   The `sqlite_db` named volume ensures that the `database/database.sqlite` file persists even if you run `docker-compose down` and `docker-compose up -d` again.
*   The `laravel_storage` named volume ensures that the contents of the `storage` directory (logs, cache, session files, file uploads in `storage/app/public`) also persist.

Remember to rebuild your images (`docker-compose build`) if you make changes to your `Dockerfile`, `Dockerfile.nginx`, or the application code/dependencies that need to be baked into the image.
