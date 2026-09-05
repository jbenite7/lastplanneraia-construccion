---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-19
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-19-runtime-budgets-al-ci-design.md
resumen: runtime-budgets al CI, recortado a andamio — diseño
---

# runtime-budgets al CI, recortado a andamio — diseño

**Fase:** CP-F-AB del bloque 2 de [[TASKS|Cola de pendientes]], **recortada**. **Frente:** `runtime-budgets-al-ci`.

## La premisa vieja caducó, y esto es lo que queda

`gates-al-ci` se escribió el 2026-08-12 para «enchufar `full-app-flow` y `runtime-budgets` al job
`design-system-runtime` y pasarlos a `passed` con procedencia de CI (8/8)». Medido hoy sobre
`fc098810`, la mitad de ese encargo ya no existe:

- **La suite estática pasa 8/8 en local.** Los ocho carriles en verde, `audit` incluido. El rojo
  que arrastraba desde el 2026-07-17 —tres `!important` en `programa-general.css:621-623`— dejó de
  serlo porque **D-GAC-1 ya está implementada**: la aserción de `test_programa_general_sprint_contract.mjs`
  distingue `@layer` y esas tres reglas viven dentro de `@layer components`. No hay que tocar el CSS.
- **`test.C` ya está en el CI**: `docker-compose.ci.yml:21` lo lleva en `DEV_DOOR_USERS`. D-7 aplicada.
- De los nueve gates de `closeout-evidence.json`, **ocho están `passed` y uno `blocked`**:
  `runtime-budgets`.

Queda, entonces, un encargo mucho más chico que el que da nombre a la fase.

## Qué se decide

**Alcance del frente: dos cosas y ninguna más.**

1. Desbloquear `runtime-budgets` — el único gate en `blocked`.
2. Dar a `full-app-flow` procedencia de una corrida real de GitHub Actions. Hoy su recibo dice
   «regenerado **localmente**», y la condición de hecho de la fase pide CI.

## Por qué se recorta, dicho sin adornos

Esta fase cablea gates que **DS-F3 va a reemplazar enteros** («los 15 actuales se reemplazan, no se
arreglan», decisión del usuario del 2026-08-18). Invertir aquí más de lo mínimo es construir dos
veces.

Se hace igualmente porque sin CI verde no hay forma de **medir** nada de DS-F0: la auditoría
necesita un carril de referencia que se sepa sano. **Es andamio declarado, no inversión.**

## Posture

- **No tocar `public/css/programa-general.css`.** El rojo histórico ya no lo causa ese archivo.
- **No ampliar a otros gates** aunque estén a mano. Ocho ya pasan; tocarlos es riesgo sin premio.
- **No regenerar ningún baseline ni golden** sin autorización explícita del usuario: está en la
  lista de bloqueo incondicional.
- **Sin dependencias nuevas.**

## Leer primero

- `docs/design-system/closeout-evidence.json` — los nueve gates y su procedencia.
- `decisiones/gates-al-ci-ejecutor.md` — D-1 a D-7, con lo ya resuelto y lo medido.
- `.github/workflows/design-system.yml` — el `needs:` entre static y runtime.
- `AGENTS.md` §Verificación y §Publicación.

## Condición de hecho

`runtime-budgets` en `passed` y `full-app-flow` con procedencia de una corrida real de Actions, con
`npm run test:design-system:static` en `RC=0` sobre el sha que se publique.

---

## Estado verificado — sigue vigente, y ahora se sabe exactamente por qué

Re-medido el 2026-08-25 contra `docs/design-system/closeout-evidence.json`. **`estado: vigente`
sigue siendo correcto**, pero la nota anterior («objetivo 2 no») era demasiado vaga para actuar
sobre ella, y **su goal `goals/runtime-budgets-al-ci/goal.md` ya declara `## Cierre`** — así que uno
de los dos estaba mal. Medido: **el goal se adelantó, y le falta media condición**.

**Lo que sí está cumplido.** Los nueve gates están en `passed` (medido con `json.load`, no de vista),
y `runtime-budgets` **tiene la procedencia que se le exigía**: recibo real de la corrida de Actions
[32787664690](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32787664690),
`exitCode: 0`, `sourceRef: 6f9f69f7`.

**Lo que falta, y es la mitad literal de la condición de hecho.** La condición pide
«`runtime-budgets` en `passed` **y `full-app-flow` con procedencia de una corrida real de Actions**».
El gate `full-app-flow` está en `passed`, pero su evidencia dice **«Recibo regenerado localmente**
(13 tests, 1.0m)», con `verifiedAt: 2026-08-14` y `sourceRef: 79debf28`. Un recibo local no es una
corrida de Actions: es exactamente la distinción que esta spec existía para instaurar, así que
darla por buena vaciaría su propio propósito.

**Qué falta, en una línea:** bajar de una corrida verde de Actions el recibo de `full-app-flow` y
fijar su procedencia en `closeout-evidence.json`, como ya se hizo con `runtime-budgets`.

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].

---

## Cierre — 2026-09-04

**La media condición que faltaba quedó cumplida, y con procedencia real.** El recibo de
`full-app-flow` se bajó del artefacto `full-app-flow-receipt-light` de la corrida
[33902983755](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/33902983755) de
GitHub Actions, sobre `main` en `6d82bba2`: `result: passed`, `exitCode: 0`, `tree.dirty: false`,
13 tests en 1.5 min. El tema oscuro de la misma corrida también dio `passed`. Leído además de la
variable del paso «Summarize gate results»: `G_FULL_APP_FLOW: success`.

Fijado en dos tiempos, como exige el contrato: el recibo en `15b075c2` y el índice apuntando a ese
commit en `98dee120` (`sourceRef`, `artifactSha256` y `sourceFingerprint` recalculados;
`verifiedAt: 2026-09-04T17:59:11Z`; `fixtureSha256` sin cambio, `0a3617dd`, igual al
`CI_FIXTURE_SHA256` de la corrida). `npm run test:design-system:static` → `STATIC_RC=0` sobre
`98dee120`. Sustituye al recibo local del 2026-08-14 (`tree.dirty: true`, `sourceRef: 79debf28`).

No se ejecutó ningún gate en local para producir esta acta: `migrate-receipts.mjs` se descartó a
propósito porque ejecuta el comando en la máquina, que es justo la procedencia que esta spec vino a
dejar atrás.

**Por qué apareció el recibo ahora y no antes.** El gate llevaba días en rojo en Actions y se lo
tenía por «línea base caducada». No lo era: escondía tres bugs de código, arreglados en el PR #31
(`6d82bba2`). La primera corrida verde tras ese merge es la que produjo este recibo.

Condición de hecho: cumplida en sus dos mitades. Su goal, que se había adelantado al declarar
`## Cierre` el 2026-08-24, ahora coincide con la spec.
