# F1 · Desmantelar `styles.css` — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

> ⚠️ **Criterio derogado el 2026-07-28 por decisión del usuario.** El objetivo original —que el
> archivo dejara de existir— **ya no aplica**. F1 cerró con `styles.css` **vivo**, en 4.382 líneas y
> reducido a sus 16 excepciones de color adjudicadas, y con sus dos `@import` en su sitio. Lo que
> queda dentro ya no es color: es layout y geometría, cuya migración es otra fase con otro perfil de
> riesgo. Ver «Task final: borrar el archivo — DEROGADA» al final de este plan y el cierre de F1 en
> `../validation-log.md`. El texto original se conserva abajo como registro de lo que se planeó, no
> como compromiso vigente.

**Goal (original, derogado):** Que `public/css/styles.css` deje de existir, repartiendo su contenido entre las capas correctas y los componentes canónicos, sin dejar un archivo residual congelado.

**Architecture:** El archivo declara su propia cascada interna (`@layer reset, theme, base, layout, components, utilities;`) y los dos entrypoints lo importan dentro de `layer(module)`. El resultado es `module.theme`, `module.base`, `module.components`… — y como `module` está por encima de `components` en la cascada global (DS-006), 6.802 líneas de CSS legacy ganan por diseño a todas las primitivas `aia-*`. Se desmantela por tramos, cada uno un commit verificable y reversible. (El «hasta desaparecer» original quedó derogado: ver el aviso de la cabecera.)

**Tech Stack:** CSS con `@layer` nativo, Node 20 (`node --test`), Playwright, Docker Compose (PHP 8.3).

**Spec:** `goals/dark-mode-todos-los-modulos/specs/F1-styles-css.md`
**Facts vinculantes:** `goals/dark-mode-todos-los-modulos/facts.md`

## Global Constraints

- **Alcance visual:** desktop de al menos 1180 px, **dark únicamente**. Viewport canónico `1180x820`, secundario `1440x900`. Prohibido generar cambios, pruebas o evidencia para mobile, tablet o el tema `linen` (retirado en F0).
- **Runtime:** todo PHP, Composer y PHPStan se ejecutan dentro del contenedor `app`. Nunca un PHP del host.
- **Publicación:** commits locales únicamente. Ningún `push`, `deploy` ni `gh pr`.
- **Cascada de capas fija:** `reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides` (DS-006).
- **Baselines y goldens:** prohibido regenerarlos para forzar verde. `docs/design-system/audit-baseline.json` está protegido por hash y exige archivo de aprobación. Borrar CSS debe **bajar** contadores; si el audit falla, repórtalo.
- **Equivalencia de entrypoints:** `public/css/aia-design-system.css` y `public/css/design-system/entrypoints/core.css` importan ambos `styles.css`. Todo cambio en cómo se importa se aplica a los dos.
- **Conservar el worktree:** hay sesiones concurrentes. Nunca `git add -A`; stagea rutas explícitas y revisa `git status --short` antes de cada commit.

## Estructura real del archivo (medida 2026-07-26)

| Líneas | Bloque | Peso |
|---|---|---|
| 1 | Declaración de capas internas | — |
| 3–57 | `@layer theme` — paleta legacy | 55 |
| 59–76 | `@layer reset` | 18 |
| 78–101 | `@layer utilities` | 24 |
| 103–145 | `@layer base` | 43 |
| 147–6670 | `@layer components` | **6.523** |
| 6671–6802 | Fuera de capa | 132 |

**6.802 líneas · 483 hex · 108 `rgba(` · 0 `--ds-active-*` · 30 `@media` por debajo de 1180 px.**

## Rojos conocidos al abrir F1

No los persigas y no los "arregles":

1. `tests/design-system/contracts.test.mjs` → `activation: worktree and index must be clean`, por archivos sucios de sesiones concurrentes.
2. `tests/browser/design-system-compliance.mjs` → `fillsDesktopShell`, **rojo deliberado** (commit `72c5f74`): detectó que `/pdc` y `/listado-actividades` no llenan su carcasa. Tiene tarea propia. **No relajes el umbral.**

Captura la lista de fallos antes de cada tarea y júzgala por si añade algo a esos dos.

---

### Task 1: Extender el guardián de canvas a las seis superficies claras

Antes de tocar una línea de CSS, F1 necesita su red. F0 dejó `design-system-body-canvas-dark.mjs` asertando el `background-color` computado del `body` en 5 rutas; seis superficies más siguen en claro y son justo las que F1 arregla.

Esta tarea **debe terminar en rojo**: el guardián dirá la verdad, y esa verdad es que seis páginas están blancas.

**Files:**
- Modify: `tests/browser/design-system-body-canvas-dark.mjs`

**Interfaces:**
- Consumes: el patrón de fixtures y login que el propio archivo ya usa (`PROJECTS`, `loginAndSelectProject` de `support/session.mjs`).
- Produces: un rojo reproducible por ruta que las tareas 3 y siguientes van apagando.

- [ ] **Step 1: Leer el archivo entero antes de editarlo**

```bash
cat tests/browser/design-system-body-canvas-dark.mjs
```

Fíjate en cómo declara las rutas y qué aserta por cada una. Vas a añadir rutas al mismo mecanismo, no a crear uno nuevo.

- [ ] **Step 2: Añadir las seis rutas claras**

Añade a la lista de rutas cubiertas: `/pdc`, `/indicadores`, `/profesionales`, `/subcontratistas`, `/control-cambios`. **`/admin` no entra**: es una mini-app con su propio front controller y login, y su estado claro está aprobado como `observed-frozen` hasta F4 — añadirla aquí crearía un rojo que F1 no puede cerrar.

Cada ruta aserta lo mismo que las cinco existentes: el `background-color` computado del `body` es el canvas oscuro, no un valor claro.

- [ ] **Step 3: Ejecutar y confirmar el rojo esperado**

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
```

Expected: **FALLA** en las cinco rutas nuevas con el `body` en `rgb(245,245,247)`, y sigue verde en las cinco de F0. Anota el listado exacto: es la línea base de progreso de toda la fase.

- [ ] **Step 4: Commit**

```bash
git add tests/browser/design-system-body-canvas-dark.mjs
git commit -m "test(design-system): extender el guard de canvas oscuro a las superficies claras"
```

El commit deja la suite en rojo **a propósito**, igual que `fillsDesktopShell`. Documenta el motivo en el cuerpo del mensaje.

---

### Task 2: Borrar el bloque responsive fuera de alcance

30 `@media` por debajo de 1180 px: vista de tarjetas móvil, drawer táctil, reflow de tablas a bloque. `AGENTS.md` excluye mobile y tablet del alcance soportado. No se migran: se borran. Es la porción más grande y más barata.

**Files:**
- Modify: `public/css/styles.css`

**Interfaces:**
- Consumes: nada.
- Produces: un archivo notablemente más corto, lo que abarata todos los tramos siguientes.

- [ ] **Step 1: Inventariar las media queries a borrar**

```bash
grep -nE '@media[^{]*max-width:\s*(3[0-9]{2}|[4-9][0-9]{2}|1[01][0-9]{2})(\.[0-9]+)?px' public/css/styles.css
grep -nE '@media[^{]*max-width:\s*([0-6][0-9]|7[0-3])(\.[0-9]+)?rem' public/css/styles.css
```

Expected: 17 y 13 coincidencias. Anótalas todas antes de tocar nada.

**Cuidado con dos casos que el `grep` no distingue:** una `@media (min-width: X)` cuyo contenido sólo tiene sentido si existe la rama móvil, y una `@media` con `max-height` (hay varias entre las líneas 790 y 840) que **no** es responsive de ancho y **no** se borra.

- [ ] **Step 2: Borrar cada bloque completo, no sólo su cabecera**

Por cada coincidencia, borra desde `@media` hasta su `}` de cierre equilibrado. Un borrado a medias deja reglas huérfanas que el navegador aplicará incondicionalmente.

Tras cada borrado:

```bash
npx biome check public/css/styles.css
```

Expected: sin errores de sintaxis. Si biome se queja de llaves, has cortado mal.

- [ ] **Step 3: Verificar que no se ha perdido nada de desktop**

```bash
node scripts/design-system-audit.mjs
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Expected: audit con contadores **más bajos** que antes; el guardián con exactamente los mismos rojos que dejó la Task 1 (ni uno más); el harness 135/135.

En navegador, `1180x820` dark: `/programa-general`, `/programacion-semanal` y `/contratos` sin cambio visual.

- [ ] **Step 4: Commit**

```bash
git add public/css/styles.css
git commit -m "chore(design-system): borrar de styles.css el responsive por debajo de 1180px"
```

---

### Task 3: Reapuntar la paleta legacy a los tokens activos

El tramo de mayor efecto por línea de todo el goal. Seis superficies están claras porque `styles.css:104-110` pinta `body` con dos variables legacy.

**Files:**
- Modify: `public/css/styles.css` (bloque `@layer theme`, líneas 3–57, y sus consumidores)

**Interfaces:**
- Consumes: `--ds-active-bg-canvas`, `--ds-active-text-primary` y el resto de tokens activos que F0 dejó sirviendo dark desde `:root`.
- Produces: cinco de las seis superficies claras pasan a oscuro. La sexta (`/admin`) es F4.

- [ ] **Step 1: Ejecutar el guardián y anotar el rojo de partida**

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
```

Expected: las cinco rutas de la Task 1 en rojo con `rgb(245,245,247)`.

- [ ] **Step 2: Inventariar cada consumidor antes de remapear**

```bash
grep -n "var(--surface-bg)\|var(--text-main)" public/css/styles.css
grep -rn "surface-bg\|text-main" public/js views src
```

Los de `styles.css` al medir eran: `--surface-bg` en las líneas 107, 127, 604, 1779, 1804; `--text-main` en 6 sitios. **El segundo `grep` no es opcional**: en F0 se borró una hoja creyéndola muerta porque el `grep` filtraba por extensión y un `.js` la inyectaba en runtime.

- [ ] **Step 3: Remapear las variables de la paleta**

En el bloque `@layer theme` (líneas 3–57), sustituye las definiciones literales por referencias a los tokens activos. Las dos que gobiernan el problema:

```css
  --surface-bg: var(--ds-active-bg-canvas);
  --text-main: var(--ds-active-text-primary);
```

Para el resto del bloque, mapea cada variable a su token `--ds-*` equivalente. **Las que no tengan equivalente no se inventan**: se dejan con su valor literal y se registran en `docs/design-system/exceptions.json` con motivo y `expiresAtVersion`.

- [ ] **Step 4: Revisar uno por uno los consumidores que asumen fondo claro**

`styles.css:127` es el caso explícito: hover de `.dropdown-item`, comentado `Force light background` junto a `Force blue text`. Con `--surface-bg` en oscuro, ese par pasa a fondo oscuro con texto azul, que puede quedar ilegible.

Por cada uno de los 5 consumidores, decide y documenta: reapuntar al token activo, o dejar el literal con excepción registrada. **Mide el contraste resultante, no lo estimes.**

- [ ] **Step 5: Verificar el efecto, no el texto del CSS**

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
node scripts/design-system-audit.mjs
npm run test:design-system:static
```

Expected: el guardián **verde en las cinco rutas nuevas**; audit con menos hex; estática sin fallos nuevos.

En navegador, `1180x820` dark, sobre el contenedor servido: `/pdc`, `/indicadores`, `/profesionales`, `/subcontratistas` y `/control-cambios` en oscuro coherente. Comprueba además el hover de un `.dropdown-item` en cada una y mide su contraste — es el consumidor con riesgo conocido.

- [ ] **Step 6: Archivar evidencia**

Captura de las cinco rutas a `1180x820` dark en `goals/dark-mode-todos-los-modulos/evidence/F1/`, y captura de `/programa-general` antes y después: es el piloto protegido y esta tarea toca su cascada.

- [ ] **Step 7: Commit**

```bash
git add public/css/styles.css docs/design-system/exceptions.json
git commit -m "feat(design-system): la paleta legacy de styles.css consume los tokens activos"
```

---

### Task 4: Absorber `reset`, `utilities` y `base`

Tres bloques pequeños y pre-clasificados: 18, 24 y 43 líneas.

**Files:**
- Modify: `public/css/styles.css` (líneas 59–145)
- Modify: `public/css/design-system/foundation.css` (destino de `reset` y `base`)
- Modify: la capa `utilities` del design system (destino de `utilities`)

