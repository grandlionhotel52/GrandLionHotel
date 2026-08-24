#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
  export DB_HOST="${MYSQLHOST}"
fi

if [ -z "${DB_PORT:-}" ] && [ -n "${MYSQLPORT:-}" ]; then
  export DB_PORT="${MYSQLPORT}"
fi

if [ -z "${DB_DATABASE:-}" ] && [ -n "${MYSQLDATABASE:-}" ]; then
  export DB_DATABASE="${MYSQLDATABASE}"
fi

if [ -z "${DB_USERNAME:-}" ] && [ -n "${MYSQLUSER:-}" ]; then
  export DB_USERNAME="${MYSQLUSER}"
fi

if [ -z "${DB_PASSWORD:-}" ] && [ -n "${MYSQLPASSWORD:-}" ]; then
  export DB_PASSWORD="${MYSQLPASSWORD}"
fi

if [ "${APP_ENV:-production}" = "production" ] && [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  echo "[startup] Refusing to start with SQLite in production."
  echo "[startup] Set DB_CONNECTION=mysql and provide persistent database credentials."
  exit 1
fi

if [ -z "${APP_KEY:-}" ]; then
  echo "[startup] APP_KEY is missing."
  echo "[startup] Generate one with 'php artisan key:generate --show' and add it in Render."
  exit 1
fi

mkdir -p \
  bootstrap/cache \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

if [ -L public/storage ]; then
  :
elif [ -e public/storage ]; then
  echo "[startup] public/storage already exists and is not a symlink; skipping storage:link."
else
  php artisan storage:link
fi

echo "[startup] Clearing stale Laravel caches..."
php artisan config:clear
php artisan cache:clear || true
php artisan view:clear || true

echo "[startup] Running Laravel migrations..."
attempt=1
max_attempts=10

until php artisan migrate --force; do
  if [ "$attempt" -ge "$max_attempts" ]; then
    echo "[startup] Database migration failed after ${max_attempts} attempts."
    exit 1
  fi

  echo "[startup] Database not ready yet. Retrying in 5 seconds..."
  attempt=$((attempt + 1))
  sleep 5
done

if [ "${SEED_ADMIN_ON_DEPLOY:-false}" = "true" ]; then
  if [ -z "${SEED_ADMIN_EMAIL:-}" ] || [ -z "${SEED_ADMIN_PASSWORD:-}" ]; then
    echo "[startup] SEED_ADMIN_ON_DEPLOY is enabled, but SEED_ADMIN_EMAIL or SEED_ADMIN_PASSWORD is missing."
    exit 1
  fi

  echo "[startup] Creating or updating the configured administrator..."
  php artisan db:seed --class='Database\Seeders\UserSeeder' --force
fi

echo "[startup] Optimizing Laravel for production..."
php artisan optimize

# Startup commands run as root while Apache handles requests as www-data.
# Restore ownership so file-backed cache/rate-limit directories can be
# created during web requests (for example, after a failed login attempt).
chown -R www-data:www-data storage bootstrap/cache

echo "[startup] Starting Laravel scheduler..."
php artisan schedule:work &

exec apache2-foreground
