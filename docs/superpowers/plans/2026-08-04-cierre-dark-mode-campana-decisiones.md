# Campaña de cierre de dark mode — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ejecutar las 54 decisiones del registro de dark mode (5 fases F*) entrelazadas con el journey improve-app (6 tasks IA-*), hasta que ninguna entrada quede «pendiente de criterio».

**Architecture:** App PHP 8.3 sin framework (front controller + FastRoute), vistas en `views/`, CSS por capas con tokens `--ds-*` en `public/css/`, tablas Handsontable/DataTables configuradas en `public/js/modules/*/hot.js`. Verificación: suite estática Node + Playwright + navegador integrado.

**Tech Stack:** PHP 8.3 (Docker `app`), MySQL 8 (`db`), Handsontable, DataTables, Bootstrap (legacy), Playwright, node --test.

## Global Constraints

- **Subagentes: modelo `opus` (Opus 5), esfuerzo bajo — directiva del usuario del 2026-08-04. Sin excepciones.**
- Spec fuente: `docs/superpowers/specs/2026-08-04-cierre-dark-mode-campana-decisiones-design.md`. Cada task cita su entrada (A-*/C-*/IA-*).
- Desktop ≥1180 px, dark only; viewport canónico **1180×820** (AGENTS.md). Nada de mobile/tablet/`linen`.
- Sesión SIEMPRE por `http://localhost:8081/dev/entrar?u=test.R&p=...` (o `test.A` para admin/lab). Nunca `/login`.
- Proyectos con datos «Da Porto» y «Optimización Aeropuerto JMC»: **solo lectura**. Mutables: el sandbox `PDC Sandbox E2E` y el proyecto 27 «Prueba» (ampliación decidida por el usuario el 2026-08-04, al descubrirse que el sandbox solo monta el módulo PDC y una semana, insuficiente para la suite de PS).
- Colores solo con tokens `--ds-*`; sin hex **ni siquiera en comentarios** (el audit los cuenta ahí).
- `npm run test:design-system:static` debe dar **8/8** antes de cerrar cualquier task.
- **Gate de cierre de toda task visual:** ciclo triple `/impeccable audit` → `/ux-heuristics` → `/refactoring-ui`, en ese orden, sobre lo tocado, a 1180×820 dark. Resultado al ledger.
- Commits atómicos por task, mensaje honesto. **Nunca incluir** `memoria/log.md` ni `memoria/trampas/drawer-en-handsontable-module.md` (los tiene otra sesión). No push sin petición explícita.
- Goldens: **ninguna task de F3 arranca antes de que F2-1 cierre.** Tras F2-1, cada task de F3 recaptura solo los goldens que su cambio mueve, con evidencia antes/después.
- Método: un resultado vacío, redondo o idéntico al anterior se sospecha de la sonda antes que de la app; la captura es parte de la medición. El panel del navegador colapsado (`innerWidth: 0`) ya contaminó 4 mediciones — comprobar `window.innerWidth === 1180` antes de medir.
- **PDC V1 DEPRECADO (decisión del usuario, 2026-08-04): fuera de alcance de toda la campaña.** Son `public/js/modules/pdc/`, `public/css/pdc.css`, la ruta `/pdc` y los módulos `listado-actividades/` y `contratos/`. Ninguna task los toca ni los verifica. **No confundir con Plan de Compras v2** (`/plan-compras`, `pdc-app/`, `src/Services/Pdc/`), que sí está vivo. Entradas cerradas como «no aplica: módulo deprecado»: C-3, C-10, C-36, C-43 y la parte PDC de C-31.
- Ledger: `.superpowers/sdd/2026-08-04-campana-cierre/progress.md` (nuevo; el viejo es git-ignored y no se recrea).

---

## FASE 1 · Redes de seguridad

### Task 1: F1-1 — Fixtures de la suite de navegador de PS (C-21)

**Files:**
- Modify: `tests/browser/support/` y/o `tests/browser/fixtures/` (helpers de apertura de semana y tarjeta de proyecto)
- Test: las 9 `tests/browser/programacion-semanal-*.mjs` (35 casos)

**Interfaces:**
- Consumes: dev door (`/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`).
- Produces: suite PS en verde o informe de imposibilidad con evidencia; tasks posteriores que tocan PS dependen de esta red.

- [ ] **Step 1: Reproducir el fallo actual y fijar la línea base.** Run: `npx playwright test tests/browser/programacion-semanal-*.mjs --workers=1 2>&1 | tail -20`. Expected: 34 failed / 1 passed (línea base conocida; si difiere, anotar y seguir).
- [ ] **Step 2: Diagnosticar los dos puntos de caída conocidos.** Leer el helper que contiene `openProgrammingWeek` (grep: `grep -rn "openProgrammingWeek" tests/browser/`) y el que busca «Project card not found: Prueba». Documentar en el ledger QUÉ espera cada uno (selector, texto, proyecto) y qué hay realmente en el DOM servido (medir con Playwright headed contra `http://localhost:8081` con dev door).
- [ ] **Step 3: Decidir la reparación por evidencia, no por comodidad.** Regla: si el fixture espera un proyecto «Prueba» que ya no existe en seeds, apuntar el fixture a `PDC Sandbox E2E` (dato real); si espera un flujo de apertura de semana que cambió de DOM, actualizar el selector al DOM actual. **Prohibido** relajar aserciones o borrar casos.
- [ ] **Step 4: Aplicar la reparación y correr la suite completa.** Run: `npx playwright test tests/browser/programacion-semanal-*.mjs --workers=1`. Expected: 35/35 PASS. Si un caso concreto falla por una razón NUEVA (no fixtures), documentarlo como hallazgo aparte sin tocarlo.
- [ ] **Step 5: Commit.** `git add tests/browser/ && git commit -m "test(ps): repara los fixtures de la suite de navegador de programacion semanal"`.

### Task 2: F1-2 — Guard del matiz de la leyenda de PG (C-47)

**Files:**
- Modify: `tests/browser/programa-general-legend-hue.mjs` **o** el token en `public/css/` (según diagnóstico)
- Test: el propio guard

**Interfaces:**
- Consumes: nada de otras tasks.
- Produces: guard en verde con umbral honesto; F3 depende de esta red para tocar PG.

- [ ] **Step 1: Reproducir.** Run: `npx playwright test tests/browser/programa-general-legend-hue.mjs --workers=1`. Expected: FAIL en `actividad-futura` (exige saturación ≥35 %, píxel real 29 %, `#66aa7e` matiz 141).
- [ ] **Step 2: Historia del token.** Run: `git log -p --follow -S "calc(c * 1.4)" -- public/css/ | head -80` y localizar cuándo se fijó el factor y cuándo se fijó el piso de 35 % en el guard (`git log -p -- tests/browser/programa-general-legend-hue.mjs | grep -n "35\|0.35" | head`). Decidir con evidencia: (a) el token perdió croma en un cambio identificable → subir el factor `calc(c * 1.4)` hasta recuperar el croma original medido; (b) el piso nunca se cumplió desde su creación → corregir el guard al valor real medido con margen (p. ej. ≥27 %), documentando en el propio test por qué.
- [ ] **Step 3: Aplicar y re-medir con el chip teñido.** El fondo del chip cambió con la paridad de PG: el guard rasteriza el punto sobre ese fondo. Correr el guard y verificar el valor medido en su salida.
- [ ] **Step 4: Suite estática.** Run: `npm run test:design-system:static`. Expected: 8/8.
- [ ] **Step 5: Commit.** Mensaje según diagnóstico: `fix(pg): devuelve el croma al punto de actividad-futura` o `test(pg): corrige el piso del guard de matiz al valor historicamente real`.

### Task 3: IA-1 — Entrevista jobs-to-be-done (GATE del journey)

**Files:**
- Create: `docs/CUSTOMER.md`
- Modify: `docs/IMPROVE-APP-PLAN.md` (fase 1 → done, Key Decisions)

**Interfaces:**
- Consumes: intake ya respondido (job por rol; flujo PG→PI→PS; quejas de obra sin registro).
- Produces: `docs/CUSTOMER.md` con `## Job Statement` (por rol), `## Job Dimensions`, `## Competing Alternatives`. IA-3..IA-6 leen este archivo.