- [ ] **Step 1: Anotar el estado de partida**

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
node scripts/design-system-audit.mjs
```

- [ ] **Step 2: Mover cada bloque a su capa canónica**

Por cada regla: si el design system ya tiene una equivalente, **bórrala de `styles.css` sin duplicarla**. Si no la tiene, muévela al archivo canónico correspondiente, tokenizada.

Al mover una regla, comprueba si el selector que trasladas era el **sujeto** o un ancestro. Perder el sujeto cambia qué elemento se pinta y ningún gate lo detecta.

- [ ] **Step 3: Verificar**

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
node scripts/design-system-audit.mjs
npm run test:design-system:static
```

Expected: guardián verde, harness 135/135, audit bajando, estática sin fallos nuevos.

- [ ] **Step 4: Commit**

```bash
git add public/css/styles.css public/css/design-system/
git commit -m "refactor(design-system): absorber reset, utilities y base de styles.css"
```

---

### Task 5 en adelante: desmantelar `@layer components` por secciones

Aquí vive el 96 % del archivo: 6.523 líneas. **No se hace en una tarea.** Se divide por las secciones que el propio archivo ya delimita con comentarios, y **cada sección es un commit con su verificación**.

## Desglose de `@layer components` — decidido el 2026-07-26

**Criterio elegido: por módulo, consolidando dentro de cada tramo hacia la primitiva canónica.**

La alternativa era por componente (todos los botones, luego todos los modales…). Consolida mejor
en una sola pasada, pero cada tramo tocaría muchas rutas a la vez. Esta fase ya lleva cuatro
defectos reales, y **los dos peores vinieron de cambios cuyo alcance era más amplio que el
problema que resolvían**: literales claros que dejaron dos títulos a 1.00:1, y un override de
color sin ámbito en la última capa que rompió tres superficies más. Por módulo cada tramo se
verifica contra una ruta concreta, que es lo único que ha atrapado algo hasta ahora.

Coste asumido: la misma regla de botón se tocará en varios tramos. Mover una regla dos veces es
barato; enviar una página ilegible no lo es.

### Tramos, con líneas medidas tras la salida de `base` (`styles.css` = 5.773)

| # | Rango | Contenido | Destino canónico | Ruta de verificación |
|---|---|---|---|---|
| 5a | 146–734 (parcial) | Contención de ancho «ALL MODULES» + poda de muerto verificado | `foundation.css` / `layout`, `vendor-datatables-legacy.css` | las 10 del guard |
| 5b | 518–905 | Paleta de estados compartida (filas + chips) — **sólo tokenizar**, ver Task 5b | hoja de módulo / tokens | `/pdc`, `/programa-general`, `/programacion-semanal`, `/programacion-intermedia` |
| 5c | 1011–1407 | Modales (Bootstrap + `.aia-modal`) y pills de contratación — ver Task 5c | retokenizar en el sitio; **NO** fusionar con `.aia-dialog` | 16 vistas con modal, con los modales **abiertos** |
| 5d | 1475–1769 | Variante inline **muerta** + backdrop e inputs — ver Task 5d | poda; tokenizar el resto | `/listado-actividades`, `/contratos` |
| 5e | 2209–3037 | Semanal: modal agregar actividad, animación de guardar | hoja del módulo, capa correcta | `/programacion-semanal` |
| 5f | 3037–3859 | Semanal: toolbar, presets de botones, phase indicator | `components/action-group.css`, hoja del módulo | `/programacion-semanal` |
| 5g | 3859–5029 | **PG / PI compartido** (1.170 líneas — el mayor) | `components/data-display.css`, hojas de módulo | `/programa-general` **y** `/programacion-intermedia` |
| 5h | 5029–5444 | Contratos | hoja del módulo | `/contratos` |
| 5i | 5444–5651 | Leyendas, legend items, bordes de tabla | `components/data-display.css` | `/programa-general` |
| 5j | 5651–5773 | **UNLAYERED OVERRIDE BRIDGE** («hacks & escapes») | caso a caso; aquí vive la `}` huérfana | todas |

**5g se parte en dos** al abordarlo: 1.170 líneas compartidas entre dos módulos es demasiado para
un tramo verificable, y es justo el perfil de cambio que ha fallado antes.

**5j va el último y merece cuidado especial.** Su nombre anuncia lo que es, contiene la `}`
huérfana que mantiene a biome en 4 errores, y por estar fuera de capa sus reglas ganan a todo.

### Lo que 5j debe saber antes de empezar — medido el 2026-07-27

Se intentó adelantar el arreglo de la `}` huérfana como higiene aislada y **se revirtió por decisión
del usuario**. Lo aprendido, para que 5j no lo redescubra:

1. **La llave sobra, no falta una apertura.** Está en `styles.css:5610` (con el archivo en 5691
   líneas). Balance de todo el archivo, quitando comentarios antes de contar: 827 `{` contra 828
   `}` → delta −1, y el desbalance ya aparece en esa línea. `.ps-action-btn` cierra bien en 5608.
   Borrarla deja el archivo en delta 0.

2. **Borrarla sola pone el audit en ROJO.** Medido aislando los escenarios: el total sube de
   **6851 a 6898**, y falla con `css-outside-layer: 841 > baseline 829`. La `}` de más hacía que el
   parser del audit creyera que ~47 reglas estaban dentro de un bloque; sin ella ve la verdad, que
   es que están fuera de capa — exactamente lo que el propio comentario de la sección declara en
   mayúsculas. **No es deuda nueva: es deuda que el error de sintaxis ocultaba.**

   Por eso 5j debe arreglar la llave **y** reubicar las reglas en la misma tarea: sólo así el
   contador baja en vez de subir, y no hace falta tocar el baseline protegido.

3. **El `parse` no ocultaba lint.** Premisa que se dio por buena y resultó falsa al medirla: biome
   recupera del error y analiza el archivo entero (**539 warnings idénticos** antes y después, y la
   última línea diagnosticada es la 5690). Lo único que el parse abortaba era el **formateo**. No
   hay un alijo de deuda escondida esperando al final del archivo.

### Contrato de cada tramo, sin excepción

1. Verificación de contraste **medida**, no razonada, en la ruta del tramo.
2. Al mover un selector, comprobar si lo que se traslada era el **sujeto** o un ancestro.
3. Si el design system ya cubre la regla, **borrar, no duplicar**.
4. **No ampliar un selector** para facilitar el movimiento, y no usar `!important` para ganar
   precedencia: la capa ya la da.
5. `/programa-general` se verifica en **todos** los tramos, no sólo en los suyos.
6. **Antes de declarar viva una regla, comprobar el markup, no sólo el consumidor.** Tres premisas
   se han caído ya por saltarse esto: bastaba con encontrar un `.js` que nombrara un id para darlo
   por vivo, cuando el elemento que ese `.js` busca lo había borrado otro commit. Busca `class=`,
   `id=`, `classList`, `className` y concatenación de cadenas, y mide cuántos elementos existen de
   verdad en el DOM.
7. **Commits parciales por bloque** (decisión del usuario, 2026-07-28, a partir de 5i). Un tramo deja
   de ser un solo commit: se parte en commits por bloque —típicamente *poda de lo muerto*,
   *tokenización de lo vivo* y *guard nuevo*—, cada uno verificable y reversible por separado. Antes
   ya se hizo así donde surgió (5a-bis fueron tres commits, 5f-bis y 5g-bis llevaron el suyo de fix);
   ahora es la norma, no la excepción. La review sigue siendo **por tramo**, sobre el rango completo
   de sus commits: el paquete se genera con `scripts/review-package BASE HEAD`, nunca con `HEAD~1`,
   que truncaría todos menos el último.

8. **Separar en el reporte el descenso real del audit del descenso por relocalización**
   (decisión del usuario, 2026-07-27). `scripts/design-system-audit.mjs:192-247`
   (`isDesignSystemOwnedFile`) exime a todo `public/css/design-system/**` de siete reglas:
   `off-scale-spacing`, `off-scale-typography`, `off-scale-shadow`, `raw-token-in-module`,
   `global-module-selector`, `local-vendor-override` y `duplicate-canonical-primitive`. Mover una
   declaración legacy al design system **baja el contador sin mejorar nada**. En 5a-bis, 15 de los
   23 puntos de descenso eran de ese tipo. `unauthorized-important` y `hardcoded-hex` **no** están
   exentas y siguen siendo indicadores honestos. Cada tramo reporta ambas cifras por separado; el
   gate monotónico se mantiene como está.

### El destino correcto para el legacy: `module.components`, no `legacy-overrides`

Validado con control negativo en el tramo 5a-bis y reproducido por la review. Al mover una regla de
`styles.css` a `adapters/legacy-bridge.css`, el destino que **conserva su precedencia de origen** es
`@layer module.components`, precedido en el archivo por:

```css
@layer module.theme, module.layout, module.components;
```

Esa línea es **load-bearing**: sin ella el orden de subcapas de `module` se voltea a
`components, theme, layout`. `legacy-overrides` parece el destino natural y es el equivocado: como
para las declaraciones `!important` el orden de capas se invierte, allí quedarían las **más débiles**
de toda la cascada en vez de las más fuertes.

- [ ] **Step 1: Producir el índice de secciones y planificarlo**

```bash
grep -nE '^\s*/\*\s*=+|^\s*/\*\s*[A-ZÁÉÍÓÚÑ][^*]{5,70}\*/' public/css/styles.css
```

Con ese índice, escribe el desglose de tareas restantes —una por sección, con su rango de líneas y su destino canónico— y añádelas a este plan antes de ejecutarlas. **Detente aquí y entrégalo para revisión**: planificar 6.500 líneas a ciegas produce un plan que nadie puede verificar.

Destinos previstos, según el spec:

| Contenido | Destino |
|---|---|
| Shell, navbar y drawer | `components/navigation.css`, `adapters/shell-sidebar.css` |
| Chips, badges y colores de fila | `components/states-feedback.css`, respetando `state-semantics.json` |
| Toolbars y filtros | `components/filter-form.css`, `components/action-group.css` |
| Tablas y datos | `components/data-display.css` y adaptadores de Handsontable/DataTables |
| Resto por módulo | La hoja del módulo dueño, en su capa correcta |

- [ ] **Step 2: Contrato de verificación de cada sección**

Toda tarea de sección repite esto, sin excepción:

```bash
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
node scripts/design-system-audit.mjs
npm run test:design-system:static
```

Más, al cerrar cada sección mayor:

```bash
npm run test:design-system:runtime
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Y verificación visual a `1180x820` dark de las superficies que la sección toca. **`/programa-general` en todas las secciones sin excepción**: es el piloto protegido por `DESIGN.md` y la excepción acordada a la tolerancia de regresión de esta fase.

---

## Reclasificación del rango 146–734 — decidida el 2026-07-27

El desglose del 2026-07-26 tituló 589 líneas con el nombre de ~35. Medido el rango real, contiene
tres naturalezas distintas, y sólo la primera es «ALL MODULES»:

| Bloque | Líneas | Naturaleza | Destino |
|---|---|---|---|
| **A** | 146–152, 297–317, 319–323 | Contención de ancho, transversal | Tramo 5a (este) |
| **B** | 164–166, 187–235, 614–616, 722–733, 172–174 | Muerto verificado (0 consumidores) | Tramo 5a (borrar) |
| **C** | el resto (~490) | CSS de módulo vivo, con fuga a claro | **Reasignado por módulo** |

El bloque **C** se reasigna a su tramo dueño, en coherencia con la decisión «por módulo» del
2026-07-26 — cada regla se verifica en la ruta donde se ve:

| Contenido de C | Líneas | Tramo destino |
|---|---|---|
| 6 reglas `background: white` de formularios de contratación, `.iconoContrato*`, `#ced4da` | 602–612, 618–694 | **5h** (`/contratos`) |
| `.form_eval`, `.parametro*`, `.pregunta*`, `.cic_*`, `.botones`, restricciones | 379–545 | **5e** (`/programacion-semanal`, CIC) |
| `.tituloFormularioCambios` | 705–720 | **nuevo 5k** (`/control-cambios`) |
| `table.dataTable tbody tr:hover td` (claro + `!important`, app-wide) | 286–289 | **5a** (este — es app-wide, se mide en las 10 rutas) |
| `.dropdown-menu` / `.dropdown-item`, `.navbar-*`, `.cuadroModal`, `.row`, `.filaBotones`, `.filaMensajes`, `.encabezado`, `.formularioRegistro`, `#dt_cliente`, `#cuadroTabla`, botones legacy, `@media max-height` | resto | **5a-bis**, tras 5a |

Las 5 `@media (max-height)` de 547–600 **están vivas**: `public/js/cargarDatosGeneralesPagina2.js:433-436`
rellena `#semanasProyectoMenu` / `#programa_generalMenu` / `#programacion_intermediaMenu` /
`#programacion_semanalMenu`. No se borran en 5a.

---

### Task 5a: contención de ancho «ALL MODULES» y poda de muerto

