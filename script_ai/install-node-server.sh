#!/usr/bin/env bash
set -eu

NODE_VER="${NODE_VER:-v24.13.1}"
INSTALL_DIR="${HOME}/opt/node"

if [ -x "${INSTALL_DIR}/bin/node" ]; then
  echo "[OK] Node already installed: $("${INSTALL_DIR}/bin/node" -v)"
  exit 0
fi

mkdir -p "${HOME}/opt"
cd "${HOME}/opt"

ARCH="linux-x64"
TARBALL="node-${NODE_VER}-${ARCH}.tar.xz"
URL="https://nodejs.org/dist/${NODE_VER}/${TARBALL}"

echo "[install] Download ${URL} ..."
curl -fsSL "${URL}" -o "${TARBALL}"
tar -xJf "${TARBALL}"
rm -rf node
mv "node-${NODE_VER}-${ARCH}" node
rm -f "${TARBALL}"

echo "[install] node: $("${INSTALL_DIR}/bin/node" -v)"
echo "[install] npm:  $(PATH="${INSTALL_DIR}/bin:${PATH}" "${INSTALL_DIR}/bin/npm" -v)"

if ! grep -q 'opt/node/bin' "${HOME}/.bashrc" 2>/dev/null; then
  echo 'export PATH="$HOME/opt/node/bin:$PATH"' >> "${HOME}/.bashrc"
fi

echo "[install] Done: ${INSTALL_DIR}"
