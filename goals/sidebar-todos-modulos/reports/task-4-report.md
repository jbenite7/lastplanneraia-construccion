---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-4-report.md
resumen: Task 4 — Subcontratistas → shell sidebar
---

# Task 4 — Subcontratistas → shell sidebar

**Status:** DONE
**Commit:** `daae7a6` — feat(shell-sidebar): Subcontratistas usa el shell sidebar (ambos estados)
**Test:** `node tests/browser/shell-sidebar-rollout.mjs` → 20/20 checks OK (PI, PG, Profesionales, Subcontratistas en PASS; resto PENDING), exit 0.

## Concerns
- Ninguno bloqueante. El HOT de Subcontratistas ya traía el mismo patrón de resize por
  `offsetWidth` que Profesionales (no CDN/versión tocada); no se observó overflow horizontal en
  ninguno de los dos estados (1180×820 dark). `$isPreConstruccion` y su branching de etiquetas
  ("Interesados Externos" vs "Subcontratistas") quedaron intactos; `$shellActive` es `'subcontratistas'`
  en ambos casos, sin afectar esa lógica. No se tocó el CSS muerto del navbar legacy.