Primer tramo de `@layer components`. Mueve las reglas que gobiernan la contención de ancho de la
app entera y borra el CSS muerto verificado del mismo rango. Es transversal por naturaleza: se
verifica en las 10 rutas del guard, no en una sola.

**Files:**
- Modify: `public/css/styles.css`
- Modify: `public/css/design-system/foundation.css`
- Modify: `public/css/design-system/vendor-datatables-legacy.css` (destino candidato, ver aviso)

**Interfaces:**
- Consumes: la cascada canónica DS-006. `foundation.css` ya trae `@layer base` y `@layer reset`
  y lo importan los dos entrypoints (`aia-design-system.css:13`, `entrypoints/core.css:5`).
- Produces: `styles.css` más corto; contención de ancho servida desde la capa correcta.

- [ ] **Step 1: Mover la contención de ancho transversal (bloque A)**

Tres grupos, con destinos distintos — no los metas todos en el mismo archivo sin pensarlo:

```css
/* 146-152 */ html, body { overflow-x: hidden; width: 100%; max-width: 100%; }
/* 297-317 */ .dataTables_wrapper, .dataTables_scroll, .dataTables_scrollHead,
              .dataTables_scrollBody, .dataTables_scrollFoot { max-width/overflow-x }
/* 319-323 */ .row { margin-right: 0 !important; margin-left: 0 !important; }
```

`html, body` es fundación pura → `foundation.css`. Las `.dataTables_*` son adaptador de vendor.

**AVISO DE ALCANCE, verificado por el controlador:** `vendor-datatables-legacy.css` es el destino
natural de las `.dataTables_*` y ya trae un `.dataTables_wrapper` en `@layer legacy-overrides`,
**pero es condicional**: `DesignSystemHeadComponent::render()` sólo lo emite cuando
`$handsontableOnly === false` (`src/View/Components/DesignSystemHeadComponent.php:16-18`), mientras
que `styles.css` se carga en todas las rutas. Mover las reglas allí **reduce su alcance**.
Antes de moverlas, comprueba qué rutas llaman `render(true)` y si alguna monta DataTables. Si
alguna lo hace, di cuál y elige otro destino; no muevas y esperes a que se note.

`.row` es de Bootstrap (vendor), no una primitiva del DS: decide su capa y justifícalo.

- [ ] **Step 2: Comprobar duplicación antes de mover — borrar, no duplicar**

Por cada regla, mira si el design system ya la cubre. `foundation.css` **no** tiene hoy
`overflow-x` ni `max-width` sobre `html`/`body`, pero sí tiene ya un bloque `@layer reset` con
`html { overflow-y: auto }` — comprueba que no entren en conflicto. Si el DS ya cubre una regla,
bórrala de `styles.css` sin copiarla.

- [ ] **Step 3: El hover claro de DataTables, app-wide**

```css
/* 286-289 */
table.dataTable tbody tr:hover td {
  background-color: rgba(213, 234, 240, 0.8) !important;
  color: black !important;
}
```

Azul claro con texto negro, sin ámbito y con `!important`, sobre todas las DataTables de la app.
Es fuga a claro y es app-wide. Reapúntalo a tokens activos y **mide el contraste resultante en
más de una ruta** — `/contratos`, `/listado-actividades` y `/control-cambios` tienen DataTables.

Esta regla es exactamente el perfil del defecto de la Ronda D de la Task 3: un override de color
sin ámbito gana en toda la app. Si tu cambio necesita `!important` o más especificidad para
funcionar, párate y repórtalo: la capa ya da la precedencia.

- [ ] **Step 4: Borrar el muerto verificado (bloque B)**

Verificado por el controlador con `grep -rl` sobre `views src public/js admin` — **cero**
consumidores: `.inputBlanco` (614-616), `.comprometerSugerido` (722-733), `.dropdown_divider`
(172-174). Más basura textual pura: las 48 líneas de CSS comentado de 187-235 y los dos
comentarios huérfanos que dejó la Task 2 en 164-166 («Mobile Drawer Implementation» y «Force
Collapse on Tablets», cuyas `@media` ya no existen).

**Confirma tú mismo cada borrado antes de hacerlo**, buscando también en `public/js` — en F0 se
borró una hoja creyéndola muerta porque el grep filtraba por extensión y un `.js` la inyectaba en
runtime. Si un selector aparece construido por concatenación de cadenas en JS, no está muerto.

`.navbar-brand` (158-162 y 168-170, dos declaraciones contradictorias) es **candidato**, no
confirmado: sobrevive en `<style>` propios de `views/profesionales/profesionales.view.php:305` y
`views/subcontratistas/subcontratistas.view.php:293`. Determina si algún markup vivo lleva esa
clase antes de tocarlas; si no lo aclaras con certeza, déjalas y dilo.

- [ ] **Step 5: Verificar — medido, no razonado**

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Expected: biome en **exactamente 4 errores** (la `}` huérfana preexistente de la sección UNLAYERED
OVERRIDE BRIDGE, que se arregla en 5j — si sube a 5, has cortado mal); audit **por debajo de
6865**; guard con **exactamente los mismos rojos** que ya tenía (el segundo test, «superficie de
la grilla», sigue rojo por `/profesionales` y `/subcontratistas` — es de un tramo posterior);
harness 135/135.

En navegador a `1180x820` dark, sobre el contenedor servido: sin scroll horizontal
(`scrollWidth === innerWidth`) en `/programa-general`, `/contratos` y `/listado-actividades`, y
hover de una fila de DataTable medido en las tres.

`/programa-general` se verifica en este tramo como en todos: es el piloto protegido por `DESIGN.md`.

- [ ] **Step 6: Commit**

```bash
git add public/css/styles.css public/css/design-system/
git commit -m "refactor(design-system): mover la contencion de ancho de styles.css a la capa canonica"
```

Nunca `git add -A`: hay tres archivos de sesiones concurrentes sucios en el worktree
(`src/View/Components/DesignSystemComponent.php`,
`tests/browser/design-system-lab-sidebar.mjs`, `tests/browser/design-system-lab.performance.mjs`).
No son tuyos: no los stagees, no los reviertas, no los limpies.

---

### Task 5a-bis: navbar muerta, dropdown duplicado y layout legacy

Cierra lo que quedó del rango transversal tras el tramo 5a. Tres bloques de naturaleza distinta,
**un commit atómico por bloque**, en este orden. Líneas medidas con `styles.css` en 5691.

**Files:**
- Modify: `public/css/styles.css`
- Modify: `public/css/design-system/foundation.css` (sólo si el bloque 2 lo exige)

**Interfaces:**
- Consumes: `foundation.css` ya define `.dropdown-item`, `.dropdown-item:hover` y
  `.dropdown-item.active` en `@layer base` (absorbidos en la Task 4).
- Produces: `styles.css` sin el residuo de la navbar retirada en F0.

#### Bloque 1 — poda de la navbar muerta (commit 1)

F0 borró `NavbarComponent.php`, `navbar.css` y `dark-mode.css`, y el rollout del shell sidebar
retiró la navbar del producto. Lo que queda en `styles.css` es residuo:

| Líneas | Regla |
|---|---|
| 119–131 | `nav.apple-navbar` |
| 134–138 | `.navbar-brand` (font-size) |
| 149–151 | `.navbar-nav .dropdown-menu` (z-index 1050 `!important`) |
| 153–157 | `.navbar-brand` (color `var(--text-main)` `!important`) |
| 159–161 | `.navbar-brand` (color white) |

Nótese que hay **tres** declaraciones de `.navbar-brand` y dos se contradicen en `color`.

**Verificado por el controlador, confírmalo tú:** `apple-navbar` tiene **cero** apariciones en
`views`, `src`, `public/js` y `admin`. El único markup vivo del repositorio con clases `navbar` es
`admin/views/layouts/main.php:40-52`, y `admin/` **no carga `styles.css`** (es una mini-app con su
propio front controller; su migración es F4). Las apariciones de `navbar-nav` y `navbar-brand` en
`views/profesionales/profesionales.view.php` y `views/subcontratistas/subcontratistas.view.php` son
**reglas CSS dentro de `<style>`**, no markup — es decir, huérfanas también.

Busca markup real (`class=`, `classList`, `className`, y concatenación de cadenas en JS) antes de
borrar. Si encuentras un consumidor vivo, **para y repórtalo**: el brief estaría equivocado.

Si confirmas que están muertas, bórralas. **No** toques `.navbar-collapse` de la sección
`UNLAYERED OVERRIDE BRIDGE`: es del tramo 5j.

#### Bloque 2 — deduplicar el dropdown contra el design system (commit 2)

`styles.css:276-298` define `.dropdown-menu`, `.dropdown-item` y `.dropdown-item:hover, :focus`.
`public/css/design-system/foundation.css` (`@layer base`) **ya define** `.dropdown-item`,
`a.dropdown-item:hover, .dropdown-item:hover` y `.dropdown-item.active`, absorbidos en la Task 4.

Hay solapamiento real: ambas fijan `color` en `.dropdown-item`, y el fix de contraste del hover
está escrito **dos veces**, en los dos archivos.

El contrato del tramo manda: **si el design system ya cubre la regla, borrar, no duplicar.**
Recorre declaración por declaración y decide cuál sobra. Cuidado con dos cosas:

- Las de `styles.css` viven en `module.components` y las de `foundation.css` en `base`. Al borrar
  la de `styles.css`, la ganadora cambia de capa: comprueba que el valor resultante es el mismo.
- `.dropdown-menu` (el contenedor) **no** tiene equivalente en `foundation.css`. Su
  `border: 1px solid rgba(0, 0, 0, 0.1)` es un borde negro translúcido sobre superficie oscura:
  decide si lo tokenizas o lo mueves, y **mide** el resultado.

`.dropdown-item` tiene 8 consumidores vivos. Verifica en navegador que el menú de cuenta del shell
sigue legible: abre el dropdown y **mide el contraste** en estado normal y en hover.

#### Bloque 3 — layout legacy a su capa (commit 3)

Reglas vivas, sin fuga a claro, que sólo están en la capa equivocada:

| Líneas | Regla | Consumidores |
|---|---|---|
| 163–168 | `.cuadroModal` | 2 |
| 170–172 | `.form-group label` | — |
| 174–178 | `.row` (width/max-width) | Bootstrap |
| 180–221 | `.filaBotones`, `.filaMensajes`, `.encabezado`, `.formularioRegistro` | 5–8 cada una |
| 248–252 | `#dt_cliente` | — |
| 260–264 | `#cuadroTabla` | 11 |
| 266–274 | botones legacy (`button.editar`, `.duplicar`, …) | — |
| 300–312 | `.grupo_botones*` | 5 |
| 482–536 | 5 `@media` de **max-height** | vivos |
| 627–629 | `#modal_spinner` | 2 |
| 631–634 | `.border-2` | 1 |

Las `@media (max-height)` **están vivas**: `public/js/cargarDatosGeneralesPagina2.js:433-436` rellena
`#semanasProyectoMenu`, `#programa_generalMenu`, `#programacion_intermediaMenu` y
`#programacion_semanalMenu`. No son responsive de ancho y no se borran. `#indicadoresMenu`, en
cambio, no tiene consumidor: compruébalo.

`.filaBotones` define seis tokens `--ps-btn-*` consumidos en otras partes del archivo: si la mueves,
**los consumidores deben seguir viéndola**. Es el riesgo principal de este bloque.

#### Fuera de alcance

No toques, están reasignados: `.form_eval`/`.parametro*`/`.pregunta*`/`.cic_*` (314–481, tramo 5e);
formularios de contratación e `.iconoContrato*` (537–626, tramo 5h); `.tituloFormularioCambios`
(636–651, tramo 5k); `table.dataTable tbody tr:hover td` (243–246, ya retokenizado en 5a).

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Expected: biome en **exactamente 4 errores** (la `}` huérfana de 5j; si sube a 5, has cortado mal);
audit **por debajo de 6851**; guard de canvas con **exactamente los mismos rojos**; harness 135/135.

En navegador a `1180x820` dark: `/programa-general` (piloto, en todos los tramos),
`/programacion-semanal` y `/contratos` sin cambio visual, y el dropdown del menú de cuenta abierto y
**medido** en normal y hover.

---

### Task 5b: tokenizar la paleta de estados compartida

Líneas **518–905** de `styles.css`. Medido el 2026-07-27, antes de escribir esta tarea.

#### Dos correcciones al desglose original

**1. El bloque no es de PDC.** Se titula «PDC Unified Palette» pero `.pdc-legend-item` lo consumen
cuatro módulos, y uno es el piloto protegido:

