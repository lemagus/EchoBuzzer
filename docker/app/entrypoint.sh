#!/usr/bin/env bash
set -euo pipefail

cd /var/www

if [ ! -f artisan ]; then
  echo "[init] Creating Laravel project..."
  composer create-project laravel/laravel /tmp/laravel --no-interaction --prefer-dist
  cp -R /tmp/laravel/. /var/www/
fi

# Ensure required PHP packages exist
if ! grep -q "inertiajs/inertia-laravel" composer.json; then
  echo "[init] Installing PHP deps (Inertia/Reverb)..."
  composer require inertiajs/inertia-laravel laravel/reverb --no-interaction --prefer-dist
fi

if [ ! -f vendor/autoload.php ]; then
  echo "[init] Installing composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Overlay project stubs only once
if [ -d /stubs ] && [ ! -f /var/www/.stubs_applied ]; then
  echo "[init] Applying project stubs..."
  cp -R /stubs/. /var/www/
  touch /var/www/.stubs_applied
fi

# Generate key if missing (prefer .env)
if [ -f .env ] && grep -q "^APP_KEY=" .env; then
  echo "[init] APP_KEY found in .env"
else
  echo "[init] Generating APP_KEY..."
  php artisan key:generate --force
fi

php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

exec "$@"
