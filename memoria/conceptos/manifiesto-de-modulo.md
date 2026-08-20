---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: scripts/design-system-contracts.mjs, docs/design-system/manifests/, module-manifest.schema.json
resumen: "El manifiesto es la declaración jurada de un módulo: qué rutas, componentes, vendors y tests consume; el gate rechaza declarar lo inexistente y closeout/goal-provenance sellan el cierre"
---
# El manifiesto de un módulo: su declaración jurada

Cada módulo migrado al design system tiene un archivo en `docs/design-system/manifests/` que
declara qué consume: `moduleId`, `routes[]`, `sources[]`, `components[]`, `vendors[]` y `tests[]`.
La forma la fija `module-manifest.schema.json` — que entre otras cosas exige `scenarios` con al
menos una entrada y capturas `golden` con su `sha256` (ver
[[manifiesto-ds-exige-golden]]).

**No es documentación: es contrato con gate.** `scripts/design-system-contracts.mjs:240-259` y
`:289-300` verifican que cada componente declarado exista en el catálogo, cada vendor en
`vendors.json`, y cada test **en disco**. Declarar un consumo inexistente rompe el gate estático;
consumir sin declarar deja al módulo fuera del censo que los scripts de partición de entrypoints
usan para decidir qué CSS sirve cada superficie
(`scripts/design-system-entrypoint-partition.mjs:312-336`).

Dos manifiestos tienen papel especial, y el gate los lista por nombre
(`design-system-contracts.mjs:33-34`):

- `programa-general.json` — el **piloto**: sus escenarios se validan uno a uno (`:340-358`).
- `laboratory.json` — el laboratorio, que declara `operational-fixtures.json` como fuente.

El resto de la familia sigue la regla general, incluidos los dos que no corresponden a un módulo
navegable clásico: `plan-compras-v2.json` (la SPA de `pdc-app/`) y `bi-runtime.json` (las
primitivas BI compartidas).

**Los dos selladores del cierre**, hermanos de esta familia:

- `closeout-evidence.json` — las **ocho gates de cierre** (eran quince hasta el 2026-08-11), cada
  una `blocking`, con fecha y evidencia. Es lo que activa la garantía 1.0.0 (ver
  [[madurez-y-api-estable]]). Ojo: el gate que las lee
  (`tests/design-system/release-governance.test.mjs:75-76`) comprueba `gates.length === 8`,
  `blocking === true` y `evidence.length > 0`, **nunca el contenido** del array — corregido el
  2026-08-10 tras medir que 14 de los recibos eran stubs de dos claves. Y **ya no exige
  `status: 'passed'`**: `D-F1b-5` retiró ese acoplamiento el 2026-08-11 y hoy uno de los ocho está
  `blocked` sin romper el contrato (ver [[gate-solo-cuenta-elementos-no-los-lee]]). La garantía es de
  forma, no de contenido.
- `goal-provenance.json` — la trazabilidad hacia el goal de gobernanza: `sourceCommit` de 40 hex y
  el `sha256` de cada fuente (`goal.md`, `facts.md`, `plan.md`), verificado byte a byte
  (`design-system-contracts.mjs:373-406`). Reescribir el goal sin re-certificar rompe el gate: la
  historia del cierre no se puede editar en silencio.

**Para qué sirve saberlo.** Antes de añadir un componente o vendor a un módulo migrado, toca su
manifiesto en la misma pasada; y antes de fiarte de lo que un módulo dice consumir, recuerda que el
manifiesto está *garantizado hacia el catálogo* pero el censo inverso (que nada consuma sin
declarar) descansa en los gates de entrypoint, no en este.

Mapa del área: [[design-system]] · el registro de qué módulo se migró cuándo:
[[IMPLEMENTATION_PLAN_INVENTORY|Registro de trabajo]].
