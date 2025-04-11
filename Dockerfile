# Use an official PHP runtime as a parent image
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-fpm-alpine AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies required by Laravel and extensions
# Add common extensions and any specific ones your app needs (e.g., gd, bcmath, redis)
RUN apk add --no-cache \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    sqlite-libs \
    icu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo \
    pdo_sqlite \
    zip \
    gd \
    exif \
    pcntl \
    intl \
    xml \
    && apk del libzip-dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Development Stage ---
# This stage includes dev dependencies and keeps source mounted
FROM base AS development

ENV APP_ENV=development

# Install dev dependencies
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Configure Xdebug (adjust as needed)
# RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy existing application directory contents (useful for development with volume mounts)
COPY . /var/www/html

# Install composer dependencies including dev
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]

# --- Production Stage ---
# This stage builds a lean image for production
FROM base AS production

ENV APP_ENV=production

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install --no-dev --no-scripts --no-progress --no-interaction --optimize-autoloader

# Copy application code
COPY . .

# Generate optimized class loader
# RUN composer dump-autoload --optimize

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
