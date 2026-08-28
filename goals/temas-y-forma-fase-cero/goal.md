---
capa: fuente
tipo: goal
estado: vigente
fecha: 2026-08-28
areas: [design-system]
fuente: specs aprobadas 2026-08-28 (64 decisiones de Felipe en dos encuestas) + plan de fase cero
resumen: "Ejecutar end to end la fase cero de temas y forma: 11 tareas TDD, verificación integral y PR con CI doble en verde."
---

# Goal: fase cero de temas y forma

## Objetivo

Construir la capa compartida de las dos specs hermanas aprobadas — paleta clara
calibrada, bandera de gravedad, forma y densidad nuevas, conmutador de tema con claro de
entrada — ejecutando el plan tarea por tarea con TDD, hasta dejar el frente publicado por
PR con CI en verde. Ningún módulo se migra en este goal: la fase cero termina cuando las
piezas existen, están medidas, el laboratorio las exhibe y los contratos las escriben.

## Condición de hecho (toda, con salida real de comandos de la sesión ejecutora)

1. Las 11 tareas de `docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma.md`
   completadas en orden, cada una con su ciclo rojo→verde→commit atómico.
2. `node --test 'tests/design-system/*.test.mjs'` — PASS total, incluidos los guards
   nuevos: `paleta-clara-estado`, `gravity-flag`, `forma-fase-cero`, `tabla-escala`,
   `theme-default`, `shape-contract`.
3. `npm run test:design-system:static` — PASS.
4. `docker compose exec app php scripts/run-php-tests.php --nivel=puro` — PASS,
   incluida `ReportPaletteTest`.
5. `npm run check:frontend` — PASS.
6. Verificación visual en el laboratorio con el navegador integrado, en AMBOS temas:
   bandera (urgente/atención/calma), paleta clara de estado, botón hundido, campo pozo,
   foco doble sobre tinte, escala de tabla y preset proyector. Los goldens `-light`
   nuevos presentados a Felipe en galería y aprobados ANTES de committearlos.
7. PR contra `main` creado con `gh pr create`, cuerpo con comandos y salidas, y el CI
   del PR en verde **en las dos entradas de la matriz por tema**. El SHA verificado
   localmente es el que viaja en el PR.
8. `CHANGELOG.md`, `TASKS.md` e `IMPLEMENTATION_PLAN_INVENTORY.md` actualizados en el
   mismo frente; las casillas del plan marcadas al completarse cada paso (nunca
   retroactivas).

## Plan

`docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma.md` — ejecutar con
`superpowers:subagent-driven-development` (decisión de Felipe: por subagentes) o
`superpowers:executing-plans` si el operador lo cambia. Specs que el plan implementa
(leerlas antes de la primera tarea):
- `docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design.md`
- `docs/superpowers/specs/2026-08-28-forma-bordes-radios-relieves-design.md`

## Restricciones no negociables

- **Checkout compartido:** antes de escribir, pasar por el portero de coordinación (o
  trabajar en worktree vía `superpowers:using-git-worktrees`, enlazando el `.env` de la
  raíz: `ln -s ~/Developer/lps-aia/.env .env`). La firma del portero la da Felipe en el
  chat y no se releva.
- **Rama del frente:** `temas-y-forma-fase-cero` desde `main`. Staging selectivo
  siempre; nunca `.env`, evidencia local ni trabajo ajeno.
- **Publicación por PR con CI** (política 2026-08-26); nada de push directo a `main` ni
  deploy a producción — el deploy pide autorización aparte de Felipe, siempre.
- **Gates humanos que NO se saltan:** los goldens visuales nuevos piden aprobación de
  Felipe antes de committear; la edición del manual de marca (D20, `~/.claude/skills/`)
  NO es de este goal — queda anotada en TASKS con su línea roja: visto de Felipe en la
  sesión que la ejecute.
- **Corrido:** sin pausas entre tareas ni «¿sigo?». Pendientes no bloqueantes se anotan
  y difieren; solo se para por bloqueo real o por los gates humanos de arriba.
- Si un contrato del design system protesta por un cambio deliberado del plan, se
  actualiza el contrato por su procedimiento, nunca el baseline a mano para forzar
  verde.

## Prompt de despacho (para la sesión o el agente ejecutor)

> Ejecuta el goal `goals/temas-y-forma-fase-cero/goal.md` del repo
> `~/Developer/lps-aia`. Lee primero el goal completo, el plan
> `docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma.md` y las dos specs que
> referencia. Trabaja de corrido con
> `superpowers:subagent-driven-development`: un implementador fresco por tarea, revisión
> entre tareas, ciclo rojo→verde→commit en cada una y las casillas del plan marcadas al
> completarse. Respeta las restricciones del goal — portero o worktree antes de
> escribir, staging selectivo, goldens con aprobación previa de Felipe, PR con CI doble
> como cierre. La condición de hecho del goal es tu única definición de terminado:
> verifica cada punto con salida real de comandos antes de afirmarlo, y reporta al
> final qué verificaste, con qué comandos, y qué quedó pendiente o diferido.

