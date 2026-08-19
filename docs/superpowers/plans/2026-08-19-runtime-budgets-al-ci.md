# Plan — runtime-budgets al CI (andamio)

**Spec:** `docs/superpowers/specs/2026-08-19-runtime-budgets-al-ci-design.md` · **Estado:** aprobado
el 2026-08-19, listo para ejecutar. **Esfuerzo:** media jornada.

## Tanda 1 — Por qué está `blocked`, medido y no recordado

Leer el recibo de `runtime-budgets` en `closeout-evidence.json` y ejecutar el gate a mano para ver
el fallo real. **No se supone la causa: se reproduce.** Si el motivo del bloqueo resultó caducado
—como caducó el de la fase entera—, decirlo y seguir.

- **Verifica:** salida del gate pegada en el `goal.md`, con el sha.

## Tanda 2 — Desbloquear

Arreglar la causa con el cambio mínimo. Si la única salida pasa por tocar un baseline o cambiar lo
que el gate mide, **PARAR y escalar**: las dos cosas están en la lista de bloqueo incondicional.

- **Verifica:** el gate en verde localmente; `npm run test:design-system:static` en `RC=0`.

## Tanda 3 — Procedencia de CI

Publicar y observar una corrida real de Actions. Tomar de ella la procedencia de `full-app-flow` y
de `runtime-budgets`, y escribirla en `closeout-evidence.json`.

Ojo con el orden: **publicar exige el visto de la coordinadora sobre el sha medido** (gate de
cierre, pasos 6-8). No se empuja para ver qué pasa.

- **Verifica:** `closeout-evidence.json` sin ningún `blocked`, con `runId` de una corrida real.

## Riesgos y reversas

- **El bloqueo es un baseline desactualizado** → no se regenera; se escala. Ya pasó en
  `contadores-cero`, donde regenerar habría congelado deriva ajena.
- **La corrida de CI falla por algo ajeno** → se anota con su sha y se escala; no se estrecha el
  gate para que pase.