- [ ] **Step 1: Entrevistar al usuario** (task interactiva — NO subagente): una pregunta por vez, en simple. (1) Confirmar los tres job statements por rol sin nombrar la app: Residente «Cuando planifico la semana de obra, quiero compromisos claros de cada responsable, para que la obra no se atrase sin que nadie lo vea venir»; Director «Cuando preparo las próximas semanas, quiero ver qué restricciones y compras faltan, para liberar el trabajo antes de que pare la obra»; Gerencia «Cuando me piden cuentas, quiero mostrar el avance real y sus causas, para sostener decisiones con datos». (2) Por cada job: ¿dónde sub-entrega hoy? (funcional/emocional/social). (3) ¿Qué alternativas usan cuando la app falla — Excel paralelo, WhatsApp, no hacer nada? (4) ¿La fuga es de primera adopción (Big Hire) o de uso diario (Little Hire)?
- [ ] **Step 2: Escribir `docs/CUSTOMER.md`** desde el esqueleto de `~/.claude/skills/improve-app/references/artifact-templates.md`, con las respuestas — cada dimensión con su nota de sub-entrega; en Alternatives incluir no-consumo. Declarar explícito: «evidencia = quejas directas sin registro + mediciones de la campaña».
- [ ] **Step 3: Presentar el borrador al usuario y ajustar.**
- [ ] **Step 4: Actualizar tracker y commit.** `git add docs/CUSTOMER.md docs/IMPROVE-APP-PLAN.md && git commit -m "docs(improve-app): fase 1 jobs-to-be-done — CUSTOMER.md por rol"`.

## FASE 2 · Goldens

### Task 4: F2-1 — Revisión y recaptura de goldens (A-1)

**Files:**
- Modify: `tests/browser/__screenshots__/**` (solo los goldens de tabla afectados)

**Interfaces:**
- Consumes: F1 en verde.
- Produces: goldens en verde = **desbloquea toda la F3**.

- [ ] **Step 1: Inventariar los goldens en rojo.** Run: `npx playwright test tests/browser/programa-general.visual.mjs tests/browser/programacion-intermedia.visual.mjs --workers=1 2>&1 | tail -15`. Listar cada golden fallido y su carpeta `test-output/*/` con esperado/actual/diff.
- [ ] **Step 2: Presentar al usuario el par esperado/actual/diff de CADA golden** (task interactiva — NO subagente; enviar las imágenes con SendUserFile). Esperar su visto bueno explícito por lote o por golden. Si señala algo inesperado: STOP, investigar eso antes de recapturar.
- [ ] **Step 3: Recapturar solo lo aprobado.** Run: `npx playwright test <specs aprobados> --update-snapshots --workers=1`. Expected: verde.
- [ ] **Step 4: Verificar que el diff de git contiene SOLO los goldens aprobados.** Run: `git status --short` y revisar.
- [ ] **Step 5: Commit.** `git commit -m "test: recaptura goldens de tabla aprobados por el usuario (A-1)"`.

## FASE 3 · Lo que mueve el píxel

*(Todas tras Task 4. Cada una termina con: suite estática 8/8 → recaptura de los goldens que su cambio mueva con evidencia antes/después → ciclo triple → commit.)*

### Task 5: F3-1 — Variante B de bordes en Handsontable/DataTables (A-3, C-7)

**Files:**
- Modify: `public/css/programacion-intermedia.css` (+ hoja compartida de tabla si el selector vive ahí; localizar con `grep -rn "cbd5e1\|203, 213, 225" public/css/`)
- Test: `tests/browser/programacion-intermedia.visual.mjs`, `npm run test:design-system:static`

**Interfaces:**
- Consumes: goldens desbloqueados (Task 4).
- Produces: rejilla tokenizada; referencia de contraste para F3-3.

- [ ] **Step 1: Medir la línea base.** En `/programacion-intermedia` (dev door, proyecto con datos en solo lectura), sonda sobre `TD.htMiddle` y `TH`: `getComputedStyle(el).borderColor`. Expected: `rgb(203, 213, 225)` en 34 celdas/cabeceras, 11,96:1 contra `rgb(17, 26, 21)`.
- [ ] **Step 2: Localizar la regla fuente.** Run: `grep -rn "cbd5e1\|203, 213, 225" public/css/ node_modules/handsontable/dist/*.css | grep -v node_modules` primero; si la regla es del vendor, escribir el override en la hoja del módulo con los selectores medidos (`TD.htMiddle`, `TH`, `.pi-soft-restriction-th`).
- [ ] **Step 3: Aplicar variante B aprobada:** separadores horizontales sutiles, sin bordes verticales, numéricas alineadas a la derecha. Borde con token de separador dark-aware del sistema (el mismo par que usa el shell; localizar en `public/css/design-system/tokens.css` el token de borde de superficie — NO inventar token nuevo). Objetivo medible: el borde deja de ser lo más brillante del área de contenido (luminancia del borde < luminancia de la tinta de datos).
- [ ] **Step 4: Verificar en las tres librerías.** Handsontable en PI y PG; DataTables en `/control-cambios` con ordenación activa para ver por fin el gatillo de filtro (C-7) — captura como evidencia.
- [ ] **Step 5: Suite + goldens + ciclo triple + commit.** `git commit -m "feat(tablas): variante B de bordes con tokens — la rejilla deja de gritar mas que los datos (A-3)"`.

### Task 6: F3-2 — Colapso de los dos racimos tipográficos (C-40)

**Files:**
- Modify: `public/css/buttons.css`, `public/css/programacion-intermedia.css`, `public/css/programacion-semanal.css`, `public/css/pdc.css`
- Test: `npm run test:design-system:static` (la baseline de `docs/design-system/audit-baseline.json` NO debe subir)

**Interfaces:**
- Consumes: Task 4.
- Produces: rampa tipográfica sin valores sub-píxel en los 4 archivos.

- [ ] **Step 1: Censar las declaraciones de los dos racimos.** Run: `grep -rn "font-size" public/css/buttons.css public/css/programacion-intermedia.css public/css/programacion-semanal.css public/css/pdc.css | grep -E "14\.4|14\.08|0\.9rem|0\.88rem|0\.85rem|13\.6|12\.8|12\.48|12\.16|0\.8rem|0\.78rem|0\.76rem"` y complementar midiendo en vivo los tamaños renderizados (los racimos medidos: 14,4/14,08/14/13,6 px y 12,8/12,48/12,16/12 px).
- [ ] **Step 2: Elegir el valor destino de cada racimo** con la rampa del sistema (leer la escala tipográfica en `docs/design-system/` y `tokens.css`): racimo alto → el token de cuerpo (14 px equivalente), racimo bajo → el token pequeño (12 px equivalente). Sustituir declaración a declaración por el token, no por px.
- [ ] **Step 3: Verificar la consecuencia visible.** Re-medir tamaños renderizados en `/programacion-intermedia`: los 16 distintos deben bajar (objetivo: ≤10) y los dos racimos quedar en 2 valores.
- [ ] **Step 4: Baseline sin subir.** Run: `npm run test:design-system:static`. Expected: 8/8 y los contadores de `off-scale-typography` bajan o quedan iguales.
- [ ] **Step 5: Goldens movidos + ciclo triple + commit.** `git commit -m "refactor(css): colapsa los dos racimos tipograficos sub-pixel a tokens de rampa (C-40)"`.

### Task 7: F3-3 — Filas de «Capítulo» del PDC sin bloque de color (C-44) — HECHA, sobre módulo hoy deprecado

> **Nota del 2026-08-04, posterior a su cierre:** esta task se ejecutó (commit `1e479a94`) sobre
> `/pdc`, que el usuario deprecó ese mismo día. El trabajo queda hecho y es inocuo, pero **el valor
> vivo está en la Task 36**, que lleva ese mismo encabezado sobrio a Programa General y Programación
> Intermedia, que sí siguen. No se invierte más en `/pdc`.

**Files:**
- Modify: `public/css/pdc.css` (y/o el renderer en `public/js/modules/pdc/hot.js` si el color se aplica inline)
- Test: `npm run test:design-system:static`, golden de PDC si existe

**Interfaces:**
- Consumes: Task 4; referencia de deferencia de Task 5.
- Produces: capítulos por tipografía + filete.

