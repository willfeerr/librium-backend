#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
  composer install --prefer-dist --no-interaction --no-progress || \
    composer install --prefer-source --no-interaction --no-progress
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan storage:link || true

echo "Waiting for PostgreSQL..."
until php -r '
$host = getenv("DB_HOST") ?: "postgres";
$port = getenv("DB_PORT") ?: "5432";
$database = getenv("DB_DATABASE") ?: "bookmarket";
$username = getenv("DB_USERNAME") ?: "bookmarket";
$password = getenv("DB_PASSWORD") ?: "bookmarket";
new PDO("pgsql:host={$host};port={$port};dbname={$database}", $username, $password);
' >/dev/null 2>&1; do
  sleep 2
done

php artisan migrate --force
php artisan optimize:clear

echo "Laravel dev server running on http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
