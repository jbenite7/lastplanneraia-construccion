# Improve App Plan · Módulo de ensamble del Plan de Compras

Tracker propio, separado del de PG→PI→PS (`docs/IMPROVE-APP-PLAN.md`), por decisión del usuario
del 2026-08-06. Alcance: `/plan-compras#/ensamble/*`, con foco en `#/ensamble/plan`
(`pdc-app/src/pages/PlanFechas.tsx`).

## Contexto

- **Fecha de arranque:** 2026-08-06. Proyecto de medición: **Da Porto** (id 73), el único con datos
  reales: 380 insumos en paquete, 20 filas de plan, 20 amarres. `PDC Sandbox E2E` está vacío.
- **Viewport de validación:** 1180×820, dark only (AGENTS.md).
- **Alcance de aplicación decidido por el usuario:** *alcance completo de la skill* — no me limito a
  CSS. Se aplica lo que cada fase justifique; se registra sin tocar solo lo que cambie dominio,
  RBAC o datos.
- **Artefactos:** este tracker + `docs/PDC-AUDIT.md` (hallazgos). El backlog ICE vive en el mismo
  `docs/EXPERIMENTS.md` del otro pase, para no partir la lista de pendientes en dos.

## Fase 1 · El job (abreviada, entrevista del 2026-08-06)

**Job statement (sin nombrar la app):**

> Cuando ya tengo el presupuesto cargado y los paquetes armados, quiero amarrar cada paquete a su
> frente del cronograma, para que el sistema pueda calcular por sí solo cuándo hay que arrancar
> cada contratación.

El job es de **ensamble**, no de alerta ni de reparto: la pantalla se contrata para *dejar el plan
armado*. La alerta (lo vencido) y el reparto (responsables) son consecuencias que solo existen si
el ensamble se completó.

**Dónde subentrega hoy** (tres áreas, dichas por el usuario):

| Dimensión | Subentrega declarada |
|---|---|
| Funcional | **Cuesta armarlo la primera vez.** Amarrar decenas de paquetes a frentes es largo; las propuestas del motor no se entienden o el desplegable no ofrece lo que se busca. |
| Funcional (mantenimiento) | **El plan se desactualiza y no se nota.** El cronograma se reprograma, o quedan paquetes amarrados que nadie recalculó y se vuelven invisibles. |
| Emocional / cognitiva | **No se entiende qué hacer ahora.** Cuatro pestañas, un botón «Recalcular» y un panel de correspondencias sin un siguiente paso evidente. |

**Big Hire vs Little Hire:** el usuario no lo decide y lo delega a la medición — se registra como
**ambos**, y las fases 2–3 deciden cuál duele más con evidencia.

Dato duro que ya inclina la balanza: en Da Porto hay **20 paquetes en el plan y 73 sin frente**
(78 % del trabajo de ensamble sin hacer, con el proyecto en marcha y 5 paquetes ya vencidos hasta
135 días). El armado inicial es el que está sin terminar.

## Phase Status

| Phase | Skill | Status | Artifact | Date |
|---|---|---|---|---|
| 1 | jobs-to-be-done | done — GATE abierto | este archivo, §Fase 1 | 2026-08-06 |
| 2 | ux-heuristics | done — 4 hallazgos (`P-1`, `P-4`, `P-5`, `P-7`), medidos en Da Porto | docs/PDC-AUDIT.md | 2026-08-06 |
| 3 | design-everyday-things | done — `P-4` (la acción por defecto es la destructiva y el botón apagado no dice su causa); registrado, no aplicado | docs/PDC-AUDIT.md | 2026-08-06 |
| 4 | refactoring-ui | done — `P-2` aplicado y verificado (nombre 39 → 287 px, fila 135 → 50 px), `P-6` registrado | docs/PDC-AUDIT.md | 2026-08-06 |
| 5 | microinteractions | pending — no llegó a correrse: la fase 2 topó con `P-1`, que hay que cerrar antes de juzgar el feedback de las acciones | docs/PDC-AUDIT.md | |
| 6 | made-to-stick | done — `P-3` aplicado («del valor · …»); el resto del copy de la pantalla se leyó y no dio más hallazgos | docs/PDC-AUDIT.md | 2026-08-06 |
| 7 | influence-psychology | skipped: app interna, sin paywall ni upsell (mismo motivo que el otro pase) | — | 2026-08-06 |
| 8 | high-perf-browser | pending | docs/PDC-AUDIT.md | |
| 9 | steve-jobs-design-review | pending | docs/PRODUCT.md | |

Statuses: pending · in-progress · awaiting-evidence · done · deferred: `<reason>` · skipped: `<reason>`

## Key Decisions

| Date | Phase | Decision | Rationale |
|---|---|---|---|
| 2026-08-06 | Intake | Tracker y artefactos propios para el PDC, backlog ICE compartido | Decisión del usuario; el PDC v2 es un subsistema aparte (SPA React), pero dos listas de pendientes divergen |
| 2026-08-06 | Intake | Alcance completo de la skill al aplicar, no solo CSS | Decisión del usuario |
| 2026-08-06 | Intake | Proyecto de medición: Da Porto | Único con datos reales; `PDC Sandbox E2E` da 0 paquetes y no permite auditar la pantalla llena |
| 2026-08-06 | Fase 1 | El job es de **ensamble**, no de alerta ni de reparto | Respuesta del usuario |
| 2026-08-06 | Fase 1 | Big/Little Hire se deja a la medición en vez de suponerlo | Respuesta del usuario; los datos de Da Porto apuntan al armado inicial |

## Next Actions

- [x] Fases 1, 2, 3, 4 y 6 cerradas el 2026-08-06. Siete hallazgos `P-1`…`P-7` en
      `docs/PDC-AUDIT.md`; dos aplicados y verificados en navegador, cinco registrados.
- [ ] **`P-1` es bloqueante y no tiene causa raíz confirmada.** Ver `docs/PDC-AUDIT.md` §P-1, que
      deja escritas las tres hipótesis ya descartadas con su medición. Merece una sesión propia de
      `systematic-debugging`. Los tres intentos de arreglo se revirtieron: `lib/agGrid.ts` está
      intacto.
- [ ] **Fase 5 (microinteractions) sin correr.** Juzgar si las acciones «se sienten vivas» mientras
      la grilla se pinta ilegible mediría el defecto equivocado. Va después de `P-1`.
- [ ] **Fase 8 (high-perf-browser) sin baseline.** `P-7` deja anotado el punto de partida: ocho
      peticiones API en paralelo al cargar, todas 200.
- [ ] **Fase 9 (revisión en frío) sin correr.** Tiene poco sentido emitir un veredicto de conjunto
      con `P-1` abierto.
- [ ] Tres decisiones esperando al usuario, en `docs/PDC-AUDIT.md` §Pendiente de decisión.
