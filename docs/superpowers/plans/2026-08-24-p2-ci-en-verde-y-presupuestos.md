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

- [x] Reproducir el fallo con el contenedor montando el árbol correcto
- [x] Diagnosticar con `superpowers:systematic-debugging`. **No regenerar snapshots ni baselines
      para forzar el verde** — un cambio visual exige aprobación explícita
- [x] Arreglar la causa, no la aserción

**Verificación:** el paso 25 en `success` en una corrida real de Actions.

## Tarea 2 — Los gates en serie dejan de esconderse

Es la tercera vez en una jornada que destrabar un gate destapa el de atrás. El defecto no son los
gates: es la serie.

- [x] Medir cuántos pasos del job son independientes de verdad
- [x] Que un rojo **no cancele** los pasos posteriores independientes, o que el resumen liste todos
      los estados aunque uno falle
- [x] Alternativa barata si la anterior sale cara: volcar a `GITHUB_STEP_SUMMARY` (converge con G8,
      Tarea 6)

**Verificación:** una corrida con un gate roto a propósito enseña el estado de los demás.

## Tarea 3 — Cerrar `runtime-budgets-al-ci`

Fase 1 del plan `2026-08-19-runtime-budgets-al-ci.md`, sha verificado `c23b1c6a`. Desbloquea el
único gate `blocked` de los nueve de `closeout-evidence.json`.

- [x] Confirmar que la baseline que P1 dejó publicada es la de Actions
- [x] **Fase 2 no tiene nada que arreglar.** La medición **solo puede producirse dentro de GitHub
      Actions**: exige `CI_RUN_ID`, `CI_GIT_SHA` y dos huellas más contra un worktree limpio. No es
      un baseline caducado — pero sí faltaba cablear `gate-receipt.mjs` para que la corrida dejara
      recibo; eso estaba «redactado y sin aplicar» y se aplicó aquí
- [x] Fase 3: tomar la procedencia en cuanto CI pase

**Verificación:** `closeout-evidence.json` sin gates `blocked`.

## Tarea 4 — Cerrar `gates-al-ci` (CP-F-AB recortado)

Sus dos decisiones ya están confirmadas por Felipe y sin ejecutar.

- [x] `test.C` en `DEV_DOOR_USERS` de `docker-compose.ci.yml` — ya estaba, de trabajo previo
- [x] Fijar el baseline acordado — ya estaba fijado en 0.4.0, de trabajo previo
- [x] Re-medir 9/9 (el noveno es `atomic-commit`, no contaba en el «8/8» original) y publicar

**No se amplía.** Cablear dos gates que DS-F3 reemplazará solo se justifica porque sin CI verde no
se mide DS-F0.

## Tarea 5 — G4 · Filtros de ruta

- [x] Excluir de los triggers lo que ningún gate lee: `memoria/**` y los `.md` de raíz
- [x] **`docs/design-system/` es contractual y NO se excluye**

## Tarea 6 — G7 y G8

- [x] G7: **medir duración por paso primero**. `full-app-flow`, `semanal-roles-phases` y
      `runtime-budgets` ya vuelcan su `durationMs` al resumen. **Paralelizar PHPStan en su propio
      job queda sin hacer** — necesita datos de varias corridas para confirmar que vale el costo de
      un `checkout` + `setup` adicional, no solo la primera medición
- [x] G8: volcar a `GITHUB_STEP_SUMMARY` los recibos y presupuestos que ya se generan

## Tarea 7 — zizmor

- [x] Auditoría de seguridad del YAML, complementaria a actionlint. Exige tooling extra — instalado
      vía `brew install zizmor`. 4 hallazgos: 2 «credential persistence» corregidos
      (`persist-credentials: false`); 2 «cache-poisoning» (confidence: Low) documentados como
      riesgo evaluado y aceptado — el repo resultó ser **público** (confirmado con `gh repo view`,
      no asumido), pero eliminar el cache de Docker vía GHA tiene costo de performance real y va
      contra G7

## Tarea 8 — Renombrar `design-system.yml` → `ci.yml`

Decisión de Felipe, 2026-08-20. **Micro-frente propio, idealmente junto a G4**, que también toca los
triggers. El nombre quedó pequeño: el workflow custodia el repo entero.

- [x] Barrido de referencias por ruta: `visual-ci-contract.test.mjs`, scripts, docs, `gh run list
      --workflow=`
- [x] Asumir que **parte el historial de corridas**

### Cierre — 2026-08-24