- [ ] **Step 1: Comprobar que el color no carga semántica.** Run: `grep -rn "139, 64, 17\|8b4011" public/css/pdc.css public/js/modules/pdc/` y leer el contexto: ¿ese naranja distingue capítulo de otros estados, o es puro encabezado? Si codifica estado, STOP y consultar al usuario.
- [ ] **Step 2: Sustituir el relleno** por: `font-weight` del token de énfasis + `border-top` de 1px con el token de separador + fondo de superficie elevada (`--ds-active-surface-raised`, el patrón que ya usan los chips de PG). Medir después: luminancia de la fila de capítulo ≤2× la de una fila normal (antes: 6,6×).
- [ ] **Step 3: Verificar con datos reales** (Da Porto, solo lectura): los capítulos siguen distinguiéndose de un vistazo en captura.
- [ ] **Step 4: Suite + goldens + ciclo triple + commit.** `git commit -m "feat(pdc): capitulos por peso tipografico y filete, no por bloque de color (C-44)"`.

### Task 8: F3-4 — Anchos de columna que ocultan datos (C-16, C-31, C-49p1)

**Files:**
- Modify: `public/js/modules/programa_general/hot.js`, `public/js/modules/programacion_intermedia/hot.js`, `public/js/modules/programacion_semanal/hot.js` (claves `colWidths`), vista de Subcontratistas (localizar su config de tabla: `grep -rn "colWidths\|columnDefs" public/js | grep -i subcontrat`)
- **PDC V1 fuera de alcance** (deprecado el 2026-08-04): no se toca `public/js/modules/pdc/`. El caso «ESTADO DEL PROCESO» que ocultaba «71 días de retraso» se cierra como no aplica.
- Test: `tests/browser/handsontable-ancho-tabla.mjs`, visuales de PG/PI

**Interfaces:**
- Consumes: Task 4.
- Produces: columnas medidas sin recorte de datos; C-49p1 deja la celda de Estado Operativo >120 px (el container query de `.ops-state-zoom` vuelve a mostrar el nombre solo).

- [ ] **Step 1: Fijar las cifras objetivo por columna (ya medidas):** PG «Id» 54→≥66 px; PI «Id» ≥ su texto máximo (26/27 códigos recortados, `9.5.1.1` pierde 18 px → medir el máximo real); Subcontratistas correo → ancho del correo más largo del proyecto de prueba; PS/tabla de C-49: la columna de Estado Operativo >120 px.
- [ ] **Step 1-bis: Cabeceras completas (añadido del usuario, 2026-08-04).** Censar en PG, PI y PS qué textos de cabecera se recortan (`scrollWidth > clientWidth` sobre `.colHeader`) y darles ancho suficiente para verse **enteros**, sin elipsis ni tooltip como sustituto. Aprovechar aquí la causa raíz de C-16: la caja interna `.colHeader` renderiza 33 px donde el `th` mide 56, o sea que **hay 23 px por columna ya pagados y desperdiciados** — recuperarlos puede bastar para varias cabeceras sin robar ancho a ninguna celda. Medir el ancho que cada cabecera necesita antes de ensanchar nada.
- [ ] **Step 2: Editar `colWidths` módulo a módulo.** En cada `hot.js`, localizar el array `colWidths` y subir SOLO las columnas de la lista. El ancho extra sale del viewport total: comprobar tras cada módulo que no aparece scroll horizontal a 1180 px (`document.documentElement.scrollWidth <= 1180`).
- [ ] **Step 3: Verificar con datos reales, en solo lectura.** Re-correr la sonda de truncamiento (contar celdas con `scrollWidth > clientWidth`): los casos de la lista deben quedar a 0; anotar el total de la app antes/después en el ledger (línea base: 105).
- [ ] **Step 4: C-49p1 verificado:** en la tabla de PS, el botón `.ops-state-zoom` muestra el nombre del estado («Lista para Confirmar») porque la celda supera el umbral de 120 px del `@container`.
- [ ] **Step 5: Suite + goldens movidos + ciclo triple + commit.** `git commit -m "fix(tablas): ensancha las columnas que ocultaban datos — ids, estado del proceso, correos (C-31/C-16/C-49)"`.

### Task 9: F3-5 — Caja de 44 px del gatillo de filtro de PS (C-48)

**Files:**
- Modify: `public/css/programacion-semanal.css:2496` (override del `::before`) y la regla del botón `button.changeType` de PS

**Interfaces:**
- Consumes: Task 4.
- Produces: gatillo con caja táctil completa; paridad de intención con T-5.

- [ ] **Step 1: Leer el override actual.** `public/css/programacion-semanal.css:2496` fija `width/height: 2rem !important` en el `::before` y la regla anterior calcula `padding: calc((var(--ds-target-min) - 2rem) / 2)`. El botón real mide 13 px: la mitad del diseño nunca se aplicó.
- [ ] **Step 2: Completar la intención (opción b aprobada):** dar al botón la caja objetivo — `min-width`/`min-height` con `var(--ds-target-min)` (44 px) y centrar el glifo. Quitar el `!important` si la capa lo permite (comprobar con el audit).
- [ ] **Step 3: Medir después:** el gatillo ya no desborda ni cuelga bajo la cabecera; caja ≥44 px; los otros dos módulos (PG 6×6 transparente, PI triángulo desnudo) sin cambios.
- [ ] **Step 4: Suite + goldens de PS + ciclo triple + commit.** `git commit -m "fix(ps): el gatillo de filtro recibe su caja tactil de 44px — se completa la intencion de T-5 (C-48)"`.

### Task 10: F3-6 — Hover del secundario sin robar la jerarquía (C-34)

**Files:**
- Modify: `public/css/buttons.css` (regla `.aia-btn--secondary:hover`)

**Interfaces:**
- Consumes: Task 4.
- Produces: primitiva compartida corregida; consumida por todas las vistas.

- [ ] **Step 1: Línea base medida:** secundario en hover `rgb(149,187,156)` luminancia 0,443 vs primario en reposo `rgb(108,144,119)` luminancia 0,245 (80 % más luminoso).
- [ ] **Step 2: Editar la regla:** en hover, el secundario pasa a superficie elevada (`--ds-active-surface-raised` o el token hover que el sistema ya defina — comprobar en `tokens.css`) + borde con el token de borde vivo, SIN relleno del acento.
- [ ] **Step 3: Medir después:** luminancia del secundario:hover < luminancia del primario en reposo; contraste de texto del secundario:hover ≥4,5:1.
- [ ] **Step 4: Verificar en 3 superficies consumidoras vivas** (PS, PI, PG — **no** `/pdc`, deprecado) que ningún botón queda ilegible; suite 8/8; goldens movidos; ciclo triple; commit. `git commit -m "fix(buttons): el hover del secundario gana presencia sin adelantar al acento (C-34)"`.

### Task 11: F3-7 — Chip contador atenuado en cero (C-24)

**Files:**
- Modify: `public/css/programacion-intermedia.css`, `public/css/programacion-semanal.css` (chips `pdc-legend-item`/equivalentes de PI y PS); el JS que pinta el conteo si hace falta una clase (localizar: `grep -rn "pdc-legend-item" public/js public/css views`)

**Interfaces:**
- Consumes: Task 4.
- Produces: chips con estado `is-zero` (o atributo `data-count="0"`) atenuado.

- [ ] **Step 1: Determinar el gancho.** Si el JS ya escribe el número en el chip, añadir en el mismo punto `chip.classList.toggle('is-zero', count === 0)`; si el conteo viene del PHP, añadir la clase en la vista. Localizar el punto exacto con el grep del bloque Files.
- [ ] **Step 2: CSS:** `.pdc-legend-item.is-zero` → fondo neutro (`--ds-active-surface-raised`), texto atenuado con el token de tinta secundaria, punto marcador sin saturación. Con count>0 NO cambia nada (el color saturado hace su trabajo: 31 atrasadas merece rojo).
- [ ] **Step 3: Verificar los dos estados:** sandbox (todo en 0 → todos atenuados) y proyecto con datos en solo lectura (202/1332/31/8 → saturados como hoy).
- [ ] **Step 4: Suite + goldens + ciclo triple + commit.** `git commit -m "feat(chips): el contador en cero se atenua y recupera el color al tener algo que contar (C-24)"`.

### Task 12: F3-8 — «Recargar» y «BI Semanal» vuelven a la barra de PS (C-17)

**Files:**
- Modify: la vista/JS de la toolbar de PS donde el task 25 creó el menú «Más» (localizar: `grep -rn "Recargar\|BI Semanal" views/ public/js/modules/programacion_semanal/ | grep -iv test`)

