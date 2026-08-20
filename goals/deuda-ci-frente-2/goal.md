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

## Archivos de este goal
- [[goal.md]] · estado en [[memoria/goals/estado]]
