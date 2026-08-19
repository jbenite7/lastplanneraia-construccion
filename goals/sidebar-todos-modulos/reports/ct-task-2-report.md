---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/ct-task-2-report.md
resumen: CT Task 2 — Filtros en cajón derecho (componente propio)
---

# CT Task 2 — Filtros en cajón derecho (componente propio)

- **Status:** DONE
- **Commit:** 73e8298
- **Test:** `node tests/browser/shell-sidebar-rollout.mjs` → 63/63 checks OK, exit 0 (60 checks preexistentes + 3 nuevos de CT: trigger existe, click abre `[data-bi-filter-drawer]`, Escape cierra).
- **Concerns:**
  - Durante QA visual encontré que `styles.css` importa `* { margin:0; padding:0 }` en `@layer module`, que en el orden global de capas (`reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides`) vence a cualquier padding declarado en `@layer components` sin importar especificidad — el header del cajón quedó sin padding (44px de alto vs. ~92px esperados). Lo resolví moviendo las reglas de padding/margin de `bi-filter-drawer.css` a `@layer legacy-overrides`, replicando el patrón ya usado en `design-system/adapters/lps-drawer.css`. Verificado con `getComputedStyle` (24px tras el fix) y visualmente.
  - `npx biome check` reporta warnings/errors de estilo (arrow functions, comillas) en `bi_filter_drawer.js`, consistentes con el estilo preexistente de `lps_drawer.js` (que falla igual o peor: 3 errores/30 warnings). No es una regresión nueva; no se tocó para no desviarse del alcance.
