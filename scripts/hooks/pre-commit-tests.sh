#!/bin/bash
# scripts/hooks/pre-commit-tests.sh
#
# Hook PreToolUse (matcher: Bash) — corre la suite del proyecto ANTES de dejar
# commitear. Es configuración de ESTE repo, no del harness global.
#
# Vive en scripts/ y no en .claude/ porque `.claude/` está en .gitignore (línea
# 219) y nada de ahí se versiona: un hook alojado allí moriría con el worktree y
# no llegaría a ningún otro clon. La LÓGICA viaja en git; el REGISTRO que lo
# activa es config personal por máquina y va en .claude/settings.json:
#
#   "PreToolUse": [{ "matcher": "Bash", "hooks": [{ "type": "command",
#     "command": "\"${CLAUDE_PROJECT_DIR}/scripts/hooks/pre-commit-tests.sh\"",
#     "timeout": 150 }] }]
#
# Por qué existe
# --------------
# El gate global (~/.claude/hooks/gate/pre-commit-verify.sh) detecta el runner
# del proyecto y lo ejecuta EN EL HOST. En lps-aia eso nunca puede funcionar:
# ni `composer` ni `php` existen en el host — todo corre en Docker (CLAUDE.md
# §Runtime). El global lo sabe y hace fail-open con un aviso, que es la
# decisión correcta para él: un hook compartido entre proyectos no debe
# suponer que todos están dockerizados.
#
# Consecuencia medida el 2026-08-19: en este repo la verificación automática al
# commitear era CERO, no parcial. El peso entero quedaba en scripts/publicar.sh,
# que solo actúa al cerrar el frente — se podía commitear con la suite roja y no
# enterarse hasta la publicación.
#
# Este hook tapa ese hueco donde corresponde: en el repo que sabe cómo se
# ejecutan sus propias pruebas. El global se queda como está.
#
# Decisiones, y por qué
# ---------------------
# * Nivel `puro` (3,3s: 24 scripts + 51 tests PHPUnit). No toca MySQL, así que
#   corre aunque el servicio `db` esté caído y jamás lee estado compartido —
#   este repo tiene varias sesiones escribiendo a la misma base de dev. El
#   nivel `db` (17,4s) sigue siendo responsabilidad de scripts/publicar.sh al
#   cerrar el frente: rápido y frecuente al commitear, exhaustivo al publicar.
#
# * Contenedor EFÍMERO con LPS_CODE_ROOT (`run --rm --no-deps`), nunca el `app`
#   persistente. El persistente monta un solo árbol por ruta absoluta, y con
#   varios worktrees activos suele ser el de otra sesión: verificar ahí mediría
#   código ajeno y daría un verde que no dice nada de este commit.
#
# * Sin filtro por `agent_id`, a diferencia del global. El payload no distingue
#   una sesión principal de Claude de un humano tecleando, y las sesiones
#   principales también commitean sin supervisión continua. Los commits que
#   Felipe hace en su propia terminal no pasan por el harness, así que a él no
#   le llega este gate nunca.
#
# * Fail-open SOLO ante causas inequívocas de entorno ausente (docker ausente,
#   daemon caído, vendor/ sin instalar). Una suite roja bloquea, y un verde que
#   no ejecutó nada también: un runner que no corre nada sale en 0 y eso no es
#   evidencia.
#
# exit 2 en PreToolUse bloquea la herramienta y devuelve stderr al agente.
# Cualquier duda del propio mecanismo → exit 0.

set -u

INPUT="$(cat)"
command -v jq >/dev/null 2>&1 || exit 0

CMD="$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty' 2>/dev/null)"
[ -z "$CMD" ] && exit 0

# Solo el momento del commit. Todo lo demás pasa sin costo.
printf '%s' "$CMD" | grep -qE '(^|[;&|[:space:]])git[[:space:]]+(-[^[:space:]]+[[:space:]]+)*commit([[:space:]]|$)' || exit 0
printf '%s' "$CMD" | grep -qE '\-\-dry-run' && exit 0

# El cwd del payload es el de la sesión, no necesariamente donde se commitea:
# un `cd /ruta && git commit` es la forma normal de trabajar. Se saca el
# destino del propio comando y solo se cae al .cwd si no hay.
CWD="$(printf '%s' "$CMD" | sed -n 's/.*cd[[:space:]]\{1,\}\([^&;|]*\).*/\1/p' | head -1 | sed 's/[[:space:]]*$//' | tr -d "\"'")"
if [ -z "$CWD" ] || [ ! -d "$CWD" ]; then
  CWD="$(printf '%s' "$INPUT" | jq -r '.cwd // empty' 2>/dev/null)"
fi
[ -z "$CWD" ] && exit 0
[ -d "$CWD" ] || exit 0
ROOT="$(cd "$CWD" 2>/dev/null && git rev-parse --show-toplevel 2>/dev/null)"
[ -z "$ROOT" ] && exit 0
cd "$ROOT" 2>/dev/null || exit 0

# Este hook es de lps-aia. Si el commit ocurre en otro repo (un subagente puede
# tener cualquier cwd), no es asunto suyo.
[ -f "$ROOT/scripts/run-php-tests.php" ] || exit 0
[ -f "$ROOT/docker-compose.yml" ] || exit 0

fail_open() {
  {
    echo "gate lps-aia: no pude verificar, así que NO bloqueo el commit."
    echo "$1"
    echo "Esto no es una suite en rojo, pero tampoco es verde: nadie corrió los tests."
    [ -n "${2:-}" ] && { echo "--- salida real ---"; printf '%s' "$2" | tail -10; }
  } >&2
  exit 0
}

command -v docker >/dev/null 2>&1 \
  || fail_open "docker no está en el PATH, y este proyecto solo corre dentro del contenedor."
docker info >/dev/null 2>&1 \
  || fail_open "el daemon de Docker no responde (¿Docker Desktop apagado?)."
[ -f "$ROOT/vendor/autoload.php" ] \
  || fail_open "falta vendor/ en este árbol. Instalá con: LPS_CODE_ROOT=\"$ROOT\" docker compose run --rm --no-deps app composer install"

OUT="$(cd "$ROOT" && LPS_CODE_ROOT="$ROOT" docker compose run --rm --no-deps app \
        php scripts/run-php-tests.php --nivel=puro 2>&1)"
RC=$?

# Un runner que no pudo arrancar no es una suite roja.
[ "$RC" -eq 127 ] && fail_open "el runner no pudo ejecutarse dentro del contenedor." "$OUT"

if [ "$RC" -eq 0 ]; then
  # Verde sin ejecutar nada tampoco es evidencia.
  if printf '%s' "$OUT" | grep -qE '=== 0 corridos'; then
    {
      echo "gate lps-aia: COMMIT BLOQUEADO — la suite salió en 0 pero no ejecutó ningún test."
      echo "Un runner que no corre nada también sale verde; eso no es evidencia."
      printf '%s' "$OUT" | tail -15
    } >&2
    exit 2
  fi
  exit 0
fi

{
  echo "gate lps-aia: COMMIT BLOQUEADO — la suite está en rojo."
  echo "Verificado de forma independiente, sin leer lo que reportaste:"
  echo "  LPS_CODE_ROOT=\"$ROOT\" docker compose run --rm --no-deps app php scripts/run-php-tests.php --nivel=puro"
  echo "exit code: $RC"
  echo "Arreglá los tests y volvé a intentar. Usar --no-verify no evita este control."
  echo "--- salida real ---"
  printf '%s' "$OUT" | tail -30
} >&2
exit 2
