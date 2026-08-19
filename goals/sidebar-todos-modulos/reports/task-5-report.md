---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-5-report.md
resumen: node tests/browser/shell-sidebar-rollout.mjs → 25/25 checks OK, exit 0: PI, Programa General, Profesionales, Subcontratistas y Control de Cambios en PASS…
---

# Task 5 — Control de Cambios → shell sidebar

## Status
DONE (no BLOCKED). Cableado idéntico a la plantilla de Profesionales/Subcontratistas
(3a968dd / daae7a6): `body.aia-shell aia-shell--sidebar`, `require .../partials/shell_sidebar.php`,
`window.__AIA_SHELL_SIDEBAR__ = true` antes de `cargarDatosGeneralesPagina2.js`,
`DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`,
y `$shellActive/$shellModuleLabel/$shellWeeks` en `ControlCambiosController::index()` (misma
query `semanas_activas` por `project_id`, guard `dbName`, try/catch → `[]`).

El riesgo señalado (overflow horizontal por layout en flujo + tabla ancha + modal XL, y doble
jQuery rompiendo el toggle) **no se materializó**: no fue necesario ningún ajuste de anchos/overflow.
Verificado con el harness (Playwright, chequeo real de `scrollWidth` del documento) y manualmente
en el navegador integrado (1180×820 dark): `document.documentElement.scrollWidth === innerWidth`
(1180) en colapsado, expandido, y con `#modalordenDeCambio` (modal-xl) abierto. El toggle/flyouts
de la sidebar funcionan correctamente pese a la segunda copia de jQuery 1.12.4 + Bootstrap 4.3.1
cargada al final del body — `sidebar_navigation.js` es vanilla JS y no colisiona.

## Commit
`30e0a17` — `feat(shell-sidebar): Control de Cambios usa el shell sidebar (ambos estados)`

## Resumen de test
`node tests/browser/shell-sidebar-rollout.mjs` → 25/25 checks OK, exit 0: PI, Programa General,
Profesionales, Subcontratistas y Control de Cambios en PASS (default colapsado, toggle,
cero-scroll del nav, sin overflow horizontal, aria-current), resto (`/programa-general-actualizar`,
`/programacion-semanal`, `/indicadores`, `/bi/control-tower`) en PENDING. `php -l` limpio en vista
y controlador.

## Decisión sobre `.encabezado`
Se dejó sin tocar. A diferencia de otros módulos, el `.encabezado` de Control de Cambios solo
contiene `<input type="hidden">` (seccion/Id/opcion/codigo) — no tiene título ni texto visible que
duplique la context-bar del shell (proyecto/módulo/semana), así que no había nada que ocultar o
retirar. Mismo criterio aplicado en Profesionales/Subcontratistas (su `.encabezado` tampoco se tocó).

## Concerns
- Ninguno bloqueante. El doble jQuery/Bootstrap y el stack CDN (DataTables/Tabulator/Charts/
  Select2/jsPDF) quedan igual que antes, fuera de alcance de esta tarea.
- No había datos de Órdenes de Cambio en el proyecto de prueba (tabla vacía); se abrió el modal
  XL manualmente vía `$('#modalordenDeCambio').modal('show')` (sin submit, sin mutar datos) solo
  para confirmar layout — se cerró sin persistir nada.
