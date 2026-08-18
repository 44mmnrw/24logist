#!/usr/bin/env bash
# Runs on production server. Layout:
#   WEB=/var/www/logist_sys/data/www/24logist.ru   (document root)
#   APP=$WEB/.app                                  (Laravel + git)
set -euo pipefail

WEB_DIR="${DEPLOY_WEB_DIR:-/var/www/logist_sys/data/www/24logist.ru}"
APP_DIR="${DEPLOY_APP_DIR:-${WEB_DIR}/.app}"
BRANCH="${DEPLOY_BRANCH:-main}"
REPO="${DEPLOY_REPO:-https://github.com/44mmnrw/24logist.git}"
SKIP_FRONTEND_BUILD="${SKIP_FRONTEND_BUILD:-0}"

log() { printf '[deploy] %s\n' "$*"; }
die() { printf '[deploy] ERROR: %s\n' "$*" >&2; exit 1; }

[[ -d "$APP_DIR" ]] || die "App not found: $APP_DIR"
[[ -d "$WEB_DIR" ]] || die "Web root not found: $WEB_DIR"

cd "$APP_DIR"

if [[ ! -d .git ]]; then
    log "git clone into $APP_DIR"
    git clone --branch "$BRANCH" "$REPO" "$APP_DIR"
fi

log "git pull origin $BRANCH"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

if [[ ! -f composer.phar ]]; then
    curl -sS https://getcomposer.org/installer | php
fi

if ! php -m 2>/dev/null | grep -qi '^intl$'; then
    die 'PHP intl extension missing'
fi

log "composer install"
php composer.phar install --no-dev --optimize-autoloader --no-interaction --no-progress

[[ -f .env ]] || die '.env missing on server'

if grep -q '^LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=' .env; then
    sed -i 's/^LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=.*/LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public/' .env
else
    echo 'LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=public' >> .env
fi

log "livewire upload patch"
sed -i 's/\r$//' script_ai/patch-livewire-upload.sh
bash script_ai/patch-livewire-upload.sh

if [[ "$SKIP_FRONTEND_BUILD" != "1" ]]; then
    log "frontend build"
    chmod +x script_ai/remote_frontend_build.sh
    bash script_ai/remote_frontend_build.sh
else
    log "skip npm build"
fi

log "sync public -> web root"
chmod +x script_ai/sync-public-to-webroot.sh
bash script_ai/sync-public-to-webroot.sh

log "artisan migrate"
php artisan migrate --force

log "seed functional section data"
php artisan db:seed --class=FunctionalSectionSeeder --force --no-interaction

log "seed growth section data"
php artisan db:seed --class=GrowthSectionSeeder --force --no-interaction

php artisan storage:link --force 2>/dev/null || true
mkdir -p storage/app/public/livewire-tmp
chmod -R ug+rwx storage bootstrap/cache
chmod 775 storage/app/public/livewire-tmp

log "generate AVIF/WebP image variants"
php artisan images:optimize --directory=landing

log "clear caches"
php artisan optimize:clear
php artisan view:cache

log "nginx static-cache profile: script_ai/nginx-performance.conf"
log "include it once inside the 24logist.ru server block, then run nginx -t && reload nginx"

log "done: $(git log -1 --oneline)"
