#!/usr/bin/env bash
# Gate de exclusión de T01-Tarea 3 (spec 2026-08-30-t01-shell-runtime-react-design §10.2).
#
# Comprueba que los archivos tocados desde HEAD (working tree + staged + no rastreados) no
# entren a ninguno de los subsistemas que esta tarea tiene prohibido modificar: /admin/,
# RLS/DataScope, esquema/migraciones de base de datos, grants, usuarios, credenciales, seeds y
# fixtures persistentes. No es un test de CI (compara contra HEAD, que se mueve con cada
# commit): es el gate manual del paso 5/6 del brief, para correr antes de cerrar la tarea.
#
# Uso: scripts/shell-navigation-exclusion-gate.sh
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

PATRONES_PROHIBIDOS=(
  '^admin/'
  'RLS'
  'DataScope'
  '^database/'
  '^migrations/'
  'migration'
  'grants?'
  '^src/Security/RbacCatalog\.php$' # se preserva, no se toca (AGENTS.md)
  'seeds?/'
  'fixtures?/.*\.sql$'
  '\.sql$'
)

archivos_tocados="$(
  {
    git diff --name-only HEAD
    git diff --name-only --cached HEAD
    git ls-files --others --exclude-standard
  } | sort -u
)"

if [ -z "$archivos_tocados" ]; then
  echo "Sin cambios frente a HEAD; nada que verificar."
  exit 0
fi

fallo=0
while IFS= read -r archivo; do
  [ -z "$archivo" ] && continue
  for patron in "${PATRONES_PROHIBIDOS[@]}"; do
    if echo "$archivo" | grep -Eq "$patron"; then
      echo "PROHIBIDO: '$archivo' coincide con el patrón excluido '$patron'"
      fallo=1
    fi
  done
done <<< "$archivos_tocados"

if [ "$fallo" -ne 0 ]; then
  echo ""
  echo "Gate de exclusión: FALLO — hay cambios en subsistemas excluidos de T01-Tarea 3."
  exit 1
fi

echo "Gate de exclusión: OK — $(echo "$archivos_tocados" | wc -l | tr -d ' ') archivo(s) tocados, ninguno en subsistema excluido."
echo "$archivos_tocados" | sed 's/^/  - /'
