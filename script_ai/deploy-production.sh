#!/usr/bin/env bash
# Runs on production server (called from deploy.sh)
set -euo pipefail

APP_DIR="${DEPLOY_APP_DIR:-/var/www/logist_sys/data/24logistru}"
BRANCH="${DEPLOY_BRANCH:-main}"
REPO="${DEPLOY_REPO:-https://github.com/44mmnrw/24logist.git}"
SKIP_FRONTEND_BUILD="${SKIP_FRONTEND_BUILD:-0}"

log() { printf '[deploy] %s\n' "$*"; }
die() { printf '[deploy] ERROR: %s\n' "$*" >&2; exit 1; }

cd "$APP_DIR" || die "App not found: $APP_DIR"

if [[ ! -d .git ]]; then
    log "git clone"
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

if [[ "$SKIP_FRONTEND_BUILD" != "1" ]]; then
    log "npm install + build"
    if ! command -v npm >/dev/null 2>&1; then
        die 'npm not found on server'
    fi
    npm ci --ignore-scripts 2>/dev/null || npm install --ignore-scripts
    npm run build
    [[ -f public/build/manifest.json ]] || die 'npm run build failed'
else
    log "skip npm build (uploaded from local machine)"
fi

log "artisan migrate"
php artisan migrate --force

php artisan storage:link --force 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

log "clear caches"
php artisan optimize:clear

log "rebuild caches"
php artisan config:cache
php artisan route:clear
php artisan view:cache

log "done: $(git log -1 --oneline)"