**Interfaces:**
- Consumes: Task 4; Task 1 (la suite de PS protege la toolbar).
- Produces: barra con Autoprogramar, Agregar Actividad, Confirmar Compromisos, Reabrir Semana, Registrar TNP, Recargar, BI Semanal; menú «Más» con Leyenda, Imprimir, Exportar CSV.

- [ ] **Step 1: Mover los dos botones del menú a la barra** en el markup/JS del menú «Más» (mantener idénticos handlers y atributos ARIA; el patrón del menú del task 25 ya pasa el ciclo de teclado — no romperlo).
- [ ] **Step 2: Verificar el motivo original del menú:** a 1180×820, la toolbar NO desborda (`toolbar.scrollWidth <= toolbar.clientWidth`) y ningún botón queda con `right > 1180`. Si desborda, STOP: reportar la aritmética al usuario antes de inventar una salida.
- [ ] **Step 3: Correr los specs de toolbar de PS** (`npx playwright test tests/browser/programacion-semanal-roles-phases.mjs --workers=1` y los que toquen la toolbar). Expected: verde.
- [ ] **Step 4: Suite + ciclo triple + commit.** `git commit -m "feat(ps): Recargar y BI Semanal salen del menu a la barra por decision del usuario (C-17)"`.

### Task 13: F3-9 — Régimen de excepciones puntuales (C-29, C-3, C-23; C-2 acotado)

**Files:**
- Modify: `state-token-exceptions.json` (localizar: `find . -name "state-token-exceptions.json" -not -path "*/node_modules/*"`), config del audit para C-3 (donde viven las supresiones intencionales: leer `docs/design-system/` y los scripts del audit), `public/css/adapters/admin-lte.css` (C-29), CSS del carril de pestañas de BI (C-23; localizar: `grep -rn "bi-tabs\|nav-tabs" public/css/`)

**Interfaces:**
- Consumes: Task 4.
- Produces: 3 excepciones justificadas, cada una con medición escrita; errores de admin legibles; corte de pestañas anunciado.

- [ ] **Step 1: C-29.** En `admin-lte.css`, los mensajes `.text-danger` de campo pasan al token de texto crítico (`--ds-color-state-critical-text`), con la entrada en `state-token-exceptions.json` justificada: «mensaje de error de campo; contraste medido 11,21:1 sobre la superficie del formulario; el aclarado al 35 % era para el enlace Salir, premisa caduca». El enlace «Salir» conserva el aclarado.
- [ ] **Step 2: C-3 RETIRADA — PDC V1 deprecado.** Era el borde-acento del toast en `pdc.css:318`. No se declara excepción para un archivo que se va; **C-3 se cierra como «no aplica: módulo deprecado»**. Si el detector sigue marcándolo, la salida correcta es que `pdc.css` salga del alcance del audit al retirar el módulo, no una excepción justificada.
- [ ] **Step 3: C-23.** Degradado de anuncio de corte en el borde derecho del carril de pestañas de BI, como excepción de presupuesto de color documentada (el carril ya desplaza y la barra es visible; el degradado solo anuncia que hay más).
- [ ] **Step 4: Verificar:** suite 8/8 (las excepciones válidas no rompen el gate); en navegador: error de admin legible de un vistazo, degradado visible en `/bi/control-tower`, toast sin cambio visual.
- [ ] **Step 5: Ciclo triple + commit.** `git commit -m "feat(ds): dos excepciones puntuales justificadas — error de admin y corte de tabs BI (C-29/C-23)"`.

### Task 14: IA-2 — Volcado de hallazgos a los artefactos del journey

**Files:**
- Modify: `DESIGN.md` (raíz — añadir sección `## UX Audit Findings`; NO tocar su contrato existente) **o** crear `docs/DESIGN-AUDIT.md` si el usuario prefiere no mezclar (preguntar en el momento, 1 pregunta)
- Create: `docs/EXPERIMENTS.md`
- Modify: `docs/IMPROVE-APP-PLAN.md` (fases 2 y 4 → done)

**Interfaces:**
- Consumes: registro de decisiones + resultados de F1–F3.
- Produces: tabla de hallazgos con severidad 0-4 y backlog ICE que IA-3..IA-6 consumen.

- [ ] **Step 1: Volcar** cada C-* con su severidad ya medida a la tabla `| Issue | Heuristic | Severity | Fix | Status |` (status: la disposición real — done con commit, task pendiente, chip, aceptado).
- [ ] **Step 2: Crear `docs/EXPERIMENTS.md`** del esqueleto de la skill, con `## Experiment Backlog` en formato ICE para lo aún abierto.
- [ ] **Step 3: Sin re-medir nada.** Tracker al día. Commit: `git commit -m "docs(improve-app): fases 2/4 — hallazgos medidos volcados a los artefactos del journey"`.

## FASE 4 · Comportamiento y estructura

### Task 15: F4-1 — El PHP manda: los JS dejan de inyectar ids duplicados (C-46)

**Files:**
- Modify: `public/js/cargarDatosGeneralesPagina2.js` (inyecta `Max_Semana`, `Semanal_Confirmada`, `baseDatos`, `permiso_canonico`, `semana`), `public/js/funcionesGenerales6.js` (inyecta `Id`, `opcion`)
- Test: sonda de ids duplicados en las 4 vistas + humo funcional

**Interfaces:**
- Consumes: Task 1 (suite PS en verde protege parte del radio).
- Produces: 0 ids duplicados de esta familia en las vistas vivas: `/programa-general`, `/programa-general-actualizar`, `/programacion-semanal`. (`/pdc` también los repetía, pero está deprecado y no se verifica ahí.)

- [ ] **Step 1: Mapa de lecturas ANTES de tocar.** Por cada id: `grep -rn "getElementById('Max_Semana')\|getElementById(\"Max_Semana\")\|#Max_Semana" public/js views src` (repetir para los 7). Documentar en el ledger: quién lee, quién escribe, y si algún consumidor depende del elemento INYECTADO (p. ej. por orden de carga o por vivir dentro de un modal que el PHP no renderiza).
- [ ] **Step 2: Confirmar que el PHP emite los 7 en las 4 vistas.** Con dev door, en cada vista: `document.querySelectorAll('#Max_Semana').length` etc. Expected hoy: 2 por id duplicado. Verificar que la copia del PHP (bloque `.encabezado`) trae valor resuelto.
- [ ] **Step 3: Quitar la inyección** en los dos JS — solo las líneas que crean los campos con esos 7 ids; si el JS además LEE el campo, la lectura queda apuntando a la copia del PHP (mismo id, ahora único). Si el paso 1 reveló un consumidor que depende de la copia inyectada: STOP, reportar al usuario con el mapa.
- [ ] **Step 4: Humo funcional por vista, en el sandbox:** PG carga y guarda una celda; PS abre semana y modales de semana (los que montaba `funcionesGenerales6.js` con `Id`/`opcion`); **`/programa-general-actualizar` es el crítico**: cargar el flujo de importación hasta la previsualización SIN aplicar (no ejecutar la importación real), verificando que `semana`, `Max_Semana`, `Semanal_Confirmada`, `baseDatos` llevan el valor correcto en el form que se enviaría.
- [ ] **Step 5: Sonda final:** 0 duplicados de los 7 ids en las 4 vistas. Suite PS verde. Commit: `git commit -m "fix(js): los inyectores dejan de duplicar los ids que el PHP ya emite — manda el servidor (C-46)"`.

### Task 16: F4-2 — Investigación de «Lista para Confirmar» con pendientes (C-49p2)

**Files:**
- Read: `public/js/modules/programacion_semanal/stateMachine.js` (donde vive `classifyState`), `public/js/modules/programacion_semanal/hot.js`
- Create: informe en el ledger + propuesta al usuario

**Interfaces:**
- Consumes: nada; independiente.
- Produces: diagnóstico + propuesta de remedio que el usuario aprueba ANTES de cualquier edición.

