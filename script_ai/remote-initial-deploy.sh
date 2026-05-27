#!/usr/bin/env bash
set -euo pipefail

APP=/var/www/logist_sys/data/24logistru
WEB=/var/www/logist_sys/data/www/24logist.ru

cd "$APP"

php composer.phar install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-intl

if [[ ! -f .env ]]; then
    cat > .env <<'ENVEOF'
APP_NAME="ЛогистРу"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://24logist.ru

APP_LOCALE=ru
APP_FALLBACK_LOCALE=ru
APP_FAKER_LOCALE=ru_RU

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=24logist_ru
DB_USERNAME=24logist_ru
DB_PASSWORD="`K|MPx&A~JQ(h{x+"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
FILESYSTEM_PUBLIC_URL=/storage
QUEUE_CONNECTION=database
CACHE_STORE=database

LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@24logist.ru"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
ENVEOF
    php artisan key:generate --force
fi

if [[ -f "$WEB/index.php" && ! -L "$WEB" ]]; then
    mv "$WEB" "${WEB}.placeholder.bak"
fi
ln -sfn "$APP/public" "$WEB"

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force || true
chmod -R ug+rwx storage bootstrap/cache
php artisan config:clear
php artisan route:clear
php artisan route:clear
php artisan view:cache

echo DEPLOY_OK
