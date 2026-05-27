#!/usr/bin/env bash
set -euo pipefail

WEB=/var/www/logist_sys/data/www/24logist.ru
APP="${WEB}/.app"

mkdir -p "${WEB}"

if [[ ! -d "${APP}/.git" ]]; then
  rm -rf "${APP}"
  mkdir -p "${APP}"
  git clone --branch main https://github.com/44mmnrw/24logist.git "${APP}"
fi

cd "${APP}"

if [[ ! -f composer.phar ]]; then
  curl -sS https://getcomposer.org/installer | php
fi

php composer.phar install --no-dev --optimize-autoloader --no-interaction

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

php artisan key:generate --force

bash script_ai/patch-livewire-upload.sh

php artisan migrate --force
php artisan db:seed --force

chmod +x script_ai/sync-public-to-webroot.sh
bash script_ai/sync-public-to-webroot.sh

php artisan optimize:clear
php artisan view:cache

echo DEPLOY_OK
