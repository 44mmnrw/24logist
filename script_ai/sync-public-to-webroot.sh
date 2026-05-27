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

for name in build css js fonts; do
  if [[ ! -d "${APP_DIR}/public/${name}" ]]; then
    continue
  fi

  mkdir -p "${WEB_DIR}/${name}"
  rsync -a --delete "${APP_DIR}/public/${name}/" "${WEB_DIR}/${name}/"
  echo "[sync] ${name}/ OK"
done

if [[ -f "${APP_DIR}/public/favicon.ico" ]]; then
  cp -f "${APP_DIR}/public/favicon.ico" "${WEB_DIR}/favicon.ico"
fi

ln -sfn "${APP_DIR}/storage/app/public" "${WEB_DIR}/storage"

if [[ -f "${APP_DIR}/script_ai/production-web-index.php" ]]; then
  cp -f "${APP_DIR}/script_ai/production-web-index.php" "${WEB_DIR}/index.php"
  echo "[sync] index.php OK"
fi

echo "[sync] storage -> .app/storage/app/public"
echo "[sync] done"
