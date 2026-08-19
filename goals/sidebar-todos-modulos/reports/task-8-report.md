---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-8-report.md
resumen: Task 8 — Subvistas CIC/CNC/CNP al shell sidebar
---

# Task 8 — Subvistas CIC/CNC/CNP al shell sidebar

## Status
DONE

## Commit
`d893b9e` — feat(shell-sidebar): subvistas CIC/CNC/CNP usan el shell sidebar (ambos estados)

## Test
`node tests/browser/shell-sidebar-rollout.mjs` → 50/50 checks OK, exit 0 (7 módulos base + CIC + CNC + CNP en PASS; Indicadores y Control Tower en PENDING como se espera).

## Concerns
- Interceptaciones de mutación añadidas al harness (POST que escriben, propias de estas 3 subvistas): `**/api/cic/save*`, `**/api/cnc/save*`, `**/api/cnp/save*`, `**/api/cnp/reprogramar*` (todas devuelven `{"respuesta":"BIEN"}` inocuo). `**/api/cnc/list`, `**/api/cnp/list`, `**/api/cic/list` y `**/api/cnc/reasons` no se interceptaron: son lecturas (SELECT), no mutan datos. Ninguna de las mutaciones interceptadas se disparó en la corrida real (el harness no interactúa con las filas de las DataTables), pero quedan cubiertas de forma defensiva.
- `cic()`, `cnc()`, `cnp()` no pasaban `$permiso`/`$area` antes; ahora las tres setean `$permiso` explícitamente y reutilizan `$area` ya existente, además de `$shellWeeks`/`$shellActive`/`$shellModuleLabel` vía un helper privado `loadShellWeeks()` compartido en el controlador.
- Cero overflow horizontal y cero-scroll del nav en ambos estados desde el primer intento; no fue necesario CSS scoped adicional (`body.aia-shell--sidebar`) para geometría del DataTables.
- `DESIGN.md` tenía un cambio no relacionado (PDC) ya presente en el árbol al iniciar; se dejó fuera del staging junto con `.impeccable/design.json` (untracked, ajeno).

## Fix — hallazgo CRITICAL del review (corrección de `/api/cic/list`)

El review detectó que el punto anterior (`/api/cic/list no se interceptó: es lectura`) era
incorrecto: `CicApiController::list()` (`src/Controllers/Api/CicApiController.php`) ejecuta
`syncPac()`, `generateMissingSubs()` y `updateIntegral()` (UPDATE/INSERT reales contra la BD del
proyecto) antes de responder — pese al nombre, es una mutación. La vista CIC lo dispara en
`$(document).ready()` vía `cargarDatosGeneralesPagina` → `listar()`, así que el harness la mutaba
en cada corrida (dos veces, por goto + reload).

**Corrección:** se añadió `page.route('**/api/cic/list*', ...)` en
`tests/browser/shell-sidebar-rollout.mjs`, junto a las demás interceptaciones de mutación, que
devuelve `{"data":[]}` — la forma vacía que espera el DataTables de `CIC.view.php` (confirmado
leyendo `views/programacion-semanal/CIC.view.php` y el shape real de respuesta de
`CicApiController::list()`, que ya usa `{"data": [...]}` como envoltorio DataTables). Con datos
vacíos la vista sigue renderizando sin error y los checks del sidebar pasan igual.

### Commit
`c26847a` — fix(shell-sidebar): interceptar /api/cic/list en el harness (muta la BD pese al nombre)

Solo `tests/browser/shell-sidebar-rollout.mjs` en el commit (`git diff --cached --name-only`
confirmado antes de commitear; `DESIGN.md` y `.impeccable/design.json` quedaron fuera).

### Test
`node tests/browser/shell-sidebar-rollout.mjs` → 50/50 checks OK, exit 0 (CIC renderiza con datos
vacíos y sus 5 checks del sidebar pasan igual que antes).

Verificación adicional de la interceptación: script ad hoc con un contador en el handler de
`page.route('**/api/cic/list*', ...)` navegando a `/programacion-semanal/cic` → `interceptedCount: 1`,
`Response for /api/cic/list: 200` sin llegar al backend real (Playwright `route.fulfill` responde
localmente, nunca reenvía la petición al servidor).
