# Task 1 — Harness data-driven de verificación del shell sidebar

## Objetivo
Crear `tests/browser/shell-sidebar-rollout.mjs`: un test Playwright standalone (estilo `tests/browser/shell-week-admin.mjs`) que recorre una lista de rutas y, para las **migradas**, verifica que el shell sidebar canónico quedó correctamente implementado en ambos estados. Para las **no migradas** aún, reporta PENDING sin fallar (así el test es verde ahora y cada migración futura solo agrega su ruta a la lista MIGRATED).

## Patrón a reutilizar (léelo primero)
`tests/browser/shell-week-admin.mjs` ya tiene el login + selección de proyecto + interceptación de mutaciones. Copia ese preámbulo:
- `import { chromium } from 'playwright'` y `import { BASE_URL, CREDENTIALS } from './fixtures/projects.mjs'`.
- `chromium.launch()`, `browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' })`.
- Interceptar mutaciones para no tocar la BD compartida: `page.route` de `**/api/semanal/save*` y `**/api/semanal/auto-program*` devolviendo `{"respuesta":"OK"}`, y `**/nueva_semana.php*`/`**/eliminar_semana.php*`/`**/verificarCICActualizada.php` devolviendo `0`.
- Login: goto `${BASE_URL}/login`, fill `#usuario`/`#password` con `CREDENTIALS`, submit, esperar `/proyectos`, click primer `.project-item button[type="submit"], .project-item .btn-enter`, esperar salir de `/proyectos`.
- Helper `check(name, ok, detail)` que acumula en `results` e imprime PASS/FAIL, y al final `process.exit(failed ? 1 : 0)`.

## Estructura de datos
```js
const ALL_ROUTES = [
  { route: '/programacion-intermedia', active: 'programacion-intermedia', label: 'Programación Intermedia' },
  { route: '/programa-general', active: 'programa-general', label: 'Programa General' },
  { route: '/profesionales', active: 'profesionales', label: 'Profesionales' },
  { route: '/subcontratistas', active: 'subcontratistas', label: 'Subcontratistas' },
  { route: '/control-cambios', active: 'control-cambios', label: 'Control de Cambios' },
  { route: '/programa-general-actualizar', active: 'actualizar-cronograma', label: 'Actualizar Cronograma' },
  { route: '/programacion-semanal', active: 'programacion-semanal', label: 'Programación Semanal' },
  { route: '/indicadores', active: 'indicadores', label: 'Indicadores LPS' },
  { route: '/bi/control-tower', active: 'control-tower', label: 'Control Tower - Informes' },
];
const MIGRATED = new Set(['/programacion-intermedia']); // se irá ampliando módulo a módulo
```

## Verificación por ruta migrada
Para cada `r` en `ALL_ROUTES`:
- Si `!MIGRATED.has(r.route)`: `console.log('PENDING ' + r.route)` y continúa (NO cuenta como fallo).
- Si migrada: `await page.goto(BASE_URL + r.route, { waitUntil: 'domcontentloaded' })`, `await page.waitForSelector('[data-shell-pattern="sidebar"]', { timeout: 20000 })`, y verifica con `check(...)`:
  1. **default colapsado**: el aside `[data-shell-pattern="sidebar"]` tiene `data-sidebar-state="collapsed"` al cargar (localStorage limpio por contexto nuevo).
  2. **toggle expande y colapsa**: click en `[data-sidebar-toggle]` → estado pasa a `expanded`; click de nuevo → vuelve a `collapsed`. (Espera ~450ms entre clicks.)
  3. **cero-scroll del nav en ambos estados**: en colapsado y en expandido, `nav.scrollHeight <= nav.clientHeight + 1` para `.aia-sidebar__nav`. (Alterna el estado con el toggle para medir cada uno.)
  4. **sin overflow horizontal del documento** en ambos estados: `document.documentElement.scrollWidth - document.documentElement.clientWidth <= 1`.
  5. **ítem activo**: existe `[data-shell-pattern="sidebar"] [data-destination-id="<r.active>"][aria-current="page"]` (o el botón/enlace del destino con aria-current). Para PI el active es `programacion-intermedia`.
  Deja cada ruta en estado colapsado al terminar (para no filtrar estado entre rutas; además el contexto es el mismo `page`).

Agrupa los checks por ruta con prefijo `[${r.label}]` en el nombre.

## Restricciones (Global Constraints)
- Viewport **1180×820, dark** (canónico del proyecto). No mobile/tablet/linen.
- NO mutar la BD compartida: todas las mutaciones interceptadas por `page.route`.
- El test debe **pasar (exit 0) ahora** con solo `/programacion-intermedia` en MIGRATED (las demás en PENDING). Verifica corriéndolo.
- Standalone: se corre con `node tests/browser/shell-sidebar-rollout.mjs` (no `--test`, no playwright runner). Requiere la app en `http://localhost:8081` (docker ya arriba).
- No toques ningún otro archivo. No agregues dependencias.

## Verificación que debes ejecutar y reportar
- `node tests/browser/shell-sidebar-rollout.mjs` → debe imprimir los checks de `/programacion-intermedia` en PASS, `PENDING` para las otras 8, y salir 0. Pega el resumen final (`N/N checks OK`) y la lista de PENDING en tu reporte.
- Commit atómico en main: `test(shell-sidebar): harness data-driven de rollout (PI verde, resto pending)` con el trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.

## Reporte
Escribe el reporte completo en `goals/sidebar-todos-modulos/reports/task-1-report.md` (qué hiciste, salida del test, commit). Devuelve solo: status (DONE/BLOCKED/…), el hash del commit, resumen de test en una línea, y cualquier concern.
