---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-11
areas: [proceso]
fuente: docs/superpowers/specs/2026-08-11-buttons-important-leyenda-design.md
resumen: Los !important de .pdc-legend-item en buttons.css — spec
---

# Los `!important` de `.pdc-legend-item` en `buttons.css` — spec

- Frente: `buttons-important-leyenda` · sesión ejecutora `36422d59`
- Fecha: 2026-08-11 · medido sobre `f1f5bd87`
- Plan: [2026-08-11-buttons-important-leyenda.md](../plans/2026-08-11-buttons-important-leyenda.md)
- Cola: `decisiones/buttons-important-leyenda-ejecutor.md`

## Qué se encargó y qué resultó ser

El encargo llegó como «de los 16 `!important` de `.pdc-legend-item`, sobran 12 y hacen falta 4».
La medición propia sobre `f1f5bd87` da otra cosa: **41 `!important` en 7 reglas**, y las 16 son
solo la regla principal.

Antes del censo, dos correcciones de mapa que ya costaron tiempo:

- **La ruta no es `public/css/design-system/components/buttons.css`, que no existe.** Es
  **`public/css/buttons.css`**. El `@import url("/css/buttons.css") layer(components)` hace fácil
  creer que vive dentro del design system.
- **`buttons.css` no aparece en `docs/design-system/exceptions.json`**, comprobado sobre el fichero
  entero y no solo sobre el presupuesto del módulo. El auditor no lo cuenta: **este frente no baja
  ninguna cifra**. Lo que se sanea es la causa.

## Censo

| Regla | Contexto | n | Objetivo | Declaraciones |
|---|---|---|---|---|
| L65 | `@layer components` | 3 | chip (dentro de `:where(...)`) | `padding-block`, `padding-inline`, `line-height` |
| L73 | `@media (max-width: 768px)` | 1 | chip (dentro de `:where(...)`) | `white-space` |
| L970 | `@layer components` | 16 | **chip** | `display`, `align-items`, `white-space`, `flex-shrink`, `height`, `max-height`, `font-size`, `padding`, `line-height`, `overflow-wrap`, `word-break`, `cursor`, `user-select`, `border-radius`, `transition`, `border` |
| L1003 | `@layer components` | 6 | **`.indicator`** (descendiente) | `display`, `flex-shrink`, `margin-right`, `width`, `height`, `border-radius` |
| L1012 | `@layer components` | 10 | **`.count-badge`** (descendiente) | `display`, `flex-shrink`, `margin-left`, `font-size`, `font-weight`, `background-color`, `color`, `padding`, `border`, `border-radius` |
| L1131 | `@media (max-width: 992px)` | 3 | chip | `font-size`, `padding`, `min-height` |
| L1187 | `@media (prefers-reduced-motion: reduce)` | 2 | chip (lista de botones) | `transition`, `animation` |

**41 en total: 25 sobre el chip y 16 sobre sus dos descendientes.** Que un tercio no esté en el
chip importa, porque el encargo hablaba de «las de `.pdc-legend-item`» y esas 16 se le habrían
colado o escapado según cómo se contara.

## Las declaraciones que se pisan

Este es el motivo por el que el frente no puede hacerse regla a regla:

| Declaración | Dónde | Qué pasa |
|---|---|---|
| `line-height` | L65 (`:where`) y L970 | El `:where()` tiene especificidad **cero**, pero con `!important` sigue compitiendo. La de L970 gana por orden. |
| `white-space` | L73 (`@media ≤768`) y L970 | Solo coinciden por debajo de 768, fuera del viewport canónico. |
| `font-size`, `padding` | L970 y L1131 (`@media ≤992`) | **Mismo valor exacto** en ambas. Por debajo de 992 la segunda no cambia nada: es redundancia, no prioridad. |
| `transition` | L970 y L1187 (`reduced-motion`) | Anulación legítima: la de accesibilidad debe ganar. |

**La trampa que esto arma**, y es la razón de reenunciar el frente: si se quita un `!important` del
bloque de 16, se mira la pantalla y no cambia nada, se concluye que sobraba — **cuando puede que
hiciera falta y otra regla lo estuviera tapando**. Un barrido hecho así deja el módulo dependiendo
de coincidencias y se cree limpio.

## Condición de hecho

No es «quitar las que sobren de una regla». Es **dejar `.pdc-legend-item` y sus descendientes con
el mínimo de `!important` que sostenga las tres pantallas**, tratando las 7 reglas como un
conjunto. El resultado del frente es **el par «cuántas se quitan / cuántas quedan» de las 41**,
porque la cifra del auditor no se mueve.

Empieza por las declaraciones que se pisan: una declaración repetida con el mismo valor sobre el
mismo selector es deuda antes que prioridad.

