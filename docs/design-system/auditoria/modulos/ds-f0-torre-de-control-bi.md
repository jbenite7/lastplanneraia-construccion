---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system, rbac, bi]
fuente: docs/design-system/auditoria/modulos/ds-f0-torre-de-control-bi.md
resumen: Módulo · Torre de Control (BI)
---

# Módulo · Torre de Control (BI)

**Estado declarado:** `pilot` (`bi-runtime.json`) · **Pantallas:** ocho (`/bi/control-tower`,
`/bi/curva-s`, `/bi/semanal`, `/bi/programa-general`, `/bi/intermedia`, `/bi/pdc`,
`/bi/contratistas`, `/bi/responsables`) · **Escenario:** tres de las ocho

Es el módulo con **más pantallas del repositorio** y el que más superficie deja sin escenario.

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/bi-control-tower.css` | 2042 | 51 | 0 | 0 | sí | 83 |
| `public/css/bi-filter-drawer.css` | 160 | 0 | 0 | 0 | sí | 27 |
| `public/css/design-system/adapters/bi-utilities.css` | 507 | 0 | 0 | 0 | sí | 4 |

### A qué apunta cada uno de los 51 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 38 | 75% |
| primitiva-aia | 10 | 20% |
| bootstrap/adminlte | 3 | 6% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/bi/_filters.php` | 69 | 0 | 0 | — | — | 0 | 7 |
| `views/bi/_layout.php` | 85 | 0 | 0 | ✓ | ✓ | 0 | 4 |
| `views/bi/_nav.php` | 33 | 0 | 0 | — | — | 0 | 0 |
| `views/bi/control-tower.php` | 719 | 0 | 0 | — | — | 0 | 3 |
| `views/bi/index.php` | 6 | 0 | 0 | — | — | 0 | 0 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 4 selectores

## Lectura

Cero hex, cero `rgb()` crudo, cero estilo en línea, 83 tokens consumidos y presupuesto `bi-runtime`
a cero en todas las reglas que declara. La deuda es de **forma**, no de color.

### 2 041 líneas en un archivo, y 38 de sus 51 `!important` van contra sí mismo

`bi-control-tower.css` es la segunda hoja más grande del repositorio. De sus 51 `!important`, solo
**tres** pelean contra Bootstrap: **38 apuntan a selectores propios** y **10 a primitivas `aia-*`**.
Como en Programación Semanal, ahí no hay proveedor al que ganarle. → `F0-090`

### Un sistema de utilidades paralelo, que es una victoria y una deuda a la vez

`public/css/design-system/adapters/bi-utilities.css` son **88 utilidades tipo Tailwind** dentro de
`@layer utilities`. Su cabecera cuenta bien por qué existe, y es una historia de éxito:
`views/bi/_layout.php` cargaba `https://cdn.tailwindcss.com`, un script que compila en el navegador
e **inyecta su salida en un `<style>` sin capa**, derrotando a las nueve capas del sistema en las
ocho rutas de BI —y cuyo preflight (`*{border-width:0}`, `h1..h6{font-size:inherit}`) rompía las
cinco primitivas `aia-*` de la superficie. Sustituirlo por 88 utilidades transcritas, con los
colores pasados a tokens, cerró un agujero grande.

La deuda que queda es la otra cara: **el markup de BI se escribe con dos vocabularios a la vez**.
`views/bi/_nav.php:6` es
`class="bi-tabs-nav flex items-center gap-1 flex-shrink-0 overflow-x-auto whitespace-nowrap"` —una
clase propia, cinco utilidades de la gramática de Tailwind, cero primitivas `aia-*`— y esa vista no
usa ninguna. Un módulo `pilot` con 88 utilidades ajenas al catálogo es un segundo sistema conviviendo
con el primero. **Qué se hace con eso es DS-F1**, no esta fase. → `F0-091`

### Cuatro de las cinco vistas no declaran `<main>` ni `<h1>`

`_layout.php` los declara y es el que envuelve al resto, así que el HTML rendido los tiene una vez.
Se anota porque el conteo por archivo, leído solo, sugeriría lo contrario. → `F0-092`,
`sin-problema`

### Lo que la semilla ya midió aquí y sigue sin cerrar

- **`C-23`** — a 1180×820 las ocho pestañas suman 1 626 px en un carril de 1 116: tres quedan fuera
  de vista («Plan de Compras», «Proveedores (CIC)», «Responsables (CIP)»). El carril declara
  `overflow-x-auto`, así que hay scroll; lo que no hay es señal de que exista.
  → `F0-093`, `estimado: true`, `bloqueadoPor: runtime-budgets-al-ci`
- **`F-4`** — el botón «Quitar filtro» de los chips mide 28×20 px en las ocho vistas, cuatro por
  debajo del piso de 24 en el eje corto (WCAG 2.5.8). → `F0-094`, `bloqueadoPor: runtime-budgets-al-ci`
- **`C-32`** — los gráficos traen `aria-label` correcto y su contenido no se lee: un `<canvas>` no
  expone datos. → `F0-095`

Las tres son mediciones de navegador. Se registran con su origen y **no se re-miden aquí**: sin
carril de gates sano, repetirlas no diría si el problema es del módulo o del medidor.

### La paleta de los gráficos es el mejor caso del repositorio

`public/js/modules/bi_chart_theme.js` tiene 21 hex y **ninguno es deuda**: son los fallbacks de seis
tokens, con el valor que Canvas pinta de verdad —no el aproximado del comentario de `tokens.css`,
porque los tokens se declaran en `oklch()` y el gamut-mapping difiere por motor— y validados con
`node scripts/validate_palette.js … --mode dark` contra banda de luminosidad, piso de croma,
separación CVD y contraste ≥ 3:1. Su presupuesto `bi-chart-theme-fallbacks` tolera exactamente 15
hex y 3 funciones de color, que es la forma correcta de declarar esto. → `F0-096`, `sin-problema`

## Lo que no se pudo medir aquí

Cinco de las ocho pantallas no tienen escenario (`/bi/contratistas`, `/bi/intermedia`, `/bi/pdc`,
`/bi/programa-general`, `/bi/responsables`), y los estados `cargando`, `error` y `selección` de los
filtros y los gráficos son de runtime. → `F0-097`, `bloqueadoPor: runtime-budgets-al-ci`
