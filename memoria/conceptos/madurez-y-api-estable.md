---
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

## Y qué garantiza la versión 1.0.0

`docs/design-system/version.json` es la fuente que manda sobre la versión viva. Que diga
`1.0.0 / stable` no es una etiqueta suelta: `tests/design-system/release-governance.test.mjs:66-72`
exige que **las quince gates de cierre** de `closeout-evidence.json` estén todas `blocking: true`,
`status: 'passed'`, con `verifiedAt` y evidencia no vacía. Solo entonces `stable-api-1.0.0.json`
puede declarar `releaseStatus: 'guaranteed'`.

Es decir: la garantía es verificable, no declarativa. Ver [[baselines-y-presupuestos]] para los
gates que miden, y [[changelog-ds-encabeza-version-vieja]] para la fuente que **no** hay que leer
para saber la versión.

Mapa del área: [[design-system]].