| Ruta | Variante | Markup |
|---|---|---|
| `/programacion-intermedia` | `.pi-legend` | `programacion_intermedia.view.php:59-73`, 8 estados propios |
| `/programacion-semanal` | `.ps-legend` | `programacion_semanal.view.php:107` |
| `/programa-general` | `.pg-legend` | `styles.css:722-763` |
| `/pdc` | — | `pdc.view.php:50-51` |

Se verifica en **las cuatro**, no sólo en `/pdc`.

**2. Alcance: SÓLO TOKENIZAR** (decisión del usuario, 2026-07-27). Hay tres cardinalidades en
conflicto: **7** colores en el CSS, **6** estados declarados para `pdc` en `state-semantics.json`, y
**4** niveles en el design system (`neutral/healthy/attention/urgent`). Homologar el CSS al contrato
colapsaría 7 colores en 4 y reasignaría varios — `missing` de violeta a amarillo, `active` de azul a
verde, `delayed` y `critical` fundidos en el mismo rojo. **Eso es rediseño**, y el spec de F1 lo
prohíbe: «Rediseñar nada. F1 es reubicación y tokenización, no diseño nuevo.»

**No toques `state-semantics.json`, ni migres a los atributos `data-aia-severity`/`data-aia-urgency`,
ni fundas dos estados en uno.** Los siete colores conservan su distinción. La homologación es una
tarea propia, fuera de F1.

#### Files

- Modify: `public/css/styles.css` (518–905)
- Modify: `public/css/tokens.css` si hace falta declarar tokens de estado nuevos

#### El trabajo

Los 21 tokens `--pdc-*` de `styles.css:536-558` son **todos claros** en una app dark-only, y su
texto es oscuro para contrastar sobre ellos:

| Estado | Fondo | Texto |
|---|---|---|
| `missing` | `#e9d5ff` | `#6b21a8` |
| `critical` | `#fecaca` | `#991b1b` |
| `delayed` | `#fed7aa` | `#8a3f12` |
| `completed-late` | `#fef08a` | `#8a5a00` |
| `completed-ontime` | `#bbf7d0` | `#25643a` |
| `active` | `#bae6fd` | `#1f4f82` |
| `not-started` | `#f3f4f6` | `#4b5563` |

Hay más literales claros fuera de esa tabla: `.row-header td` y `td.pdc-header` (`#8b4011` con
`#fafafa` y borde `#e87722`), `.pdc-status-info` (`#64748b` sobre `#f8fafc`) y el toast de
`styles.css:880` (`#007bff`).

**El fondo y el texto se reasignan juntos, en la misma pasada.** Es la trampa que ya costó una ronda
en la Task 3: oscurecer el fondo dejando el texto oscuro produjo títulos a 1.00:1, literalmente
invisibles. Cada pareja fondo/texto se **mide** tras el cambio.

#### Fuera de alcance

`styles.css:906-925` («PDC: Mobile & Responsive Utilities») es de otra naturaleza y no entra aquí.
Tampoco `state-semantics.json` ni el markup que aplica las clases
(`public/js/modules/pdc/hot.js`, `src/Controllers/Gestion/ReportController.php`).

#### Verificación

Contraste **medido** de las 7 parejas fondo/texto, más `.row-header`, `td.pdc-header` y
`.pdc-status-info`, en las cuatro rutas. AA: 4.5:1 texto normal, 3:1 texto grande.

Los chips son controles (`role="button"`, `tabindex="0"`): su borde y su estado de foco entran en
WCAG 1.4.11 (3:1). Mide también el foco, no sólo el reposo.

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Expected: biome en **4 errores** exactos; audit **por debajo de 6822**, con el desglose de cuánto es
borrado real y cuánto relocalización; guard con los mismos rojos; harness 135/135.

`/programa-general` es piloto **y** consumidor directo de este bloque: su leyenda tiene que quedar
idéntica en geometría y legible en color.

---

### Task 5c: modales y pills de contratación

Líneas **1011–1407** de `styles.css`. Medido el 2026-07-27, antes de escribir esta tarea.

#### Dos correcciones al desglose original

**1. El destino `components/dialog.css` es el componente equivocado.** Son 27 líneas y definen
`.aia-dialog .aia-modal-surface`, un `<dialog>` **nativo** con `::backdrop`. Lo que tú tocas es el
modal **legacy de Bootstrap** (`.modal-content`, `.modal-header`, `.modal-footer`) y el shell
`.aia-modal`. Son dos componentes distintos: **no los fusiones**. Retokeniza en el sitio, y si algo
tiene que moverse, el destino que conserva la precedencia es `@layer module.components` dentro de
`adapters/legacy-bridge.css`, precedido de `@layer module.theme, module.layout, module.components;`.

**2. No son dos rutas, son dieciséis vistas.** El plan decía `/contratos` y `/pdc`. Medido:
`.aia-modal` aparece en **16** archivos de markup y `.modal-content` en 16 — entre ellos
`views/programa-general/programa_general.view.php`, que es el **piloto protegido**, además de
`/programacion-intermedia`, `/listado-actividades` y `/programacion-semanal` con sus subvistas
CIC/CNP y `_changeMonitorModal.php`. `.aia-tipo-pill` sí es de una sola vista.

#### Files

- Modify: `public/css/styles.css` (1011–1407)
- Modify: `public/css/tokens.css` si hace falta declarar tokens nuevos

#### El trabajo

Tres bloques, todos con fuga a claro:

| Líneas | Bloque | Literales claros |
|---|---|---|
| 1011–1050 | Modales Bootstrap | bordes `rgba(0, 0, 0, 0.05)` (negro translúcido, invisible sobre oscuro), fondo de `.close` |
| 1051–1313 | `.aia-modal` shell | 5 custom properties: `--aia-modal-green-light: #d5e5db`, `--aia-modal-green-soft: #eef5f1`, `--aia-modal-border: rgba(26,86,51,0.18)`, más `color: #24313a` |
| 1314–1407 | Pills `.aia-tipo-pill` + hint | `background: #fff`, `color: #495057`, bordes `#6c757d`, y 4 variantes de modalidad (`#6c757d`, `#17a2b8`, `#007bff`, `#343a40`) |

**Fondo y texto se reasignan juntos y se miden.** Las cuatro variantes de modalidad tienen dos
estados cada una (reposo y `.is-checked`, que invierte a `color: #fff` sobre el color de marca):
**mide los ocho**, no sólo los de reposo. Y `.is-disabled` baja a `opacity: 0.45`, que reduce el
contraste efectivo: mídelo también.

**Ten presente que la paleta del design system no tiene azul** (sus dominios son verde `#1a5633`,
naranja `#b55211`, teal `#00a499` y morado `#6752bf`). La variante `--si` es `#007bff`. Si no hay
token con ese matiz, **no inventes uno ni cambies el matiz por tu cuenta**: deja el literal,
documéntalo y dilo en el reporte. Cambiar matices es diseño, y este tramo no lo autoriza.

#### El riesgo principal: los modales están cerrados

Un modal cerrado mide `0x0` y sus hijos no aparecen en un barrido del DOM en reposo. En esta fase
ya mordió exactamente eso: un override de color rompió tablas dentro de `.aia-modal` que un barrido
del DOM visible no vio. El tramo 5a-ter cerró el mismo riesgo abriendo **72 modales uno a uno**.

**Toda tu medición de contraste se hace con los modales abiertos**, y tu sonda debe autovalidarse
(comprobar que cuenta elementos que sabes que existen) antes de que confíes en un cero.

#### Fuera de alcance

`styles.css:1408+` («Inline edit variant») es el tramo 5d. `components/dialog.css` no se toca.
Tampoco el markup.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Expected: biome en **4 errores** exactos; guard con los mismos rojos; harness 135/135; audit con el
desglose de borrado real frente a relocalización.

**Aviso sobre el audit:** hoy está en rojo por `programa-general hardcoded-hex: 1 > path budget 0`,
causado por un `#9c9c98` que una sesión paralela escribió dentro de un comentario en
`public/css/programa-general.css`. **No es tuyo y no lo arregles.** Comprueba que sigue siendo el
único fallo y que tu trabajo no añade otro.

`/programa-general` es piloto **y** tiene `.aia-modal`: sus modales entran en tu medición.

---

### Task 5d: podar la variante inline muerta y tokenizar backdrop e inputs

Líneas **1475–1769** de `styles.css` (5691 líneas). Medido el 2026-07-27, después de que la sesión
de modales aterrizara.

#### Bloque A — poda de la variante inline (commit 1)

`styles.css:1475-1665` (~190 líneas) estiliza `#dt_cliente .aia-tipo-toggle--inline`, el panel
emergente de edición en celda. **Está muerto**, verificado por el controlador y ya observado de
forma independiente durante el tramo 5c:

- `buildTipoContratoPills` se define en `views/listado-actividades/listadoActividades.view.php:497`
  y tiene **un solo llamador**, `:1065`, que pasa siempre `'modal'`.
- La clase `aia-tipo-toggle--inline` sólo puede nacer de la rama `variant === 'inline'` de `:498`,
  que nunca se ejecuta.
- No hay concatenación de cadenas que la forme en ningún otro sitio.

**Confírmalo tú antes de borrar** (busca `class=`, `classList`, `className` y concatenación en
`views`, `src` y `public/js`). Si encuentras un consumidor vivo, **para y repórtalo**.

La rama muerta del JS (`listadoActividades.view.php:498`) **no la toques**: es markup, no CSS, y
ampliar ahí no es de este tramo. Repórtala para una tarea aparte.

#### Bloque B — tokenizar backdrop e inputs (commit 2)

`styles.css:1745-1752` (`Modal Backdrop`) y `1753-1769` (`Inputs & Form Controls`). Llévalos a
tokens con el criterio de siempre: **fondo y texto juntos, medidos**.

Los inputs son controles: su borde y su anillo de foco entran en WCAG 1.4.11 (3:1). En el tramo 5c
se midió que el borde de reposo de los inputs quedaba en **1,89:1** — por debajo del umbral. Si tu
cambio lo mejora, dilo con el número; si no puedes arreglarlo sin cambiar diseño, déjalo y decláralo.

#### NO TOCAR — recién arreglado por otra sesión

`styles.css:1666-1744` acaba de ser corregido y verificado por la sesión de modales (commits
`94ad742` y `f7dfbbf`): botones outline fuera del pie, la superficie legada lima y el botón
deshabilitado. Su guard `tests/browser/modales-dark-homologacion.mjs` pasa 12/12 y cubre esa zona.
**Déjala como está**; si crees que hay un defecto ahí, repórtalo en vez de editarlo.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base fiable medida hoy, con el árbol limpio: `styles.css` **5691**, audit **6605**, biome
**4 errores**, guard primer test **verde 10/10** y segundo rojo **sólo** en `/profesionales` y
`/subcontratistas`, modales **12/12**, harness **135/135**.

**El audit falla hoy por un rojo heredado que no es tuyo**: `programa-general hardcoded-hex: 1 >
path budget 0`, un `#9c9c98` que otra sesión escribió dentro de un comentario y commiteó. No lo
arregles; comprueba que sigue siendo el único fallo.

Separa en el reporte el descenso por borrado real del descenso por relocalización.

---

### Task 5e: la piel clara de DataTables

Líneas **1770–1982** de `styles.css` (5691 líneas). Rango leído entero y medido el 2026-07-27.

#### Dónde están las DataTables de verdad

Medido en tramos anteriores y confirmado dos veces: `/contratos`, `/listado-actividades`, `/pdc` y
`/control-cambios` tienen **cero** `table.dataTable` — están migradas a Handsontable. Las únicas
DataTables vivas del producto están en **`/programacion-semanal/cic`, `/cnc` y `/cnp`**. Verifica en
esas tres, no en las rutas que el nombre del selector sugiere.

#### Bloque A — tokenizar la piel (commit 1)

Todo esto es claro sobre una app dark-only, y todo lleva `!important`:

| Líneas | Regla | Literales |
|---|---|---|
| 1824–1836 | `table.dataTable thead th` | fondo `#f2f2f7`, tinta `#1d1d1f`, borde `#d1d1d6` |
| 1839–1848 | `table.dataTable tbody td` | borde `#e5e5ea`, tinta **`#3a3a3c`** |
| 1870–1873 | `thead tr:nth-child(2) th` (fila de filtro) | fondo `#ffffff` |
| 1876–1891 | `table.dataTable input, select, textarea` | fondo `#fbfbfd`, borde `#d1d1d6` |
| 1900–1907 | `.dataTables_scrollBody::-webkit-scrollbar-thumb` | `#c1c1c1` |

**Fondo y texto se reasignan juntos y se miden.** Es la regla que más veces se ha roto en esta fase.

