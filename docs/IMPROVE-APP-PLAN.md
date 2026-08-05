# Improve App Plan

## Context

- **Fecha de arranque:** 2026-08-04. App: Last Planner AIA (LPS para obra), web de escritorio,
  dark only, ≥1180 px (AGENTS.md).
- **Job por rol (intake):** Residente = compromisos semanales fiables; Director = anticipar lo que
  va a bloquear; Gerencia = demostrar avance con datos. La fase 1 lo mapea así, por rol.
- **Flujo más áspero (intake):** el flujo completo Programa General → Programación Intermedia →
  Programación Semanal (la cascada LPS entera, no un módulo suelto).
- **Evidencia (intake):** quejas directas de obra que le llegan al usuario, **sin registro
  escrito**; más las mediciones de la campaña de dark mode (105 truncamientos, 54 decisiones,
  8 barridos). Se dice explícito: no hay analítica ni tickets.
- **Plataforma:** solo web de escritorio → sin fase iOS. App interna de empresa, sin superficies
  de venta → fase 7 fuera.
- **Encaje decidido:** este journey corre **entrelazado con la campaña de cierre de dark mode**
  (`docs/superpowers/specs/2026-08-04-cierre-dark-mode-campana-decisiones-design.md`, sección
  «Adenda · improve-app»). Las fases 2–4 no se re-ejecutan desde cero: la campaña ya corrió esas
  lentes (ux-heuristics y refactoring-ui en 38 ciclos + 8 barridos) y sus hallazgos se vuelcan a
  los artefactos de este tracker. Las fases nuevas de verdad son 1, 3, 5, 6 y 9.

## Phase Status

| Phase | Skill | Status | Artifact | Date |
|---|---|---|---|---|
| 1 | jobs-to-be-done | done — GATE abierto; fases 2-9 desbloqueadas | CUSTOMER.md | 2026-08-04 |
| 2 | ux-heuristics | done — la corrió la campaña (ciclo triple + 8 barridos); 54 hallazgos volcados con severidad y disposición | docs/DESIGN-AUDIT.md, EXPERIMENTS.md | 2026-08-04 |
| 3 | design-everyday-things | done — lente de Norman sobre PG→PI→PS; 7 hallazgos nuevos (`N-1`…`N-7`) con severidad y C-14 medido y absorbido. **Nada aplicado:** todo es cambio de comportamiento, se registra y se pregunta | docs/DESIGN-AUDIT.md, EXPERIMENTS.md | 2026-08-05 |
| 4 | refactoring-ui | done — ídem fase 2, dentro del ciclo triple de la campaña; volcada en la misma tabla | docs/DESIGN-AUDIT.md, EXPERIMENTS.md | 2026-08-04 |
| 5 | microinteractions | pending — sobre las acciones diarias: confirmar compromisos, guardar celda, filtrar, importar | DESIGN.md, EXPERIMENTS.md | |
| 6 | made-to-stick | pending — copy in-app; absorbe C-27 (tildes) y deja C-33 (frase de dominio) como pregunta al usuario | POSITIONING.md, EXPERIMENTS.md | |
| 7 | influence-psychology | skipped: app interna de empresa, sin paywall ni superficies de upsell | — | 2026-08-04 |
| 8 | high-perf-browser | deferred: hasta que una medición o queja señale lentitud percibida | DESIGN.md, EXPERIMENTS.md | 2026-08-04 |
| 9 | steve-jobs-design-review | pending — revisión final en frío del flujo PG→PI→PS, tras cerrar la campaña | PRODUCT.md, DESIGN.md, EXPERIMENTS.md | |

Statuses: pending · in-progress · awaiting-evidence · done · deferred: <reason> · skipped: <reason>

## Key Decisions

