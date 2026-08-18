#!/usr/bin/env bash
# Verifica y solo entonces publica. Existe porque el gate de AGENTS.md §Publicacion
# vivia solo en la prosa, y en tres jornadas seguidas -2026-08-10, 11 y 12- tres
# sesiones distintas encadenaron la verificacion al comando de publicar y empujaron
# sin leer el resultado. Las tres veces salio benigno por suerte, no por
# procedimiento. Un gate solo gobierna si puede IMPEDIR la publicacion.
#
#   bash scripts/publicar.sh            # verifica y publica
#   bash scripts/publicar.sh --solo-verificar
#
# Lo que NO hace: desplegar. Publicar en `main` y llevarlo a la obra no son lo
# mismo, y el despliegue necesita autorizacion propia y explicita, siempre.
set -u  # -e NO: aqui se leen codigos de salida a proposito, uno por uno.

cd "$(dirname "$0")/.." || exit 2

# Aislar el entorno de verificacion al arbol de ESTE worktree. Sin esto, el gate
# miente del lado peligroso: `docker-compose.yml` fija `name: last-planner-aia`, asi
# que `docker compose exec app` de cualquier worktree aterriza en el contenedor
# compartido, y `docker-compose.override.yml` lo monta desde
# `${LPS_CODE_ROOT:-<checkout principal>}`. Resultado medido el 2026-08-18: este
# script dio los tres verdes desde un worktree en `06627082` mientras el contenedor
# servia el principal en `081a33c8` -commits distintos-. Un gate obligatorio que
# avala con evidencia de otro arbol es peor que no tener gate.
# Hacen falta las DOS variables: el nombre resuelve a que contenedor vas, la ruta
# resuelve que arbol monta. Ver
# memoria/trampas/suite-estatica-miente-en-worktree-secundario.md
export LPS_CODE_ROOT="$PWD"
export COMPOSE_PROJECT_NAME="lps-aia-publicar-$(git rev-parse --short HEAD)"

solo_verificar=0
[ "${1:-}" = "--solo-verificar" ] && solo_verificar=1

fallos=0
avisos=0

comprobar() {
  local nombre="$1"; shift
  local bloqueante="$1"; shift
  local log; log="$(mktemp)"
  "$@" > "$log" 2>&1
  # El codigo se lee AQUI, en su propia linea, sin tuberia de por medio: tras un
  # pipe, `$?` es del ultimo tramo y vale 0 pase lo que pase. En zsh, `PIPESTATUS`
  # tampoco es lo que uno cree.
  local rc=$?
  if [ "$rc" -eq 0 ]; then
    printf '  ✔ %-34s RC=0\n' "$nombre"
  elif [ "$bloqueante" -eq 1 ]; then
    printf '  ✖ %-34s RC=%s  BLOQUEA\n' "$nombre" "$rc"
    tail -n 4 "$log" | sed 's/^/      /'
    fallos=$((fallos + 1))
  else
    printf '  ⚠ %-34s RC=%s  avisa, no bloquea\n' "$nombre" "$rc"
    tail -n 2 "$log" | sed 's/^/      /'
    avisos=$((avisos + 1))
  fi
  rm -f "$log"
}

echo "Verificando sobre $(git rev-parse --short HEAD)…"
comprobar "design-system:static" 1 npm run test:design-system:static
comprobar "contrato piloto PG"   1 node tests/test_programa_general_sprint_contract.mjs
# La wiki avisa y no bloquea a proposito: su hallazgo tipico es la alarma de
# veracidad -un contador de commits-, que pide trabajo pero no dice que lo que
# vas a publicar este mal. Bloquear con ella ensenaria a saltarse el script.
comprobar "wiki (lint + veracidad)" 0 npm run test:wiki

echo
if [ "$fallos" -gt 0 ]; then
  echo "DENEGADO: $fallos comprobacion(es) bloqueante(s) en rojo. No se publica."
  exit 1
fi
[ "$avisos" -gt 0 ] && echo "Hay $avisos aviso(s). No bloquean, pero alguien tiene que mirarlos."

if [ "$solo_verificar" -eq 1 ]; then
  echo "Solo verificacion: no se publica."
  exit 0
fi

git fetch origin --quiet || { echo "DENEGADO: 'git fetch' fallo."; exit 1; }
entrantes=$(git rev-list --count HEAD..origin/main)
if [ "$entrantes" -gt 0 ]; then
  echo "DENEGADO: hay $entrantes commit(s) entrantes en origin/main."
  echo "Integra ('git merge origin/main') y VUELVE A VERIFICAR — despues de integrar, no antes."
  exit 1
fi
if [ -n "$(git status --porcelain)" ]; then
  echo "DENEGADO: el arbol tiene cambios sin commitear."
  exit 1
fi

echo "Publicando…"
# `HEAD:main`, no `main`: publica el SHA que se acaba de verificar y nada mas. Con
# `git push origin main` se publica a donde apunte `main` en el instante del push, y
# `main` es estado compartido — el 2026-08-18 otra sesion fusiono su rama entre una
# verificacion y su push, y el push se llevo dos commits ajenos que ninguna
# verificacion habia tocado. Es la regla del paso 6 de AGENTS.md, que este script
# incumplia en su ultima linea mientras existia para hacerla cumplir.
git push origin HEAD:main
