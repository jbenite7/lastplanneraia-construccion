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
| 1 | jobs-to-be-done | pending — GATE, primera tarea del encaje | CUSTOMER.md | |
| 2 | ux-heuristics | in-progress — la corre la campaña (ciclo triple + barridos); volcado a DESIGN.md al cierre de cada fase | DESIGN.md, EXPERIMENTS.md | 2026-08-04 |
| 3 | design-everyday-things | pending — lente nueva: gulfs de Norman sobre el flujo PG→PI→PS | DESIGN.md, EXPERIMENTS.md | |
| 4 | refactoring-ui | in-progress — ídem fase 2, dentro del ciclo triple de la campaña | DESIGN.md, EXPERIMENTS.md | 2026-08-04 |
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

## Next Actions

- [ ] Fase 1 (jobs-to-be-done) como tarea temprana del plan de la campaña — entrevista al usuario,
  produce CUSTOMER.md (gate del resto)
- [ ] Volcar los hallazgos ya medidos de la campaña a DESIGN.md `## UX Audit Findings` y crear
  EXPERIMENTS.md con el backlog ICE (tarea de encaje, sin re-medir nada)
- [ ] Fase 3 sobre PG→PI→PS cuando la fase 4 de la campaña (comportamiento) esté cerrada
- [ ] Fases 5 y 6 tras la fase 3; fase 9 al cierre de la campaña