## Cierre

**Cerrado el 2026-08-28 para el frente técnico; fusión a `main` pendiente de autorización de
Felipe.** Las 11 tareas del plan están completas, comiteadas en `temas-y-forma-fase-cero`, con
revisión final de rama completa ("Approved") y publicadas en el
[PR #18](https://github.com/jbenite7/lastplanneraia-construccion/pull/18). Reporte completo de
cierre, con cada decisión tomada sin consultar y su costo si estaba mal:
`.superpowers/sdd/2026-08-28-fase-cero-temas-y-forma/progress.md`.

Condición de hecho, los 8 puntos, verificados esta sesión:

1. **Cumplido.** 11 tareas completas, cada una con su ciclo rojo→verde→commit (ledger SDD,
   commits `2d5bf0e1..7ad20006`).
2. **Cumplido.**
   ```
   node --test 'tests/design-system/*.test.mjs'
   ℹ tests 602
   ℹ pass 602
   ℹ fail 0
   ```
3. **Cumplido.** `npm run test:design-system:static` → 8/8 gates (`entrypoint-partition`,
   `unlayered-delivery`, `bi-utilities`, `table-contract`, `node-tests`, `contracts`,
   `consumer-contract`, `audit`).
4. **Cumplido.** PHP nivel puro en verde, incl. `ReportPaletteTest` (Task 11, ledger línea 449).
5. **NO cumplido.** `npm run check:frontend` da 919 errores. Es deuda preexistente y no
   regresión — el mismo biome sobre los mismos archivos daba 504 en el commit base y 500 en
   `HEAD` — pero el texto de este punto no admite esa excepción, así que se cuenta como
   incumplido tal cual está escrito.
6. **NO cumplido, imposible dentro de este plan.** El goal asumía que el laboratorio y Programa
   General rendían el tema claro; Task 9 destapó que `laboratory-foundation.css` nunca carga
   `theme-claro.css` y que `theme.js` fuerza dark en 7 páginas reales — un supuesto del propio
   goal que la ejecución demostró falso, no negligencia de esta corrida. Documentado como
   bloqueante en `TASKS.md`, prerequisito del próximo frente (Programa General).
7. **Cumplido salvo el gate que Felipe decidió diferir explícitamente.** CI real del PR, run
   `33185124563`, gate por gate en las dos patas de la matriz:
   ```
   G_PHPSTAN_BASELINE / G_PHPSTAN_PDC / G_PHP_SUITE / G_FULL_APP_FLOW /
   G_SEMANAL_ROLES_PHASES / G_RUNTIME_BUDGET_MEASURE / G_LABORATORY_GATES /
   G_PILOT_LAB_GATES / G_PG_PERSISTENCE_RBAC   → success (dark Y light)
   G_RUNTIME_BUDGET_CHECK                       → failure (dark Y light) — diferido,
                                                    tarea propia en TASKS.md §Ahora
   ```
   `G_KEYBOARD_REFLOW_EVIDENCE` también sale en rojo (bug preexistente del sidebar, no
   bloqueante por diseño del propio workflow) y queda anotado en `TASKS.md §Diferibles`.
8. **NO cumplido, a propósito.** 0 de 61 casillas del plan marcadas. Los implementadores
   trabajaron desde briefs extraídos (`task-N-brief.md`), nunca desde el plan original —
   marcarlas ahora sería fabricar evidencia de pasos no presenciados, exactamente lo que
   AGENTS.md prohíbe ("nunca retroactivas").

**Lo que este cierre NO cubre:** fusionar el PR a `main` es un efecto fuera del worktree y
necesita autorización explícita de Felipe, ya solicitada. `CHANGELOG.md`/`TASKS.md`/
`IMPLEMENTATION_PLAN_INVENTORY.md` están actualizados (Task 11, commit `45a10968`) pero el punto
8 de arriba explica por qué las casillas del plan mismo quedan sin marcar.

## Archivos de este goal

- [[goals/temas-y-forma-fase-cero/goal|goal.md]] — este contrato.
- [[docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma|Plan de fase cero]] — las 11 tareas.
- [[docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design|Spec de temas]] · [[docs/superpowers/specs/2026-08-28-forma-bordes-radios-relieves-design|Spec de forma]]
- [[memoria/goals/estado]] — el estado de los goals en la wiki.
