# Task 1 — Harness data-driven de verificación del shell sidebar

## Qué hice

Creé `tests/browser/shell-sidebar-rollout.mjs`, un test Playwright standalone (mismo
estilo que `tests/browser/shell-week-admin.mjs`) que recorre `ALL_ROUTES` (9 rutas del
brief) contra el set `MIGRATED = Set(['/programacion-intermedia'])`:

- Rutas no migradas: imprime `PENDING <ruta>` y continúa, sin contar como fallo.
- Rutas migradas: login + selección de proyecto (reutilizando el preámbulo de
  `shell-week-admin.mjs`, viewport 1180×820 dark, mutaciones interceptadas vía
  `page.route` para `api/semanal/save`, `api/semanal/auto-program`,
  `nueva_semana.php`, `eliminar_semana.php`, `verificarCICActualizada.php`), y corre 5
  checks agrupados con prefijo `[Label]`:
  1. default colapsado (`data-sidebar-state="collapsed"` al cargar)
  2. toggle expande y colapsa (`[data-sidebar-toggle]`, 450ms entre clicks)
  3. cero-scroll del nav en ambos estados (`.aia-sidebar__nav` scrollHeight ≤
     clientHeight+1)
  4. sin overflow horizontal del documento en ambos estados
  5. ítem activo con `[data-destination-id="<active>"][aria-current="page"]`

  Al terminar cada ruta, deja el sidebar en `collapsed` para no filtrar estado entre
  rutas (mismo `page` reutilizado).

Antes de escribir el harness confirmé los selectores contra el código real (no
inventados): `src/View/Components/DesignSystemComponent.php` genera
`data-shell-pattern="sidebar"`, `data-sidebar-state`, `data-sidebar-toggle`,
`data-destination-id`, `aria-current="page"` exactamente como los usa
`shell-week-admin.mjs`, así que no fue necesario ajustar nada del brief — encajó
verbatim.

## Desviación necesaria (no anticipada por el brief)

`tests/browser/shell-sidebar-rollout.mjs` caía bajo la regla whitelist de
`.gitignore` (`tests/browser/*` + negaciones explícitas por archivo, líneas
121–164). Sin una línea `!tests/browser/shell-sidebar-rollout.mjs`, `git add` de ese
archivo era rechazado (`ignored by one of your .gitignore files`) y el commit
"solo con el archivo nuevo" habría sido literalmente imposible. Agregué una línea a
`.gitignore` siguiendo el mismo patrón exacto que las 40+ entradas existentes
(justo después de `!tests/browser/shell-week-admin.mjs`). Es el único archivo
adicional tocado; no incluí nada del trabajo PDC presente en el árbol.

## Verificación ejecutada

```
node tests/browser/shell-sidebar-rollout.mjs
```

Salida (contenedor `app`/`db` ya arriba, app servida en `http://localhost:8081`):

```
PASS [Programación Intermedia] default colapsado — sidebarState=collapsed
PASS [Programación Intermedia] toggle expande y colapsa — expanded=expanded recollapsed=collapsed
PASS [Programación Intermedia] cero-scroll del nav en ambos estados — collapsed=true expanded=true
PASS [Programación Intermedia] sin overflow horizontal en ambos estados — expanded=true collapsed=true
PASS [Programación Intermedia] ítem activo con aria-current — active=programacion-intermedia found=true
PENDING /programa-general
PENDING /profesionales
PENDING /subcontratistas
PENDING /control-cambios
PENDING /programa-general-actualizar
PENDING /programacion-semanal
PENDING /indicadores
PENDING /bi/control-tower

5/5 checks OK
```

Exit code: `0`. Ejecutado dos veces (antes y después del commit) con el mismo
resultado.

## Commit

```
846afec test(shell-sidebar): harness data-driven de rollout (PI verde, resto pending)
```

Archivos: `tests/browser/shell-sidebar-rollout.mjs` (nuevo, 122 líneas) y
`.gitignore` (+1 línea, ver desviación arriba). Verifiqué el staging antes de
commitear (`git status --short` mostraba únicamente `M .gitignore` y
`A tests/browser/shell-sidebar-rollout.mjs`) — el trabajo PDC no relacionado del
árbol no quedó incluido.

## Status

DONE
