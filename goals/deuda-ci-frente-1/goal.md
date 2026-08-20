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

## Cierre

Cerrado el 2026-08-20. Los cuatro cambios entraron: 8 `uses:` anclados por SHA con comentario
de versión (checkout/setup-node v4.4.0, upload-artifact v4.6.2), `.github/dependabot.yml`
semanal para `github-actions`, `timeout-minutes` 20/60, y actionlint v1.7.12 (binario fijado
por checksum) como primer paso del job estático — su único hallazgo local (SC2034 en el bucle
de espera) corregido. El contrato `visual-ci-contract.test.mjs` pasó a exigir la forma pineada.

**Verificación:** actionlint RC=0 y suite estática 8/8 en local, antes y después de integrar
`origin/main`; corrida de PR [#6](https://github.com/jbenite7/lastplanneraia-construccion/pull/6)
(`32392721353`): job estático `success` completo y un único rojo en «Check runtime budgets
against the baseline» por `initializationMs` — el mismo paso y causa preexistentes de `main`,
deuda de otro frente. Cero rojos nuevos: condición de hecho cumplida. Publicado vía
`scripts/publicar.sh` (el SHA queda anotado bajo esta línea al confirmar).

**Publicado:** `13ae83f1` en `origin/main` (confirmado con fetch + rev-parse). Primera corrida
de `main` con el YAML nuevo (`32394566769`): estático `success` completo, único rojo el paso de
presupuestos por `initializationMs` — sin rojos nuevos. PR #6 quedó MERGED; `dependabot.yml`
visible en `main`.

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
