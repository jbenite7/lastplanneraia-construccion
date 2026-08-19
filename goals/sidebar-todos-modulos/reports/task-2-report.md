---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-2-report.md
resumen: Task 2 — Migrar Programa General al shell sidebar
---

# Task 2 — Migrar Programa General al shell sidebar

## Status
DONE

## Commit
da792e8 — feat(shell-sidebar): Programa General usa el shell sidebar (ambos estados)

## Archivos tocados
- `src/Controllers/Programacion/ProgramaGeneralController.php`: agrega bloque `$shellWeeks`
  (misma consulta que PI: `Semana, Fecha_Inicio_Sem, Fecha_Fin_Sem` por `project_id`, orden
  descendente) y `$shellActive = 'programa-general'` / `$shellModuleLabel = 'Programa General'`,
  seteados justo antes del `require` de la vista.
- `views/programa-general/programa_general.view.php`: body ahora
  `class="aia-shell aia-shell--sidebar pg-page"`; `require __DIR__ . '/../partials/shell_sidebar.php';`
  al inicio del body (tras `#loading`, igual que PI); `window.__AIA_SHELL_SIDEBAR__ = true;` agregado
  junto a `window.__PROJECT_AREA__` (antes de que carguen hot.js/funcionesGenerales6.js); se agregó
  `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js')`. El cajón LPS
  (`drawer_unificado.php` + `lps_drawer.js`) se conservó sin cambios.
- `tests/browser/shell-sidebar-rollout.mjs`: `/programa-general` agregado a `MIGRATED`; se añadió el
  fold de robustez pedido (limpia `localStorage['aia-sidebar-state']` y recarga con
  `waitUntil: 'domcontentloaded'` antes de medir el estado inicial) para TODAS las rutas migradas,
  incluida PI.

## Verificación ejecutada
1. `docker compose exec -T app php -l` sobre vista y controlador → sin errores de sintaxis (ambos).
2. `node tests/browser/shell-sidebar-rollout.mjs` → exit 0, 10/10 checks OK. Resumen:
   - `[Programación Intermedia]` y `[Programa General]`: PASS en las 5 verificaciones cada uno
     (default colapsado, toggle expande/colapsa, cero-scroll del nav en ambos estados, sin overflow
     horizontal en ambos estados, ítem activo con `aria-current`).
   - Resto de rutas (`/profesionales`, `/subcontratistas`, `/control-cambios`,
     `/programa-general-actualizar`, `/programacion-semanal`, `/indicadores`, `/bi/control-tower`):
     PENDING, como se espera.
3. Verificación visual en navegador integrado (1180×820, dark, `/programa-general`, sesión real):
   colapsado y expandido. En ambos estados el Handsontable se reajusta automáticamente al ancho
   disponible (Handsontable 14.6.1 trae ResizeObserver interno — no hizo falta disparar
   re-render/`updateSettings` manual), sin overflow horizontal ni solape con el rail LPS derecho
   ("CONCURRENCIA LPS"). El ítem "Programa General" queda resaltado como activo en el rail.

## Concerns
Ninguno. No se tocaron otros módulos, el partial/CSS/JS canónicos del shell, ni el trabajo PDC del
árbol (staging verificado con `git diff --cached --name-only` antes del commit: solo los 3 archivos
listados arriba).
