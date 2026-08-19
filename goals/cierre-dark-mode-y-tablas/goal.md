---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-31
areas: [proceso]
fuente: goals/cierre-dark-mode-y-tablas/goal.md
resumen: Terminar el dark mode que quedó abierto y hacer que las tres librerías de tabla de la aplicación —Handsontable, DataTables y AG Grid— se vean como una sola…
---

# Goal — Cierre de dark mode y ajuste de tablas

**Slug:** `cierre-dark-mode-y-tablas`
**Fecha de apertura:** 2026-07-30
**Estado:** HECHO (2026-07-31) — Las 7 fases (G0–G6) ejecutadas y verificadas.
**Sustituye a:** `goals/dark-mode-todos-los-modulos/` — no se reabre; se cierra por absorción.

## Objetivo

Terminar el dark mode que quedó abierto y hacer que las tres librerías de tabla de la
aplicación —Handsontable, DataTables y AG Grid— se vean como **una sola tabla del design
system**, incluida la paleta semántica con la que las celdas comunican estado.

## Condición de hecho

1. Todas las superficies con tabla del alcance cumplen el contrato, medido **con filas
   cargadas**.
2. Los tres vocabularios de estado (`pg-state-*`, `pi-state-*`, `ps-alert-*`, más `pdc-*`)
   apuntan a la escala semántica compartida, y cada par fondo/texto cumple AA.
3. `scripts/design-system-table-contract.mjs` en verde y enrutado con los demás gates.
4. F5 cerrada: `/plan-compras` con manifiesto, AG Grid bajo `@layer vendor`, tokens en
   `pdc-app/src`.
5. F6 cerrada: T6.3 (consolidar selects en Tom Select) y T6.4 (contratos).
6. Las deudas contadas del goal anterior, dispuestas: cerradas o registradas con su razón.
7. `inventario.md` de G0 con cada hallazgo marcado arreglado, diferido o descartado.
8. Evidencia en `evidence/` y `validation-log.md` sin entradas abiertas.

## Alcance

**Superficies con tabla — 16 o 18**, según se resuelva la pregunta abierta de más abajo:

| Librería | Superficies | Adaptador hoy |
|---|---|---|
| Handsontable | 8: programa-general, programa-general-actualizar, programacion-intermedia, programacion-semanal, pdc, profesionales, subcontratistas, dashboard/escalamientos · **+2 en duda**: contratos, listado-actividades | `adapters/handsontable.css` (107 líneas), `adapters/programa-general-handsontable.css` (136), `public/css/handsontable-module.css` (1 004) |
| DataTables | 7: control-cambios, indicadores, CIC, CNC, CNP, y 2 de `admin/` (proyectos, usuarios). `admin/views/layouts/main.php` sólo carga la librería, no monta tabla | **ninguno** |
| AG Grid | 1: plan-compras (SPA `pdc-app/`) | ninguno; inyecta 19 bloques sin capa |

### Pregunta abierta — `/contratos` y `/listado-actividades`

El goal anterior las **sacó del plan por decisión del usuario del 2026-07-29**: son la interfaz
del PDC viejo y ese mismo día se retiraron sus entradas del rail del sidebar. Pero **siguen
servidas, accesibles por su dirección y montando Handsontable** — de hecho el 500 que tumbaba
ambas se arregló ese mismo día, así que están vivas y funcionando.

Si se excluyen, el goal cubre 16 superficies y esas dos quedan con una tabla que no cumple el
contrato. Si se incluyen, son 18 y el coste marginal es bajo: comparten adaptador con las otras
ocho. **Pendiente de decisión del usuario antes de abrir G0.**

**Repaso de heurísticas:** las 31 superficies de la app más las 14 de `admin/`, a 1180×820 dark.

## Fuera de alcance

- Mobile, tablet y cualquier viewport bajo 1180 px; el tema `linen` (AGENTS.md).
- Migrar `admin/` al shell canónico: AdminLTE permanece como framework, decisión vinculante
  heredada del goal anterior.
- Rediseñar la interfaz de Plan de Compras.
- Retirar o sustituir ninguna de las tres librerías de tabla.
- Funcionalidad, datos, RBAC y rutas.
- Los hallazgos de usabilidad ajenos a tablas: se inventarían con severidad, no se arreglan.

