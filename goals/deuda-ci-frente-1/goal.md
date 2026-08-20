---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: goals/deuda-ci-frente-1/goal.md
resumen: Anclar por SHA los 8 usos de actions de design-system.yml, añadir Dependabot para github-actions, poner timeout-minutes a ambos jobs y lintar el workflow con…
---

# Goal: Deuda del CI — Frente 1 (G1+G3+G5)

Anclar por SHA los 8 usos de actions de `design-system.yml`, añadir Dependabot para
`github-actions`, poner `timeout-minutes` a ambos jobs y lintar el workflow con actionlint.

**Condición de hecho:** actionlint y `npm run test:design-system:static` verdes en local;
corrida de PR sin rojos nuevos (única falla admitida: «Check runtime budgets against the
baseline» por `initializationMs`, deuda preexistente de otro frente); publicado en `main`
vía `scripts/publicar.sh` y primera corrida de `main` igualmente sin rojos nuevos.

**Plan:** `docs/superpowers/plans/2026-08-20-deuda-ci-frente-1.md`
**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