## Método de medición, con la corrección de `contadores-cero` incorporada

- **Computado contra computado**, antes contra después. Comparar el valor *declarado* en la hoja
  con el *computado* del navegador finge un cambio que no existe y esconde uno que sí:
  `memoria/trampas/valor-declarado-no-es-valor-computado.md`.
- **Línea base remedida tras cada restauración**, no reutilizada.
- **Las tres pantallas.** `.pdc-legend-item` lo usan `programa_general.view.php`,
  `programacion_intermedia.view.php`, `programa-general.css`, `programacion-semanal.css`,
  `programacion-intermedia.css`, `aia-design-system.css`, `styles.css`, `tokens.css` y
  `toolbar-controls.css`. Verificar solo en Intermedia no vale.
- **Ojo con las capas:** `buttons.css` entra por `@import ... layer(components)` y se envuelve otra
  vez, o sea `components.components`. **Para `!important` el orden de capas se invierte**, así que
  una regla en `components` a secas pierde contra esta aunque tenga mucha más especificidad
  (`memoria/trampas/css-layer-cascade.md`).

## Resultado

**41 → 16.** Se quitaron **25**: quedan 10 en el chip y 6 en los descendientes.

| Paso | Qué se quitó | Total |
|---|---|---|
| T2 | `font-size` y `padding` de `@media ≤992`, que repetían el valor de :970 | 41 → 39 |
| T3 | el chip sale del `:where` de :48, cuyas 3 declaraciones ignoraba | 39 → 36 |
| T4 | 10 de los 16 del chip | 36 → 26 |
| T5 | 10 de los 16 de `.indicator` y `.count-badge` | 26 → 16 |

**Los que se quedan, con quién los necesita** — y ninguno lo necesitan las tres pantallas a la vez:

| Declaración | Pantallas donde hace falta |
|---|---|
| chip `white-space`, `font-size`, `transition`, `border` | las tres |
| chip `flex-shrink`, `line-height` | solo Programa General |
| chip `min-height` (`@media ≤992`) | las tres, bajo 992 |
| `.indicator display` | Intermedia y Semanal (en PG el punto de color desaparecía) |
| `.indicator width`, `height` | solo Programa General (5px → 8px sin ellos) |
| `.count-badge font-size` | Intermedia y Semanal |
| `.count-badge color` | solo Semanal |
| `.count-badge background-color` | solo Programa General |

**La lista heredada decía cuatro necesarias y se quedaba corta por dos**: `flex-shrink` y
`transition` también pierden sin `!important`.

### Dos cifras, las dos ciertas, y no son la misma

- **41 → 16** es lo que pesa sobre la leyenda, que es lo que el frente perseguía.
- **160 → 138** es el total de `!important` del archivo: **22 menos**, no 25. La diferencia son las
  3 de T3, que **siguen en el archivo** —la regla vive para `.badge`, `.aia-chip` y compañía— y solo
  dejaron de alcanzar al chip.
- Biome lo corrobora sin haberlo buscado: sus avisos bajan **2626 → 2604**, exactamente 22.
  `noImportantStyles` es *warning*, no *error*, así que los 863 errores de `npm run check:frontend`
  no se mueven — y ya estaban ahí antes del frente, medidos sobre `f1f5bd87`.

### Verificación

- `npm run test:design-system:static` **RC=0, 8/8**.
- Computado contra la base en **las tres pantallas y a 1180 y 900**, antes y después de cada paso.
- `npm run check:frontend` sale **RC=1 antes y después**: rojo preexistente del repo, no de este
  frente. Comprobado poniendo `buttons.css` en su versión de `f1f5bd87` y volviendo a medir — no
  con `git stash`, que **con el árbol limpio no guarda nada y devuelve una comparación falsa**
  (caído en ello durante este frente).
- El auditor de diseño **no se mueve, y era lo esperado**: `buttons.css` no está en
  `exceptions.json`. El hook de Impeccable pasó de 137 a 121 a 16 hallazgos sin que se tocara
  ninguna de esas líneas — es `memoria/trampas/el-contador-no-mide-el-archivo.md`.

## Nota de método sobre este propio censo

La primera pasada de este censo dio **41 repartidas en 7 reglas pero atribuyó a la regla del chip
seis declaraciones que son de `.indicator`**, y una segunda pasada con el parser arreglado dio 38
por truncar selectores largos. Las dos cifras se llegaron a comunicar antes de estar bien. El censo
bueno es el de la tabla de arriba: comentarios eliminados, pila de llaves para el contexto de
`@media`/`@layer`, y sin truncar. Es la misma familia que
`memoria/trampas/el-dom-dice-que-existe-no-que-se-ve.md`: **el instrumento tiene que responder a la
pregunta que se está haciendo.**
