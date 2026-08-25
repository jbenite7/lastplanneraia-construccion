---
capa: fuente
tipo: spec
estado: vigente
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

## Estado verificado — sigue vigente

Verificado contra el código el 2026-08-25. **`estado: vigente` aquí significa que el trabajo sigue abierto** — es una afirmación deliberada, no el valor por defecto del backfill.

**Qué falta:** idem: objetivo 1 cumplido, objetivo 2 (procedencia de corrida real) no, closeout-evidence.json:124-141

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
