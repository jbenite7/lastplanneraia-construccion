---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: goals/cierre-version-1-1-0-design-system/goal.md
resumen: Publicar la versión 1.1.0 del design system: pagar o re-vencer con evidencia las 39 excepciones que expiran en ella (migración de /proyectos incluida)…
---

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

## Cierre formal (2026-08-07)

**CERRADO.** Condición de hecho cumplida y verificada:

- `npm run test:design-system:static` en **8/8** con `docs/design-system/version.json` en
  `1.1.0/stable`, medido en worktree con **stack Docker propio** y worktree limpio.
- **Cero** entradas `expiresAtVersion: "1.1.0"` en `exceptions.json` (39 → 32, todas en `1.2.0`).
- Informe una-a-una en el ledger: **7 pagadas / 32 re-vencidas**, cada una con su evidencia.
- Ciclo triple de `/proyectos` **aprobado** (20/20 en el audit técnico; 2 hallazgos P3
  preexistentes, documentados y no corregidos).

**Corrección al contrato:** la decisión D1 partía de una premisa falsa. Las 15 excepciones de
`theme-overrides.css` no eran «verbatim del selector de proyecto» sino normalizaciones globales de
`border-radius` del propio design system, idénticas a las del agregador `aia-design-system.css` —a
eso se refería el «verbatim»—. Migrar `/proyectos` a primitivas `aia-*` habría pagado **cero** de
ellas, y además esa pantalla ya estaba migrada por la campaña dark mode. El grupo se resolvió con la
regla de D3 (medir si el opresor sigue vivo), que es la única que la evidencia soportaba.

Commits: `3781f1c1` (grupo A), `a6d5d01a` (acento), `81c61a08` (grupos C y D), `8c45ae41` (gates
D2), `a5223a0c` (activación atómica), `a844a0f8` (deuda destapada por el bump).

**Lo que hereda 1.2.0:** las 32 excepciones re-vencidas las cobra el retiro del puente legacy
móvil/vendor de Handsontable. Ver [[subir-la-version-del-ds-cobra-deudas]] y
[[version-escrita-a-mano-rompe-el-bump]].

## Archivos de este goal

- [goal.md](goal.md) — este contrato.
- Plan: [2026-08-04-cierre-version-1-1-0-design-system.md](../../docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md)
- Spec: [2026-08-04-cierre-version-1-1-0-design-system-design.md](../../docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md)
- Estado de goals: [estado.md](../../memoria/goals/estado.md)
