#!/usr/bin/env bash
# Compila la SPA y sincroniza el bundle a lps-aia (public/pdc-app/).
# El deploy a SiteGround es el de lps-aia (git pull): el bundle viaja commiteado.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LPS_DIR="${LPS_DIR:-$REPO_DIR/../lps-aia}"
DEST="$LPS_DIR/public/pdc-app"

# Marker: antes de tocar nada (y menos hacer rm -rf) verificamos que LPS_DIR
# sea de verdad lps-aia — el shell PHP que consume este bundle debe existir.
MARKER="$LPS_DIR/views/plan-compras/app.view.php"
if [ ! -f "$MARKER" ]; then
  echo "ERROR: $LPS_DIR no parece ser lps-aia (falta $MARKER). Exporta LPS_DIR si vive en otra ruta." >&2
  exit 1
fi

cd "$REPO_DIR"
npm run build

rm -rf "$DEST"
mkdir -p "$DEST"
cp -R "$REPO_DIR/dist/." "$DEST/"

# El index.html del build es solo para dev/preview de Vite: en prod la página
# la sirve el shell PHP, y dejarlo publicado permitiría servirlo por accidente.
rm -f "$DEST/index.html"

# Trazabilidad: desde qué commit de plan-de-compras salió este bundle.
{
  echo "repo: plan-de-compras"
  echo "commit: $(git -C "$REPO_DIR" rev-parse HEAD)$(git -C "$REPO_DIR" diff --quiet HEAD 2>/dev/null || echo ' (dirty)')"
  echo "fecha: $(date -u '+%Y-%m-%dT%H:%M:%SZ')"
} > "$DEST/BUILD.txt"

echo "OK: bundle sincronizado en $DEST"
cat "$DEST/BUILD.txt"
ls -l "$DEST/assets"
