# F1 · Seis modales cuya piel vive fuera del tramo 5c

**Depende de:** tramo 5c (commit `c4fe9a4`, shell `.aia-modal` a oscuro).
**Riesgo:** medio. Toca cinco archivos de piel independientes; solo uno (el bloque de botones de
`styles.css`) tiene alcance global.
**Fuente de los defectos:** `.superpowers/sdd/task-5c-report.md` §5.7 — nueve regresiones medidas,
de las que este spec cubre las siete primeras (las dos últimas son preexistentes y quedan fuera).

## Objetivo

Que los seis modales que el tramo 5c dejó combinando superficie oscura con tinta oscura (o al
revés) queden homologados al tema operativo dark, arreglando **cada uno en el archivo que
realmente gana la cascada**.

## Problema

El tramo 5c llevó el shell `.aia-modal` (`public/css/styles.css:1051-1313`) del eje de tokens
**fijo** (`--ds-color-*`, anclado a claro) al eje **activo** (`--ds-active-*`, resuelto contra el
tema). El shell pinta el contenedor; la piel concreta de seis modales vive en otros cinco sitios
que siguen en el eje fijo o en literales. El resultado es tinta oscura sobre superficie oscura.

No es un problema de valores de color: es **un eje de token sin migrar**. De ahí que la corrección
sea mecánica y que el criterio de «terminado» sea *no queda ninguna referencia fija dentro del
modal*, no *los ratios medidos superan 4,5:1*.

### Corrección al informe de origen

`task-5c-report.md` §5.7 fila 5 atribuye `#modal_change_monitor` a `public/css/change-monitor.css`.
**Es falso.** Ese archivo existe (127 líneas, cargado desde
`views/programacion-semanal/programacion_semanal.view.php:42`) y **no contiene ni una regla
`.cm-modal`**. El dueño real es `public/css/programacion-semanal.css:2890-3130`. La diferencia es
material: `programacion-semanal.css` tiene el presupuesto de audit más estricto del repositorio.

## Estado medido al abrir el tramo

| Archivo | `--ds-active-*` | `--ds-color-*` fijos | Presupuesto de audit |
|---|---:|---:|---|
| `public/css/pdc.css` | **0** | 60 | hex 0 · inline 0 · radius 0 · style-block 0 |
| `public/css/programacion-semanal.css` | 261 | 208 (31 en `.cm-*`) | **todo a cero**, incl. `hardcoded-color-function` |
| `public/css/styles.css` | 58 | 12 | sin presupuesto por ruta |
| `views/control-cambios/…` | — | — | sin presupuesto ni manifiesto |
| `views/programa-general-actualizar/…` | — | — | sin presupuesto ni manifiesto |

`/pdc` es el caso extremo: **cero** referencias al eje activo en todo el archivo. La página entera
sigue en claro y su migración es un tramo posterior del goal; este spec solo lleva a oscuro
`#modalContrato`, cuyo shell ya es oscuro y por tanto ya está roto hoy.

Ni `control-cambios` ni `programa-general-actualizar` tienen archivo CSS propio. El primero
arrastra un `<style>` **vacío** (líneas 141-142); el segundo, uno de **405 líneas** (24-429).

## Diseño

### Regla única: conmutación de eje

En los cinco archivos se aplica la misma tabla. No se inventa ningún valor.

| Fijo (claro) | → Activo |
|---|---|
| `--ds-color-bg-parchment`, `--ds-color-surface` | `--ds-active-surface` |
| `--ds-color-surface-tint`, `--ds-color-bg-page` (pies, `thead`) | `--ds-active-surface-raised` |
| `--ds-color-surface-subtle`, `--ds-color-surface-hover` | `--ds-active-surface-glass` |
| `--ds-color-text-primary` | `--ds-active-text-primary` |
| `--ds-color-text-secondary`, `--ds-color-text-tertiary` | `--ds-active-text-secondary` |
| `--ds-color-border-default` / `-subtle` / `-strong` | `--ds-active-border` (ver Riesgo 1) |
| `--ds-color-border-focus` | `--ds-active-focus-ring` |

**No se conmutan** `--ds-color-text-inverse` ni `--ds-color-brand-primary*` cuando pintan sobre el
degradado verde de cabecera: ese verde es idéntico en los dos temas y el tramo 5c lo dejó intacto
en los 48 modales del shell. Conmutarlos desalinearía estas cabeceras del resto.

