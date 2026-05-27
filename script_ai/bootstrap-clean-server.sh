#!/usr/bin/env bash
set -euo pipefail

WEB="/var/www/logist_sys/data/www/24logist.ru"
APP="${WEB}/.app"
REPO="https://github.com/44mmnrw/24logist.git"

mkdir -p "${WEB}"

if [[ ! -d "${APP}/.git" ]]; then
  rm -rf "${APP}"
  mkdir -p "${APP}"
  git clone --branch main "$REPO" "${APP}"
fi

cd "${APP}"

chmod +x script_ai/install-node-server.sh script_ai/patch-livewire-upload.sh script_ai/remote_frontend_build.sh

if [[ ! -f composer.phar ]]; then
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  rm -f composer-setup.php
fi

php composer.phar install --no-dev --optimize-autoloader --no-interaction

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

set_kv() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${value}#" .env
  else
    printf "\n%s=%s\n" "$key" "$value" >> .env
  fi
}

set_kv "APP_ENV" "production"
set_kv "APP_DEBUG" "false"
set_kv "APP_URL" "https://24logist.ru"
set_kv "DB_CONNECTION" "mysql"
set_kv "DB_HOST" "127.0.0.1"
set_kv "DB_PORT" "3306"
set_kv "DB_DATABASE" "24logist_ru"
set_kv "DB_USERNAME" "24logist_ru"
set_kv "DB_PASSWORD" "\`K|MPx&A~JQ(h{x+"
set_kv "LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK" "local"
set_kv "FILESYSTEM_PUBLIC_URL" "/storage"
set_kv "SESSION_SECURE_COOKIE" "true"

php artisan key:generate --force

chmod +x script_ai/sync-public-to-webroot.sh
bash script_ai/sync-public-to-webroot.sh

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan storage:link --force || true
chmod -R ug+rwx storage bootstrap/cache

bash script_ai/install-node-server.sh
BUILD_NODE_MAX_OLD_SPACE=512 BUILD_TIMEOUT_MINUTES=20 BUILD_NPM_CI_TIMEOUT_MINUTES=25 bash script_ai/remote_frontend_build.sh

sed -i 's/\r$//' script_ai/patch-livewire-upload.sh
bash script_ai/patch-livewire-upload.sh

php artisan view:clear
php artisan view:cache

echo "DEPLOY_OK"
