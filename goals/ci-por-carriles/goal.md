---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-09-28
areas: [proceso]
fuente: goals/ci-por-carriles/goal.md
resumen: Hacer que el CI corra solo los gates que el diff de un PR alcanza («CI por carriles»), sin perder cobertura en main.
---

# Goal: CI por carriles

**Objetivo:** Que el CI de un Pull Request corra solo los gates que su diff alcanza, clasificando
cada ruta en un carril y encendiendo únicamente los gates de esos carriles. Una ruta sin dueño
enciende todo, y `main` corre siempre completo.

**Condición de hecho (R1–R8 de la spec):**

- **R1** En `pull_request`, cada gate corre solo si el diff toca un carril que lo dispara.
- **R2** Ruta sin dueño: todo corre.
- **R3** En `push` a `main` corren todos los carriles, y una corrida de `main` nunca se cancela por otra posterior.
- **R4** Los gates que no dependen del tema corren una sola vez, no en las dos patas de la matriz.
- **R5** Un gate omitido se ve como omitido, con su carril, en «Resultado de los gates»; un omitido no se lee como verde.
- **R6** Siguen en verde los tests de contrato que leen `ci.yml`: `npm run test:design-system:static` y `tests/test_runtime_boundary_ci_contract.php`.
- **R7** Ahorro medido, no estimado, en PR solo de documentación, solo de `frontend/src` y solo de un CSS de módulo. Meta numérica: pendiente de medir.
- **R8** Un gate omitido no deja pasos de subida de recibos fallando.

**Plan:** `docs/superpowers/plans/2026-09-28-ci-por-carriles.md`
**Spec:** `docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md`

## Archivos de este goal

- [[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|Spec de diseño]]
- [[docs/superpowers/plans/2026-09-28-ci-por-carriles|Plan de implementación]]
- [[memoria/goals/estado|Estado de goals en la wiki]]
