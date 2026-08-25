---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-07
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-07-reapertura-movil-y-tema-claro-design.md
resumen: Que el viewport 390x844 vuelva a ser un valor válido en los esquemas, contratos y gates del design system, sin que ninguna familia lo declare todavía y sin…
---

# Reapertura de móvil/tablet y tema claro — diseño

- Fecha: 2026-08-07
- Estado: aprobado en brainstorming, pendiente de plan
- Alcance de este documento: el programa completo (F1–F4) y la **spec ejecutable de F1**.
  F2, F3 y F4 tendrán cada una su propia spec antes de implementarse.

## Por qué

El 2026-08-07 se retiraron de los `.md` normativos (`AGENTS.md`, `CLAUDE.md`, `DESIGN.md`,
`docs/design-system/README.md`, `docs/design-system/manual-accessibility-review.md` y
`memoria/mapas/design-system.md`) las prohibiciones de trabajar en móvil, tablet y tema claro.
Los documentos quedaron abiertos; el código y los gates no. Siguen vivos:

- el candado `tests/design-system/mobile-viewport-removal.test.mjs` (DS-031, 2026-08-06),
- la salida del viewport `390x844` de `homologation.json`, de los esquemas y de los tres gates,
- el candado `tests/design-system/linen-removal.test.mjs` y `theme-default.test.mjs` (DS-030,
  2026-07-25), que fijan dark como único tema sin conmutador.

Este documento define cómo se reabre eso en el código sin dejar el repositorio sin red.

## Decisiones tomadas

| # | Decisión | Alternativas descartadas |
|---|---|---|
| D1 | Se abren **los dos frentes completos**: móvil real y tema claro real. | Solo destrabar los candados; un frente sin el otro. |
| D2 | En móvil funcionan **las 13 áreas**. Las tablas desktop se convierten en **una card por registro**. | Solo lo de obra; todo pero solo lectura; solo tablet. |
| D3 | Las grillas de vendor (Handsontable, AG Grid) **no se montan en móvil**: se renderiza una lista de cards desde los mismos datos, con su propia vista. | Cards para leer y grilla para editar; grilla con scroll; esas pantallas solo desktop. |
| D4 | El tema claro se elige con **conmutador manual y preferencia guardada**. | Seguir al sistema; sistema + conmutador; claro como tema único. |
| D5 | La paleta clara es **nueva, derivada de los tokens AIA actuales**. No se resucita `linen`. | Resucitar `linen` de `d81215f0^`; híbrido estructura vieja + color nuevo. |
| D6 | Los gates adoptan una **matriz diagonal**: contraste y axe en los 6 combos (2 temas × 3 viewports); goldens visuales en `dark/1180x820` y `claro/390x844`, y el resto por muestreo. | Matriz completa de 60 escenarios; abrir sin gate y añadir después. |
| D7 | El orden es **móvil primero, tema claro después** (opción A). | Claro primero; los dos a la vez módulo por módulo. |
| D8 | En F1 el viewport móvil queda **declarado pero no exigido**. | Exigirlo ya con goldens del estado roto; exigirlo solo en axe. |

### Razón de D7

Las cards móviles y el tema claro tocan el mismo CSS de cada módulo. En paralelo, cada módulo se
tocaría dos veces y los goldens se regenerarían dos veces. Con móvil primero, el tema claro se
pinta una sola vez sobre la estructura definitiva. Hacerlo al revés obligaría a rehacer el layout
después de haberlo pintado en dos temas.

### Razón de D5

De la paleta clara solo sobreviven nueve tokens (`--ds-color-state-*-light`,
`--ds-color-focus-ring-light`) y `DESIGN.md` los reserva explícitamente para impresos y XLSX, no
para pantalla. `linen` es recuperable de `d81215f0^`, pero arrastra decisiones cromáticas
anteriores a la escala de estado actual, a los tokens de celda y a las primitivas BI.

## Las cuatro fases

