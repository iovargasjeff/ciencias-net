#!/bin/sh
set -e

echo "⏳ Esperando a PostgreSQL..."
until PGPASSWORD="${DB_PASSWORD}" pg_isready -h "${DB_HOST:-db}" -U "${DB_USERNAME:-cienciasnet_user}" -d "${DB_DATABASE:-cienciasnet}" -q; do
  sleep 2
done
echo "✓ PostgreSQL listo"

php artisan key:generate --no-interaction --force

php artisan migrate --force --no-interaction

php artisan db:seed --force --no-interaction

php artisan storage:link --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "✓ Backend listo. Iniciando PHP-FPM..."
exec "$@"
