---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-29
areas: [arquitectura, frontend, seguridad]
fuente: goals/paridad-shell-react-rls/plan.md
resumen: "Secuencia de ejecución gobernada: RLS primero y paridad React después."
---

# Plan de ejecución — Paridad del shell React y RLS

## Secuencia bloqueante

1. Ejecutar `docs/superpowers/plans/2026-08-28-rls-aplicacion-fail-closed.md`.
2. Detenerse ante el gate explícito de `--apply` y grants de la base compartida.
3. Cerrar todos los gates RLS.
4. Ejecutar `docs/superpowers/plans/2026-08-28-paridad-shell-react.md`.
5. Obtener aprobación humana de los 18 goldens.
6. Cerrar por Pull Request con CI verde.

El avance se registra en los ledgers SDD y en el historial de commits; no se fabrican evidencias
marcando retroactivamente casillas de los planes.