| Fase | Qué entrega | Tamaño |
|---|---|---|
| **F1 — Destrabar** | Los esquemas, contratos y gates aceptan el viewport móvil sin exigirlo. Sin cambio visual. | chica |
| **F2 — Móvil real** | Patrón tabla→card, vista de cards para Handsontable y AG Grid, las 13 áreas, goldens y axe móviles. | grande |
| **F3 — Tema claro** | Paleta clara nueva, ramas CSS, conmutador con preferencia guardada, bootstrap sin parpadeo. | grande |
| **F4 — Matriz diagonal** | Los gates pasan a la matriz de D6 y los candados se reinstalan en su forma nueva. | media |

Notas de programa, no de F1:

- El Plan de Compras es una SPA React aparte (`pdc-app/`, AG Grid). Su vista de cards no comparte
  código con el resto de la app y merece su propia spec dentro de F2.
- `theme-default.test.mjs` fija a mano las 22 declaraciones del bloque `:root`. En F3 ese test
  **cambia de forma**, no desaparece.
- `linen-removal.test.mjs:86` comprueba la cadena literal ``tema `linen` `` en `DESIGN.md`. No
  distingue «prometer un tema retirado» de «explicar que se retiró»: el 2026-08-07 una redacción
  que decía justo lo contrario de prometerlo lo puso en rojo. En F3 hay que sustituirlo por una
  comprobación de intención, no de cadena.

## Precondiciones de F2

Hallazgo de la revisión final de F1 (2026-08-07): hoy se puede declarar un escenario `390x844`
reutilizando el golden y el `sha256` de un escenario desktop y el gate lo deja pasar en verde —
verifica que el golden exista y que su hash cuadre, pero no que corresponda al viewport declarado
en el escenario. Además, los carriles runtime filtran por `viewport.width >= 1180`, así que un
escenario móvil nunca llega a renderizarse ni a compararse: el gate está midiendo un archivo que el
runtime jamás produjo. Esto no se corrige en F1 — es alcance de F2 — pero deja tres precondiciones
que F2 debe resolver **antes de declarar el primer escenario `390x844`**:

1. **Retirar los tres filtros `viewport.width >= 1180`** de los carriles visual y de accesibilidad,
   para que un escenario móvil realmente se renderice y se compare:
   - `tests/browser/design-system-lab.visual.mjs:22`
   - `tests/browser/programa-general.visual.mjs:12`
   - `tests/browser/design-system-lab.a11y.mjs:19`
2. **Atar el golden a su viewport** en el gate de contratos, para que un golden capturado en
   desktop no pueda presentarse como evidencia de un escenario móvil (hoy el gate solo comprueba
   existencia del archivo y que el `sha256` coincida con el contenido, sin cruzar el viewport del
   escenario contra el viewport con que se capturó el golden).
3. **Arreglar el harness `runFixture`** (`tests/design-system/contracts.test.mjs`): copiar los
   archivos de test que hoy faltan (el harness solo copia 9 de la veintena que los contratos
   referencian) y enlazar `.git` al fixture temporal. F2 necesitará pruebas de caso positivo sobre
   este harness — hoy solo permite probar que algo falla, no que algo pasa.

---

# F1 — Destrabar

## Objetivo

Que el viewport `390x844` vuelva a ser un valor **válido** en los esquemas, contratos y gates del
design system, sin que ninguna familia lo declare todavía y sin generar evidencia nueva.

## Condición de hecho

1. `npm run test:design-system:static` pasa sus ocho puertas.
2. Los gates siguen ejerciendo exactamente los **20 escenarios** desktop de hoy (10 familias × 2
   viewports × 1 tema).
3. No se añade, regenera ni borra ningún golden.
4. Un manifiesto de módulo con un escenario `390x844` **valida contra el esquema** (hoy no lo
   permite el candado) y **sigue sin satisfacer** el gate de cobertura de familias.

## La idea central: separar «permitido» de «exigido»

Hoy los dos conjuntos son el mismo en cinco sitios. F1 los separa. Nada más.

### Cambios

