---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-3-report.md
resumen: Task 3 — Profesionales → shell sidebar
---

# Task 3 — Profesionales → shell sidebar

**Status:** DONE
**Commit:** `3a968dd` — feat(shell-sidebar): Profesionales usa el shell sidebar (ambos estados)
**Test:** `node tests/browser/shell-sidebar-rollout.mjs` → 15/15 checks OK (PI, PG, Profesionales en PASS; resto PENDING), exit 0.

## Concerns
- Ninguno bloqueante. `#hot-container` sigue con `height: calc(100vh - 180px)` heredado de la
  plantilla original, pero al estar dentro del layout flex (`flex: 1 1 auto`) el flex-grow termina
  dominando el alto real; verificado en navegador (1180×820 dark, colapsado y expandido) que el HOT
  llena el área sin overflow horizontal ni vertical del documento, y que `window.__AIA_SHELL_SIDEBAR__`
  suprime correctamente `#navbarSupportedContent`/`.navbar-brand`/`.navbar-nav` (confirmado por JS:
  ningún nodo existe en el DOM). No se tocó el bloque CSS muerto del navbar legacy (~líneas 238-313):
  se dejó por precaución al no ser estrictamente necesario para el objetivo y evitar over-refactor.