**No se tocan los estados semánticos** (`state-*`) salvo donde se indica: DESIGN.md los declara
tintes fijos, constantes entre temas.

### Los cinco dueños de cascada

**1 · `/control-cambios` `#modalordenDeCambio`** — 16 etiquetas a 1,00:1.
`views/control-cambios/controlCambios.view.php` + **nuevo** `public/css/control-cambios.css`.

Las utilidades `bg-light` / `bg-white` de Bootstrap llevan `!important` y viven en `@layer vendor`;
como el orden de capas se **invierte** para `!important`, ninguna `@layer` posterior puede
tocarlas. No hay arreglo desde CSS: se retiran del markup.

El inventario real dentro de `#modalordenDeCambio` (líneas 143-572) es mayor que las 16 etiquetas
que midió el informe: parte del markup vive en ramas que no se pintan en el estado por defecto, y
`bg-*` no es la única utilidad de vendor con color.

| Utilidad | Ocurrencias | Qué pinta |
|---|---:|---|
| `border-right` | 23 | línea `1px solid #dee2e6 !important` |
| `bg-light` | 19 | `#f8f9fa !important` — celda de etiqueta |
| `border-bottom` | 11 | línea clara |
| `bg-white` | 8 | `#fff !important` — contenedor de valor |
| `border` | 8 | caja clara del `row` |
| `border-top` | 8 | línea clara |
| `text-muted` | 8 | `#6c757d !important` — tinta de los contadores |
| **Total con color** | **85** | |
| `border-0` | 17 | **no lleva color; se queda** |

- `bg-light` → `.cc-field-label`, fondo `--ds-active-surface-raised`.
- `bg-white` → `.cc-field-value`, fondo `--ds-active-surface`.
- La polaridad (etiqueta más clara que el valor) invierte la del markup actual **a propósito**:
  reproduce la decisión ya tomada en `.aia-modal .table thead` por el tramo 5c, y hace que la
  rejilla se lea como la tabla que en realidad es.
- Las cuatro utilidades de borde y `text-muted` tienen exactamente el mismo problema de cascada y
  también se retiran: bordes derivados de `--ds-active-border`, contadores a
  `--ds-active-text-secondary`.

El CSS va a un archivo nuevo, no al `<style>` vacío: es el único destino que deja el módulo en
condiciones de recibir manifiesto y presupuesto cero sin volver a moverlo todo.

**2 · `/pdc` `#modalContrato`** — cuerpo 1,37:1, `.form-control` 2,02:1.
`public/css/pdc.css` (sin capa, entra por `<link>` desde `views/pdc/pdc.view.php`; gana a toda
`@layer`).

Se migra la piel completa del modal, no solo lo medido: `.modal-content` (`:280`), la tinta de
`#modalContrato` (`:271`), campos (`:446`, `:559`), bordes, tablas y superficies internas.
Cabecera y `text-inverse` intactos.

Los campos de solo lectura pierden el gradiente `surface-tint → state-success-bg`: sobre oscuro ese
verde pálido se convierte en el elemento más claro del modal, justo el que menos debe llamar la
atención. Pasan a `--ds-active-surface-glass` + borde **dashed** `--ds-active-border` + tinta
secundaria. El dashed ya transportaba el significado por un canal no cromático y sigue siendo el
principal.

Presupuesto: `hardcoded-hex 0`, `inline-style 0`, `hardcoded-radius 0`. `hardcoded-color-function`
**sí** está permitido aquí (el archivo ya tiene `rgba()`), pero no se añaden nuevos.

**3 · `/programa-general-actualizar` `#modalAutoAsociar`** — cuerpo 1,07:1, pie 1,01:1.
`views/programa-general-actualizar/programaGeneralActualizar.view.php:717-786` + **nuevo**
`public/css/programa-general-actualizar.css`.

Se retiran los `style=` en línea (`background: #F4F1EA` en `:729`, `background: #FAFAFA` en `:779`)
y los cuatro de la cabecera (`:720`, `:722`, `:723`, `:725`), que replican a mano lo que el shell ya
pinta tokenizado y medido en 8,25:1. Al borrarlos gana el shell. **Consecuencia aceptada:** la
cabecera pasa de verde plano a degradado, como los otros 48 modales.

