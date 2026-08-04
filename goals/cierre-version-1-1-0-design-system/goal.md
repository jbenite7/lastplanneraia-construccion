# Goal: Cierre de la versión 1.1.0 del design system

**Objetivo:** Publicar la versión 1.1.0 del design system: pagar o re-vencer con evidencia las 39
excepciones que expiran en ella (migración de `/proyectos` incluida), flexibilizar los gates de
activación a «al menos 1.0.0» y sincronizar manifiestos + changelog en un commit de activación
atómico.

**Condición de hecho:** `npm run test:design-system:static` en **8/8** con
`docs/design-system/version.json` en `1.1.0/stable`; cero entradas `expiresAtVersion: "1.1.0"` en
`docs/design-system/exceptions.json`; informe una-a-una (pagadas N / re-vencidas M) en el ledger;
ciclo triple de `/proyectos` aprobado.

**Plan:** `docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md` (7 tasks).
**Spec:** `docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md`
(decisiones D1–D4 del usuario, 2026-08-04).

**Precondición dura:** no arranca hasta que la campaña dark mode
(`docs/superpowers/plans/2026-08-04-cierre-dark-mode-campana-decisiones.md`) haya terminado (D4).

## Archivos de este goal

- [goal.md](goal.md) — este contrato.
- Plan: [2026-08-04-cierre-version-1-1-0-design-system.md](../../docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md)
- Spec: [2026-08-04-cierre-version-1-1-0-design-system-design.md](../../docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md)
- Estado de goals: [estado.md](../../memoria/goals/estado.md)
