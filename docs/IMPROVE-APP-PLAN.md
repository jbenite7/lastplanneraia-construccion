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
| 5 | microinteractions | done — las 4 acciones diarias desmontadas en Trigger/Rules/Feedback/Loops **midiendo en navegador**; 6 hallazgos (`M-1`…`M-6`), momento firma = confirmar compromisos. Solo `M-1` era CSS puro y se aplicó; los otros 5 tocan comportamiento y van al backlog | docs/DESIGN-AUDIT.md, EXPERIMENTS.md | 2026-08-05 |
| 6 | made-to-stick | done — score SUCCESs sobre el onboarding de Plan de Compras v2, estados vacíos, errores, CTAs y tooltips de PG→PI→PS; 6 hallazgos (`S-1`…`S-6`) y **11 cadenas reescritas** (S-1, S-2 y C-33). C-33 aplicado con la frase genérica del equipo, marcada **provisional** en el código. Lo de dominio se registra, no se toca | docs/POSITIONING.md, docs/DESIGN-AUDIT.md, EXPERIMENTS.md | 2026-08-05 |
| 7 | influence-psychology | skipped: app interna de empresa, sin paywall ni superficies de upsell | — | 2026-08-04 |
| 8 | high-perf-browser | deferred: hasta que una medición o queja señale lentitud percibida | DESIGN.md, EXPERIMENTS.md | 2026-08-04 |
| 9 | steve-jobs-design-review | pending — enganchada al cierre del Frente 1 (`docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md`). **No es «done»**: la única revisión en frío que existe (`docs/PRODUCT.md`, Task 31, 2026-08-05) midió la cascada **antes** de los arreglos que el Frente 1 va a aplicar; revisarla de nuevo ahora repetiría defectos ya censados y a punto de repararse. `docs/PRODUCT.md` necesitará una pasada de refresco cuando el Frente 1 cierre, no una creación desde cero — ya existe | PRODUCT.md, DESIGN.md, EXPERIMENTS.md | |

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
| 2026-08-05 | Fase 5 | El **momento firma es «confirmar compromisos»**, decidido por el usuario | Es el corazón del LPS y el acto de mayor consecuencia del ciclo semanal: sin él la app sería una hoja de cálculo compartida |
| 2026-08-05 | Fase 5 | De los 6 hallazgos solo se aplica `M-1` (feedback de hover/pulsación en los chips atenuados); los otros 5 se registran | Regla de la fase: lo que es solo CSS/feedback visual se aplica con su verificación; lo que toca comportamiento o contrato de accesibilidad va al backlog con ICE |
| 2026-08-05 | Fase 5 | El pulido del momento firma se **propone y no se aplica**, aun siendo el momento elegido | Sus dos mitades son contrato de accesibilidad (atar el botón a su causa) y animación nueva (sello de fase); ninguna cabe en «solo CSS de feedback» |
| 2026-08-05 | Fase 6 | **C-33 se aplica con la frase genérica del equipo, marcada como provisional en el código** en vez de dejarse como chip | Decisión del usuario: el estado vacío ya no deja a nadie sin salida, y la frase definitiva de su obra puede sustituirla después sin coste |
| 2026-08-05 | Fase 6 | La regla de aplicación del copy es **no-dominio se reescribe, dominio se registra**; `GLOSARIO.md` es la autoridad de nomenclatura | Reescribir «compromiso», «restricción» o «Autoprogramar» rompería el vocabulario compartido con la obra, que es justo lo que sostiene los tres jobs |
| 2026-08-05 | Fase 6 | `docs/POSITIONING.md` se escribe como **esqueleto mínimo**: solo `## Key Messages`, derivado de los tres job statements de `CUSTOMER.md` | App interna sin superficie de venta (la misma razón por la que la fase 7 está `skipped`); rellenar audiencia, categoría y diferenciadores sería inventar |
| 2026-08-05 | Fase 6 | El hallazgo de la fase: **el copy no falla por tono ni por longitud, sino por ausencia de paso siguiente** en los momentos de error y de vacío | Es la misma forma que encontró la fase 5 con las microinteracciones —el hueco siempre del mismo lado—, ahora en palabras |
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
- [x] **Límite que la fase 3 dejó sin medir, cerrado en la fase 5:** el `⚠ Sin asignar` de C-14 **sí
  cae dentro del viewport** — celda en `x = 411 → 514 px` sobre 1180, `scrollWidth == clientWidth ==
  1180` y `scrollX = 0`. Queda descartado el desplazamiento horizontal como explicación de «no lo
  vi»; lo que sigue en pie de C-14 es el peso visual del indicio y su canal de fondo muerto.
- [x] Fase 5 (microinteractions) — cerrada el 2026-08-05 (Task 29 / IA-4) con las cuatro acciones
  medidas en navegador: `## Microinteraction Inventory` en `docs/DESIGN-AUDIT.md`, seis hallazgos
  `M-1`…`M-6` y cinco tarjetas ICE. El patrón del hueco es constante: **falta siempre el estado
  «cargando»** (guardar celda e importar cronograma no lo tienen) mientras que error y validación
  están bien cubiertos. De paso quedó confirmado en vivo N-6 (KPI 56 · lista de 8).