| Date | Phase | Decision | Rationale |
|---|---|---|---|
| 2026-08-04 | Intake | El job se mapea por rol (Residente/Director/Gerencia), no como un job único | Decisión del usuario en el intake |
| 2026-08-04 | Intake | Flujo objetivo: la cascada completa PG→PI→PS | Decisión del usuario; es donde llegan las quejas de obra |
| 2026-08-04 | Intake | Fase 7 skipped, fase 8 deferred | App interna sin venta; sin evidencia de lentitud aún |
| 2026-08-04 | Encaje | Fases 2 y 4 se satisfacen con el ciclo triple y los barridos de la campaña de dark mode; un control exigido por varias reglas se cumple con una comprobación | Evitar re-auditar lo ya medido; política global de eficiencia |
| 2026-08-04 | Encaje | Ningún cambio de UI sin hallazgo de fases 1–3 detrás | Regla 8 de la skill; la campaña ya cumple (todo viene de mediciones) |
| 2026-08-04 | Fase 1 | El Residente entra por lo funcional (anticipar restricciones) y lo social defensivo (constancia de la causa); se descarta el miedo a «quedar mal al comprometerse» como motor | Respuesta del usuario en la entrevista |
| 2026-08-04 | Fase 1 | Subentrega declarada en las **cuatro** áreas ofrecidas: no hay dimensión sana que sostenga a las demás | Respuesta del usuario; sin analítica que la contraste |
| 2026-08-04 | Fase 1 | La fuga es Little Hire (uso repetido), no Big Hire: falla el ciclo semanal, no el arranque | Las cuatro subentregas son de uso diario y la app ya está implantada |
| 2026-08-04 | Fase 1 | La cascada PG→PI→PS es el cuello de botella de los **tres** jobs a la vez, no solo el flujo más usado | Si el residente no registra bien, el director no ve patrones y la gerencia no sostiene cifras |
| 2026-08-05 | Fase 3 | El artefacto de la fase 3 es `docs/DESIGN-AUDIT.md`, no el `DESIGN.md` de la raíz | Misma decisión que tomó la tarea de encaje: `DESIGN.md` es contrato de consumo, no registro de auditoría |
| 2026-08-05 | Fase 3 | C-14 se cierra **midiendo el indicio**, no aplicando el remedio: el caso del usuario fue «había indicio y no lo vi», y la marca resultó más apagada que el dato normal (1,35:1 entre ambos) con su canal de fondo muerto | Decisión del usuario sobre su propio caso; el remedio de peso visual queda como hallazgo con severidad, sin aplicar |
| 2026-08-05 | Fase 3 | Los 7 hallazgos `N-*` se registran, **ninguno se aplica** | Seis de siete son cambio de comportamiento o de contrato de accesibilidad; la regla de la fase es registrar y preguntar |
| 2026-08-04 | Encaje | La biblia de flujos (`docs/superpowers/specs/2026-08-04-biblia-de-flujos-design.md`) comparte backlog con este tracker: la matriz esfuerzo/impacto **es** EXPERIMENTS.md | Un solo backlog; evita dos listas de pendientes divergentes |

## Next Actions

- [x] Fase 1 (jobs-to-be-done) — cerrada el 2026-08-04 con entrevista al usuario; `docs/CUSTOMER.md`
  escrito con los tres jobs por rol, nueve dimensiones y las alternativas. Gate abierto.
- [x] Volcar los hallazgos ya medidos de la campaña a `## UX Audit Findings` y al backlog ICE
  (tarea de encaje, sin re-medir nada) — cerrada el 2026-08-04. Se creó `docs/DESIGN-AUDIT.md` en
  vez de extender el `DESIGN.md` de la raíz, que es contrato de consumo y no registro de auditoría;
  las 54 entradas A-*/B-*/C-* quedan con severidad 0-4 y disposición real, y las 10 que siguen
  abiertas sin task se añadieron al backlog compartido de `docs/EXPERIMENTS.md`.
- [x] Fase 3 (design-everyday-things) sobre PG→PI→PS — cerrada el 2026-08-05 (Task 22 / IA-3). Siete
  hallazgos `N-1`…`N-7` en `docs/DESIGN-AUDIT.md` con severidad (2 de gravedad 3, 5 de gravedad 2) y
  ocho tarjetas ICE en `docs/EXPERIMENTS.md`; C-14 medido y movido a `backlog ICE`. El aporte propio
  de la lente son **tres canales muertos o mudos** que ninguna captura enseña: `.ps-cell-empty-alert`
  sin regla CSS, el estado «guardando» que solo existe en la vista móvil excluida, y `role="status"`
  en 1 de las 4 declaraciones de `#save-status`.
- [ ] **Pregunta abierta al usuario, heredada de la fase 3:** N-2 (¿el candado de compromisos debe
  seguir siendo exclusivo del Admin, o el Residente puede reabrir dentro de una ventana?) y N-1 (¿se
  bloquean las celdas de restricción sin Responsable, en vez de revertirlas después?). Ninguna se
  toca sin su respuesta.
- [ ] **Límite que la fase 3 deja sin medir:** si el `⚠` de C-14 cae dentro del viewport de 1180 px
  sin desplazamiento horizontal. Es la otra explicación posible de «no lo vi» y exige navegador.
- [ ] Fases 5 y 6 tras la fase 3; fase 9 al cierre de la campaña