- [ ] **Step 1: Usar superpowers:systematic-debugging.** Leer `classifyState` en `stateMachine.js`: ¿qué condiciones producen «Lista para Confirmar» y qué produce el conteo de condiciones pendientes del botón (`is-critical`)? Son dos cálculos: localizar ambos y sus entradas (el inventario de 9 condiciones del task 23 está en el ledger viejo como referencia).
- [ ] **Step 2: Reproducir con datos reales** (Optimización Aeropuerto JMC, solo lectura): tomar las 4 filas medidas donde botón=`is-critical` y celda=`ps-alert-medium`/`ps-alert-control`, y trazar para una de ellas qué condición pendiente tiene y por qué el estado la declara lista.
- [ ] **Step 3: Escribir el diagnóstico:** la regla exacta (con cita de línea) por la que el estado ignora esas condiciones. Decisión del usuario ya tomada: **no pueden convivir** — si hay pendientes, no está lista. Proponer el remedio mínimo en la regla de cálculo (p. ej. `classifyState` no devuelve «Lista para Confirmar» mientras `condicionesPendientes > 0`, degradando al estado anterior) y su impacto (cuántas filas del proyecto real cambian de estado).
- [ ] **Step 4: Presentar al usuario y ESPERAR aprobación** (cambia significado de dominio). Solo tras el sí: aplicar, verificar en las 4 filas + suite PS + test del guard de matiz de estado (`tests/browser/programa-general-state-hue.mjs`, `ops-state-chip-hue.mjs`), commit: `git commit -m "fix(ps): una fila con condiciones pendientes no se declara Lista para Confirmar (C-49)"`.

### Task 17: RETIRADA — PDC V1 deprecado (2026-08-04)

Era «Chips del PDC: teclado + aria-pressed + región de estado (C-10, C-43)», sobre `/pdc`.
El usuario deprecó PDC V1 (`listado-actividades/`, `contratos/`, `pdc/` viejo), así que **C-10 y C-43
se cierran como «no aplica: módulo deprecado»**, no como deuda pendiente. No se reintroduce.

### Task 18: F4-4 — `<main>` y `h1` en once vistas (C-30)

**Files:**
- Modify: las vistas sin `<main>`/`h1` (censo del barrido: todas menos `/programa-general`, `/proyectos`, `/bi/control-tower` tienen falta; el patrón bueno es `views/dashboard/escalamientos.php`)
- Test: sonda de estructura por ruta

**Interfaces:**
- Consumes: nada.
- Produces: cada vista con un `<main>` y un `h1` sin saltos de nivel.

- [ ] **Step 1: Censo exacto.** Sonda sobre las ~25 rutas (dev door): por ruta, `!!document.querySelector('main')`, primer heading y saltos de nivel. Guardar la lista real en el ledger (el censo previo es de agosto 3-4; verificar).
- [ ] **Step 2: Vista a vista:** envolver el contenido principal en `<main>` (sin mover nodos de sitio: envolver, no reordenar) y declarar el `h1` — usando el nombre que ya usan las migas de pan/título de pestaña (mecanismo del commit `e6f7f4c`), NO inventar nomenclatura. `/programa-general` además corrige su salto h1→h3.
- [ ] **Step 3: Verificar que ningún estilo se mueve:** los selectores CSS que cuelgan de la jerarquía tocada (`grep -n "main\b" public/css/*.css` antes de empezar) + captura antes/después idéntica por vista (excepto el heading si era invisible).
- [ ] **Step 4: Suite + goldens si algo se movió + ciclo triple + commit** (un commit por lote coherente de vistas): `git commit -m "feat(a11y): main y h1 en las vistas que no los declaraban, con el patron de escalamientos (C-30)"`.

### Task 19: F4-5 — La Guía Operativa sustituye a la Leyenda muerta (C-26)

**Files:**
- Modify: la vista de PI que contiene los dos modales con id `modal_leyenda_colores` (localizar: `grep -rln "modal_leyenda_colores" views/`)

**Interfaces:**
- Consumes: Task 15 (los ids `permiso_canonico`/`Semanal_Confirmada` de PI ya caen con F4-1).
- Produces: id único; markup muerto eliminado.

- [ ] **Step 1: Confirmar la inalcanzabilidad** (ya verificada en vivo, re-comprobar): el botón «Leyenda» abre la Guía Operativa; la «Leyenda de Colores de Las Actividades» no se abre por ninguna vía. `grep -rn "modal_leyenda_colores" public/js views/` para ver que nadie más lo referencia.
- [ ] **Step 2: Borrar el modal muerto** («Leyenda de Colores») y dejar único el id en la Guía. Limpiar en el mismo lote `modal_leyenda_colores_Label` y `modalEliminarLabel` duplicados de PI.
- [ ] **Step 3: Verificar:** el botón «Leyenda» sigue abriendo la Guía, cierre correcto, backdrop limpio; sonda: 0 ids duplicados en `/programacion-intermedia`. Commit: `git commit -m "fix(pi): elimina la Leyenda de Colores muerta — la Guia Operativa es la unica y su id queda unico (C-26)"`.

### Task 20: F4-6 — Tildes de la guía operativa (C-27)

**Files:**
- Modify: el markup del modal de Guía Operativa de PI (misma vista de Task 19)

**Interfaces:**
- Consumes: Task 19 (mismo archivo — ejecutar después para no pisarse).
- Produces: copy del modal alineado con los chips.

- [ ] **Step 1: Correcciones exactas (solo el modal, NO datos ni `GLOSARIO.md`):** «Guia»→«Guía», «Ejecucion»→«Ejecución», «habilitacion»→«habilitación», «critica»→«crítica», «preparacion»→«preparación», «Gestion»→«Gestión», «Programacion»→«Programación», «tecnico»→«técnico».
- [ ] **Step 2: Verificar en la fuente que es texto escrito sin tilde, no mojibake** (lección del task 20 viejo: si aparecen secuencias `Ã`, es dato en BD y NO se toca aquí — reportar).
- [ ] **Step 3: Verificar en navegador** (modal renderiza limpio) + commit: `git commit -m "fix(pi): la guia operativa escribe con tilde lo que los chips ya escriben con tilde (C-27)"`.

### Task 21: F4-7 — Marco del iframe de Power BI (C-22)

**Files:**
- Modify: la vista de `/indicadores` y su CSS (localizar: `grep -rln "powerbi" views/ public/css/`)

**Interfaces:**
- Consumes: nada.
- Produces: transición visual suavizada; el tema del informe queda como tarea del usuario en Power BI (anotar en el resumen final).

- [ ] **Step 1: Enmarcar el iframe:** contenedor con padding del token de espaciado, borde del token de separador y fondo de superficie del sistema, para que el bloque blanco lea como «contenido embebido» y no como fuga. **PROHIBIDO** `filter: invert()`.
- [ ] **Step 2: Verificar a 1180×820:** sin overflow, consola limpia, el informe sigue legible. Ciclo triple. Commit: `git commit -m "feat(indicadores): enmarca el informe de Power BI para suavizar la isla blanca (C-22)"`.

### Task 22: IA-3 — Lente de Norman sobre PG→PI→PS (design-everyday-things)