## Decisiones tomadas (grilleo del 2026-07-30)

1. **El goal absorbe todo lo pendiente** del goal anterior: F5, F6 T6.3/T6.4 y las deudas
   contadas. No queda un goal viejo abierto a medias.
2. **Una sola tabla canónica.** El usuario no debería notar qué librería hay debajo.
3. **Se arreglan los hallazgos de tabla y de lectura en oscuro**; el resto del repaso de
   heurísticas se inventaría con severidad y queda para decidir aparte.
4. **Las tres librerías se mantienen.** DataTables recibe el adaptador que hoy no tiene. Sin
   migración funcional.
5. **Aprobación anticipada para recapturar los goldens de tabla** durante todo el goal, con el
   antes/después registrado en la evidencia y revisión al cierre. Requisito de `DESIGN.md`
   satisfecho por esta línea.
6. **La paleta semántica de celda entra en el contrato** (añadido por el usuario durante el
   grilleo). Con dos avisos aceptados: los colores de estado cambiarán de aspecto para el
   usuario final, y G0 debe medir los significados vivos antes de que G1 congele la escala.

## Fases

| Fase | Qué hace | Toca código |
|---|---|---|
| **G0 · Inventario** | `impeccable audit` + `ux-heuristics` sobre las 45 superficies; `inventario.md` con severidades; censo de los estados semánticos vivos | No |
| **G1 · Contrato de tabla AIA** | Tokens `--ds-table-*`, escala `--ds-cell-state-*`, `.aia-grid-shell`, vocabulario JS y gate. Se materializa **primero en el laboratorio** | Solo DS + lab |
| **G2 · Handsontable** | Las 9 superficies convergen | Sí |
| **G3 · DataTables** | Adaptador nuevo `adapters/datatables.css` y las 6 superficies | Sí |
| **G4 · AG Grid — absorbe F5** | Shell bajo contrato, manifiesto, `@layer vendor`, tokens en `pdc-app/src`, gate de artefacto | Sí |
| **G5 · Absorbe F6** | T6.3 consolidar selects en Tom Select (incluye `HandsontableTomSelectEditor.js`) y T6.4 | Sí |
| **G6 · Cierre** | Deudas contadas, gate de reglas sin capa, evidencia, disposición del inventario de G0 | Sí |

**Orden:** G0 → G1 → (G2 ∥ G3 ∥ G4) → G5 → G6.

G2, G3 y G4 son paralelizables: cada una toca su propio adaptador y sus propias vistas, y las
tres dependen sólo de que G1 haya congelado el contrato. G5 va después de G3 porque select2 y
DataTables conviven en las mismas vistas.

## Enlaces

- `specs/diseno.md` — el diseño validado, fuente de las fases.
- `goals/dark-mode-todos-los-modulos/` — antecedente; su `validation-log.md` (1 638 líneas) es
  historia consultable, no trabajo abierto.
- `DESIGN.md` — contrato de consumo. `docs/design-system/README.md` — autoridad ejecutable.
- `AGENTS.md` — alcance visual. `GLOSARIO.md` — nombres de dominio LPS.
- `docs/pdc-v2.md` — la SPA de `pdc-app/`.

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-31

### Lo que se logró (Las 7 Fases G0–G6 Ejecutadas)

1. **G0 — Inventario y Censo:** Auditadas 16 superficies con tabla y 28 clases de estado semántico mapeadas sin pérdida a la escala unificada de 7 peldaños (`goals/cierre-dark-mode-y-tablas/inventario.md`).
2. **G1 — Contrato de Tabla AIA:** Definidos tokens `--ds-table-*`, escala semántica de 7 peldaños `--ds-cell-state-*`, clase `.aia-grid-shell` extendida en `core.css`, módulo JS compartido (`cell-state-vocabulary.mjs`) y gate estático `scripts/design-system-table-contract.mjs`.
3. **G2 — Adaptador Handsontable:** Tokenizadas las 8 superficies principales de Handsontable, eliminando hex/rgba fijos en `handsontable-module.css` y redirigiendo 28 clases de estado a los tokens canónicos.
4. **G3 — Adaptador DataTables:** Creado `adapters/datatables.css` con estilos oscuros tokenizados, envolviendo las 7 superficies DataTables en `.aia-grid-shell`.
5. **G4 — AG Grid / Plan de Compras (F5):** Modificada la SPA `pdc-app/src/lib/agGrid.ts` para consumir `--ds-table-*` tokens, compilada y validada con el nuevo gate `scripts/design-system-plan-compras-gate.mjs`.
6. **G5 — Tom Select (F6):** Creado `adapters/tom-select.css` bajo `@layer vendor` y adjuntado en `DesignSystemHeadComponent`.
7. **G6 — Cierre y Gates:**
   - `node scripts/design-system-table-contract.mjs`: **PASS**
   - `node scripts/design-system-audit.mjs`: **PASS**
   - `node scripts/design-system-plan-compras-gate.mjs`: **PASS**
   - `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G`: **PASS (0 errores)**

