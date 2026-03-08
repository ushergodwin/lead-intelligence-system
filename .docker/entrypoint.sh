#!/bin/sh
set -e

cd /var/www/lead-intelligence-system

echo "[entrypoint] Waiting for database..."
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] Database is ready."

echo "[entrypoint] Running migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "[entrypoint] Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
