---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/vocabulario-estados-cascada/goal.md
resumen: La cascada LPS nombra el mismo ciclo de una actividad con tres vocabularios que conviven: 35 cadenas distintas medidas sobre de02471a. El frente reduce ese…
---

# Frente: vocabulario-estados-cascada

## Objetivo
La cascada LPS nombra el mismo ciclo de una actividad con tres vocabularios que conviven: 35
cadenas distintas medidas sobre `de02471a`. El frente reduce ese número, no lo traduce: un mapa de
equivalencias sería un cuarto vocabulario. Lo que exige criterio del usuario se encola sin
decidirse.

## Condición de hecho
1. Spec con el censo en tabla y plan aprobado en su gate antes de editar. ✔ escritos
2. Recuento antes/después medido. Objetivo de esta pasada: **35 → 29**.
3. Antes/después a 1180×820 dark de `/programacion-intermedia`, sesión por la puerta de servicio,
   contra un stack que sirva **este** worktree.
4. Verde con salida real de esta sesión: `npm run test:design-system:static`;
   `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`;
   pruebas del área.

## Archivos declarados
- `public/js/modules/programacion_intermedia/hot.js`
- `views/programacion-intermedia/programacion_intermedia.view.php`
- `tests/design-system/ops-state-contract.test.mjs`
- `docs/superpowers/specs/2026-08-11-vocabulario-estados-cascada-design.md`
- `docs/superpowers/plans/2026-08-11-vocabulario-estados-cascada.md`
- `decisiones/vocabulario-estados-cascada.md`

## Contención
| archivo | commits hoy | quién más lo declara |
|---|---|---|
| `public/js/modules/programacion_intermedia/hot.js` | 2 | nadie lo declara |
| `views/programacion-intermedia/programacion_intermedia.view.php` | 1 | **riesgo real:** `06e4383d` (frente `contadores-cero`) no declara archivos, pero su objeto son las etiquetas contadoras `(0)` — y los `count-badge` de la leyenda de Intermedia viven en este mismo archivo, en las mismas líneas que T2 toca |
| `tests/design-system/ops-state-contract.test.mjs` | 0 | nadie |
| `docs/design-system/state-semantics.json` | 0 | nadie (este frente **no** lo edita) |

`docs/decisiones-pendientes.md` lo declara `a187ccda`; este frente **no** lo toca: usa su propia
cola `decisiones/vocabulario-estados-cascada.md`, un archivo por sesión.

Mitigación: T2 es un cambio corto y se integra justo antes de publicar.

## Cadena de herramientas
- `skill:superpowers:brainstorming` — el frente arranca por decidir vocabulario, no por editar.
- `skill:superpowers:writing-plans` — el plan pasa gate antes de tocar una línea.
- `skill:coordinating-agent-sessions:decision` — encolar D-VOC-1…4 sin parar el trabajo.
- `skill:coordinating-agent-sessions:frente-cerrar` — gate de 9 pasos con visto por sha.
- `skill:superpowers:verification-before-completion` — ningún verde sin salida real.
- `agente:ecc:silent-failure-hunter` — un renombrado de etiquetas puede dejar ramas muertas mudas.
- `mcp:Claude_Browser` — el antes/después a 1180×820 dark es condición de hecho.