`git mv .github/workflows/{design-system,ci}.yml`. Barrido de referencias por ruta literal
confirmado archivo por archivo, no solo grep superficial: actualizadas las tres pruebas que leen
el YAML por ruta (`visual-ci-contract.test.mjs`, `ci-workflow-provenance.test.mjs`,
`phpstan-baseline.test.mjs`, 8 ocurrencias) y las referencias vivas en `CLAUDE.md`, `DESIGN.md` y
la trampa de memoria `el-archivo-que-tocas-puede-tener-un-contrato.md`. Los ~25 hits restantes
(`goals/*/goal.md` con `estado: cerrado` o cuyo contenido ya narra su propio cierre, planes y specs
de `docs/superpowers/` ya ejecutados, `decisiones/`, `docs/reportes/estado-desarrollo.html`,
`memoria/log.md`, `memoria/mapas/qa-y-gates.md`) se dejan intactos a propósito: narran eventos ya
ocurridos cuando el archivo se llamaba `design-system.yml`, y reescribirlos sería reescribir
historia, no corregir una referencia rota.

**Verificación:** `actionlint .github/workflows/ci.yml` → `RC=0`. `npm run test:design-system:static`
→ 8/8. `node --test` sobre las tres pruebas que leen la ruta → 22/22. Publicado vía
`scripts/publicar.sh` en `3c670c5c` (`origin/main` confirmado con fetch + rev-parse, sin
ahead/behind). **Corrida real de GitHub Actions con el nombre nuevo:**
[32791129071](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32791129071)
(`gh run list --workflow=ci.yml`) sobre `3c670c5c` — `design-system-static` y `design-system-runtime`
en `success`, corrida completa en `success`. La anotación «Process completed with exit code 1» que
muestra `gh run view` es de un paso `continue-on-error: true` de los gates no bloqueantes (P2 Tarea
2); no afecta la conclusión del job ni de la corrida.

Como se advirtió: renombrar partió el historial de corridas — `gh run list --workflow=design-system.yml`
ya no devuelve nada; las corridas viejas quedan asociadas al nombre retirado.

---

## Fuera de este plan, y para Felipe

**G6 · branch protection / merge queue** cambia el flujo de publicación de **todas** las sesiones
(`publicar.sh` → PRs). No se aplica sin visto explícito.

## Condición de hecho

Una corrida de Actions con todos los pasos en `success`; `closeout-evidence.json` sin `blocked`; y
`runtime-budgets-al-ci` y `gates-al-ci` con su `## Cierre` escrito.

## Cierre — 2026-08-24

**Cumplida, Tareas 1–7.** Tres corridas reales de GitHub Actions confirman el trabajo, cada una
sobre el sha exacto publicado:

- [32776968532](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32776968532) —
  evidencia del `actual.png` real que fundamentó la recaptura del golden (Tarea 1).
- [32786522052](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32786522052) —
  primera corrida **completa en verde**, sobre `41be8484`: confirma Tareas 1 y 2.
- [32789042846](https://github.com/jbenite7/lastplanneraia-construccion/actions/runs/32789042846) —
  segunda corrida completa en verde, sobre `920b38df` (incluye el commit que actualiza
  `closeout-evidence.json`): confirma Tareas 3–7 juntas.

`docs/design-system/closeout-evidence.json`: **9/9 gates en `passed`**, ninguno `blocked` —
verificado con `python3` sobre el índice. `runtime-budgets-al-ci` y `gates-al-ci` cierran con su
`## Cierre` escrito (ver ambos `goal.md`).

**Tarea 8 (renombrar `design-system.yml` → `ci.yml`) quedó fuera de este cierre, a propósito.** No
formaba parte de la condición de hecho de este plan: es un cambio de mayor alcance (60+ archivos
mencionan «design-system», y el propio plan advierte que parte el historial de corridas de GitHub),
y se difirió como micro-frente propio para no apurarlo al cierre de este.

**Ejecutada más tarde el mismo día**, ya como frente aparte y con su corrida real de Actions
confirmada: ver «Tarea 8 → ### Cierre — 2026-08-24» más arriba en este documento, y el sha
publicado `3c670c5c`. Este párrafo describe el estado en el momento del cierre de las Tareas 1–7,
no el estado final del plan.

**Verificación del gate de cierre:** `bash scripts/publicar.sh --solo-verificar` sobre `920b38df`,
4/4 en verde (`design-system:static`, contrato piloto PG, wiki forma, wiki veracidad+pruebas). El
sha ya estaba publicado (push incremental por tarea, siguiendo la regla de commits atómicos), y
`git rev-parse origin/main` == `920b38df03ee15ca9cd1843391a31c47ab96e65f`.

**Pendientes anotados en `TASKS.md`, no bloqueantes:** paralelizar PHPStan (G7, sin datos
suficientes todavía) y los dos hallazgos de `cache-poisoning` de zizmor (riesgo evaluado, aceptado
por ahora).
