---
tipo: trampa
estado: vigente
fecha: 2026-07-23
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-branch-preexisting-red-gates
resumen: "El 2026-08-07 la suite estática del design system corre verde en sus ocho gates y ya no es una cadena && que corte en el primer rojo; lo que sigue rojo es biome (859 errores). Debajo, la historia de los rojos tolerados de main 541953e y cómo validar gates en worktrees"
---
**Reverificado el 2026-08-07: los dos rojos que esta nota daba por tolerados ya no existen, y la
cadena que los ocultaba tampoco.**

- `npm run test:design-system:static` corre **en verde sus ocho gates** (`entrypoint-partition`,
  `unlayered-delivery`, `bi-utilities`, `table-contract`, `node-tests`, `contracts`,
  `consumer-contract`, `audit`), medido en el árbol principal.
- Ya **no es una cadena `&&`**: `package.json:9` apunta a `scripts/design-system-static-suite.mjs`,
  que corre los ocho pasos aunque alguno falle y cierra con un resumen (`:26-36`). El motivo está
  escrito en su cabecera (`:2-4`): la cadena escondía `contracts`, `consumer-contract` y `audit`
  tras el primer rojo. Ya no hace falta correr esos tres a mano.
- `tests/design-system/laboratory-hardening.test.mjs` pasa **7/7**.
- **Biome sigue sin ser un gate verde, y peor de lo que decía esta nota:** `npm run check:frontend`
  reporta **859 errores, 2.610 avisos y 397 infos**, no dos errores. La cifra de dos era del estado
  de main en `541953e`.

Lo de abajo se conserva como historia del estado de gates en main `541953e` (2026-07-22) y su
resolución en la rama `claude/adoring-heisenberg-71861a` (commits `fac6dd2`+`67b86d1`, 2026-07-22),
más las reglas de operación que **sí siguen vigentes**: el worktree limpio que exige el gate de
contratos, el registro en `referencedTests`, y el rojo ambiental de mtimes en worktrees recién
creados.

- ~~Rojos tolerados que QUEDAN en main~~ (**ya no**, ver arriba) tras mergear esa rama: `laboratory-hardening` doc-drift (`tests/design-system/laboratory-hardening.test.mjs` — expectativa obsoleta, no regresión) y biome 2 errores (uno en `public/js/modules/aia_ui/components.js`).
- RESUELTO en la rama: los ~182 fallos de `design-system-consumer-contract.mjs` — se retiró `consumerContract: "v1"` (clave ausente, patrón programa-general/auth) de foundation-shell, programacion-intermedia y programacion-semanal porque su deuda es estructural (PI/PS sin primitivas aia-* ni assets canónicos; foundation-shell usa `!important` deliberados de navigation.css contra los globals legacy — incumplible sin refactor de cascada). `project-selector` queda como único v1. El contrato v1 NO admite excepciones: `exceptions` poblado es fallo per se; no existe la vía «v1 + excepciones». También se commiteó `tests/browser/programacion-intermedia.visual.mjs` (existía solo untracked; `.gitignore` `tests/browser/*` lo tragaba sin línea `!`) y se añadió a `referencedTests` del fixture de closeout.
- El rojo histórico de audit (`duplicate-canonical-primitive 150>147`) sigue **VERDE en main**.
- ~~La cadena `test:design-system:static` corta en el primer rojo (`&&`); partición corre PRIMERO; mientras `laboratory-hardening` siga rojo, contracts/consumer/audit requieren ejecución directa.~~ **Derogado el 2026-08-07:** la cadena es hoy un script que corre los ocho pasos completos.
- `theme-overrides.css` (entrypoints segmentados) es copia verbatim de los bloques inline del agregador: sus 15 `!important` tienen excepciones exactas en `exceptions.json` (expiran 1.1.0) y suman +15 warnings de biome (plan-mandated). Si el agregador cambia sus bloques inline, re-copiar verbatim; imports de adapters sin vendor van a `core.css` (lo exige el gate de partición).
- El gate de contratos (`contracts.test.mjs` → activation) exige worktree e índice limpios: commitear antes de correrlo, o validar árbol sucio via `git add -A` → `git stash create` → `git worktree add --detach /tmp/x $SHA` → correr allí → `git reset -q` y remove. Ojo symlink de node_modules (GIT_CONFIG_* con excludesFile).
- Tests nuevos referenciados por manifiestos van también a `referencedTests` de `tests/design-system/closeout-contract-fixture.mjs` (lista estática; `design-system-contracts.mjs` solo gobierna laboratory, programa-general, programacion-intermedia y project-selector) y al allowlist de manifiestos de `contracts.test.mjs`, o el gate falla con «missing test»/matrix.
- En worktrees RECIÉN creados, `stylesheet versions follow nested CSS changes` (`foundation.test.mjs`) falla como artefacto ambiental: el checkout re-estampa mtimes de `public/css/*` a la hora de creación mientras el PHP dockerizado sirve una copia con sello anterior; en el árbol principal (mtimes coherentes) pasa. No es regresión ni se arregla con `touch` — reportarlo como ambiental.

**Why:** no re-diagnosticar ni atribuir estos rojos a trabajo propio; el gate puede estar rojo aunque el cambio en curso sea correcto. **How to apply:** separar estos fallos del resultado de la superficie bajo prueba; correr los gates propios directo (`node scripts/design-system-entrypoint-partition.mjs`, `node --test <archivos>`); si consumer-contract vuelve a fallar tras el merge, es deuda nueva, no la de `3e2e296`. Relacionado: [[path-with-space-esm-guard-noop]], [[gate-visual-tolerancia-enganosa]], [[css-layer-cascade]].
