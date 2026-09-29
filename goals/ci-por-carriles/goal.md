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
- **R7** Ahorro medido, no estimado, en PR solo de documentación, solo de `frontend/src` y solo de un CSS de módulo. Medido el 2026-09-29: 23 s, 198 s y 911 s frente a ~15 min.
- **R8** Un gate omitido no deja pasos de subida de recibos fallando.

**Plan:** `docs/superpowers/plans/2026-09-28-ci-por-carriles.md`
**Spec:** `docs/superpowers/specs/2026-09-28-ci-por-carriles-design.md`

## Cierre (2026-09-29)

Mergeado a `main` por orden de Felipe: PR #82 (`981ae3d2`) y PR #83 (`5e3b8e16`, el conteo de tests de `CLAUDE.md`).

| Criterio | Evidencia |
|---|---|
| R1 | 132 tests del selector y del workflow (RC=0) y tres PR de prueba con el selector leído en el registro: `docs` → ninguno (#84), `front-src` → `static`, `frontend` (#85), `ds-modulo` → `static`, `php_runtime`, `css_minify`, `e2e`, `lab` (#86) |
| R2 | test de ruta sin dueño; y #82, que al tocar `.github/**` cayó en `todo` con las nueve banderas |
| R3 | **Pendiente de lectura:** corrida de `main` `36596525901` sobre `981ae3d2` |
| R4 | #82: la pata `dark` omitió los gates independientes del tema (PHPStan, grants, suite PHP, admin-db, e2e, presupuestos, persistencia y el chequeo del CSS minificado) y bajó de ~730 s a 410 s |
| R5 | test que ejecuta el `run` del resumen con `bash` en 6 escenarios; la tabla renderizada en GitHub no se leyó (la API no la expone) |
| R6 | contratos en verde en local (24 de 24, RC=0; PHP de frontera 68 comprobaciones) y `design-system-static` en verde en el CI de #82 |
| R7 | 23 s (#84), 198 s (#85), 911 s (#86) y 994 s (#82) sobre una línea base de ~15 min y ~1.680 s de suma de jobs |
| R8 | #86: el piloto se omitió, su recibo de presupuestos no se subió y el job terminó en verde |

## Archivos de este goal

- [[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|Spec de diseño]]
- [[docs/superpowers/plans/2026-09-28-ci-por-carriles|Plan de implementación]]
- [[memoria/goals/estado|Estado de goals en la wiki]]
