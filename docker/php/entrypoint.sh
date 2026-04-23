#!/usr/bin/env sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

mkdir -p storage/framework/cache
mkdir -p storage/framework/cache/livewire-components
mkdir -p storage/framework/cache/livewire-components/classes
mkdir -p storage/framework/cache/livewire-components/views
mkdir -p storage/framework/cache/livewire-components/scripts
mkdir -p storage/framework/cache/livewire-components/styles
mkdir -p storage/framework/cache/livewire-components/placeholders
mkdir -p storage/framework/views/livewire
mkdir -p storage/framework/views/livewire/classes
mkdir -p storage/framework/views/livewire/views
mkdir -p storage/framework/views/livewire/scripts
mkdir -p storage/framework/views/livewire/styles
mkdir -p storage/framework/views/livewire/placeholders
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

if [ "${DB_CONNECTION}" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    until php -r '
        $host = getenv("DB_HOST") ?: "mysql";
        $port = getenv("DB_PORT") ?: "3306";
        $db = getenv("DB_DATABASE") ?: "manager";
        $user = getenv("DB_USERNAME") ?: "manager";
        $pass = getenv("DB_PASSWORD") ?: "manager";
        try {
            new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass);
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    '; do
        sleep 2
    done
fi

exec "$@"