| Archivo | Cambio |
|---|---|
| `docs/design-system/runtime-budget.schema.json` (~326) | El enum de `viewport` admite `390x844`. |
| `docs/design-system/family-approvals.schema.json` (~85) | El enum de `viewports` admite `390x844`. |
| `scripts/design-system-runtime-budget.mjs:38` | `SUPPORTED_VIEWPORTS` suma `390x844`. |
| `scripts/design-system-runtime-budget-provenance.mjs:26` | `VIEWPORTS` suma `390x844`. |
| `scripts/design-system-contracts.mjs:334` | `supportedViewportKeys` se parte en `SUPPORTED_VIEWPORTS` (aceptables) y `REQUIRED_VIEWPORTS` (obligatorios por familia). Los bucles de cobertura (~340, ~348) iteran sobre `REQUIRED_VIEWPORTS`. |
| `scripts/design-system-contracts.mjs:168` | La igualdad exacta de `approval.viewports` pasa a dos reglas: contiene todos los `REQUIRED_VIEWPORTS`, y no contiene nada fuera de `SUPPORTED_VIEWPORTS`. |
| `docs/design-system/contracts/module-migration.md` | Documenta la distinción permitido/exigido y que móvil está abierto pero sin cobertura obligatoria. |

### Lo que NO cambia en F1

- `homologation.json`: las 10 familias siguen declarando `["1180x820", "1440x900"]`.
- `family-approvals.json`: las aprobaciones firmadas no se tocan.
- `module-manifest.schema.json`: ya admite `width ≥ 320` (línea 118). El veto vivía solo en el
  test, no en el esquema.
- La derivación de densidad de `design-system-contracts.mjs:316` (`width >= 1200 ? compact :
  touch`): ya trata correctamente el ancho móvil como `touch`.
- Ninguna hoja de `public/css`. Los `@media (max-width: 768px/991px)` existentes siguen intactos:
  el CSS responsive nunca se borró, solo salió de los gates.

## El candado

`tests/design-system/mobile-viewport-removal.test.mjs` **se reescribe, no se borra**, y se renombra
a `mobile-viewport-scope.test.mjs`. Cambia de intención:

| Antes afirmaba | Ahora afirma |
|---|---|
| Los esquemas no admiten `390x844`. | Los esquemas admiten `390x844` (regresión inversa: que no vuelva a cerrarse por descuido). |
| Toda familia declara exactamente los dos desktop. | Toda familia declara **al menos** los dos desktop, y solo viewports soportados. |
| Ningún manifiesto declara un escenario móvil. | Todo escenario declarado tiene golden y `sha256` — declarar móvil sin evidencia falla. |
| Los gates no iteran sobre `390x844`. | Los gates exigen los `REQUIRED_VIEWPORTS` y ninguno más. |

La intención protegida es la misma que en DS-031: **que no haya viewport declarado sin evidencia
que lo sostenga**. Lo que cambia es que antes se garantizaba prohibiendo el viewport y ahora
exigiendo su evidencia.

## Pruebas

- **Nueva** (`mobile-viewport-scope.test.mjs`): las cuatro afirmaciones de la tabla anterior.
- **Nueva** (fixture negativa): un manifiesto sintético con escenario `390x844` **sin** golden hace
  fallar el gate de contratos. Sin esta prueba, la apertura no tiene red.
- **Existentes que deben seguir verdes sin tocarse:** `contracts.test.mjs`,
  `runtime-budget.test.mjs`, `runtime-budget-provenance.test.mjs`,
  `runtime-budget-aggregate.test.mjs`, `governance-docs.test.mjs`.
- **Comando de cierre:** `npm run test:design-system:static` (ocho puertas).

No se corre el carril runtime (`test:design-system:runtime`) en F1: no hay cambio visual que
validar y su entrada no cambió.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Separar permitido/exigido afloja el gate más de lo previsto y F2 cuela un viewport sin evidencia. | La fixture negativa es obligatoria en F1, no en F2. |
| `family-approvals.json` tiene aprobaciones firmadas con `approvalRef`; relajar su validación podría admitir una aprobación inválida. | La regla nueva solo relaja el conjunto de viewports; el resto de campos obligatorios (`approvalRef`, `approvedAt`, `evidence`) no se toca. |
| DS-031 queda contradicho en `decisions.md`. | No se edita DS-031: se añade una decisión nueva (DS-032) que la supersede, con fecha y motivo. Las actas no se reescriben. DS-031 cita el candado por su nombre de archivo, así que DS-032 debe registrar el renombrado para que la referencia no quede colgada. |
| El renombrado del candado lo deja fuera de la suite. | No aplica: `design-system-static-suite.mjs:13` recoge los tests por glob sobre `tests/design-system`, no por lista. Ningún workflow ni script lo nombra explícitamente. |

