---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-29
areas: [arquitectura, frontend, rbac, seguridad, design-system]
fuente: goals/paridad-shell-react-rls/goal.md
resumen: "Cerrar la paridad funcional del shell React y el aislamiento fail-closed por proyecto."
---

# Goal — Paridad del shell React y RLS

**Slug:** `paridad-shell-react-rls`
**Fecha de apertura:** 2026-08-29
**Estado:** EN CURSO
**Rama:** `shell-minimo-react`
**Worktree:** `.claude/worktrees/shell-minimo-react`

## Objetivo

Convertir `/app` en el shell de uso real con, como mínimo, las capacidades observables del login,
selector y shell legacy, conservando los módulos PHP y haciendo que toda consulta operativa falle
cerrada sin un alcance de proyecto autorizado.

## Condición de hecho

1. Los ocho gates del plan RLS están verdes, incluido aislamiento A→B en lectura y mutación.
2. Las doce tareas de paridad React están implementadas y sus filas de matriz están aceptadas.
3. Claro/oscuro × 390/768/1180 pasan accesibilidad, overflow y aprobación visual.
4. Legacy y rollback siguen funcionando sin desactivar RLS.
5. El frente entra a `main` mediante Pull Request con CI verde; producción queda fuera de alcance.

## Autoridad

- Spec aprobada: `docs/superpowers/specs/2026-08-28-paridad-shell-react-rls-design.md`.
- Plan RLS: `docs/superpowers/plans/2026-08-28-rls-aplicacion-fail-closed.md`.
- Plan React: `docs/superpowers/plans/2026-08-28-paridad-shell-react.md`.
- Atlas aprobado: `docs/superpowers/specs/evidencia/2026-08-28-shell-react-design-system-atlas.html`.

## Gates separados

La aprobación del frente no autoriza `--apply`, cambios de grants sobre la base compartida,
publicación, merge ni despliegue. Cada acción conserva su gate específico.
