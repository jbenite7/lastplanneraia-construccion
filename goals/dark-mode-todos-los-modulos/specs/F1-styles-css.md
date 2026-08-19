---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: goals/dark-mode-todos-los-modulos/specs/F1-styles-css.md
resumen: Que public/css/styles.css deje de existir, repartiendo su contenido entre las capas correctas y los componentes canónicos, sin dejar un archivo residual…
---

# F1 · Desmantelar `styles.css`

**Depende de:** F0. Bloquea a F2 y F3. Puede correr en paralelo con F4.
**Riesgo:** el más alto del goal — cambia quién gana en la cascada de toda la app.

## Objetivo

Que `public/css/styles.css` deje de existir, repartiendo su contenido entre las capas
correctas y los componentes canónicos, sin dejar un archivo residual «congelado».

## Problema

`styles.css` son **6 802 líneas** importadas en `layer(module)` por los dos entrypoints
(`entrypoints/core.css:19` y `aia-design-system.css:32`). En la cascada declarada por DS-006
—`reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides`—
`module` está **por encima de `components`**. Es decir: 6 802 líneas de CSS legacy ganan por
diseño a todas las primitivas `aia-*`.

Contiene 483 hex y 171 `rgba()` con cero `--ds-active-*`, y es el origen principal tanto de la
fuga a claro como de los 2 245 `!important` no autorizados que el resto de módulos han tenido
que escribir para ganarle.

## Estado medido al abrir F1 (2026-07-26, tras cerrar F0)

F0 no tocó este archivo, y las cifras de apertura del goal siguen exactas: **6.802 líneas**,
**483 hex**, **108 `rgba(`**, **cero `--ds-active-*`**. Las `@media` por debajo de 1180 px son
**30** (17 en `px`, 13 en `rem`).

### El objetivo de mayor valor, localizado

F0 cerró con una corrección incómoda: su criterio «ningún documento cae en claro» resultó falso
al pie de la letra. Seis superficies siguen con `body` en `rgb(245,245,247)` —`/pdc`,
`/indicadores`, `/profesionales`, `/subcontratistas`, `/control-cambios` y todo `/admin`—. F1 es
la fase que lo arregla, y el mecanismo está localizado con precisión:

```css
/* public/css/styles.css */
:26   --surface-bg: #f5f5f7;   /* Off-white background */
:36   --text-main:  #1d1d1f;   /* Almost Black */

:103  @layer base {
:104  body {
:107    background-color: var(--surface-bg);
:108    color: var(--text-main);
```

Ese `@layer base` está **anidado dentro de `layer(module)`** por los dos entrypoints, así que
resuelve como `module.base` y gana a la capa `components` del design system.

Reapuntar esos dos tokens a `--ds-active-bg-canvas` / `--ds-active-text-primary` es el cambio de
menor tamaño y mayor efecto de todo el goal. **No es un remapeo ciego:** `--surface-bg` tiene 5
consumidores, y al menos uno (`styles.css:127`, hover de `.dropdown-item`, comentado literalmente
`Force light background` junto a `Force blue text`) asume fondo claro para su color de texto.
Cada consumidor se revisa por separado; el que no tenga equivalente se registra como deuda.

## Lecciones de F0 que este spec incorpora

F0 dejó pasar cuatro defectos que superaron todos los gates. Los cuatro tienen la misma forma y
F1 borra mucho más que F0, así que aplican con más fuerza:

1. **Sujeto contra ancestro.** Al quitar un segmento de un selector, comprobar si lo que se
   elimina era el **sujeto** de la regla o sólo un ancestro. Perder el sujeto cambia en silencio
   qué elemento se pinta, y ningún gate del repositorio lo detecta.
2. **Un test que aserta ausencia no es un guardián.** «No queda `.dark-mode`» pasaba en verde
   mientras la página estaba blanca. Todo tramo de F1 que cambie color debe verificarse contra el
   **valor computado**, no contra el texto del CSS.
3. **Grepear por extensión esconde consumidores.** `navbar.css` parecía muerto porque el `grep`
   filtraba `*.php,*.css,*.json,*.mjs` y un `.js` lo inyectaba en runtime. Antes de borrar
   cualquier cosa de `styles.css`, buscar sus selectores y variables también en `public/js`.
4. **Un `sources`/referencia que apunta a un archivo borrado no lo ve nadie** salvo que el gate
   compruebe existencia. F1 borra un archivo entero al final: revisar qué lo nombra.

### Guardián disponible desde el primer tramo

F0 dejó `tests/browser/design-system-body-canvas-dark.mjs`, que aserta el `background-color`
computado del `body` y los tokens de estado en 5 rutas, y está cableado en
`npm run test:design-system:runtime`. **F1 lo extiende a las seis superficies claras como primer
paso**, de modo que el tramo T1.2 tenga su rojo antes de empezar y su verde al terminar. Es la
red que faltó en F0.

## Hallazgo que reduce el trabajo