## Fuera de alcance de F1

Cards, CSS, temas, goldens, axe, el conmutador, `pdc-app/`, y cualquier cambio en
`homologation.json` o en las aprobaciones firmadas.

## Cierre

**DEROGADA el 2026-08-25.** Sus cuatro fases tienen hoy vehículo vigente, y los tres avisos
concretos que solo vivían aquí se trasladaron antes de derogarla.

**La decisión que manda sobre esta spec ya la tomó Felipe:** D-9 (`DECISIONES_PENDIENTES.md:425`),
resuelta el 2026-08-20 y revisada el mismo día. Declara **cuatro de siete fases cerradas** (MO-F1,
F2a-1, F2a-2a, F2a-2b) y fija que **el tema claro no queda estacionado: va justo detrás de móvil**,
corrigiendo su propia primera respuesta porque «estacionarlo indefinidamente era, en la práctica, no
hacerlo nunca». **P4 ejecuta esa decisión al pie de la letra**, y la cita.

| Fase | Estado medido 2026-08-25 | Vehículo hoy |
|---|---|---|
| **F1** · Destrabar | Cerrada (DS-032/033/034) | — |
| **F2** · Móvil real | Piloto cerrado; faltan **13 módulos** | P4 · MO-F2b |
| **F3** · Tema claro | Sin empezar. **Es reconstruir, no reactivar**: `linen` se retiró el 2026-07-25 y no hay conmutador | P4 · MO-F3 |
| **F4** · Matriz diagonal | Retirada como fase propia el 2026-08-18 | P3 · absorbida en DS-F1 y DS-F3 |

Las **decisiones D5 y D7** las recoge P4 literalmente («es reconstruir, no reactivar»; móvil antes
que claro). Las **tres precondiciones de F2** están resueltas en el código.

### Lo que se trasladó, y por qué no podía perderse

Los tres van a [[docs/superpowers/plans/2026-08-24-p4-movil-y-tema-claro]]:

1. **El Plan de Compras no admite la receta del piloto, y está entre los 13.** `plan-compras-v2` es
   una SPA React con AG Grid que no comparte código con el resto: el umbral de montaje y el menú
   flotante del shell no le aplican tal cual. Esta spec pedía «su propia spec dentro de F2» y **no
   existe ninguna** — cero menciones de `pdc` en P3 y en P4, comprobado. Sin este aviso, alguien
   aplicaría la receta a doce módulos y se estrellaría con el decimotercero.
2. **`theme-default.test.mjs` cambia de forma en F3, no desaparece.** Fija a mano las **22**
   declaraciones del bloque `:root`, contadas hoy. Con un segundo tema esa ya no es la pregunta
   correcta.
3. **`linen-removal.test.mjs` debe pasar de comparar cadena a comparar intención.** Hoy hace
   `/linen/i.test(...)`, y **no distingue prometer un tema retirado de explicar que se retiró** — el
   2026-08-07 puso en rojo una redacción que decía justo lo contrario de prometerlo. Al documentar
   el tema claro nuevo, la palabra va a aparecer legítimamente.

Los dos últimos son la clase de aviso que se paga caro perder: **con el tema claro puesto los dos se
ponen rojos por hacer bien las cosas**, y quien llegue sin saberlo los ablandaría en vez de
reformarlos.

### Corrección a la medición asistida

Una verificación intermedia concluyó que el Plan de Compras «no aparece en ningún manifiesto». **Es
falso, y se comprobó antes de escribirlo aquí:** `docs/design-system/manifests/plan-compras-v2.json`
existe y declara `["desktop"]`. Los números de P4 son exactos — 15 manifiestos con `layouts`, uno ya
con móvil (`programa-general`, el piloto), y 13 de producto sin él excluyendo `laboratory`. El
hallazgo real no era de conteo sino de **método**: el PDC está contado, pero la receta no le sirve.