**Files:**
- Modify: `docs/DESIGN.md` o `docs/DESIGN-AUDIT.md` (el que decidió el usuario en Task 14), `docs/EXPERIMENTS.md`, `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: `docs/CUSTOMER.md` (Task 3), hallazgos de Task 14.
- Produces: hallazgos gulf-of-execution/evaluation con severidad; absorbe C-14.

- [ ] **Step 1: Invocar la skill `design-everyday-things`** sobre los tres flujos núcleo: confirmar compromisos (PS), liberar restricciones (PI), actualizar avance (PG). Buscar: signifiers débiles, dónde una restricción de UI haría imposible el error (mejor que avisar), feedback >0,1 s, confirmaciones que deberían ser deshacer.
- [ ] **Step 2: C-14 dentro de esta lente:** pedir al usuario que reproduzca su caso («no vi qué retenía el estado») y observar: ¿aparece `⚠ Sin asignar` en la celda? ¿pasa el cursor por el chip? Con su respuesta, decidir si el motivo se hace visible sin interacción (y si eso es un cambio de esta campaña o un chip).
- [ ] **Step 3: Registrar hallazgos** con severidad en la tabla de findings + backlog ICE. Lo que sea cambio de comportamiento NO se aplica: se registra y se pregunta. Tracker al día. Commit docs.

## FASE 5 · Admin, metadatos y limpieza

### Task 23: F5-1 — Adaptador de admin completo + marca AIA (C-38, C-25)

**Files:**
- Modify: `public/css/adapters/admin-lte.css`

**Interfaces:**
- Consumes: nada.
- Produces: las 3 variantes outline tokenizadas; marca AIA ≥4,5:1.

- [ ] **Step 1: C-38, con la lección del intento fallido:** el adaptador usa `:where()` (especificidad 0) y AdminLTE trae `.dark-mode .btn-outline-success` (0,2,0) — el vendor gana siempre. Escribir las reglas de `.btn-outline-success/info/warning` ancladas a `.dark-mode` (igualar la especificidad del vendor), **sin `!important`**, cambiando el PAR completo borde+texto (lección del task 27/28).
- [ ] **Step 2: Verificar en `/admin/matching/family-catalog`** (dev door admin, `test.A`): los botones de exportar dejan el borde Bootstrap (`rgb(40,167,69)`, `rgb(23,162,184)`, `rgb(255,193,7)`) por el token; contraste ≥4,5:1 medido. Ojo a la caché del `?v=1.0.0`: recarga dura antes de medir.
- [ ] **Step 3: C-25:** subir un escalón de luminancia la marca «AIA» (hoy 4,46:1; objetivo ≥4,5:1 medido con sonda). Verificar en las 8 rutas de admin.
- [ ] **Step 4: Suite + ciclo triple sobre admin + commit.** `git commit -m "fix(admin): outline-success/info/warning al token con la especificidad del vendor, y la marca AIA sobre el minimo AA (C-38/C-25)"`.

### Task 24: F5-2 — Campos opcionales marcados en crear usuario (C-28)

**Files:**
- Modify: `admin/views/` — create.php y edit.php de usuarios (localizar: `grep -rln "Contraseña\|Cargo" admin/views/ | head`)

**Interfaces:**
- Consumes: nada.
- Produces: los DOS opcionales marcados (guía de Nielsen), no cinco asteriscos.

- [ ] **Step 1: Editar las etiquetas:** «Email» → «Email (opcional)» y «Cargo» → «Cargo (opcional)» en create.php y edit.php.
- [ ] **Step 2: Verificar en `/admin/usuarios/crear`** que renderiza limpio y el formulario no cambia de geometría. Commit: `git commit -m "feat(admin): marca los dos campos opcionales del formulario de usuario (C-28)"`.

### Task 25: RETIRADA — PDC V1 deprecado (2026-08-04)

Era «Etiquetas del modal de contrato del PDC (C-36)»: 34 de 53 campos con etiqueta visible pero no
asociada, en el formulario más largo de la aplicación. Ese modal pertenece a `contratos/`, dentro de
PDC V1. **C-36 se cierra como «no aplica: módulo deprecado»**. Si PDC v2 (`/plan-compras`) monta un
formulario equivalente, su accesibilidad se audita allí desde cero, no se traslada este censo.

### Task 26: F5-4 — Tabla equivalente para los gráficos de BI (C-32)

**Files:**
- Modify: las vistas de BI con canvas (localizar: `grep -rln "aria-label=\"Gráfico" views/`) y el JS que pinta las series (mismo dato de origen)

**Interfaces:**
- Consumes: nada.
- Produces: cada gráfico con tabla `.sr-only` generada de la misma fuente que la serie.

- [ ] **Step 1: Localizar dónde el JS recibe la serie** (el mismo array que alimenta el chart). En ese punto, generar también `<table class="sr-only">` con encabezados y filas (semana → valor), y vincular con `aria-describedby` del canvas a un contenedor con la tabla.
- [ ] **Step 2: Comprobar la clase `.sr-only`** existe en el sistema (grep en `public/css/`); si el sistema usa otra utilidad de ocultamiento accesible, usar esa.
- [ ] **Step 3: Verificar:** la tabla no es visible, el lector la encuentra (DOM correcto), los valores COINCIDEN con la serie pintada (misma variable, no copia). Commit: `git commit -m "feat(bi): tabla equivalente oculta junto a cada grafico, generada de la misma serie (C-32)"`.

### Task 27: F5-5 — Lote mecánico de metadatos y auditorías (C-37, C-18, C-19, C-8, C-5, C-11, C-15, C-20)

**Files:**
- Modify: `public/js/modules/programa_general/hot.js` (C-37: `aria-hidden` en los 24 `.changeType`; C-18: borrar `fitActionsRowSingleLine()` en `hot.js:1203` — está en el hot.js de PS/compartido, confirmar con grep; C-19: tooltip condicionado), `state-tint-exceptions.json` (C-8: migrar a firma), `.impeccable/design.json` (C-5: regenerar)
- Create: 3 informes de auditoría en el ledger (C-11, C-15, C-20)

**Interfaces:**
- Consumes: nada.
- Produces: metadatos uniformes + 3 informes que el usuario decide (las auditorías REPORTAN, no arreglan).

- [ ] **Step 1: C-37.** En el renderer de PG que crea `.changeType`, poner `aria-hidden="true"` incondicional (hoy 12/24). Sonda: 24/24.
- [ ] **Step 2: C-18.** `grep -rn "fitActionsRowSingleLine" public/js` → borrar la función muerta y su comentario falso; comprobar 0 referencias.
- [ ] **Step 3: C-19.** En el `title` de cabeceras (task 26 viejo): condicionarlo a recorte real en un hook `afterRender` de Handsontable (la receta del ledger: medir DESPUÉS del ancho definitivo, no en el renderer). Sonda: «Id» sin tooltip, cabecera recortada con tooltip.
- [ ] **Step 4: C-8.** Migrar `state-tint-exceptions.json` de ancla por línea a ancla por firma, con el mismo esquema que su hermano del task 12-bis (leerlo como plantilla). Gate en verde tras migrar.
- [ ] **Step 5: C-5.** Regenerar el sidecar: el comando vive en la skill impeccable (`/impeccable document` o el script que cite `DESIGN.md`); verificar diff coherente con `DESIGN.md`.
- [ ] **Step 6: Las 3 auditorías (solo informe):** C-11 `grep -rn "@media" public/css | grep -E "76[89]|1[01][0-9][0-9]" ` → listar todo media query que solape 1180 px; C-15 `grep -rn "layer(" public/css/*.css public/css/**/*.css` cruzado con `@layer` interno de cada archivo → listar dobles capas; C-20 censo de tokens sin variante dark (comparar los bloques claro/oscuro de `tokens.css`). Los tres informes van al ledger con recomendación, SIN aplicar.
- [ ] **Step 7: Suite + commit.** `git commit -m "chore(ds): lote mecanico — aria-hidden uniforme, codigo muerto fuera, tooltip honesto, anclas por firma, sidecar (C-37/C-18/C-19/C-8/C-5) + 3 auditorias reportadas"`.

### Task 28: F5-6 — Borrado de las 22 ramas viejas (C-1)

**Files:**
- Read: `docs/superpowers/ramas-viejas-2026-08-03.md` (censo 22/22 sin contenido único)

**Interfaces:**
- Consumes: nada.
- Produces: repo local sin ramas muertas.

- [ ] **Step 1: Re-verificar por muestreo** (el censo tiene un día): para 3 ramas al azar, `git log main..<rama> --oneline` debe salir vacío y `git cherry main <rama>` sin `+`.
- [ ] **Step 2: Borrar las 22 en local:** `git branch -d <rama>` (con `-d`, NO `-D`: si alguna se resiste, es que tiene contenido — STOP y reportar). Las remotas NO se tocan sin petición explícita.
- [ ] **Step 3: Anotar en el ledger** la lista borrada. Sin commit (no hay cambio de árbol).

### Task 29: IA-4 — Microinteracciones de las acciones diarias

**Files:**
- Modify: `docs/DESIGN.md`/`DESIGN-AUDIT.md` (`## Microinteraction Inventory`), `docs/EXPERIMENTS.md`, `docs/IMPROVE-APP-PLAN.md`

**Interfaces:**
- Consumes: Task 22 (IA-3).
- Produces: inventario Trigger/Rules/Feedback/Loops + un momento firma elegido con el usuario.

- [ ] **Step 1: Invocar la skill `microinteractions`** sobre: confirmar compromisos, guardar celda (Handsontable), filtrar (chips), importar cronograma. Por cada una: ¿feedback <100 ms en el elemento tocado? ¿estados vacío/cargando/parcial/error mapeados?
- [ ] **Step 2: Preguntar al usuario el momento firma** (test de eliminación: ¿la app se sentiría peor sin él?). Candidato natural: la confirmación de compromisos (el corazón del LPS).
- [ ] **Step 3: Registrar hallazgos; los arreglos que sean solo CSS/feedback visual se aplican con su ciclo triple; los que toquen comportamiento van al backlog con ICE.** Tracker + commit docs.

### Task 30: IA-5 — Copy in-app con SUCCESs (made-to-stick)