El archivo ya trae marcas de sección por capa pretendida: `/* Fin Layer Theme */` (línea 57),
`/* Fin Layer Reset */` (76), `/* Fin Layer Utilities */` (101), `/* Fin Layer Base */` (145).
Los primeros ~145 renglones están pre-clasificados.

Y de sus **53 `@media`**, alrededor de treinta son `max-width` por debajo de 1180 px: vista de
tarjetas móvil, drawer táctil, reflow de tablas a bloque. AGENTS.md excluye mobile y tablet del
alcance soportado. Ese bloque no se migra: **se borra**. Es la porción más grande y más barata.

## Estrategia

Desmantelado incremental por secciones (decisión 5). El archivo encoge commit a commit hasta
desaparecer. Cada tramo es un commit verificable y reversible; no hay big-bang.

### Orden de tramos

| # | Tramo | Destino |
|---|---|---|
| T1.1 | `@media` bajo 1180 px | **Borrar.** Fuera del alcance soportado |
| T1.2 | Variables de paleta legacy (líneas 1–57) | Mapear a `--ds-*` y borrar; las que no tengan equivalente se registran como deuda en `exceptions.json` |
| T1.3 | Reset y base (58–145) | Absorber en `design-system/foundation.css` |
| T1.4 | Utilidades (79–101) | Absorber en la capa `utilities` del DS |
| T1.5 | Shell, navbar y drawer (193–560) | Absorber en `components/navigation.css` y `adapters/shell-sidebar.css`, que ya cubren el shell canónico |
| T1.6 | Chips, badges y colores de fila (978–1360) | Absorber en `components/states-feedback.css`, respetando `state-semantics.json` |
| T1.7 | Toolbars y filtros (1367–1470) | Absorber en `components/filter-form.css` y `components/action-group.css` |
| T1.8 | Tablas y datos (1471 en adelante) | Absorber en `components/data-display.css` y los adaptadores de Handsontable/DataTables |
| T1.9 | Resto por módulo | Mover a la hoja del módulo dueño, en su capa correcta |
| T1.10 | Borrar el archivo | Retirar los dos `@import` y el archivo |

Cada tramo: mover, tokenizar contra `--ds-active-*`, retirar los `!important` que dejan de ser
necesarios, verificar, commit.

## Tolerancia a regresión

Decisión 6: se tolera regresión visual temporal en toda la app, con bitácora en
`validation-log.md` y cierre obligatorio antes de terminar F1.

**Excepción acordada:** `/programa-general` mantiene evidencia visual obligatoria en cada
tramo. Es el piloto del DS, hoy con cero hallazgos de audit, y `DESIGN.md` lo declara
protegido frente a cambios originados en otras superficies. Si un tramo lo rompe, se corrige
en el mismo commit, no en la bitácora.

La bitácora registra, por tramo: qué superficie quedó degradada, en qué, y en qué commit se
cierra. **F1 no cierra con entradas abiertas.**

## Fuera de alcance

- Rediseñar nada. F1 es reubicación y tokenización, no diseño nuevo.
- `admin/`, que no carga `styles.css` (es F4).
- Los vendors de F6, salvo donde `styles.css` los pise.

## Verificación

Por tramo:

```bash
node scripts/design-system-audit.mjs
npm run test:design-system:static
```

Al cierre de cada tramo, además:

```bash
npm run test:design-system:runtime
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Verificación visual en `1180×820` dark: `/programa-general` en **todos** los tramos; el resto
de superficies con manifiesto al cierre de cada tramo mayor (T1.5, T1.6, T1.8, T1.10).

## Métricas de progreso

F1 va bien si estos números bajan monótonamente:

| Métrica | Al abrir | Al cerrar |
|---|---|---|
| Líneas de `styles.css` | 6 802 | 0 |
| `hardcoded-hex` en `styles.css` | 483 | 0 |
| `hardcoded-color-function` en `styles.css` | 171 | 0 |
| `unauthorized-important` (total del repo) | 2 245 | esperado por debajo de 1 200 |
| `css-outside-layer` (total) | 846 | esperado por debajo de 300 |

Los dos últimos son estimaciones, no compromisos: se ajustan al medir T1.5.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Un tramo rompe superficies sin gate y nadie lo nota | La bitácora es obligatoria; el cierre de F1 exige recorrer las 31 superficies |
| Borrar el bloque móvil rompe una vista que alguien usa en tablet | AGENTS.md lo declara fuera de alcance; si aparece uso real, se detiene y se escala al usuario |
| Retirar `!important` cambia precedencias en cadena | Se retiran sólo los que el propio tramo vuelve innecesarios, verificando en el mismo commit |
| El piloto se rompe pese a la excepción | Evidencia visual por tramo lo detecta dentro del commit que lo causa |

## Criterio de cierre

1. `public/css/styles.css` no existe y ningún `@import` lo referencia.
2. Audit en verde con el total global por debajo del baseline de apertura.
3. Bitácora sin entradas abiertas.
4. Las 6 superficies con manifiesto siguen en presupuesto cero.
5. Evidencia visual del piloto en cada tramo, archivada en `evidence/F1/`.
