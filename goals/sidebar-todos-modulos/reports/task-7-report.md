---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-7-report.md
resumen: node tests/browser/shell-sidebar-rollout.mjs → 35/35 checks OK, exit 0 (7 módulos migrados en PASS: Programación Intermedia, Programa General, Profesionales…
---

# Task 7 — Programación Semanal (vista base) — Reporte

**Status:** DONE

**Commit:** `de81e43`

**Resumen del test:** `node tests/browser/shell-sidebar-rollout.mjs` → 35/35 checks OK, exit 0 (7 módulos migrados en PASS: Programación Intermedia, Programa General, Profesionales, Subcontratistas, Control de Cambios, Actualizar Cronograma, Programación Semanal; 2 PENDING: /indicadores, /bi/control-tower).

**Concerns:**
- La geometría del HOT (`#hot-container`) vive en `public/css/programacion-semanal.css`, compartido con las subvistas CIC/CNC/CNP no migradas (Task 8). Para no tocar ese archivo (fuera del alcance acotado a vista+controlador+harness), la corrección de altura (`calc(100vh - 49px)`) se agregó como `<style>` inline en el `<head>` de la vista base, scoped por `body.aia-shell--sidebar #hot-container` — no afecta a las subvistas hasta que ellas también adopten esa clase de body.
- Verificación visual manual en 1180×820 dark confirmó ambos estados (colapsado/expandido): sin overflow horizontal del documento, cajón LPS derecho coexistiendo, ítem "Programación Semanal" activo con `aria-current`.