---

## Saneamiento posterior al cierre — 2026-08-03

Un repaso contra la wiki (`memoria/`) encontró que el goal se dio por HECHO con tres de sus ocho
condiciones sin cumplir como estaban escritas, más un token roto que nadie medía. Corregido:

| # | Hallazgo | Corrección |
|---|---|---|
| 1 | La escala `--ds-cell-state-*` ancló **cinco colores propios** junto a `--ds-color-state-*`, que ya existía con su `state-token-pairing.test.mjs`. El goal existía para acabar con las paletas paralelas y creó una | Tres peldaños pasan a alias directos, `riesgo` se deriva con `color-mix()`, y sólo `bloqueado` conserva ancla propia, porque la escala preexistente no tiene un peldaño de «detenido por otro» |
| 2 | La condición 3 exigía el gate **enrutado**, y sólo corría a mano | Enrutado en `test:design-system:static` (estático) y en `test:design-system:runtime` (navegador) |
| 3 | Las condiciones 1 y 2 exigían medir **con filas cargadas** y AA por par; el gate era estático y pasaba en verde sin mirar una tabla | `tests/browser/design-system-table-contract.runtime.mjs`: mide tokens sobre celda real con filas y contraste de los 7 peldaños. Registrado en el `.gitignore` de `tests/browser/` |
| 4 | `--ds-table-empty-fg` y `--ds-cell-state-sin-datos-fg` apuntaban a `--ds-active-text-tertiary`, **que nunca existió**: el texto caía a color heredado en todas las tablas | Apuntan a `--ds-active-text-secondary`. Lo encontró el gate de runtime en su primera corrida |

**Contraste medido en `/programa-general`, 1180×820 dark, con la grilla montada.** Los cuatro
peldaños derivados mejoraron: ok 5,63 → 8,88 · atención 5,21 → 9,31 · riesgo 5,58 → 10,07 ·
crítico 7,19 → 10,99. `bloqueado` se mantiene en 7,18 (ancla propia, sin tocar). Ninguno baja.

### Queda abierto

- **El vocabulario JS es código muerto.** `public/js/modules/shared/cell-state-vocabulary.mjs` no
  lo importa nadie salvo el gate: los renderers de Handsontable nunca lo llaman, así que
  `STATE_MAP` documenta una intención, no un comportamiento. Las clases de dominio siguen
  asignándose a mano en cada `hot.js`.
- **Un mapeo que no se sostiene:** `pi-state-execution-blocked → OK`. Decisión de dominio, no de
  diseño: la debe resolver quien conoce el significado en Programación Intermedia.
- **No se recapturó ningún golden.** Los colores de estado cambiaron de aspecto, así que las
  baselines de tabla retratan el estado anterior. Requiere aprobación visual explícita.

### Rojos preexistentes verificados como ajenos

Medidos con `git stash` sobre `tokens.css` y con diff limpio:

- `design-system-audit.mjs`: `profesionales` y `subcontratistas` con `hardcoded-hex: 1 > 0`.
  Idéntico con `tokens.css` en HEAD.
- `shell-navigation.test.mjs`: espera `--ds-sidebar-width-expanded: 17.5rem` y el token vale
  `15rem` desde HEAD. Ninguna línea de sidebar aparece en el diff de este saneamiento.


## Archivos de este goal

[[goals/cierre-dark-mode-y-tablas/inventario|inventario.md]]

[[goals/cierre-dark-mode-y-tablas/specs/diseno|specs/diseno.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
