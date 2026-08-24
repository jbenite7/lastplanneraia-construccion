---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-24
areas: [proceso, design-system]
fuente: docs/superpowers/plans/2026-08-24-p2-ci-en-verde-y-presupuestos.md
resumen: "P2 · Poner el CI entero en verde (incluido el paso 25 que quedó al descubierto), cerrar runtime-budgets y gates-al-ci, y pagar la deuda de CI diferida: G4, G7, G8 y zizmor"
---

# P2 · El CI en verde y los presupuestos

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`. Las Tareas 4–7 sí son
> repartibles entre subagentes: tocan ficheros distintos y ninguna publica.

**Spec:** [[docs/superpowers/specs/2026-08-24-estado-consolidado-del-repo-design]]
**Depende de:** P1 completo.

**Goal:** que el workflow del CI pase entero, y que `runtime-budgets-al-ci` y `gates-al-ci` cierren.

**Por qué antes que el design system:** sin CI verde no hay forma de **medir** nada de DS-F0 en
adelante. Es andamio declarado, no inversión — DS-F3 va a reemplazar estos gates.

---

## Tarea 1 — El paso 25, que estaba escondido

`Run pilot lab gates (Programa General)` falla. Venía `skipped` y nadie lo había visto: los gates de
ese job corren **en serie**, así que cada rojo tapa al siguiente.

- [ ] Reproducir el fallo con el contenedor montando el árbol correcto
- [ ] Diagnosticar con `superpowers:systematic-debugging`. **No regenerar snapshots ni baselines
      para forzar el verde** — un cambio visual exige aprobación explícita
- [ ] Arreglar la causa, no la aserción

**Verificación:** el paso 25 en `success` en una corrida real de Actions.

## Tarea 2 — Los gates en serie dejan de esconderse

Es la tercera vez en una jornada que destrabar un gate destapa el de atrás. El defecto no son los
gates: es la serie.

- [ ] Medir cuántos pasos del job son independientes de verdad
- [ ] Que un rojo **no cancele** los pasos posteriores independientes, o que el resumen liste todos
      los estados aunque uno falle
- [ ] Alternativa barata si la anterior sale cara: volcar a `GITHUB_STEP_SUMMARY` (converge con G8,
      Tarea 6)

**Verificación:** una corrida con un gate roto a propósito enseña el estado de los demás.

## Tarea 3 — Cerrar `runtime-budgets-al-ci`

Fase 1 del plan `2026-08-19-runtime-budgets-al-ci.md`, sha verificado `c23b1c6a`. Desbloquea el
único gate `blocked` de los nueve de `closeout-evidence.json`.

- [ ] Confirmar que la baseline que P1 dejó publicada es la de Actions
- [ ] **Fase 2 no tiene nada que arreglar.** La medición **solo puede producirse dentro de GitHub
      Actions**: exige `CI_RUN_ID`, `CI_GIT_SHA` y dos huellas más contra un worktree limpio. No es
      un baseline caducado
- [ ] Fase 3: tomar la procedencia en cuanto CI pase

**Verificación:** `closeout-evidence.json` sin gates `blocked`.

## Tarea 4 — Cerrar `gates-al-ci` (CP-F-AB recortado)

Sus dos decisiones ya están confirmadas por Felipe y sin ejecutar.

- [ ] `test.C` en `DEV_DOOR_USERS` de `docker-compose.ci.yml`
- [ ] Fijar el baseline acordado
- [ ] Re-medir 8/8 y publicar

**No se amplía.** Cablear dos gates que DS-F3 reemplazará solo se justifica porque sin CI verde no
se mide DS-F0.

## Tarea 5 — G4 · Filtros de ruta

- [ ] Excluir de los triggers lo que ningún gate lee: `memoria/**` y los `.md` de raíz
- [ ] **`docs/design-system/` es contractual y NO se excluye**

## Tarea 6 — G7 y G8

- [ ] G7: **medir duración por paso primero**. Candidato: PHPStan como job paralelo — no necesita la
      app levantada
- [ ] G8: volcar a `GITHUB_STEP_SUMMARY` los recibos y presupuestos que ya se generan

## Tarea 7 — zizmor

- [ ] Auditoría de seguridad del YAML, complementaria a actionlint. Exige tooling extra

## Tarea 8 — Renombrar `design-system.yml` → `ci.yml`

Decisión de Felipe, 2026-08-20. **Micro-frente propio, idealmente junto a G4**, que también toca los
triggers. El nombre quedó pequeño: el workflow custodia el repo entero.

- [ ] Barrido de referencias por ruta: `visual-ci-contract.test.mjs`, scripts, docs, `gh run list
      --workflow=`
- [ ] Asumir que **parte el historial de corridas**

---

## Fuera de este plan, y para Felipe

**G6 · branch protection / merge queue** cambia el flujo de publicación de **todas** las sesiones
(`publicar.sh` → PRs). No se aplica sin visto explícito.

## Condición de hecho

Una corrida de Actions con todos los pasos en `success`; `closeout-evidence.json` sin `blocked`; y
`runtime-budgets-al-ci` y `gates-al-ci` con su `## Cierre` escrito.
