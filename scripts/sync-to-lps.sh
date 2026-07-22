#!/usr/bin/env bash
# Compila la SPA y sincroniza el bundle a lps-aia (public/pdc-app/).
# El deploy a SiteGround es el de lps-aia (git pull): el bundle viaja commiteado.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LPS_DIR="${LPS_DIR:-$REPO_DIR/../lps-aia}"
DEST="$LPS_DIR/public/pdc-app"

if [ ! -d "$LPS_DIR/public" ]; then
  echo "ERROR: no encuentro lps-aia en $LPS_DIR (exporta LPS_DIR si vive en otra ruta)" >&2
  exit 1
fi

cd "$REPO_DIR"
npm run build

rm -rf "$DEST"
mkdir -p "$DEST"
cp -R "$REPO_DIR/dist/." "$DEST/"

echo "OK: bundle sincronizado en $DEST"
ls -l "$DEST/assets"
