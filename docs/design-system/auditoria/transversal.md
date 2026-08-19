# Lo transversal — el sistema, sus hojas compartidas y sus gates

Lo que no pertenece a ningún módulo: las hojas que todos cargan, los tokens, los entrypoints y los
mecanismos que deberían detectar la deuda. **Es donde está lo crítico.**

## Los 1 520 `!important`, y por qué contarlos nunca sirvió

El conteo agregado es el dato que se venía usando y no distingue lo que hay que distinguir. Al
clasificar cada uno por la **familia del selector al que apunta**:

| A qué apunta | Cuántos | % | Qué significa |
|---|---:|---:|---|
| CSS de proveedor (Handsontable 449, DataTables 230, Bootstrap/AdminLTE 168, Tom Select 22, SweetAlert2 21, Select2 13) | **903** | 59% | Es la salida prevista. El contrato exime a los adaptadores por esto exactamente |
| Selectores propios del módulo | **543** | 36% | No hay proveedor al que ganarle: es cascada rota adentro. **No la arregla ningún adaptador** |
| Primitivas `aia-*` | **74** | 5% | El sistema pisándose a sí mismo |

Las dos últimas filas son deuda que se puede cerrar **sin tocar a ningún proveedor**, y son el 41%.
Esa es la cifra útil, y no existía. → `F0-120`

**Aviso sobre los 74 contra `aia-*`: no todos son iguales.** Quince están en
`public/css/design-system/core.css` y son las seis declaraciones de `.aia-visually-hidden`
(líneas 373-378), donde `!important` es el patrón canónico correcto —una clase de ocultado accesible
tiene que ganar siempre. Los doce de `components/navigation.css` sí son cascada interna. Contarlos
juntos habría producido el mismo error que esta sección viene a corregir. → `F0-121`

## Cinco archivos con doble encapsulado de capa

`C-15` del registro del 3-ago midió esto en `buttons.css` y sigue en pie, ampliado:

| Archivo | Capa del `@import` | Capa que abre dentro | Dónde acaban sus reglas |
|---|---|---|---|
| `styles.css` | `layer(module)` | `@layer theme, layout, components` | `module.components` |
| `buttons.css` | `layer(components)` | `@layer components` | `components.components` |
| `access.css` | `layer(utilities)` | `@layer utilities` | `utilities.utilities` |
| `handsontable-module.css` | `layer(vendor)` | `@layer` propio | anidado bajo `vendor` |

Un sub-escalón anidado pierde contra las reglas planas de su capa madre. Ninguno de los cuatro lo
decidió nadie: el `@import` pone la capa y el archivo abre la suya sin saberlo. → `F0-122`

### Y uno que se contradice a sí mismo tres veces

`public/css/handsontable-header-global.css` dice en su línea 6:

```
* ESTE ARCHIVO NO LLEVA @layer: Si se encapsula perdería contra el vendor.
```

y lo repite en la 132 («vive fuera de @layer a proposito para no perder la pelea»). Es cierto que el
archivo no abre ninguna capa. Pero **el agregador sí lo encapsula**:

```css
/* public/css/aia-design-system.css:11 */
@import url("/css/handsontable-header-global.css?v=1.1.0") layer(vendor);
```

Funciona hoy —entra en `vendor`, la misma capa que el CSS de Handsontable, después que él, y usa
`!important` en sus 65 reglas— pero **no por la razón que el comentario afirma**. Quien reordene los
`@import` romperá la hoja y leerá un comentario que le dice que el archivo está fuera de capa.
→ `F0-123`

## Tokens: 600 definidos, cinco rotos

De los 600 tokens definidos en las 66 hojas, hay **cinco que se consumen y nadie define**, ni en CSS
ni en JavaScript. Es la trampa que `memoria/trampas/gate-estatico-no-ve-tokens-rotos` describe: un
gate que lee archivos da verde con un token que apunta a nada, porque el valor resuelto solo existe
en el navegador.

| Token | Dónde se usa | ¿Fallback? | Efecto real |
|---|---|:-:|---|
| `--ds-active-text-tertiary` | `login-brand-unified.css:148`, `programa-general-actualizar.css:450` y `:588` | **no** | La declaración se descarta: `color` hereda, `border-color` toma `currentColor`, `background` queda transparente |
| `--ds-font-size-sm` | `adapters/tom-select.css:11` | **no** | `font-size` inválido: se descarta, con `!important` y todo |
| `--ds-opacity-disabled` | `components/navigation.css:331` | **no** | `opacity` inválida: el estado deshabilitado no se atenúa |
| `--ds-active-surface-hover` | `adapters/tom-select.css:40` | sí (`--ds-table-row-hover`) | Cae al fallback; benigno |
| `--ds-context-bar-height` | `adapters/shell-sidebar.css:48` | sí (`2.5rem`) | Cae al fallback; benigno |

Los tres primeros son `mayor`: hay tres propiedades que el autor escribió y el navegador no aplica,
en tres superficies distintas, y `--ds-active-text-secondary` —el hermano que sí existe— demuestra
que el nombre era el equivocado, no el concepto. → `F0-124`

**`--hot-table-width` NO está en esta lista** aunque ningún CSS lo defina: lo escribe
`public/js/modules/aia_ui/hot_table_width.js:66` con `style.setProperty` en tiempo de ejecución. Un
censo que solo mirara CSS lo habría reportado como roto. → `F0-125`, `sin-problema`

## Color crudo en las hojas que todos cargan

`tokens.css` tiene 64 hex y es exactamente donde deben estar: es la capa que los define, con 337
tokens declarados y cero `!important`. Fuera de ahí:

