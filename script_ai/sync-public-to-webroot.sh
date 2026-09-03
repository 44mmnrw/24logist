#!/usr/bin/env bash
# Copy Laravel public assets (.app/public/*) to the web root (parent of .app).
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB_DIR="$(dirname "$APP_DIR")"

if [[ ! -f "${APP_DIR}/artisan" ]]; then
  echo "[ERROR] artisan not found in ${APP_DIR}"
  exit 1
fi

if [[ ! -f "${WEB_DIR}/index.php" ]]; then
  echo "[ERROR] index.php not found in web root ${WEB_DIR}"
  exit 1
fi

echo "[sync] app=${APP_DIR}"
echo "[sync] web=${WEB_DIR}"

for stale in robots.txt sitemap.xml; do
  if [[ -f "${WEB_DIR}/${stale}" ]]; then
    rm -f "${WEB_DIR}/${stale}"
    echo "[sync] removed stale ${stale} (served by Laravel)"
  fi
done

for name in build css js fonts images sounds; do
  if [[ ! -d "${APP_DIR}/public/${name}" ]]; then
    continue
  fi

  mkdir -p "${WEB_DIR}/${name}"
  rsync -a --delete "${APP_DIR}/public/${name}/" "${WEB_DIR}/${name}/"
  echo "[sync] ${name}/ OK"
done

# Empty favicon.ico in web root blocks Laravel route and breaks Yandex indexing.
if [[ -f "${WEB_DIR}/favicon.ico" ]] && [[ ! -s "${WEB_DIR}/favicon.ico" ]]; then
  rm -f "${WEB_DIR}/favicon.ico"
  echo "[sync] removed empty favicon.ico (served by Laravel /favicon.ico)"
fi

if [[ -f "${APP_DIR}/public/favicon.ico" ]] && [[ -s "${APP_DIR}/public/favicon.ico" ]]; then
  cp -f "${APP_DIR}/public/favicon.ico" "${WEB_DIR}/favicon.ico"
  echo "[sync] favicon.ico OK"
fi

ln -sfn "${APP_DIR}/storage/app/public" "${WEB_DIR}/storage"

if [[ -f "${APP_DIR}/script_ai/production-web-index.php" ]]; then
  cp -f "${APP_DIR}/script_ai/production-web-index.php" "${WEB_DIR}/index.php"
  echo "[sync] index.php OK"
fi

echo "[sync] storage -> .app/storage/app/public"
echo "[sync] done"