**El `color: #3a3a3c` de la línea 1844 tiene historia:** es el que impidió mover el hover de
DataTables al adaptador en el tramo 5a. Como vive en `module.components` con `!important`, y para
`!important` el orden de capas se invierte, ganaba a cualquier cosa puesta en `legacy-overrides`. Si
lo tokenizas aquí, comprueba y **reporta** si eso desbloquea reunir el hover con su adaptador — no
lo muevas tú, sólo dilo.

Cuidado con dos avisos heredados:
- La regla de inputs (1876–1891) fija **fondo sin tinta**. El tramo 5d midió 0 elementos en las 8
  rutas con grilla, así que hoy es inerte, pero si tokenizas el fondo sin la tinta reproduces el
  defecto de las 19 etiquetas invisibles. Trátalos como pareja.
- Los `font-size` con `!important` (1775–1813) **no son color**. Tokenízalos sólo si hay escalón
  equivalente en el design system; si no, déjalos y dilo.

#### Bloque B — podar el comentario huérfano (commit 2)

`styles.css:1958–1961` es una cabecera de sección `MOBILE CARD VIEW (No-Scroll Strategy)` **sin una
sola regla debajo**: su contenido se fue con el borrado del responsive por debajo de 1180px. Es
basura textual, como los dos comentarios que dejó la Task 2 y que podó el tramo 5a. Compruébalo y
bórralo.

#### Fuera de alcance

`styles.css:1983+` («PHASE 2 — LEGACY MODULE STANDARDIZATION», Control de Cambios y Semanal) es del
tramo siguiente. `1966–1981` (`MODERNIZATION`: tooltip e `icon-status-*`) ya consume tokens `--aia-*`
con fallback: **no lo toques** salvo que midas que alguno cae por debajo de AA.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base medida hoy con el árbol limpio: `styles.css` **5691**, audit **6590 y VERDE**, biome
**4 errores**, guard primer test verde 10/10 y segundo rojo **sólo** en `/profesionales` y
`/subcontratistas`, modales **12/12**, harness **135/135**.

El audit está **verde**: cualquier fallo nuevo es tuyo. Separa en el reporte el descenso por borrado
real del descenso por relocalización.

---

### Task 5f: Semanal — botonera, modal de agregar actividad y bandeja

Líneas **2046–2555** de `styles.css` (5774 líneas). Rango y literales medidos el 2026-07-28.

El tramo original 5f abarcaba 2046–2802 (757 líneas). **Se parte en dos**, igual que el plan previó
para 5g: esta tarea llega hasta 2555 y el panel claro de 2556–2802 va en **5f-bis**.

#### Ruta de verificación

`/programacion-semanal` y sus subvistas. **`/control-cambios`** también: las líneas 2070–2078
(`.cc-filter-80`, `.cc-logo-col`) son suyas.

#### Dónde está la fuga a claro

| Líneas | Qué es | Literales |
|---|---|---|
| 2155–2187 | Botonera de Semanal | `rgba(255,255,255,.36)`, `#ffffff`, verde `#14532d`, rojo `#dc2626`, foco `rgba(37,99,235,.28)` |
| 2205 | Superficie | `#f4f1ea` (parchment claro) |
| 2214–2222 | Cabecera del modal | gradiente verde con fallbacks `#1a3c2a`/`#1a5633` y tinta `#fafafa` |
| 2258 | Sombra | `rgb(15 23 42 / 10%)` |
| 2532–2533 | Resalte de validación | `#dc3545`, `rgba(220,53,69,.25)` |

**Fondo y texto se reasignan juntos y se miden.** Es la regla más veces rota en esta fase.

Ojo con los fallbacks tipo `var(--aia-green-dark, #1a3c2a)`: el literal es el *fallback*, no el valor
que pinta. **Mide cuál gana antes de tocarlo** — en el tramo 5c se comprobó que los comentarios de
`tokens.css` atribuyen a `--aia-green-dark` un hex que no es el que realmente pinta (las coordenadas
OKLCH dan otro), y sustituir el literal por el token habría oscurecido 50 cabeceras.

#### Fuera de alcance

`2046–2065` (`MODERNIZATION`: tooltip e `icon-status-*`) ya consume tokens con fallback y el tramo
5e lo midió a 4,55:1: **no lo toques** salvo que midas que cae bajo AA. `2556+` es 5f-bis.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base con el árbol limpio: `styles.css` **5774**, audit **6581 y VERDE**, biome **4 errores**,
guard primer test verde 10/10 y segundo rojo **sólo** en `/profesionales` y `/subcontratistas`,
modales **12/12**, harness **135/135**. El audit está verde: cualquier fallo nuevo es tuyo.

**Mide también en el estado de edición y con los modales abiertos**, no sólo en reposo: el tramo 5e
destapó un control a 1,02:1 que sólo existe al pulsar editar, y ningún barrido en reposo lo veía.

---

### Task 5f-bis: el modal de aviso y el conmutador de módulo de Semanal

Líneas **2512–2758** de `styles.css` (5730 líneas). Rango leído entero el 2026-07-28. Segunda mitad
del 5f original.

#### Bloque A — modal de aviso `.ps-modal-*` (2556–2654)

Un modal entero en claro, con su tinta oscura correspondiente:

| Regla | Literales |
|---|---|
| `.ps-bg-white` | `#fff` |
| `.ps-modal-content` | borde `#d9dee6` (fallback de `--aia-separators`), sombra `rgb(17 24 39 / 22%)` |
| `.ps-modal-header` | `linear-gradient(180deg, #fff, #f8fafc)` |
| `.ps-modal-title` y `.ps-modal-text` | tinta `#111827` (fallback de `--aia-text-primary`) |
| `.ps-modal-close` | `#eef2f7`, hover `#e2e8f0` |
| `.ps-modal-icon-wrapper--critical` | `#fdecec` sobre `#9a1f1f` |
| `.ps-modal-footer` | `#f8fafc`, y su `.btn` naranja con `color: #fff` |

#### Bloque B — conmutador de módulo `.ps-module-*` (2701–2757)

También enteramente claro, y **está vivo**: el tramo 5a-bis lo midió en tres rutas.

`.ps-module-switcher` (`#dbe6f1` sobre `#f8fafc`), `.ps-module-tab` (`#ffffff` con tinta `#475569`,
borde `#e2e8f0`), su `:hover`/`:focus` (`#f8fafc`, `#1e293b`, `#cbd5e1`) y `.is-active` (`#ffffff`
con `#1e5ea8` en tinta **y** borde).

`.is-active` es un **indicador de estado**: su borde entra en WCAG 1.4.11 (3:1). Mídelo contra el
switcher que lo rodea, no sólo contra su propio relleno.

#### Bloque C — sueltos

`@keyframes ps-btn-pulse` (`rgba(26,86,51,0.45)`) y `.cic-text-dark` (`rgb(82 23 23)`, tinta oscura
suelta). El resto —`.ps-rend-col-*`, `.ps-modal-content-wrap`, `.ps-toolbar-*`, `.ps-filter-*`,
`.ps-btn-gap`, `.ps-meta-small`— es layout sin color: **no lo toques**.

#### Antes de tokenizar, comprueba qué vive

Cinco premisas de briefs anteriores en esta fase resultaron falsas al medirlas, y dos tramos acabaron
borrando reglas cuyo sujeto no existía. Busca markup real (`class=`, `classList`, `className` y
concatenación de cadenas en JS) para `.ps-modal-*`, `.ps-bg-white` y `.cic-text-dark`. **Si algo está
muerto, bórralo en vez de tokenizarlo** y dilo; si está vivo, tokenízalo y mídelo.

Y comprueba **quién gana la cascada**: `public/css/programacion-semanal.css` llega por `<link>` propio
pero declara `@layer components`, y como los nombres de capa son globales al documento, sus reglas
caen en la capa top-level `components` — anterior a `module` — de modo que con `!important` **ganan** a
`styles.css`. En los tramos 5e y 5f esto dejó muertas 16 declaraciones que parecían vivas.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base con el árbol limpio: `styles.css` **5730**, audit **6516 y VERDE**, biome **4 errores**,
guard primer test verde 10/10 y segundo rojo **sólo** en `/profesionales` y `/subcontratistas`,
modales **13/13**, harness **135/135**. El audit está verde: cualquier fallo nuevo es tuyo.

**Mide con el modal abierto**, no en reposo: un modal cerrado mide 0x0 y sus hijos no aparecen en un
barrido. El conmutador sí es visible en reposo, en la barra de botones.

---

### Task 5g: el conmutador de módulo y la toolbar de Semanal

Líneas **2759–3141** de `styles.css` (5633 líneas). Medido el 2026-07-28. El bloque original
(2759–3704) se parte en dos: la fuga a claro se concentra en 3142+, que va en **5g-bis**.

#### Bloque A — el conmutador de módulo `.aia-info-nav` (2763–2830)

Es el trabajo real de este tramo, y arrastra un incumplimiento conocido.

| Línea | Regla | Literal |
|---|---|---|
| 2791 | `.filaBotones .aia-info-nav__menu` | `background: #ffffff` — **el desplegable es blanco** |
| 2792 | idem | `box-shadow: rgba(30,72,45,.18)` |
| 2825 | `.filaBotones .aia-info-nav__item.is-active` | `color: #ffffff` |
| 2829 | `.filaBotones .aia-info-nav__check` | `color: #ffffff` |

**Incumplimiento medido dos veces (implementador y review del 5f-bis), pendiente de cierre:** el
indicador de la opción activa **no usa borde** (`border-top-width: 0px`) sino relleno, y ese relleno
mide **1,14:1 contra el fondo del menú** — WCAG 1.4.11 exige 3:1 para un indicador de estado. La
tinta sí cumple (12,37:1): se lee el texto, pero no se distingue **cuál** opción está activa.

Ojo: hay reglas del mismo componente en **dos archivos**. `public/css/listado-actividades.css:764`
también estiliza `.aia-info-nav__item.is-active`. **Mide cuál gana** antes de tocar nada, y arregla
donde gana, no donde te resulte cómodo.

Si al tokenizar el menú el indicador sigue por debajo de 3:1, tienes dos salidas que **no** son
diseño nuevo: subir el contraste del relleno con los tokens existentes, o añadir un indicador no
cromático (peso, marca). Si ninguna funciona sin inventar, **dilo y déjalo declarado**.

#### Bloque B — toolbar y presets (2865–3141)

`.toolbarFilaBotones` con sus variantes `ps-size-compact` / `ps-size-comfortable`, los grupos de
botones y los wrappers. **Casi todo es layout sin color**: sólo `styles.css:3108` (`color: #fff`)
tiene literal. No conviertas layout en trabajo de color: si una regla no tiene color, déjala.

#### Antes de tokenizar, comprueba qué vive

En los tramos 5e, 5f y 5f-bis resultaron muertas 30+ declaraciones que parecían vivas, y dos
bloques enteros tenían el sujeto inexistente. Haz censo por **clase exacta** (no por subcadena:
`ps-modal-footer` casa con `ps-modal-footer-between` y da falsos vivos) y cuenta nodos reales.

Y comprueba **quién gana la cascada**: hay reglas de este componente en `styles.css`,
`listado-actividades.css` y posiblemente otras hojas; algunas llegan por `<link>` sin capa pero
declaran `@layer components`, y como los nombres de capa son globales al documento, caen en la capa
top-level `components` —anterior a `module`— y con `!important` **ganan** a `styles.css`.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base con el árbol limpio: `styles.css` **5633**, audit **6450 y VERDE**, biome **4 errores**,
guard primer test verde 10/10 y segundo rojo **sólo** en `/profesionales` y `/subcontratistas`,
modales **14/14**, harness **135/135**. El audit está verde: cualquier fallo nuevo es tuyo.

**El conmutador es visible en reposo**, en la barra de botones de `/listado-actividades`,
`/contratos` y `/pdc`. Pero **su menú sólo existe desplegado**: ábrelo para medirlo.

---

### Task 5g-bis: la paleta de estados de Programación Semanal

Líneas **3148–3710** de `styles.css` (5639 líneas). Es el bloque con **mayor densidad de color de
todo el archivo**: 89 literales en 563 líneas. Medido el 2026-07-28.

#### Qué es

La paleta de alertas de Semanal —`.ps-alert-critical`, `-critical-route`, `-high`, `-medium`,
`-info`, `-control`— aplicada a tres superficies: chips de leyenda (`.pdc-legend-item`), swatches del
modal (`.ps-legend-modal-swatch`) y celdas de Handsontable (`.ps-page #hot-container .handsontable
td.ps-row-state`). Más el indicador de fase del context bar (`.context-weekly-phase`).

