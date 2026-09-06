---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-09-06
areas: [design-system]
fuente: goals/bloqueo-tema-claro/goal.md
resumen: "Levantar el bloqueo del tema claro: el guion que fuerza el oscuro y la hoja clara que ningún módulo carga, con el laboratorio rindiendo en claro y sus goldens en CI."
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: bloqueo-tema-claro

## Fase del plan
Plan: docs/superpowers/plans/2026-09-06-bloqueo-tema-claro.md
Fase: única
Sha de arranque: 30794901 (`main` tras el merge del PR #20)
Rama: `fix/bloqueo-tema-claro`, worktree `.claude/worktrees/shell-minimo-react`
Presupuesto: 3 días (2 estimados el 2026-09-06 antes de medir; la medición sumó una causa)

## Objetivo
Que toda pantalla servida por PHP y el laboratorio del design system honren el tema que
`theme-bootstrap.js` decide —claro de entrada (D12), preferencia por aparato (D14)—, y que el
carril visual del CI mida el laboratorio en los dos temas (D16) con goldens claros aprobados.

## Lo que se midió antes de declararlo (2026-09-06, sobre `main` en `30794901`)
Dos causas, no una, y la segunda no estaba en `TASKS.md`:

1. **`public/js/modules/aia_ui/theme.js` fuerza `data-aia-theme="dark"` sin condición** después
   de que `theme-bootstrap.js` ya aplicó el tema elegido. Lo cargan **7 vistas de forma estática**
   (`login`, `password-forgot`, `password-reset`, `project_selector`, `plan-compras/app`,
   `bi/_layout`, `bi/control-tower-piloto`) y **12 más de forma dinámica** vía
   `public/js/linksComunesHead2.js:60` (PG, PI, PS, CNP, CNC, CIC, profesionales, subcontratistas,
   control de cambios, indicadores, escalamientos, PG-actualizar). Las 19 emiten el bootstrap
   por `DesignSystemHeadComponent`, así que al retirar el forzado ninguna queda sin tema.
   Reproducido en `/programa-general` con `localStorage.aia-theme = "light"` y recarga:
   `{"attr":"dark","clases":"aia-theme-dark","stored":"light","bg":"rgb(17, 26, 21)"}`.
2. **Las re-vinculaciones claras viven solo en `public/css/design-system/theme-claro.css`, y
   ningún entrypoint la importa.** `entrypoints/theme-overrides.css`, `aia-design-system.css` y
   `design-system/laboratory-foundation.css` atan los tokens oscuros a `:root`. Solo el shell
   React (`public/app/index.html:19`) y la Torre piloto (`views/bi/control-tower-piloto.php:37`)
   la enlazan a mano. Reproducido: con el atributo forzado a `light` en `/programa-general`,
   `--ds-active-bg-page` sigue en `#111a15`; al inyectar la hoja clara, pasa a `#ffffff`, texto
   `#18181b`, `color-scheme: light`.

Consumidores del global `window.AiaDesignSystem.getTheme()` que `theme.js` publica: **ninguno en
código de producto** (`grep` en `public/js`, `pdc-app/src`, `ct-app/src`, `frontend/src`); solo
`tests/browser/shell-sidebar-rollout.mjs:110` lo espera, y `design_system_lab.js:8` define el suyo.

## Condición de hecho
1. En las 19 pantallas PHP y en `/internal/design-system`, sin preferencia guardada el
   documento arranca con `data-aia-theme="light"` y fondo claro; con `aia-theme = "dark"` guardado,
   oscuro. Medido en navegador contra el árbol de la rama (no el de la raíz).
2. `node --test tests/design-system/theme-default.test.mjs tests/design-system/theme-default-claro.test.mjs tests/design-system/theme-claro-tokens.test.mjs tests/design-system/linen-removal.test.mjs tests/design-system/dead-theme-removal.test.mjs tests/design-system/visual-ci-contract.test.mjs` y `node tests/test_foundation_shell_contract.mjs` en `RC=0`.
3. `docs/design-system/manifests/laboratory.json` declara 20 escenarios `light` con golden y
   `sha256`, aprobados por Felipe sobre la galería; `visual-ci-contract.test.mjs` exige
   `light: 10`.
4. El CI del PR en verde: `design-system-static`, `design-system-runtime (light)` y
   `design-system-runtime (dark)`.
5. El frente entra a `main` por Pull Request. Producción fuera de alcance.

## Posture
- No migrar ningún módulo al claro: este frente **destraba**; el primer módulo (Programa General,
  D23) tiene su propio plan.
- No regenerar goldens oscuros. Si un golden oscuro cambia, es hallazgo, no ajuste.
- Los goldens claros nuevos solo se commitean con el visto de Felipe sobre la galería.
- No tocar `admin/` (D24 tiene su vía) ni `ct-app/src/lib/theme.ts` (clave propia
  `ct-piloto-theme`; se anota como pendiente para el cierre de la Torre, D23).
- No reapuntar `LPS_CODE_ROOT` del contenedor compartido: la verificación en navegador corre en
  la pila aislada de CI (`docker-compose.ci.yml`, puerto 18081).
- Los tests que daban el oscuro por sentado se cambian **con intención declarada** (materializan
  el tema que afirman); ningún assert se afloja para ponerlo verde.

## Leer primero
- `docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design.md` (D12, D14, D16, D18, D19)
- `TASKS.md` §Bloqueantes, entradas del 2026-08-28 sobre `theme.js` y el laboratorio
- `tests/design-system/visual-ci-contract.test.mjs` (el censo por módulo y tema)
- `docs/coordinacion-sesiones.md` reglas 4, 5 y 7 (contenedor compartido, efímero, paso 0)

## Archivos de este goal
- [[goals/bloqueo-tema-claro/goal]] — este archivo
- [[docs/superpowers/plans/2026-09-06-bloqueo-tema-claro]] — el plan
- [[memoria/goals/estado]] — estado de todos los goals
