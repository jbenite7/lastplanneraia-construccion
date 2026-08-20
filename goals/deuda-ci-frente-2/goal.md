---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: goals/deuda-ci-frente-2/goal.md
resumen: Cache buildx type=gha de la capa base de la imagen PHP en ambos jobs del CI, con mejora medida frío/caliente y sin rojos nuevos.
---

# Goal: Deuda del CI — Frente 2 (G2 mínimo)

Cachear la capa base de la imagen PHP (apt + extensiones) con Buildx `type=gha` en ambos
jobs, sin reordenar el Dockerfile (alcance A, decisión de Felipe 2026-08-20).

**Condición de hecho:** suite estática y actionlint verdes en local; corrida de PR sin rojos
nuevos; una corrida con cache caliente donde el build de la imagen baje de la línea base
(81 s estático / 93 s runtime); publicado vía `scripts/publicar.sh` y confirmado en `main`.

**Plan:** `docs/superpowers/plans/2026-08-20-deuda-ci-frente-2.md`
**Spec:** `docs/superpowers/specs/2026-08-20-deuda-ci-design.md`

## Cierre

Cerrado el 2026-08-20. La imagen se pre-construye con buildx (`type=gha`, `mode=min`,
tag fijo `lps-aia-app-ci:local`) en ambos jobs; compose ya no la reconstruye (`build db` +
`up -d db app` sin `--build`). El Dockerfile no se tocó (alcance A).

**Verificación (corrida `32406977581`, PR #10):** en frío, sin rojos nuevos y el runtime ya
reutilizó el cache sembrado por el estático (26 s build + 46 s arranque = 72 s). En caliente
(attempt 2): «Build the PHP test runtime» **20 s contra 81 s de línea base (−75 %)**; runtime
**72 s contra 93 s (−23 %)**. Único rojo en ambas: «Check runtime budgets against the
baseline» por `initializationMs` — el mismo paso y causa preexistentes de `main`. En el
camino quedó resuelto el conflicto con `10dd634e` (el espejo de CSS pasó a versionarse y el
paso de generación cambió a verificación), re-verificando después de integrar.

**Publicado:** `2281a80b` en `origin/main` (fetch + rev-parse coinciden); PR #10 MERGED.
Primera corrida de `main` (`32408756105`): sin rojos nuevos; runtime ya en 54 s (23 s build +
31 s arranque) contra 93 s de línea base. El estático marcó 119 s porque el cache gha se
hereda hacia las ramas pero no desde ellas — esta corrida siembra el scope de `main` y las
siguientes leen caliente, como el PR demostró (20 s).

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