- [ ] **Pregunta abierta al usuario, de la fase 5:** ¿el pulido del momento firma (M-6) se hace, y
  con cuál de sus dos mitades — atar el botón apagado a su causa, el sello de fase al confirmar, o
  ambas?
- [x] Fase 6 (made-to-stick) — cerrada el 2026-08-05 (Task 30 / IA-5). `docs/POSITIONING.md` creado
  con `## Key Messages` (uno por rol, derivados de `CUSTOMER.md`); §Score SUCCESs del copy in-app en
  `docs/DESIGN-AUDIT.md` con `S-1`…`S-6`; 4 tarjetas ICE nuevas. **Once cadenas reescritas y
  verificadas en navegador** a 1180×820 dark: 4 errores genéricos de PG/PI/PS («Error de red»,
  «Error cargando datos») que ahora dicen qué se perdió y qué hacer, 5 textos de PI sin tilde (el
  `C-27b` que la Task 20 recomendó) y el estado vacío de Control de Cambios. El total de hallazgos
  del audit pasa de 67 a **73**.
- [ ] **Pregunta abierta al usuario, de la fase 6:** ¿ratifica la frase provisional del estado vacío
  de Control de Cambios, o la sustituye por la de su obra? Y de `S-6`: ¿abre alguno de los tres
  textos de dominio (vacío del filtro en PS, los dos avisos de «sin Responsable AIA», los rótulos de
  las acciones)?
- [ ] **Fase 9 — no está cerrada; corrige una contradicción de esta misma tabla (Task 7 Frente 0,
  2026-08-10).** Esta casilla decía `[x]` y «cerrada el 2026-08-05» mientras la fila de la tabla
  `## Phase Status` de arriba seguía diciendo `pending` desde que se escribió el plan — las dos
  frases nunca coincidieron. Lo que el Task 31 sí hizo, y que sigue en pie: el barrido consolidado de
  las 28 superficies (contrato en verde) y una **primera** revisión en frío que produjo
  `docs/PRODUCT.md`. Esa revisión midió la cascada **antes** de los arreglos que trae el Frente 1, así
  que no cierra la fase 9: la revisión que sí la cierra es la que corre **al terminar el Frente 1**,
  sobre la cascada ya arreglada (`docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md`).
  Detalle de lo ya hecho por Task 31:
  1. **Barrido consolidado** de las 28 superficies vivas (22 de la app + 6 de `admin/`) a 1180×820
     dark con las tres lentes en orden. **Cero regresiones y cero rojos de contrato:** 28/28 en 200,
     **0 errores de consola**, **0 desbordamiento horizontal**, **28/28 con `<main>` y `h1` real**,
     **0 celdas y 0 cabeceras recortadas** en las seis superficies de rejilla. De los cinco hallazgos
     top del barrido del 2026-08-03: dos cerrados, uno cerrado salvo en `/control-cambios`, uno
     **no reproducible** (el mojibake: 0 coincidencias en 7 superficies) y uno atenuado. **9
     hallazgos nuevos** (`F-1` … `F-9`), ninguno bloqueante, todos registrados sin aplicar. Detalle
     en `docs/superpowers/barrido-diseno-2026-08-03.md` §Pasada final.
  2. **IA-6 · revisión en frío** del flujo PG→PI→PS como usuario nuevo. Nombrada la Única Cosa
     («cada semana la obra promete solo lo que puede cumplir, y al terminar se sabe si cumplió»),
     contados los **13 pasos y la semana entera** hasta el primer dato útil, y emitido el veredicto
     binario: **NO para un usuario nuevo sin acompañamiento; SÍ para uno entrenado.** Tres cortes
     propuestos —**ninguno aplicado, nada borrado**— y cinco arreglos ordenados. Seis entradas
     nuevas (`R-1` … `R-6`), con `R-1` (la app no tiene páginas de error: 404 y 403 responden 13
     bytes de texto plano) marcada **bloqueante**.
  3. **`docs/PRODUCT.md` creado** con `## Outcome Roadmap` en cuatro cajones (bloqueante · ahora ·
     diferible · decisiones de dominio) y **`docs/DESIGN-AUDIT.md` refrescado a su disposición real
     de cierre**: las 11 filas `pendiente (Task N)` a `done`, `N-1` a `done` y `N-2` a
     `cerrado sin código` por decisión del usuario, y 15 entradas nuevas. Total 73 → **88**, con
     `pendiente` en **cero**.
- [ ] **Lo que queda abierto no es trabajo, son decisiones.** Diez, recogidas una a una en
  `docs/DESIGN-AUDIT.md` §Pendiente de decisión del usuario: las 9 excepciones de a11y de
  `.pdc-header`, los dos goldens ciegos (fila de capítulo en PG, estado bloqueado en PI), la
  evidencia huérfana de `programa-general-actualizar`, la ratificación de la frase de C-33, el hex
  del toast en `styles.css:936`, el rojo de `foundation.test.mjs` por mtimes, el hueco de
  `applySharedConstraints`, las tres auditorías de la Task 27, los cortes de producto y el tema
  oscuro del informe dentro de Power BI (tarea del usuario, fuera del repo).
