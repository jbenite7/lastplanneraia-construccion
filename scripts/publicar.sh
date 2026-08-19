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

# El invariante que hay que garantizar antes de verificar nada:
#
#     el contenedor que responde tiene que montar EL ARBOL QUE ESTOY VERIFICANDO.
#
# `docker-compose.yml` fija `name: last-planner-aia`, asi que `docker compose exec app`
# de cualquier worktree aterriza en el contenedor compartido, y
# `docker-compose.override.yml` lo monta desde `${LPS_CODE_ROOT:-<checkout principal>}`.
# Medido el 2026-08-18: este script dio tres verdes desde un worktree en `06627082`
# mientras el contenedor servia el principal en `081a33c8`. Un gate obligatorio que
# avala con evidencia de otro arbol es peor que no tener gate.
#
# Ese defecto se ataco fabricando un `COMPOSE_PROJECT_NAME` propio por sha. Fallo por
# el otro lado, medido el 2026-08-19: ese proyecto no tiene NINGUN contenedor, y
# `tests/design-system/foundation.test.mjs:12-19` elige camino preguntandole a docker
# que servicios corren — sin `app` arriba cae a `compose run`, que levanta un
# contenedor nuevo y revienta el tope de 180 s de `node-tests`. 445 tests, 444 pasaron,
# 0 fallaron, 1 cancelado, y el gate denegaba. Levantar el stack aislado tampoco vale:
# 8081, 3307 y 8082 son puertos fijos y chocarian.
#
# Un entorno vacio miente igual que uno ajeno: dice «aqui no hay nada que te
# contradiga» cuando lo que pasa es que no hay nada. Asi que el invariante se
# COMPRUEBA en vez de fabricarse. Ver
# memoria/trampas/publicar-sh-se-aisla-y-se-rompe-en-la-raiz.md y su hermana
# suite-estatica-miente-en-worktree-secundario.md
export LPS_CODE_ROOT="$PWD"

# Principal o enlazado. En el principal `--absolute-git-dir` y `--git-common-dir`
# resuelven a la misma ruta; en uno enlazado el primero cuelga de
# `.git/worktrees/<nombre>`. No decide SI se comprueba —se comprueba siempre— sino
# que remedio se imprime cuando falla, que es distinto en cada caso.
GIT_DIR_ABS=$(cd "$(git rev-parse --absolute-git-dir 2>/dev/null || echo .)" && pwd -P 2>/dev/null || echo "?")
GIT_COMMON_ABS=$(cd "$(git rev-parse --git-common-dir 2>/dev/null || echo .)" && pwd -P 2>/dev/null || echo "?")
if [ "$GIT_DIR_ABS" = "$GIT_COMMON_ABS" ]; then ES_PRINCIPAL=1; else ES_PRINCIPAL=0; fi

# Que monta ahora mismo el contenedor `app`, si es que corre alguno.
CID=$(docker compose ps -q app 2>/dev/null | head -1)
if [ -n "$CID" ]; then
  MONTADO=$(docker inspect "$CID" \
    --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' \
    2>/dev/null)
  MONTADO_REAL=$(cd "$MONTADO" 2>/dev/null && pwd -P || echo "$MONTADO")
  AQUI=$(pwd -P)
  if [ "$MONTADO_REAL" != "$AQUI" ]; then
    echo "DENEGADO: el contenedor 'app' no sirve el arbol que ibas a verificar."
    echo "  monta:    ${MONTADO_REAL:-<no se pudo leer>}"
    echo "  verificas: $AQUI"
    echo
    echo "Un verde medido contra otro arbol no dice nada de este. Apunta el contenedor aqui:"
    echo "  LPS_CODE_ROOT=\"\$(pwd)\" docker compose up -d app"
    if [ "$ES_PRINCIPAL" -eq 1 ]; then
      echo "Estas en el worktree principal: al terminar no hace falta devolverlo, ya es su sitio."
    else
      echo "Estas en un worktree enlazado: al terminar devuelvelo a la raiz del repo,"
      echo "o la proxima sesion que verifique alli se encontrara con el tuyo."
    fi
    exit 1
  fi
else
  echo "AVISO: no hay contenedor 'app' corriendo. Las pruebas que hablan con docker"
  echo "levantaran uno por invocacion ('compose run'), y 'node-tests' puede agotar su"
  echo "tope de 180 s. Si eso pasa, arranca el stack: docker compose up -d app"
fi

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
# La wiki va en DOS comprobaciones porque mezclaba dos cosas de naturaleza distinta,
# y una sola severidad no podia servir a las dos:
#
#   - La FORMA (enlaces rotos, frontmatter incompleto, una fuente sin declarar) es un
#     defecto de lo que vas a publicar. Bloquea.
#   - La ALARMA DE VERACIDAD es un contador de commits: pide trabajo, pero no dice que
#     lo que vas a publicar este mal. Avisa, y bloquear con ella ensenaria a saltarse
#     el script entero -razon original de que la wiki no bloqueara, y sigue siendo cierta-.
#
# Juntas, o se bloqueaba por un contador o no se bloqueaba por un defecto real. Se separaron
# el 2026-08-19, despues de medir TRES veces el mismo hueco: un merge traia un documento sin
# declarar, el lint estricto lo reportaba, el semaforo de avisos lo dejaba pasar, y el arreglo
# llegaba siempre DESPUES de publicar.
#
# El mensaje del hallazgo lleva dentro el comando exacto que lo arregla, porque a quien le cae
# encima suele ser alguien de otro frente que acaba de crear un documento y no tiene por que
# conocer este esquema.
comprobar "wiki (forma)"            1 npm run test:wiki:forma
comprobar "wiki (veracidad + pruebas)" 0 npm run test:wiki

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