**4 · `/programa-general-actualizar` `#modalImportacionExitosa`** — `h3` 1,28:1, `p` 1,77:1.
Misma vista (`:630-648`), mismo archivo CSS nuevo.

Se retiran los `style=` de `h3` (`:637`) y `p` (`:638`); las tintas pasan a `--ds-active-text-primary`
y `--ds-active-text-secondary`. El círculo de éxito (`#d5e5db` + icono `#1a5633`, `:634-635`) **no
está roto** (8,7:1) y conserva su aspecto, pero se tokeniza a `--ds-color-state-success-bg` /
`--ds-color-state-success-text` — los estados fijos: mismo color exacto, dos `hardcoded-hex` menos.

El `<style>` de 405 líneas de esta vista **no se toca**: es un refactor que nadie pidió. Queda
anotado como deuda para su propio tramo.

**5 · `/programacion-semanal` `#modal_change_monitor`** — cuerpo 1,07:1, pie 1,03:1.
`public/css/programacion-semanal.css:2890-3130`.

- `.cm-modal-body` (`:2980`) `bg-parchment` → `--ds-active-surface`.
- `.cm-modal-footer` (`:3091`) `bg-page` → `--ds-active-surface-raised`.
- El resto del bloque `.cm-*` según la tabla de conmutación.
- Los tres tintes de fila —`color-mix(… var(--ds-color-state-*-text) 10%, transparent)`— pasan a
  mezclar con la **mitad clara** del par (`state-*-bg`). Un 10% de tinta oscura sobre superficie
  oscura es invisible, y esos tintes son el único canal que distingue una fila conforme de una
  crítica. El porcentaje se fija **por medición**, no a ojo.
**Corrección durante el planteamiento.** El grilleo incluyó una pregunta sobre conmutar
`--ds-color-brand-architecture` a su variante `on-dark` dentro de este bloque. Verificado después:
dentro de `2890-3130` hay **cero** ocurrencias. Las que había detectado el barrido viven en
`.ps-dropdown-item` y `.btn-dropdown-trigger` (líneas 2853-2887), que son el desplegable de
programación semanal, **no** el change monitor. Quedan fuera de este tramo y la decisión no se
aplica a nada aquí.

Presupuesto **cero absoluto**: solo tokens y `color-mix()` sobre `var()`. Que `color-mix` es
admisible lo prueba `.cm-total-badge`, que ya lo usa en este archivo bajo el mismo presupuesto.

**6 · Botones outline de los pies** — 4 botones entre 1,14:1 y 1,63:1.
`public/css/styles.css:1630-1641` y su bloque `:hover`/`:focus` (declaraciones en `:1659-1660`).

El bloque cubre seis clases —`.aia-btn-secondary`, `.aia-btn-danger`, `.btn-secondary`,
`.btn-outline-secondary`, `.btn-danger`, `.btn-default`— en los pies de los **58** modales
`.aia-modal`, no solo los cuatro medidos. Se cambia el bloque entero a la receta canónica que ya
existe en `public/css/design-system/core.css:113` para `.aia-btn--secondary`:

- borde `--ds-active-border` (hoy `rgba(26, 86, 51, 0.35)`),
- tinta `--ds-active-text-primary` (hoy `var(--aia-modal-green-primary)`, verde corporativo oscuro),
- fondo transparente, para conservar el aspecto outline,
- hover a `--ds-active-surface-glass` (hoy `rgba(26, 86, 51, 0.08)`).

`.btn-danger` **no** recibe tratamiento propio en este tramo pese a que hoy pinta un botón
destructivo de verde corporativo. Es un defecto semántico preexistente y arreglarlo es rediseño,
no homologación a dark.

El `Cancelar` de los diálogos de borrado en `/listado-actividades:324`, `/pdc:550` y
`/control-cambios:586` es el caso más urgente: hoy queda casi invisible.

## Riesgos