**Files:**
- Create: `docs/POSITIONING.md` (esqueleto de la skill, solo `## Key Messages` poblado)
- Modify: `docs/EXPERIMENTS.md`, `docs/IMPROVE-APP-PLAN.md`; vistas cuyo copy apruebe el usuario

**Interfaces:**
- Consumes: `docs/CUSTOMER.md` (lenguaje del job por rol).
- Produces: copy por superficie con score y reescritura aprobada; C-33 preguntado.

- [ ] **Step 1: Invocar la skill `made-to-stick`** sobre: onboarding de **Plan de Compras v2** (`/plan-compras`, el modal «Paso 1 de 6» — **no** el `/pdc` viejo, deprecado), estados vacíos (los buenos ya censados — solo se toca lo que falle el score), errores, CTAs y tooltips de PG→PI→PS.
- [ ] **Step 2: C-33 aquí:** preguntar al usuario la frase de dominio del estado vacío de Control de Cambios («¿de dónde nacen las solicitudes de cambio?»). Con su frase, aplicarla; sin ella, chip.
- [ ] **Step 3: Reescrituras aprobadas se aplican** (texto de UI no-dominio); las de dominio solo con su visto bueno explícito. Verificación en navegador + commit.

### Task 31: Barrido final de campaña + IA-6 (steve-jobs-design-review) + cierre

**Files:**
- Modify: `docs/superpowers/barrido-diseno-2026-08-03.md` (pasada final), `docs/PRODUCT.md` (create — `## Outcome Roadmap`), `docs/IMPROVE-APP-PLAN.md` (cierre), registro de decisiones (disposición final de las 54), **`docs/DESIGN-AUDIT.md` (Task 14): las 20 filas `pendiente (Task N)` se refrescan a su disposición real de cierre — la tabla se creó a mitad de campaña y nadie más la sincroniza**

**Interfaces:**
- Consumes: todo lo anterior.
- Produces: condición de hecho de la campaña cumplida.

- [ ] **Step 1: Barrido completo final:** las ~25 superficies + 7 de admin, tres lentes en orden, 1180×820 dark, consolidando contra el doc del barrido — solo lo nuevo o cambiado. Expected: sin hallazgos nuevos de las categorías tratadas; lo nuevo que aparezca → chip o task según tamaño (preguntar si es grande).
- [ ] **Step 2: IA-6 — revisión en frío:** invocar `steve-jobs-design-review` sobre el flujo PG→PI→PS como usuario nuevo (sandbox): el One Thing, pasos-hasta-valor, veredicto binario, lista ordenada de cortes y arreglos, y la parte de atrás de la valla (vacíos, errores, 404). Los cortes propuestos se presentan al usuario — **nada se borra sin su sí**.
- [ ] **Step 3: Crear `docs/PRODUCT.md`** con `## Outcome Roadmap` poblado con cortes/arreglos priorizados.
- [ ] **Step 4: Disposición final de las 54 entradas** en el registro de decisiones: cada una marcada ejecutada (commit), chip (creado con `spawn_task`: C-33 si quedó sin frase, C-35, C-39, C-41, C-42, C-12, C-6, C-9, campaña C-2, más lo que las auditorías de Task 27 ameriten), o cerrada. Verificar contra la condición de hecho del spec (5 puntos).
- [ ] **Step 5: `memoria/` ingest** de lo aprendido (respetando `docs/wiki-operacion.md` y sin tocar los 2 archivos de la otra sesión si siguen sin commitear — confirmar antes), ledger cerrado, resumen final al usuario con: verificado, comandos, resultados, límites pendientes y la tarea externa suya (tema oscuro del informe en Power BI).
- [ ] **Step 6: Traspaso al cierre de 1.1.0.** Con la campaña terminada se cumple la precondición D4 del goal `goals/cierre-version-1-1-0-design-system/goal.md`. Crear el chip de arranque con `spawn_task` (título: «Ejecutar el cierre de la versión 1.1.0 del design system»; prompt: ejecutar `docs/superpowers/plans/2026-08-04-cierre-version-1-1-0-design-system.md` con superpowers:subagent-driven-development o executing-plans, leyendo antes `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md`; la precondición D4 ya está cumplida por este cierre) y decírselo al usuario en el resumen final. Si el chip de la sesión del 2026-08-04 sigue vivo, no duplicar: basta señalarlo como listo para lanzar.

### Task 32: Restaurar el indicador de fase de Programación Semanal (regresión hallada en Task 1)

**Files:**
- Modify: `public/js/modules/programacion_semanal/hot.js` (`ensureContextPhaseShell()`, línea ~1116)
- Test: los 16 casos de `tests/browser/programacion-semanal-*.mjs` que esperan `.ps-weekly-phase-title`

**Interfaces:**
- Consumes: Task 4 (mueve píxel: va tras la recaptura de goldens).
- Produces: indicador de fase visible de nuevo en escritorio; 16 casos de la suite recuperados.

Origen: hallazgo de la Task 1, verificado de forma independiente por el coordinador en navegador a 1200 px — `.context-bar .container-fluid.d-flex.align-items-center.justify-content-between` da **0 coincidencias**, no hay `.context-breadcrumb`, y `#ctxWeeklyPhase` / `.ps-weekly-phase-title` no existen en el DOM servido. La función busca el contenedor de la navbar legacy que murió con el rollout del shell lateral, así que `return null` y el indicador no se pinta nunca. Decisión del usuario (2026-08-04): **restaurarlo** — es devolver algo que existía, no inventar UI.

- [ ] **Step 1: Medir el estado actual** en `/programacion-semanal` con dev door a 1180×820: confirmar los 4 valores de arriba y capturar la barra de contexto como evidencia del «antes».
- [ ] **Step 2: Localizar el anclaje del shell actual.** La `.context-bar` viva contiene hoy tres `<span>` y un `.aia-menu.context-week-menu`. Determinar en el markup del shell (buscar la vista/partial que la renderiza) cuál es el punto de inserción correcto y si el shell ya expone un contenedor con nombre propio al que engancharse.
- [ ] **Step 3: Reenganchar `ensureContextPhaseShell()`** al selector real, conservando el markup que inyecta (`#ctxWeeklyPhase`, `.ps-weekly-phase-title`, las clases de modificador de fase). No cambiar el texto ni la lógica de fases: solo dónde se monta.
- [ ] **Step 4: Verificar:** el indicador aparece y cambia con la fase; sin overflow a 1180 px; consola limpia; estilo coherente con el shell (tokens `--ds-*`, sin hex).
- [ ] **Step 5: Correr los 16 casos** que dependían de él: `npx playwright test tests/browser/programacion-semanal-*.mjs --workers=1`. Expected: los 16 pasan de fallo a verde; el resto no empeora.
- [ ] **Step 6: Suite estática 8/8 + goldens movidos + ciclo triple + commit.** `git commit -m "fix(ps): restaura el indicador de fase que el shell lateral dejo huerfano"`.

### Task 33: Apretar la tolerancia de los goldens (hallazgo de la Task 5) — PRIORITARIA

**Files:**
- Modify: `tests/browser/programa-general.visual.mjs:90`, `tests/browser/programacion-intermedia.visual.mjs:43` (`maxDiffPixelRatio`), más cualquier otro spec visual con la misma tolerancia
- Test: los propios visuales

**Interfaces:**
- Consumes: goldens al día (Task 4 + Task 5).
- Produces: red visual que de verdad detecta cambios; **todas las tasks visuales posteriores dependen de ella**. Va antes que el resto de la fase 3.

Origen: la Task 5 midió que su propio cambio de rejilla ocupa el 2,66 % de la imagen y **pasó en verde**, porque la tolerancia es del 3 % (~29.000 px a 1180×820). Además `--update-snapshots` no reescribe por debajo del umbral: hace falta `=all`. Decisión del usuario (2026-08-04): apretarla ya.

- [ ] **Step 1: Censar** todos los specs visuales y su tolerancia: `grep -rn "maxDiffPixelRatio\|maxDiffPixels" tests/browser/`.
- [ ] **Step 2: Bajar a ~0,002 (0,2 %)** en todos. Justificar el valor en un comentario: por debajo del ruido de renderizado entre corridas, por encima de cero para no romper por antialiasing.
- [ ] **Step 3: Comprobar que la nueva vara no da falsos rojos:** correr los visuales **tres veces seguidas sin tocar nada**. Expected: verde las tres. Si alguna da rojo, el piso es demasiado bajo — subirlo al mínimo que aguante tres corridas limpias, y decirlo.
- [ ] **Step 4: Probar que la vara MUERDE:** introducir un cambio visual pequeño y deliberado (p. ej. 1 px de borde), comprobar que ahora sale rojo, y revertirlo. Sin esta prueba no se sabe si el gate mide algo.
- [ ] **Step 5: Suite estática 8/8 + commit.** `git commit -m "test(visual): la tolerancia de los goldens baja del 3% al 0,2% y por fin muerde"`.