- **`buttons.css`: 31 hex, y treinta y uno son `color: #ffffff`.** Conviven con variantes que sí
  hacen lo correcto en la línea de al lado — `background-color: var(--aia-green-primary, …)` y
  debajo `color: #ffffff`. Blanco literal como color de texto en un producto cuyo único tema es
  dark, con `--ds-color-text-inverse` ya definido. → `F0-126`
- **`styles.css:398-406`: tres iconos de estado en hex** (`#c22f2f` rojo, `#c2ac2f` ámbar,
  `#48c22f` verde) donde la escalera de estado del sistema ya tiene las tres familias. → `F0-127`
- **`styles.css:1486-1501`: cuatro primitivas `aia-*` definidas con la paleta de Bootstrap 4.**
  `.aia-tipo-pill--s` declara `--tipo-brand: #6c757d`, `--mo` usa `#17a2b8`, `--si` usa `#007bff` y
  `--oc` usa `#343a40`: son `secondary`, `info`, `primary` y `dark` de Bootstrap, literales. Clases
  con el prefijo del sistema, coloreadas con la paleta de un proveedor. → `F0-128`
- **Nueve `white` literales en `styles.css`** (`background: white`, `color: white`). → `F0-129`

## Los gates: dos que no pueden ver, uno que no corre

Esta es la parte crítica del inventario, y las tres son de **mecanismo**, no de código.

### El baseline dobla la deuda real

```
$ node scripts/design-system-audit.mjs
totalViolations: 3896        # deuda medida hoy
$ audit-baseline.json → totalViolations: 7161, generatedAt: 2026-07-25
```

**3 265 hallazgos de margen ocioso.** Regla a regla, lo que se puede introducir sin que nada se ponga
rojo:

| Regla | Real | Baseline | Margen |
|---|---:|---:|---:|
| `unauthorized-important` | 1 447 | 2 166 | +719 |
| `hardcoded-hex` | 110 | 806 | **+696** |
| `hardcoded-color-function` | 208 | 547 | +339 |
| `raw-token-in-module` | 458 | 791 | +333 |
| `css-outside-layer` | 504 | 829 | +325 |
| `off-scale-spacing` | 457 | 733 | +276 |
| `off-scale-typography` | 221 | 402 | +181 |
| `local-vendor-override` | 137 | 237 | +100 |

`hardcoded-hex` puede multiplicarse por siete —de 110 a 806— y el gate seguirá en verde. La nota del
propio baseline dice «reducir por modulo migrado; no aumentarlo sin justificacion»: desde el 25 de
julio se migraron trece módulos y no bajó una sola vez. → `F0-030`

### Una regla que el presupuesto no nombra no se evalúa

`scripts/design-system-audit.mjs:369` es
`for (const [rule, allowed] of Object.entries(budget.maxViolations))`. **Itera las claves que hay.**
Una regla ausente no produce comprobación: no es un cero implícito, es silencio.

De los 18 presupuestos de `exceptions.json`, **quince no nombran** `unauthorized-important`,
`off-scale-spacing`, `off-scale-typography`, `off-scale-shadow`, `local-vendor-override` ni
`duplicate-canonical-primitive`. Programación Semanal, con 435 `!important`, **no tiene techo por
módulo** para esa regla; lo único que la contiene es el baseline global, que es el de arriba.

El único presupuesto que declara las doce reglas es `programacion-intermedia`, y por eso parece el
módulo con más deuda: es el único donde la deuda está **contada**. → `F0-031`

### El gate del Plan de Compras comprueba dos cosas y no lo ejecuta nadie

`scripts/design-system-audit.mjs:25` declara
`scanRoots = ['views', 'public/js', 'public/css', 'src/View/Components', 'admin']`. **`pdc-app/` no
está**, ni la fuente ni el bundle: ninguno de los 3 896 hallazgos viene del Plan de Compras.

Su gate propio, `scripts/design-system-plan-compras-gate.mjs`, comprueba (a) que el bundle no
redefina `--ds-active-bg-canvas` en un `:root` sin capa y (b) que la cadena `var(--ds-` aparezca **al
menos una vez**. Y no está cableado en ningún script de `package.json` ni en
`.github/workflows/design-system.yml`: las únicas referencias vivas son dos documentos que cuentan
que se corrió a mano al cerrar un goal en julio. → `F0-051`, `F0-052`

## Estructura de documento

`C-30` midió en agosto que solo tres pantallas declaraban `<main>`. Hoy, de los 56 archivos de vista:
**21 declaran `<main>` y 22 declaran `<h1>`**. La corrección alcanzó a la app y **no alcanzó a
`admin/`**, donde trece de catorce vistas dependen del `<main>` del layout y once no tienen `<h1>`
propio (`F0-101`).

## Sobre las herramientas de esta auditoría

Tres límites que hay que decir para que las cifras se puedan usar:

1. **El escáner de CSS separa hex en código de hex en comentario** —155 contra 68— porque el gate
   del repositorio no lo hace y esa es la trampa que
   `memoria/trampas/audit-ve-color-en-comentarios` documenta. Las cifras de este directorio son de
   código.
2. **El clasificador de `!important` lee el texto del selector.** No resuelve el DOM: `td` dentro de
   `#hot-container` se cuenta como Handsontable porque ahí un `td` es una celda de la grilla, y eso
   es una regla escrita, no una deducción. El muestreo manual de 20 clasificaciones encontró dos
   errores —`.btn-filter-toggle` y `.btn-pdc-modern` leídos como Bootstrap por el prefijo `.btn-`—
   que están corregidos en las cifras de arriba.
3. **El escáner de vistas lee archivos, no HTML rendido.** Por eso los ocho ids «duplicados» de
   Programa General son falso positivo (`F0-011`) y por eso la deuda de estilo en línea que vive en
   blobs de HTML dentro de JavaScript no aparece en su tabla (`F0-042`).