**1 · La escala activa tiene menos escalones que la fija.** `--ds-active-*` no ofrece
`text-tertiary` ni `border-subtle`/`-strong`: tres niveles de borde y tres de tinta colapsan a uno
y dos. En `.cm-*` eso aplanaría la jerarquía de la tabla del change monitor. Los escalones que
falten se derivan con `color-mix(in srgb, var(--ds-active-border) N%, transparent)`, el patrón que
el tramo 5c ya validó y que no cuenta como `hardcoded-color-function`.

**2 · Dos archivos CSS nuevos entran en la cascada.** `control-cambios.css` y
`programa-general-actualizar.css` no deben repetir el error de `pdc.css` (entrar sin capa y ganarle
a todo). Se declaran en `@layer module`, junto a `styles.css`. Como las utilidades de Bootstrap que
competían se **retiran** del markup, no hace falta ganar por `!important`.

**3 · La cabecera de `#modalAutoAsociar` cambia de aspecto** (plano → degradado). Es consecuencia
buscada de borrar el inline, pero es un cambio visible que debe aparecer en la evidencia.

**4 · `programacion-semanal.css` no admite un solo literal.** Cualquier `rgba()` o hex que se cuele
rompe el presupuesto del módulo.

## Criterio de terminado

1. Dentro de los seis modales no queda ninguna referencia `--ds-color-*` de superficie, tinta o
   borde, ni ningún `style=` de color, ni ninguna utilidad `bg-*`/`border-*` de Bootstrap.
2. Los siete defectos de §5.7 (filas 1-7) miden **≥ 4,5:1** con los modales abiertos, viewport
   1180×820, dark, contra el contenedor servido.
3. `CSS.getMatchedStylesForNode` (CDP) confirma, para cada rol corregido, que la regla que gana es
   la que se escribió — no una heredada ni una que quedó inerte.
4. El audit no rompe ningún presupuesto por ruta nuevo, y el total no sube.
5. Un test de regresión permanente cubre los seis modales.

## Verificación

- **Sonda Playwright** acotada a los seis modales, abriéndolos uno a uno (un modal cerrado mide
  0×0; se comprueba alto > 0 y `display` distinto de `none` antes de medir), con tabla de ratio
  antes/después de cada rol listado en §5.7. Mismo método que el tramo 5c: composición de alpha
  sobre ancestros, resolución de gradientes al peor paso, conversión exacta por canvas.
- **Confirmación por CDP** de qué regla gana en cada rol corregido.
- **Test de regresión nuevo** en `tests/browser/`, con su entrada `!` añadida a `.gitignore` **en el
  mismo commit** — `tests/browser/` está ignorado con allowlist y un archivo nuevo ahí no se
  commitea sin ella.
- `node scripts/design-system-audit.mjs` antes y después, comparando totales y presupuestos.
- `npx biome check` sobre los archivos CSS tocados.

**No** se regeneran baselines ni goldens. `docs/design-system/audit-baseline.json` está protegido
por hash y no se toca.

## Entrega

Un commit por dueño de cascada, más uno de documentación al cierre. El orden es **urgencia
primero**: los botones del pie van los primeros por ser el defecto más grave (el `Cancelar` de tres
diálogos de borrado, hoy casi invisible) y a la vez el más pequeño, y porque el resto de tareas ya
lo encuentran resuelto.

1. `styles.css` — botones de los pies (58 modales) + sonda y test de regresión
2. `control-cambios` (vista + CSS nuevo)
3. `pdc.css`
4. `programa-general-actualizar` (vista + CSS nuevo)
5. `programacion-semanal.css`
6. Reporte y `validation-log.md`

El worktree tiene cambios ajenos de otras sesiones (`src/View/Components/DesignSystemComponent.php`,
`tests/browser/design-system-lab-sidebar.mjs`, `tests/browser/design-system-lab.performance.mjs`).
Staging selectivo siempre; nunca `git add -A`.

## Fuera de alcance

- Las filas 8 y 9 de §5.7 (`span.text-danger` a 3,11:1; borde de `.aia-tipo-pill--oc.is-checked`):
  preexistentes, y la segunda exige mover un hex de marca que el goal protege.
- El defecto de especificidad del hover de las pills (§5.6).
- La migración de `/pdc` fuera de `#modalContrato`, y el `<style>` de 405 líneas de
  `programa-general-actualizar`: ambos, tramo propio.
- Cualquier trabajo en mobile, tablet o tema `linen`: fuera del alcance visual vigente.
