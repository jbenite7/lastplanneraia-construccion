---
tipo: trampa
estado: vigente
fecha: 2026-08-07
areas: [qa, design-system, worktrees, docker]
fuente: sesion
resumen: "npm run test:design-system:static da un rojo falso en node-tests desde un worktree secundario: docker-compose.yml fija name: last-planner-aia, así que el PHP se ejecuta en el contenedor del worktree principal y compara mtimes de otro árbol"
---
**Desde un worktree secundario, `npm run test:design-system:static` puede dar 7/8 sin que nada esté
roto.** Medido el 2026-08-07 al cerrar la campaña de dark mode: `node-tests` en rojo con

```
AssertionError: entrypoint 1786064302 is older than tokens 1786131838
  tests/design-system/foundation.test.mjs:279
```

**La causa no está en el CSS ni en los mtimes del worktree que ejecuta la prueba** — allí ambos
archivos tenían el *mismo* mtime al segundo. Está en dónde se ejecuta el PHP:

`foundation.test.mjs:10-22` define `runPhpInApp()`, que lanza `docker compose exec -T app php -r` (o
`compose run --rm --no-deps app` si no hay servicio arriba) con `cwd` en la raíz del repo; y
`docker-compose.yml:1` fija **`name: last-planner-aia`**, un nombre de proyecto fijo, no derivado del
directorio. Por eso el `compose exec` de un worktree secundario aterriza en el contenedor **del
worktree principal**, que por su `docker-compose.override.yml` monta *ese otro* árbol: el PHP
renderiza `DesignSystemHeadComponent` leyendo los mtimes del árbol ajeno y los compara contra el
`statSync` local de `public/css/tokens.css`, que sí es del worktree que ejecuta. Dos árboles
distintos dentro de la misma aserción.

**El arreglo es una variable de entorno, no un cambio de código:**

```bash
COMPOSE_PROJECT_NAME=<nombre-propio> npm run test:design-system:static
```

Con eso la suite dio **8/8** sobre el mismo árbol, sin tocar un solo archivo. No hace falta publicar
puertos ni levantar el stack: sin servicios corriendo en tu proyecto, el propio helper cae a
`compose run --rm --no-deps app`, que no publica nada y no colisiona con el stack del humano.

**Por qué importa más allá de esta prueba:** cualquier verificación que ejecute PHP dentro del
contenedor tiene el mismo defecto desde un worktree secundario, y falla del lado peligroso —
*también* puede dar **verde** midiendo el árbol de otra sesión. Un 8/8 obtenido sin
`COMPOSE_PROJECT_NAME` propio no dice nada sobre tu código. Ojo especialmente en
[[goals/cierre-version-1-1-0-design-system/goal|cierre-version-1-1-0-design-system]], cuya condición
de hecho es literalmente «la suite estática en 8/8».

Relacionadas: [[qa-y-gates]], [[design-system]], [[gate-visual-tolerancia-enganosa]].