### Task 34: Tipar las columnas numéricas y alinearlas a la derecha (tercio pendiente de la variante B)

**Files:**
- Modify: `public/js/modules/programa_general/hot.js` (config de columnas; `cantidad_ppto` :2918 y `EjecutadoDisplay` :2930 ya son `type: 'numeric'`, `Ejecutado_Teorico` :2922 no lo es), y los `hot.js` equivalentes de PI y PS donde aplique (**no** `pdc/`, deprecado)

**Interfaces:**
- Consumes: Task 5 (bordes) y Task 33 (la red que detectará el cambio).
- Produces: la variante B aprobada, completa.

Origen: la Task 5 implementó 2 de los 3 elementos de la variante B. El tercero exige tipar columnas en JS, porque hoy cada columna fuerza `className: 'htCenter htMiddle'` y alinear solo las tipadas dejaría columnas gemelas desalineadas entre sí. Decisión del usuario (2026-08-04): hacerlo como task propia.

- [ ] **Step 1: Censar** qué columnas contienen números de verdad en PG, PI y PS (no en `pdc/`, deprecado), y cuáles están tipadas hoy (`grep -n "type: 'numeric'" public/js/modules/*/hot.js`). Distinguir números reales de códigos jerárquicos como `3.5.2.1` — **esos NO se alinean a la derecha**, son identificadores, no cantidades.
- [ ] **Step 2: Tipar** las columnas numéricas que falten y quitarles el `htCenter` forzado, dejando la alineación a la derecha por clase del sistema.
- [ ] **Step 3: Verificar con datos reales en solo lectura:** las columnas gemelas quedan alineadas igual entre sí; los códigos jerárquicos siguen sin alinearse a la derecha; los totales se leen alineados por unidades.
- [ ] **Step 4: Suite estática 8/8 + goldens movidos (ya con la tolerancia fina) + ciclo triple + commit.** `git commit -m "feat(tablas): las columnas numericas se tipan y alinean a la derecha — cierra la variante B"`.

### Task 35: El golden de Programación Intermedia retrata una grilla vacía (hallazgo de la Task 33)

**Files:**
- Modify: `tests/browser/programacion-intermedia.visual.mjs:22-23` (el mock de `**/api/pi/list**` devuelve `data: []`)
- Patrón a copiar: `tests/browser/programa-general.visual.mjs:14-25` (`FILAS_DE_ESTADO`), que ya resolvió el mismo defecto

**Interfaces:**
- Consumes: Task 33 (tolerancia fina).
- Produces: golden de PI capaz de detectar regresiones de celda. **Las tasks 6 y 8 editan PI**, así que va antes que ellas.

Origen: la Task 33 midió que su prueba de mordida (borde de celda 1px→2px) **no hizo reaccionar al golden de PI**, porque su mock devuelve cero filas. Apretar la tolerancia no arregla un retrato en el que no sale lo que hay que vigilar: hoy PI solo cubre cromo y maquetación, y es ciega a bordes de celda, tintes de fila y ruta crítica.

- [ ] **Step 1: Leer el patrón de PG** (`FILAS_DE_ESTADO`) y entender qué estados siembra y por qué esos.
- [ ] **Step 2: Sembrar el mock de PI** con filas que cubran sus estados propios de alistamiento (no copiar los de PG: PI tiene su propio vocabulario — comprobarlo en el módulo antes de inventar). Incluir al menos una fila por estado que la vista tiña.
- [ ] **Step 3: Re-baselinear el golden de PI** con `--update-snapshots=all` (recordar: por debajo de la tolerancia no reescribe sin `=all`) y **presentar el nuevo retrato al usuario antes de consagrarlo** — pasa de tabla vacía a tabla con datos, así que es un cambio de línea base, no un ajuste.
- [ ] **Step 4: Probar que ahora muerde:** repetir la mordida de la Task 33 (borde de celda 1px→2px) y confirmar que PI se pone en rojo; revertir.
- [ ] **Step 5: Sincronizar sha256 de manifiestos + suite estática 8/8 + commit.** `git commit -m "test(visual): el golden de PI deja de retratar una tabla vacia y por fin vigila celdas"`.

### Task 36: Llevar el encabezado sobrio a Programa General y Programación Intermedia (paridad de C-44)

**Files:**
- Modify: `public/css/design-system/tokens.css:325-327` (`--pdc-header-*`, hoy compartido) y/o las reglas de `.pdc-header` / `.row-header` que consumen PG y PI
- Test: goldens de `programa-general.visual.mjs` y `programacion-intermedia.visual.mjs` (ambos asertan píxeles y ahora muerden al 0,2 %)

**Interfaces:**
- Consumes: Task 7 (el tratamiento sobrio ya definido en `#dt_cliente`), Task 33 y 35 (la red visual afinada).
- Produces: un solo lenguaje visual de encabezado en las tres tablas.

Origen: la Task 7 dejó `/pdc` con encabezado sobrio (negrita + filete, luminancia 1,64× frente a 5,72×) pero acotó el cambio a `#dt_cliente` porque `--pdc-header-bg` lo comparten PG y PI —verificado: `programa_general/hot.js:824,1322,1531` y `programacion_intermedia/hot.js:746` estampan `pdc-header`— y tocarlo movía sus goldens sin autorización. Decisión del usuario (2026-08-04): **igualar las tres**.

- [ ] **Step 1: Mapear los consumidores reales** del token y de las reglas `.pdc-header td` / `.row-header td` (`styles.css:514,609` apuntan a `tr`, la vía de PG). Distinguir qué superficie consume qué, para no cambiar de más.
- [ ] **Step 2: Comprobar la compuerta de semántica en PG y PI**, igual que hizo la Task 7 en PDC: que en esos módulos el naranja marque encabezado y no un estado. Si en alguno codifica estado, **STOP y reportar** — la paridad no vale romper significado.
- [ ] **Step 3: Aplicar el mismo tratamiento** (peso tipográfico + filete superior, superficie neutra del sistema). Reutilizar exactamente lo que la Task 7 dejó, no una variante nueva.
- [ ] **Step 4: Medir con datos reales en solo lectura:** luminancia de la fila de capítulo ≤2× la normal en PG y PI, y captura que demuestre que los capítulos siguen encontrándose de un vistazo.
- [ ] **Step 5: Recapturar los goldens de PG y PI** con `--update-snapshots=all`, **presentando el antes/después al usuario** (mueve dos líneas base que él ya aprobó una vez). Sincronizar sha256 de manifiestos.
- [ ] **Step 6: Suite estática 8/8 + ciclo triple + commit.** `git commit -m "feat(tablas): PG y PI estrenan el encabezado sobrio del PDC — un solo lenguaje en las tres"`.

---

## Self-review

- **Cobertura del spec:** F1-1→T1, F1-2→T2, F2-1→T4, F3-1..9→T5-13, F4-1..7→T15-21, F5-1..6→T23-28, IA-1..6→T3/T14/T22/T29/T30/T31, barrido final y condición de hecho→T31. Cerradas-sin-trabajo y chips: T31 step 4. Sin huecos.
- **Placeholders:** las tasks de investigación (T2, T16, T27 step 6) producen informes con regla de decisión explícita, no «TBD»; los pasos de edición citan selector, valor medido y objetivo numérico.
- **Consistencia:** `pdc-legend-item` (T11 — la clase se llama así por herencia, pero sus consumidores vivos son PI y PS), `--ds-active-surface-raised` (T7/T10/T11), `.ops-state-zoom` y umbral 120 px (T8), dev door y viewport uniformes en todas.
- **Purga de PDC V1 (2026-08-04):** retiradas las tasks 17 y 25 enteras; acotadas la 8 (fuera «ESTADO DEL PROCESO»), la 10, la 13 (fuera C-3), la 15, la 30 y la 34. La 7 queda hecha pero marcada. Ninguna task viva lee, mide ni edita `pdc/`, `listado-actividades/` o `contratos/`.
