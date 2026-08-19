---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-publicar-sh-invariante-de-montaje-design.md
resumen: Autorizado por el usuario el 2026-08-19. Toca un gate, y los gates son contrato: por eso lleva spec.
---

# `publicar.sh`: el invariante es el montaje, no el nombre del proyecto — diseño

**Autorizado por el usuario el 2026-08-19.** Toca un gate, y los gates son contrato: por eso lleva
spec.

## El problema, en una frase

El gate se rompe a sí mismo en el worktree principal, y su arreglo literal no alcanza para los
secundarios.

## Los dos hechos medidos

**Uno.** `scripts/publicar.sh:28-29` exporta `LPS_CODE_ROOT="$PWD"` y
`COMPOSE_PROJECT_NAME="lps-aia-publicar-<sha>"`. Ese proyecto **no tiene contenedores**, y
`tests/design-system/foundation.test.mjs:12-19` elige camino preguntándole a Docker qué servicios
corren: sin `app` arriba cae a `compose run`, que levanta un contenedor nuevo y revienta el tope de
180 s de `node-tests`. Resultado el 2026-08-19: **445 tests, 444 pasan, 0 fallan, 1 cancelado**, y
el gate deniega. Ver [[publicar-sh-se-aisla-y-se-rompe-en-la-raiz]].

**Dos.** No se arregla levantando el stack aislado: `docker-compose.yml` publica 8081, 3307 y 8082
con puertos fijos, y los dos primeros sin variable. Un segundo stack choca.

## Por qué el arreglo literal no basta

La instrucción fue «que el aislamiento se aplique solo en worktrees secundarios, comparando
`git-common-dir` con `git-dir`». Eso arregla el principal y **deja rotos los cuatro secundarios**,
que son justamente donde están trabajando las sesiones de ejecución: allí el aislamiento sigue
puesto y el timeout sigue ocurriendo.

## Qué se decide

**El aislamiento por nombre de proyecto se retira. Lo sustituye una comprobación directa del
invariante que ese aislamiento intentaba garantizar.**

El invariante nunca fue «cada worktree tiene su proyecto compose». Era, y sigue siendo:

> El contenedor que responde tiene que montar **el árbol que estoy verificando**.

Se comprueba, en vez de fabricarse:

- `LPS_CODE_ROOT="$PWD"` se mantiene: sigue diciendo qué árbol montar.
- `COMPOSE_PROJECT_NAME` deja de forzarse.
- Antes de verificar, el script pregunta al contenedor `app` qué monta en `/var/www/html` y lo
  compara con `$PWD` resuelto. Si no coinciden, **deniega** e imprime el comando exacto que lo
  arregla: `LPS_CODE_ROOT="$(pwd)" docker compose up -d app`.

`git rev-parse --absolute-git-dir` frente a `--git-common-dir` se conserva como discriminador,
que es lo que el usuario pidió: en el principal son la misma ruta, en un worktree enlazado el
primero cuelga de `.git/worktrees/<nombre>`. Se usa para **explicar** el fallo con el remedio que
corresponde a cada caso, no para decidir si se comprueba — se comprueba siempre.

## Por qué esto es más estricto, y no menos

El defecto que originó el aislamiento el 2026-08-18 fue un gate que dio tres verdes desde un
worktree en `06627082` mientras el contenedor servía el principal en `081a33c8`. Con esta versión
ese caso exacto **deniega**, y dice por qué. El aislamiento lo evitaba fabricando un entorno vacío;
la comprobación lo caza mirando el real. Un entorno vacío también miente: dice «aquí no hay nada
que contradiga» cuando lo que ocurre es que no hay nada.

## Posture

- **No tocar `foundation.test.mjs`** ni el tope de 180 s de la suite: son de otro dueño y el defecto
  no está ahí. El test hace lo correcto — pregunta antes de elegir camino.
- **No tocar `docker-compose.yml`** ni los puertos.
- **No relajar ninguna de las comprobaciones** que el script ya hace, ni su orden.
- **Sin dependencias nuevas**: `git rev-parse`, `docker inspect` y `pwd -P`.

## Leer primero

- `scripts/publicar.sh` entero — sobre todo los comentarios, que llevan el porqué de cada línea.
- `memoria/trampas/publicar-sh-se-aisla-y-se-rompe-en-la-raiz.md`
- `memoria/trampas/suite-estatica-miente-en-worktree-secundario.md` — el defecto original.
- `AGENTS.md` §Publicación, los 9 pasos.

## Condición de hecho

Desde el worktree principal, `bash scripts/publicar.sh --solo-verificar` termina en `RC=0` con las
tres comprobaciones en verde. Desde un worktree secundario cuyo contenedor sirve otro árbol,
deniega nombrando las dos rutas y el comando de remedio. Ambas cosas demostradas con salida real.