Hoy usa fondos `--aia-*-very-light` / `--aia-*-background` con tinta `--aia-*-dark`: **fondos claros
con texto oscuro**, en una app dark-only.

#### La escalera ya existe: úsala, no inventes

Otra sesión estableció en `public/css/tokens.css:234-252` una escalera de tintes de estado para
oscuro: `--ds-state-tint-{violet,red,orange,amber,green,blue,teal}-{1,2,3}`, donde `-1` es croma
pleno, `-2` el 60 % y `-3` el 30 %. Ya la consumen `styles.css:2290, 2294, 2335, 2359, 5284, 5288`
— pero **cero veces en tu rango**.

El mapeo por familia de matiz es directo (`critical`→red, `high`→orange, `medium`→amber,
`info`→blue, `control`→green), pero **no lo apliques a ciegas**: hay tres superficies con
necesidades distintas —un chip pequeño, un swatch y una celda de tabla— y el escalón `-1/-2/-3` debe
elegirse **midiendo**, no por simetría. Conserva la familia de matiz de cada estado: cambiarla es
rediseño y está vedado.

#### Lo que más se ha roto en esta fase

**Fondo y texto se reasignan juntos y se miden.** Aquí hay tres declaraciones por regla (fondo, tinta
y borde): las tres se mueven juntas o ninguna. En el tramo 5g las opciones inactivas de un menú
quedaron a 1,24:1 exactamente por mover una y no la otra.

El **borde** de estos chips es un indicador de estado: WCAG 1.4.11 pide **3:1**. Mídelo contra el
chip y contra lo que lo rodea. Aviso útil: el design system **no declara hoy ningún token de borde
que llegue a 3:1 sobre oscuro** (`--ds-active-border` rinde 1,86–1,91:1). Si necesitas frontera
fuerte, el tramo 5e usó `--ds-active-text-secondary` documentando el desajuste semántico.

#### Antes de tokenizar, comprueba qué vive

En 5e, 5f, 5f-bis y 5g resultaron muertas más de 30 declaraciones que parecían vivas, y varios
bloques tenían el sujeto inexistente. Censo por **clase exacta** (grepear subcadenas da falsos
vivos) y cuenta nodos reales. **Si algo está muerto, bórralo en vez de tokenizarlo.**

Comprueba también **quién gana la cascada**: `programacion-semanal.css` llega por `<link>` pero
declara `@layer components`, y como los nombres de capa son globales al documento cae en la capa
top-level `components` —anterior a `module`— de modo que con `!important` **gana** a `styles.css`.
En 5e y 5f eso dejó muertas 16 declaraciones. En 5g la cascada ganaba en **archivos distintos según
la ruta**: compruébalo ruta por ruta, no una vez.

#### Verificación

```bash
npx biome check public/css/styles.css
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/shell-sidebar-rollout.mjs --workers=1
```

Línea base con el árbol limpio: `styles.css` **5639**, audit **6418 y VERDE**, biome **4 errores**,
guard primer test verde 10/10 y segundo rojo **sólo** en `/profesionales` y `/subcontratistas`,
modales **15/15**, harness **135/135**. El audit está verde: cualquier fallo nuevo es tuyo.

Hay guards vivos que cubren este territorio y deben seguir verdes: `pdc-chips-dark.mjs`,
`state-tint-ladder.mjs` y `ops-state-chip-hue.mjs`.

**Mide con la grilla cargada y el modal de leyenda abierto.** Los swatches sólo existen con el modal
desplegado y las celdas de estado sólo con filas: en reposo no se ve ninguno de los dos.

**Sé breve en los comentarios.** El tramo 5g fue el único que hizo crecer el archivo y hubo que
podarlo: deja 1–2 líneas por bloque y que el detalle viva en tu reporte.

---

## Renumeración de los tramos restantes (2026-07-28)

La tabla del 2026-07-26 quedó desfasada al partirse `5f` en `5f`/`5f-bis`/`5g`/`5g-bis`. Rangos
**medidos sobre las 5.404 líneas actuales**, con las cabeceras de sección reales del archivo:

| # | Rango | Contenido | Ruta de verificación |
|---|---|---|---|
| 5h | 3476–4645 | Programa General / Programación Intermedia compartido — **el mayor, 1.170 líneas; se parte en dos** | `/programa-general`, `/programacion-intermedia` |
| 5i | 4646–5071 | Contratos | `/contratos` |
| 5j | 5072–5281 | PDC, indicadores de desviación, leyendas compartidas | `/pdc` |
| 5k | 5282–5404 | «Fin Layer Components» + **UNLAYERED OVERRIDE BRIDGE** (aquí vive la `}` huérfana) | todas |

`.tituloFormularioCambios`, que la reclasificación del 2026-07-27 mandó a «un nuevo 5k
(`/control-cambios`)», ya no cae en ese rango: quien lo encuentre lo trata en su tramo y lo declara.

### Precondición vinculante — descubierta midiendo en 5h-2, **reatribuida al leer 5j**

> **Corrección (2026-07-28).** Esta precondición se escribió apuntando a 5k y al bloque
> `html body.pdc-page #pdc-hot-shell .pdc-legend` del bridge. **Es el bloque equivocado.** Al leer el
> rango completo de 5j se ve que son **dos bloques distintos**, y el portante está en **5j**.

El bloque **`4634–4691`, dentro de `@layer components`** («OPTIMIZACIÓN PREMIUM 2026: Leyendas
Responsivas Simétricas Unificadas»), es **quien mantiene muertas** las reglas de geometría de leyenda
que 5h-2 pudo retirar: fija ancho, tipografía, padding y márgenes de los chips de `#pgLegend`,
`#piLegend` y `#psAlertsLegend` con `!important` y selectores de especificidad alta. La review de
5h-2 lo citó como «`styles.css:4689-4739`» con el archivo en 4882 líneas; tras 5i son estas líneas.

Si **5j** lo borra **sin reponer esa geometría**, `/programa-general`, `/programacion-intermedia` y
`/programacion-semanal` pierden el dimensionado unificado de sus leyendas — y **ningún guard lo
vería**, porque la geometría no es color.

**5j no puede limitarse a reubicarlo: debe medir la geometría de la leyenda (ancho, alto, tipografía
y márgenes del chip) en las tres rutas, antes y después.**

El bloque del bridge (`html body.pdc-page #pdc-hot-shell .pdc-legend`, ahora ≈4816–4831) es **otra
cosa**: un override sólo para `/pdc` que convierte esa leyenda en rejilla de 7 columnas. Sigue siendo
de 5k, y sigue mereciendo cuidado, pero no es el que sostiene la geometría compartida.

Segundo hallazgo del mismo sitio, para el tramo que lo toque: las reglas
`table.dataTable tbody tr.row-{critical-delay,delayed,warning} td` consumen `--pi-*` **sin ámbito
`.pi-page`**, con literal claro de reserva (`var(--pi-overdue-bg, #fed7aa)`). Fuera de `.pi-page`
esos tokens no existen, así que resuelven al literal **claro**. Es preexistente —`styles.css`
tampoco los definía en `:root`— pero es una fuga a claro latente y hay que cerrarla, no heredarla.

### Dónde se parte 5h, y por qué ahí

Medido leyendo el rango completo: el corte natural es **4149**, donde empieza `.pi-page` y termina
todo lo de `.pg-page`. Es a la vez frontera de módulo y reparto equilibrado del color — cada mitad
lleva su bloque de tokens de estado y su bloque claro `*-legend-quick-*`:

- **5h-1 = 3476–4147** (672 líneas): limpieza compartida de ancho, `.pg-*` de toolbar y celda, los
  **42 tokens `--pg-*`**, celdas de estado de HOT/DataTables, `.pg-legend`, `.pg-legend-modal`,
  `.pg-legend-quick-*`, `.pg-alert-badge` y un `@media (min-width: 64rem)`.
- **5h-2 = 4149–4645** (497 líneas): todo `.pi-page` — los **27 tokens `--pi-*`**, celdas de estado,
  `.pi-legend`, `.pi-legend-quick-*`, `.pi-legend-modal`, tooltips naranjas, botones y su `@media`.

Los dos `@media` del rango son `min-width: 64rem` (1024 px): **no** son el responsive prohibido de
la Task 2 y no se borran por esa vía.

### El dato que cambia la naturaleza de 5h — medido el 2026-07-28

`programa-general.css` y `programacion-intermedia.css` entran por `<link>` propio y **declaran
`@layer module` sin subcapa**. Como las reglas sin subcapa de una capa van *después* de todas sus
subcapas, ganan a `module.components`, que es donde resuelve `styles.css`.

Comparadas las declaraciones:

- **42 de 42** tokens `--pg-*` de `styles.css` están redeclarados en `programa-general.css`.
- **24 de 27** tokens `--pi-*` lo están en `programacion-intermedia.css`. Los tres que **no**:
  `--pi-tooltip-bg`, `--pi-tooltip-fg`, `--pi-tooltip-separator`.

Y las hojas de módulo ya los apuntan a `--ds-color-state-*`. Es decir: los bloques de tokens claros
de `styles.css` son con casi total probabilidad **muertos**, y las reglas que los consumen ya
resuelven oscuro. Esto convierte 5h en un tramo de **borrado**, no de tokenización — el mismo perfil
que 5f-bis y 5g-bis.

**No lo des por hecho: censa.** Es exactamente la clase de premisa que se ha caído cinco veces en
esta fase. Un token redeclarado no prueba que la *regla* que lo consume esté muerta.

### Concurrencia con la sesión de paleta de estado oscura

`docs/superpowers/plans/2026-07-28-paleta-estado-oscura.md` está **en ejecución** (sus artefactos de
Task 1 están sin commitear en el worktree) y toca terreno vecino: su Task 2 invierte los cuatro pares
`--ds-color-state-*` (mueve el suelo de cualquier medida de contraste), su Task 4 toca
`programacion-intermedia.css`, su Task 6 `programa-general.css` y el markup del piloto, y su Task 3
toca `styles.css` en los bloques de `.pdc-legend-item` — eso último colisiona con **5j**, no con 5h.

Regla para 5h: si `public/css/styles.css` aparece sucio, **BLOCKED**, no editar en conflicto. Y las
mediciones de contraste se toman y se re-verifican dentro de la misma sesión del tramo, porque su
Task 2 puede aterrizar en medio y dejarlas obsoletas.

---

### Task 5h-1: Programa General (3476–4147)

**Ruta:** `/programa-general` — que además es el **piloto protegido por `DESIGN.md`**, así que aquí
la tolerancia a regresión de F1 no aplica: lo que se rompa se arregla en el mismo commit.

- [ ] **Step 1: Censo antes de tocar nada**

Por **clase exacta**, nunca por subcadena (`pg-legend` casa con `pg-legend-quick` y con
`pg-legend-modal`). Busca en `views/`, `public/js/` y `src/` — incluida la concatenación de cadenas
en JS — y cuenta nodos reales en el DOM de `/programa-general`, no ocurrencias en el CSS.

Cuenta aparte, porque deciden el destino de bloques enteros:

- `.pdc-legend-item` dentro de `.pg-legend` — el tramo 5b midió **0 nodos** en `/programa-general`
  (su leyenda real es `#pgLegend .aia-chip.pg-filter-chip`). Confírmalo o refútalo.
- `.pg-legend-quick-*` y `.pg-legend-modal-*`: viven en un modal. **Ábrelo antes de medir.**
- `.pg-alert-badge`, `.pg-help-icon`, `.pg-cell-meta`, `.legacy-modal-*`, `.legacy-ul-clean`,
  `.legacy-img-center`.

- [ ] **Step 2: Establecer quién gana, ruta por ruta**

Para cada regla que declare color, mide qué archivo la pinta de verdad. `programa-general.css`
declara `@layer module` sin subcapa y gana a `styles.css`; una regla cuyo valor computado no cambia
al borrarla de `styles.css` está **muerta** y se borra, no se tokeniza. Verifícalo sirviendo el
archivo modificado, no razonándolo.

- [ ] **Step 3: Borrar lo muerto, tokenizar sólo lo vivo**

Los 42 tokens `--pg-*` y las reglas que se demuestren inertes: borrar. Lo que quede vivo se apunta a
`--ds-active-*`, **reasignando fondo y texto juntos** — y si la regla toca borde, son tres
declaraciones. No cambies matices: es diseño, y el spec de F1 lo prohíbe.

- [ ] **Step 4: Medir contraste con los estados abiertos**

AA 4,5:1 texto, 3:1 texto grande, 3:1 bordes de control (WCAG 1.4.11). Con el **modal de leyenda
abierto** y con la **tabla cargada** — un barrido en reposo no ve ni el modal (0×0) ni los chips de
estado, y ese es el modo de fallo que más defectos ha destapado en esta fase. Autovalida la sonda
antes de fiarte de un cero.

