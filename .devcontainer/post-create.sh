#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

composer install --prefer-dist --no-interaction --no-progress || \
  composer install --prefer-source --no-interaction --no-progress

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

echo "Dev container ready. API: http://localhost:8000/api/docs"
