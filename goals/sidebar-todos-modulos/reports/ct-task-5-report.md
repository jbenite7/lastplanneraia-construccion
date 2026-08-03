# Control Tower — Task 5: registrar las 8 rutas /bi/* en foundation-shell.json

## Status
DONE

## Commit
`ce5374f` — chore(control-tower): declarar las 8 rutas /bi/* en foundation-shell.json

## Cambios
- `tests/browser/shell-sidebar-rollout.mjs`: agregadas las 7 rutas `/bi/*` restantes
  (`/bi/programa-general`, `/bi/intermedia`, `/bi/semanal`, `/bi/pdc`, `/bi/contratistas`,
  `/bi/responsables`, `/bi/curva-s`) a `ALL_ROUTES` y `MIGRATED`, todas con
  `active: 'control-tower'` (`/bi/control-tower` ya estaba desde CT-1).
- `docs/design-system/manifests/foundation-shell.json`: `routes` pasó de 11 a 19,
  sumando las 8 rutas `/bi/*` en orden alfabético al inicio del arreglo (ordenado).

## Gates (todos verdes)
- `node tests/browser/shell-sidebar-rollout.mjs` → 98/98 checks OK, exit 0 (12 rutas × 5
  checks + 3 checks extra del cajón de filtros en `/bi/control-tower`).
- `node --test tests/design-system/shell-navigation.test.mjs` → 2 pass, 0 fail.
- `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → 20 asserts PASS,
  "Shell sidebar partial: PASS".
- `node tests/browser/shell-week-admin.mjs` → 13/13 checks OK.
- `node tests/test_foundation_shell_contract.mjs` → exit 0 (sin salida, éxito silencioso).
- `node scripts/design-system-router.mjs docs/design-system/manifests/foundation-shell.json`
  → "sin cambios de UI relevantes", exit 0.

## Concerns
- El working tree tenía cambios ajenos preexistentes no relacionados con esta tarea:
  `DESIGN.md` (modificado), `.impeccable/design.json` (untracked), `.superpowers/`
  (untracked), y además `public/css/bi-control-tower.css` / `public/js/modules/bi-spa.js`
  (modificados, trabajo de otra tarea en curso — probablemente Task 4). Ninguno se tocó ni
  se incluyó en el stage/commit; se verificó `git diff --cached --name-only` antes de
  commitear y solo contenía los 2 archivos del alcance de esta tarea.
- No se ejecutó `contracts.test` explícitamente en esta tarea (no estaba en la lista de
  gates de Task 5), pero es esperable que marque "árbol no limpio" por los archivos ajenos
  arriba mencionados — eso es un rojo ajeno preexistente, no de este commit.
- Pendiente (fuera de alcance de Task 5, nota de las instrucciones de ejecución): review
  final de rama (whole-branch) y push a origin/main tras aprobación.