- [ ] **Step 5: Verificación**

```bash
node scripts/design-system-audit.mjs
npm run test:design-system:static
npx playwright test tests/browser/design-system-body-canvas-dark.mjs --workers=1
npx playwright test tests/browser/modales-dark-homologacion.mjs --workers=1
npx playwright test tests/browser/programa-general-legend-hue.mjs --workers=1
npx biome check public/css/styles.css
```

Esperado: audit **verde y bajando**; biome en **exactamente 4 errores** (la `}` huérfana de 5k; si
sube a 5, has cortado mal); guard de canvas con los rojos conocidos y ninguno nuevo; modales 16/16.
Reporta el descenso del audit separando **borrado real** de **relocalización**.

---

### Task 5h-2: Programación Intermedia (3799–4294)

**Ruta:** `/programacion-intermedia`. `/programa-general` se verifica igual, por ser el piloto.

**Rango re-medido tras 5h-1**, que borró 350 líneas por encima: el bloque `.pi-page` va de **3799**
(`.pi-page #dt_cliente thead tr:nth-child(2)`) a **4294** (cierre del `@media (min-width: 64rem)`),
justo antes de `/* Contratos */` en 4296. Los números 4149–4645 del desglose original ya no valen.

**Más el resto del bloque compartido, 3476–3489**, que 5h-1 dejó a propósito y documentó en el
propio archivo: los arms `.pi-page #cuadroTabla` y `.pi-page #dt_cliente`. El arm `.pg-page` mide 0
nodos y también espera aquí; el arm **`.ps-page` sigue vivo** en las subvistas de Programación
Semanal y **no se toca** — es de un tramo posterior. Al quitar arms de una lista separada por comas,
comprueba que lo que borras es un arm entero y no el sujeto de la regla.

Mismos cinco pasos que 5h-1, con estas diferencias medidas:

- Los **tres tokens de tooltip** (`--pi-tooltip-bg`, `--pi-tooltip-fg`, `--pi-tooltip-separator`)
  **no** están redeclarados en la hoja de módulo: son los únicos del bloque que casi seguro siguen
  vivos. El tooltip es naranja corporativo; su matiz no se toca.
- `.pi-state-neutral` (≈4304) trae su pareja fondo/texto **en hex crudo**, no en token: es el único
  estado del bloque que no pasa por variable.
- `.pi-page .pdc-legend-autoscaling .pdc-legend-item` sí parece tener markup vivo en esta ruta, al
  revés que en `/programa-general`. Censa por separado: es lo que decide si el bloque de leyenda se
  borra o se tokeniza.
- El tooltip sólo existe **al pasar el ratón**. Ábrelo antes de medirlo.

---

### Task 5i: Contratos (4124–4410)

**Ruta:** `/contratos`, con el modal `#modalEditarContratos` **abierto**. `/programa-general` se
verifica igual, por ser el piloto.

**El límite inferior es 4410, no 4549.** `/* PDC */` empieza en 4411 y es del tramo 5j. El comentario
de sección tiene 3 caracteres y se escapa de cualquier índice que pida comentarios más largos: es
justo la trampa del límite inferior que ya tumbó cinco premisas en esta fase.

**Este tramo NO es como los anteriores: el grueso ya está migrado.** La sesión de modales huérfanos
(commit `f7dfbbf`) rehízo este bloque entero — era «un skin entero sin migrar» — y hoy consume
`--ds-active-surface`, `--ds-active-border`, `--ds-active-text-primary` y compañía. Espera un tramo
**pequeño y de detalle**, no una poda masiva. Censa igualmente: no heredes esta frase sin medirla.

Lo que queda por resolver, medido leyendo el rango completo:

- **Seis `rgba()` crudos** en sombras y anillos de foco (≈4134, 4193, 4293, 4360, 4366) y un
  `rgba()` de borde en la cabecera (≈4141).
- **Dos `#fafafa`** de tinta sobre la cabecera verde (≈4150 título, ≈4165 botón de cierre).
- **`.ct-text-*`** (≈4398–4409) apuntando a tokens legacy `--aia-*`, uno con literal de reserva.

#### Dos cosas que NO se tocan, y por qué

1. **El degradado de `.ct-modal-header` (≈4140) conserva sus literales A PROPÓSITO.** La review del
   tramo 5c lo dejó verificado: los comentarios de `tokens.css:6-7` **inducen a error** —
   `--aia-green-dark` no pinta el valor que dice su comentario, y lo mismo `--aia-green-primary`—, y
   sustituir los literales por esos tokens **oscurecería 50 cabeceras de modal**. Si quieres
   tokenizarlo, primero mide qué pinta cada token de verdad; y si no coincide, déjalo y decláralo.
2. **La línea ≈4268, `.ct-overplanning-alert`, no se toca.** Usa `--ds-color-state-warning-bg` como
   color de texto y está inventariada como `at-risk` en
   `docs/design-system/state-token-exceptions.json` (commit `ccd6f70`). Hoy da 14,96:1 **por
   accidente**; emparejarla ahora la rompe (~1,4:1) y sólo es correcto hacerlo en el mismo paso que
   la inversión de la paleta, que lleva otra sesión. Tocarla pone el guard de emparejamiento en rojo.

#### Commits parciales

Por decisión del usuario (punto 7 del contrato de tramo), este tramo se parte en commits por bloque
en vez de uno solo.

---

### Task 5j: PDC, indicadores de desviación y leyendas compartidas (4360–4708)

**Ruta:** `/pdc`. `/programa-general` se verifica igual, por ser el piloto — y aquí **además**
`/programacion-intermedia` y `/programacion-semanal`, porque el bloque de geometría de leyenda es
compartido por los tres.

**Límites:** de `/* PDC */` (4360) al cierre de `@layer components`. **La línea 4708 es la llave que
cierra la capa: no la toques.** La llave huérfana está en la **4750**, dentro del bridge, y es del
tramo 5k.

#### Lo que hay, leído entero

| Líneas | Qué es |
|---|---|
| 4361–4430 | Ayudantes del modal de PDC (`.pdc-modal-*`, `.pdc-row-center`, `.pdc-inline-msg`, `.pdc-bg-muted`) — layout con literales claros sueltos |
| 4431–4448 | `.pdc-icon-*`, cinco colores de estado en hex crudo |
| 4452–4481 | `.pdc-delta` y sus dos variantes, **ya tokenizadas** y con un comentario que documenta una colisión semántica deliberada. Guard: `pdc-chips-dark.mjs` |
| 4483–4497 | `.pdc-ready-icon--*`, tres hex con `!important` |
| 4499–4528 | **`.pdc-btn-alertas`: piel clara entera** en una barra ya oscura. Es la deuda que el tramo 5g declaró y no cerró |
| 4530–4537 | `.w-80`, `.ps-cell-readonly` |
| 4549–4571 | `@media (min-width: 993px)` con la geometría de 205px |
| 4573–4604 | Centrado interno del chip y el carrusel de `#pdc-hot-shell` |
| 4606–4631 | Badge de fecha de corte, **ya tokenizado** |
| 4634–4691 | **Geometría unificada de leyenda — PORTANTE, ver precondición** |
| 4693–4707 | Ocultar la barra de scroll de las tres leyendas |

#### Las tres trampas propias de este tramo

1. **El bloque 4634–4691 es portante.** Lee la precondición de arriba antes de tocarlo. Mide ancho,
   alto, tipografía y márgenes del chip en las **tres** rutas, antes y después.
2. **`pdc.css` entra por `<link>` SIN CAPA y gana a todo lo capado.** El tramo 5b ya midió que por
   ese mecanismo `.pdc-toast` y `.count-badge` quedaban inertes aquí. Espera encontrar más reglas
   muertas de las que parece — **pero censa cada una, no lo heredes**.
3. **El `@media (min-width: 993px)` de 4549 está siempre activo dentro del alcance soportado**
   (desktop ≥1180 px). No es responsive prohibido y **no se borra por esa vía**; desenvolverlo es una
   decisión aparte, y si la tomas, mide que la geometría no cambie.

#### Lo que sí es trabajo real

`.pdc-btn-alertas` (4500–4528) es una piel clara completa —fondo, tinta, borde y estado activo— en
una barra que ya es oscura. Es el único bloque del rango con fuga a claro de tamaño. Reasigna
**fondo y texto juntos**, y si tocas el borde son tres declaraciones. El naranja del estado activo es
matiz corporativo: **no lo cambies**, sólo tradúcelo si existe token equivalente.

#### Ojo con la sonda de contraste

`tests/browser/support/contrast.mjs` **sobreestima sobre degradados**: no compone `background-image`.
`/pdc` tiene un `.aia-modal` con la misma cabecera en degradado que Contratos. Está documentado en el
bloque `LIMITE CONOCIDO` de esa hoja desde el tramo 5i; mide contra las paradas reales del degradado.

#### Commits parciales

Por decisión del usuario, este tramo se parte en commits por bloque.

---

### Task 5k: el UNLAYERED OVERRIDE BRIDGE y la `}` huérfana (4374–4496)

**Último tramo, y el más delicado.** Rutas de verificación: todas las que el guard de canvas cubre,
más `/pdc` y `/programacion-semanal/{cic,cnc,cnp}`. `/programa-general` siempre, por ser el piloto.

#### Lo que hay, leído entero

| Líneas | Bloque | Censo hecho por el controlador |
|---|---|---|
| 4382–4384 | `.navbar-collapse { z-index }` | sólo aparece en `public/js/modules/aia_ui/nav_drawer.js`; **cero markup**. La navbar la desmanteló F0 |
| 4387–4389 | `.modal-backdrop { z-index }` | vivo (`pdc.view.php`, `pdc/hot.js`, y Bootstrap lo crea) |
| 4394–4399 | `.btn-sm, .btn-pdc-modern` | **muy vivo**: 16 archivos |
| 4404–4413 | `.ps-action-btn` | vivo (`cargarDatosGeneralesPagina2.js`, `programacion_semanal/hot.js`) |
| **4415** | **la `}` huérfana** | ver abajo |
| 4422–4434 | rejilla de Handsontable, `#cbd5e1` | **duplicado exacto** de `handsontable-module.css:108-109` |
| 4437–4447 | bordes de DataTables, `#cbd5e1` | DataTables vivas sólo en `/programacion-semanal/{cic,cnc,cnp}` |
| 4454–4479 | `tr.row-{critical-delay,delayed,warning}` | consumen `--pi-*` **sin ámbito** con literal claro de reserva |
| 4482–4495 | leyenda de `/pdc` en rejilla de 7 columnas | vivo (`pdc.view.php`) |

#### La mecánica que hace especial a este tramo

Estas reglas son `!important` **y sin capa**. Para declaraciones `!important` el orden de capas se
invierte, y **lo no capado gana a todas las capas**: es decir, este bloque está en la **cima** de la
cascada. Mover cualquiera de estas reglas a una capa —la que sea— **la debilita**. Por eso la sección
grita en mayúsculas que no debe quedar dentro de ningún `@layer`, y por eso `legacy-overrides` es el
peor destino posible: allí las `!important` quedan las más débiles de todas.

**La pregunta correcta no es «a qué capa se mueve» sino «¿todavía necesita ganar?».** Este puente
existe para escapar de la propia capa de `styles.css` — y `styles.css` ya casi no existe. Varias de
estas reglas pelean contra un rival que F1 ya retiró. Mídelo regla por regla: si su competidor
desapareció, deja de necesitar el puente.

#### La `}` huérfana: por qué no se puede arreglar sola

Medido y revertido una vez por decisión del usuario:

1. **La llave sobra.** Balance del archivo, quitando comentarios: **−1**, y el desbalance aparece justo
   ahí. `.ps-action-btn` cierra bien en 4413. Borrarla deja el archivo en delta 0.
2. **Borrarla sola pone el audit en ROJO.** Medido en su momento: el total subió de 6851 a 6898 y
   falló con `css-outside-layer: 841 > baseline 829`. La llave de más hacía que el parser del audit
   creyera que ~47 reglas estaban dentro de un bloque; sin ella ve la verdad, que están fuera de capa
   — exactamente lo que el comentario de la sección declara en mayúsculas. **No es deuda nueva: es
   deuda que el error de sintaxis ocultaba.**
3. Por eso **5k debe arreglar la llave y reubicar o borrar las reglas en la MISMA tarea.** Sólo así el
   contador baja en vez de subir, y no hace falta tocar el baseline protegido.
4. **El parse no ocultaba lint.** Medido: 539 warnings idénticos antes y después. Lo único que
   abortaba era el formateo de biome. No hay un alijo de deuda esperando al final del archivo.

