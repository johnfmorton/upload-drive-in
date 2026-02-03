## This is a suggested deployment script for Laravel Forge for Upload Drive-in

set -euo pipefail

cd /home/forge/REPLACE-WITH-APP-PATH

# --- Ensure a clean tree and exactly match remote branch ---
# If you have submodules, uncomment the submodule lines below.
git fetch --prune origin
git reset --hard "origin/${FORGE_SITE_BRANCH}"
git clean -fd
# the following are not required for the current app setup
# git submodule sync --recursive
# git submodule update --init --recursive --force

# --- PHP deps ---
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# --- Prevent concurrent php-fpm reloads ---
touch /tmp/fpmlock 2>/dev/null || true
(
  flock -w 10 9 || exit 1
  echo 'Reloading PHP FPM...'
  sudo -S service "$FORGE_PHP_FPM" reload
) 9</tmp/fpmlock

# --- Frontend build ---
npm ci
npm run build

# --- Migrations ---
if [ -f artisan ]; then
  $FORGE_PHP artisan migrate --force
fi