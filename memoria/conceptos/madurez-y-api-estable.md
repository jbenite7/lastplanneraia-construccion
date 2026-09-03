---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: scripts/design-system-contracts.mjs, docs/design-system/component-catalog.json, stable-api-1.0.0.json
resumen: "Un componente candidate vive solo en el catálogo; stable además está enumerado en stable-api-1.0.0.json con aprobación visual, y esa igualdad la exige un gate"
---
# Qué separa un componente `candidate` de uno `stable`

Un componente del design system declara su madurez en
`docs/design-system/component-catalog.json`, con cuatro valores posibles:
`stable`, `candidate`, `compatibility` y `deprecated`
(`scripts/design-system-contracts.mjs:73`).

La diferencia que importa es qué implica cada uno:

| | `candidate` | `stable` |
|---|---|---|
| Vive en el catálogo | sí | sí |
| Enumerado en `stable-api-1.0.0.json` | no | **sí, y debe coincidir** |
| Aprobación visual | no exigida | `visualApproval.status === 'approved'` |
| Garantía SemVer | ninguna | sí |

**Lo que hace real esa diferencia es un gate, no una convención.**
`scripts/design-system-contracts.mjs:95` filtra del catálogo los componentes con
`maturity === 'stable'` y los contrasta contra la API publicada. Si un componente aparece en
`stable-api-1.0.0.json` sin estar catalogado como `stable`, el gate falla con
`stable API <id>: catalog maturity must be stable` (`:119-124`). También compara `family`, `api` y
`evidenceSurfaces`, y exige a Programa General como consumidor declarado.

**Para qué sirve saberlo.** Antes de consumir un componente en un módulo, mira su `maturity`: si es
`candidate`, puede cambiar bajo tus pies sin que nadie rompa una promesa. Si es `stable`, cambiarlo
obliga a pasar por el contrato de release.

## Y qué garantiza una versión estable

`docs/design-system/version.json` es la fuente que manda sobre la versión viva, que es **`1.1.0`
desde el 2026-08-07**. Que diga `stable` no es una etiqueta suelta:
`tests/design-system/release-governance.test.mjs:79-80` exige que **todas las gates de cierre** de
`closeout-evidence.json` estén declaradas todas, `blocking: true` y con `evidence.length > 0`. Solo
entonces `stable-api-1.0.0.json` puede declarar `releaseStatus: 'guaranteed'`.

**Corrección del pase de veracidad del 2026-08-10:** ese `evidence.length > 0` comprueba **forma,
no contenido**. Nunca mira qué hay dentro del array. Es exactamente el hueco que dejó pasar los 14
recibos de `docs/design-system/evidence/` que resultaron ser stubs de dos claves
(`{"gateId": "static", "result": "passed"}`), sin comando, sin salida, sin fecha — medido en la
Task 6 del Frente 0 (2026-08-10). Ver [[estado|Estado de los goals]] y el Frente 1b.

**Corrección del pase del 2026-08-12:** ese diagnóstico se pagó. Hoy son **nueve** gates, no quince —bajaron a ocho el 2026-08-11 y subieron a nueve el 2026-08-14 al entrar `semanal-roles-phases`—,
sus ocho recibos traen `command`, `exitCode`, `durationMs` y `outputTail` (ya no son stubs), y el
contrato **dejó de exigir `status: 'passed'`** — `D-F1b-5`, 2026-08-11, porque exigirlo empujaba a
declarar aprobado lo que no lo estaba. Uno de los ocho está hoy `blocked` y eso es legítimo. Lo que
sigue siendo cierto es la forma del gate: cuenta elementos, no los lee
(ver [[gate-solo-cuenta-elementos-no-los-lee]]).
`tests/design-system/release-governance.test.mjs:79-80` exige que los **nueve gates de cierre** de
`closeout-evidence.json` estén todos `blocking: true` y con `evidence.length > 0`. **Ya no exige
`status: 'passed'` en todos**: D-F1b-5 (2026-08-11) retiró ese acoplamiento porque, con la versión
ya estable, exigirlo obligaba a declarar aprobados gates que no lo estaban — fue el incentivo que
produjo quince recibos `passed` sin ejecutar (el comentario de `release-governance.test.mjs:68-74`
lo documenta). Hoy `runtime-budgets` está `blocked` y el gate pasa, porque dice la verdad.

**Corrección del pase de veracidad del 2026-08-10, cerrada el 2026-08-12:** aquel
`evidence.length > 0` comprobaba forma, no contenido, y dejó pasar 14 recibos stub de dos claves
(Task 6, Frente 0). El Frente 1b lo arregló: los recibos de `docs/design-system/evidence/` llevan
hoy `command`, `exitCode`, `artifactSha256` y `sourceRef` reales, y
`tests/design-system/gate-receipt-content.test.mjs` abre cada recibo con `validarRecibo()`
(`scripts/design-system/gate-receipt.mjs`). Ver [[gate-solo-cuenta-elementos-no-los-lee]].

Ojo con el nombre del archivo: `stable-api-1.0.0.json` **no** se renombra en cada versión. Su
`targetVersion` sigue siendo `1.0.0` porque enumera la API que ganó la garantía SemVer en aquel
hito; su `designSystemVersion`, en cambio, sí acompaña a la versión viva.

Y la activación **no se repite en cada bump**: fue un hito único cumplido en `1.0.0`. Desde el
cierre de la 1.1.0, los gates aceptan cualquier SemVer con major ≥1 más `status: stable`, mediante
`ACTIVATED_VERSION_PATTERN` (`scripts/design-system-activation-git.mjs`), que comparten el gate y
sus tests. Antes comprobaban el literal `'1.0.0'` en tres sitios distintos y cualquier subida
declaraba el sistema no activado — ver [[version-escrita-a-mano-rompe-el-bump]].

Es decir: la garantía es hoy verificable en forma **y** en contenido — ver la corrección de
arriba. Ver [[baselines-y-presupuestos]] para los gates que miden, y
[[changelog-ds-encabeza-version-vieja]] para la fuente que **no** hay que leer para saber la
versión.

Mapa del área: [[design-system]].