#### Dos pistas fuertes, ya medidas

- **La rejilla de Handsontable (4422–4434) es un duplicado exacto**: `handsontable-module.css:108-109`
  declara los mismos `#cbd5e1 !important` y entra por `layer(vendor)`. Hoy gana el puente por no estar
  capado; si se borra, **toma el relevo el módulo con el mismo valor**. Verifícalo y, si se confirma,
  es borrado limpio sin mover un píxel. Ojo: ese `#cbd5e1` es un borde claro sobre tabla oscura, así
  que **el defecto de contraste sobrevive al borrado** — es del módulo, no de aquí. Decláralo.
- **`.navbar-collapse` no tiene markup.** F0 desmanteló la navbar. Comprueba si `nav_drawer.js` sigue
  creando el elemento o si es otro huérfano de aquella poda.

#### El biome debe BAJAR de 4 a 3 errores

Es el único tramo donde ese número cambia: los 4 errores actuales son el parse de la `}` huérfana. Al
arreglarla deben quedar **3**. Si quedan 4, no la has arreglado; si suben, has cortado mal.

#### Commits parciales

Por decisión del usuario, este tramo se parte en commits por bloque. Sugerido: censo y poda de lo
muerto, luego cada bloque vivo, y la llave **con** la reubicación que la compensa.

---

### Task 5l: barrer los 67 literales de color que quedan

**Decidido el 2026-07-28**, tras medir uno a uno qué queda: **41 hex y 26 `rgba()`**, frente a los 483
y 108 de apertura. No es otra fase — es un tramo, porque la mayoría son **el mismo literal repetido**
o tokens muertos, no 67 decisiones distintas.

**El objetivo real no es cero, es «cero salvo excepciones documentadas».** Hoy hay **una** excepción
medida y ya adjudicada: `--aia-modal-green-dark: #1a3c2a` (línea 1074). El tramo 5i verificó que
sustituirla por su token **oscurecería 50 cabeceras de modal**, porque los comentarios de `tokens.css`
inducen a error sobre qué pinta cada token. **No se toca, y su comentario se queda.**

#### La tensión de alcance, declarada

Un barrido «por literal» es exactamente la forma de cambio amplio que la decisión del 2026-07-26
descartó, porque **los dos peores defectos de esta fase vinieron de cambios más amplios que su
problema**. Pero al medir dónde vive cada literal resulta que **están agrupados por módulo**, así que
el principio se conserva: cada bloque de abajo se verifica en **una ruta concreta**, y cada uno va en
su propio commit.

#### Bloques, con su censo y su ruta

| # | Qué | Dónde | Ruta de verificación |
|---|---|---|---|
| **A** | **12 tokens de paleta legacy con CERO consumidores**: `--primary-dark`, `--primary-light`, los seis `--status-*`, `--color-purple`, `--color-indigo`, `--color-teal`, `--text-inverse` | 13-14, 17-22, 25-27, 42 | ninguna: es borrado |
| **B** | **3 literales dentro de comentarios** (el audit los cuenta) | 192, 199, 203 | ninguna |
| **C** | `#ced4da` ×5, bordes de los formularios de contratación | 336, 422, 431, 446, 452 | `/contratos` |
| **D** | `rgba(55, 68, 81, …)` ×6, bordes de los formularios de evaluación | 238, 253, 273, 295, 317, 364 | `/programacion-semanal/cic` |
| **E** | `.tituloFormularioCambios` | 504-505 | `/control-cambios` |
| **F** | Los cuatro tokens `--shadow-*` **y sus consumidores** | 50-53, 938, 945, 1003, 1089 | ver aviso |
| **G** | Sueltos: `--tipo-brand` ×4, los tres colores de icono de estado, `#fff`/`#fafafa`/`#007bff`/`#1a5633`, el degradado de 2733 y los `var(--x, #hex)` de 2055/2063 | dispersos | la ruta de cada uno |

**`--primary` (línea 12) NO entra en el bloque A: tiene 5 consumidores vivos.** Sólo se borran los que
den **cero** por censo de nombre exacto sobre `public/css/`, `views/`, `public/js/` y `src/`.

#### Aviso sobre el bloque F: las sombras no son un cambio neutro

Los cuatro `--shadow-*` **están consumidos** (6, 2, 1 y 3 veces). Y hay precedente medido en el tramo
5i: `--ds-shadow-lg` **duplica el desenfoque** del literal que sustituye (24 px → 48 px), y
`--ds-shadow-md` conserva la geometría a costa del alfa. Es decir, **no hay equivalencia exacta**.

Mide antes de elegir peldaño y, si ninguno conserva la geometría, **declara el desajuste y aplázalo**
en vez de cambiar la sombra por su cuenta: cambiar geometría de sombra es diseño, y el spec de F1 lo
prohíbe.

#### Dos asignaciones que se perdieron y este tramo recupera

La reclasificación del 2026-07-27 mandó `.form_eval`, `.parametro*` y `.pregunta` al tramo 5e, y
`.tituloFormularioCambios` a «un nuevo 5k». **Ninguna de las dos se ejecutó**: 5e acabó siendo el modal
de agregar actividad de Semanal, y 5k pasó a ser el puente sin capa. Los bloques D y E las recuperan.

#### Verificación

Además de los gates de siempre, este tramo tiene una señal propia: **`hardcoded-hex` y
`hardcoded-color-function` de `styles.css` deben quedar en 1 y 0** (la excepción documentada). Si
queda alguno más, decláralo con su motivo medido.

---

### Task 5m: el desplegable select2 blanco dentro del modal oscuro

**Decidido por el usuario el 2026-07-28**, tras el tramo 5l: va como tramo propio, no como chip. Es el
**último defecto visible** que deja F1 en `styles.css` y son los **7 literales** que faltan para cerrar
el archivo.

**Ruta:** `/programacion-semanal`, modal `#modalNuevaActividad`, **con el desplegable abierto**.
`/programa-general` se verifica igual, por ser el piloto.

#### El síntoma

El panel del desplegable sale **blanco puro** dentro de un modal oscuro, en una app dark-only.

#### El mecanismo, medido — y por qué el arreglo obvio NO funciona

El design system **ya declara lo correcto**: `design-system/adapters/select2.css:58-66` pinta
`.select2-dropdown` con `--ds-active-surface-raised`, `--ds-active-text-primary` y
`--ds-active-border`. Pierde por **dos** razones independientes:

1. El adaptador declara `@layer components` y `styles.css` resuelve como `module.components` →
   **`module` gana a `components`** para declaraciones normales. Por eso el bloque
   `#modalNuevaActividad .select2-*` de `styles.css:1472-1553` manda.
2. **Y aunque se borrara ese bloque, el desplegable seguiría blanco:**
   `views/programacion-semanal/programacion_semanal.view.php:38` carga
   `/public/vendor/select2/select2.min.css` por **`<link>` crudo**, es decir **sin capa** — y lo no
   capado gana a todas las capas en declaraciones normales.

**Borrar el override no basta. Hay que meter el vendor en su capa.**

#### La vía sancionada ya existe en el repo

`design-system/entrypoints/attach-select2.css` hace exactamente lo que falta:

```css
@import url("/public/vendor/select2/select2.min.css") layer(vendor);
@import url("/css/design-system/adapters/select2.css?v=1.0.0");
```

Está cableada en `DesignSystemHeadComponent::VENDOR_ATTACHMENTS` (`'select2' => …`), ruteada en
`public/index.php:291` y declarada pública. Los vendors se piden desde el **manifiesto del módulo**,
que `renderForModule()` lee.

**Precedente idéntico y ya autorizado en esta fase:** el tramo 5a retiró cuatro `<link>` crudos
duplicados a `handsontable-module.css` por este mismo motivo, y la propia
`programacion_semanal.view.php:37` ya lo había hecho antes con el comentario «el link crudo duplicaba
la cascada».

#### El trabajo

1. Declarar `select2` en el manifiesto del módulo de Programación Semanal y **retirar el `<link>`
   crudo** de la vista.
2. Comprobar que el adaptador pasa a pintar: el panel, el buscador, las opciones y el resaltado.
3. **Sólo entonces**, retirar de `styles.css:1472-1553` lo que quede duplicado, y tokenizar lo que
   siga vivo. Los 7 literales: `#24313a` (tinta del valor), `#7a8790` (placeholder), dos `#fff`
   (panel y buscador), dos `rgba(104,116,125,0.24)` (bordes) y `rgba(36,49,58,0.14)` (sombra).

#### Trampas propias de este tramo

- **El desplegable no existe en reposo.** Select2 crea `.select2-dropdown` al abrirlo, y lo monta
  **fuera del modal**, en el `<body>`. Ábrelo antes de medir y autovalida la sonda.
- **`:where()` en el adaptador tiene especificidad (0,0,0).** Una vez el vendor esté capado ya no
  importa —la capa decide antes que la especificidad—, pero **cualquier regla de autor sin capa** le
  ganaría. Verifica que no queda ninguna.
- **Fondo y texto se reasignan juntos.** El panel blanco lleva tinta oscura; al oscurecer el panel hay
  que comprobar la tinta en la misma pasada.
- **Otras rutas usan select2.** `#modalEditarContratos` tiene su propio bloque en `styles.css:4311+`.
  Si tocas el manifiesto de un módulo, comprueba que no cambias las demás rutas por debajo.

---

### ~~Task final: borrar el archivo~~ — **DEROGADA por decisión del usuario (2026-07-28)**

> **No se ejecuta.** `public/css/styles.css` **sobrevive**, reducido a las excepciones adjudicadas, y
> **sus dos `@import` se quedan**. Con ella queda derogada la métrica «líneas de `styles.css`:
> 6.802 → 0» de la tabla de abajo.

**Por qué.** El desmantelado por tramos (5a → 5m, dieciséis tramos, todos con review) estaba acotado a
**fuga de color y tokenización**, que es lo que el spec pedía, y eso está hecho: de **483 hex y 108
`rgba()`** el archivo bajó a **10 y 6**, y los 16 que quedan **no son deuda**, son excepciones
adjudicadas una a una con censo propio de la review — no existe token equivalente, o la sombra no
tiene peldaño en la escalera, o cambiarlas sería rediseño que el propio spec prohíbe.

Lo que queda en el archivo ya **no es color**: son ~4.300 líneas de **layout y geometría** —anchos de
columna de DataTables, barras de scroll, maquetación de tablas densas, presets de tamaño—. Borrarlo
descolocaría la aplicación entera. Migrar ese layout es un trabajo **distinto**, con otro perfil de
riesgo, y merece su propia fase y su propio plan; meterlo aquí sería colarlo por la puerta de atrás
de una fase de dark mode.

**Lo único que sí se conserva de esta tarea** es su Step 4, que ya no es «final» sino condición de
cierre: `goals/dark-mode-todos-los-modulos/validation-log.md` **no puede quedar con entradas
abiertas**. Esa exigencia sigue viva y es lo que gobierna el cierre de F1.

## Métricas de progreso

**Criterio revisado el 2026-07-28** (ver la Task final derogada): el objetivo deja de ser «el archivo
no existe» y pasa a ser **«cero literales de color salvo excepciones adjudicadas»**.

| Métrica | Al abrir | Objetivo revisado | **Real al cerrar** |
|---|---|---|---|
| Líneas de `styles.css` | 6.802 | ~~0~~ derogado | **4.382** (−36 %) |
| `hardcoded-hex` en `styles.css` | 483 | sólo excepciones | **10** (−98 %) |
| `rgba(` en `styles.css` | 108 | sólo excepciones | **6** (−94 %) |
| Rutas rojas en el guardián de canvas | 5 | 0 | **0** ✅ |
| Total del audit | 7.161 | por debajo, nunca por encima | **5.785** (−19 %) |

**Las 16 excepciones supervivientes no son deuda pendiente**, y ésa es la diferencia que justifica el
criterio nuevo. Cada una tiene motivo medido y adjudicado por una review con censo propio:

- **4 `--tipo-brand`** y **3 iconos de estado de `/pdc`** — verificado por grep sobre todo
  `public/css/`: **no existe token equivalente** en la paleta del DS.
- **`--primary`** — tiene consumidores vivos.
- **5 sombras** — ningún peldaño de la escalera `--ds-shadow-*` conserva su geometría, y moverla es
  diseño, que el spec prohíbe.
- **El resto**, literales cuyo token equivalente rasteriza a un valor distinto (el defecto de
  comentarios de `tokens.css` que midió el tramo 5c).

`unauthorized-important` sale de la tabla: se midió que **se redistribuye** entre archivos en vez de
bajar, así que como métrica de progreso engaña. Lo honesto es `hardcoded-hex`, que no está exenta en
ninguna raíz escaneada.
