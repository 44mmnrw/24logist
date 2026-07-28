#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f package.json ]]; then
  echo "[ERROR] package.json not found in $(pwd)"
  exit 1
fi

export PATH="$HOME/bin:$HOME/opt/node/bin:$HOME/.nvm/versions/node/v24.13.1/bin:$PATH"
export NODE_OPTIONS="--max-old-space-size=${BUILD_NODE_MAX_OLD_SPACE:-512}"
export CI=1

if ! command -v node >/dev/null 2>&1 || ! command -v npm >/dev/null 2>&1; then
  echo "[ERROR] node/npm not found. PATH=$PATH"
  echo "[HINT] Run once: bash script_ai/install-node-server.sh"
  exit 127
fi

echo "[REMOTE] node: $(command -v node) $(node -v)"
echo "[REMOTE] npm:  $(command -v npm) $(npm -v)"
echo "[REMOTE] frontend-only build mode (no artisan/cache reset)..."
rm -f public/hot
rm -rf node_modules/.vite public/build.__new

echo "[REMOTE] npm ci (max ${BUILD_NPM_CI_TIMEOUT_MINUTES:-25}m)..."
(
  while sleep 60; do
    echo "[REMOTE] npm ci still running..."
  done
) &
NPM_TICK_PID=$!
NPM_EXIT=0
timeout --foreground -k 30s "${BUILD_NPM_CI_TIMEOUT_MINUTES:-25}m" \
  env CI=1 npm ci --no-audit --no-fund --fetch-retries=5 --fetch-retry-mintimeout=20000 --fetch-retry-maxtimeout=120000 || NPM_EXIT=$?
kill "${NPM_TICK_PID}" >/dev/null 2>&1 || true
wait "${NPM_TICK_PID}" 2>/dev/null || true

if [[ "${NPM_EXIT}" -eq 124 ]]; then
  echo "[ERROR] npm ci timeout"
  exit 124
fi
if [[ "${NPM_EXIT}" -ne 0 ]]; then
  echo "[ERROR] npm ci failed: ${NPM_EXIT}"
  exit "${NPM_EXIT}"
fi

echo "[REMOTE] sharp runtime check..."
node -e "
  const sharp = require('sharp');
  if (!sharp.format.webp?.output?.file || !sharp.format.heif?.output?.file) {
    throw new Error('sharp has no WebP or AVIF/HEIF output support');
  }
  console.log('[REMOTE] sharp', sharp.versions.sharp, 'libvips', sharp.versions.vips, 'WebP+AVIF OK');
"

echo "[REMOTE] vite build (max ${BUILD_TIMEOUT_MINUTES:-20}m)..."
timeout --foreground -k 30s "${BUILD_TIMEOUT_MINUTES:-20}m" bash -c "
  export PATH=\"$PATH\"
  npm run build -- --outDir public/build.__new --logLevel info &
  BUILD_PID=\$!
  while kill -0 \"\$BUILD_PID\" 2>/dev/null; do
    echo '[REMOTE] vite build in progress...'
    sleep 30
  done
  wait \"\$BUILD_PID\"
"

if [[ ! -f public/build.__new/manifest.json ]]; then
  echo "[ERROR] public/build.__new/manifest.json missing"
  exit 1
fi

echo "[REMOTE] swap build directory atomically..."
rm -rf public/build.__old
if [[ -d public/build ]]; then
  mv public/build public/build.__old
fi
mv public/build.__new public/build
rm -rf public/build.__old

echo "[REMOTE] Frontend build OK"
stat -c "%y %n" public/build/manifest.json 2>/dev/null || ls -la public/build/manifest.json
