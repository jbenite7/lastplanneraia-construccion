# Módulo · Programa General

**Estado declarado:** `pilot` (`programa-general.json`) · **Pantalla:** `/programa-general`
· **Escenario:** sí (`programa-general-dark-1180x820`)

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/programa-general.css` | 626 | 3 | 0 | 0 | sí | 69 |
| `public/css/design-system/adapters/programa-general-handsontable.css` | 191 | 40 | 0 | 0 | sí | 27 |

### A qué apunta cada uno de los 43 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| handsontable | 40 | 93% |
| primitiva-aia | 3 | 7% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/programa-general/programa_general.view.php` | 155 | 0 | 0 | ✓ | ✓ | 8 | 16 |

## Selectores de vendor que este módulo toca

- `handsontable` — 29 selectores

## Lectura

**Es de los módulos más sanos del recorrido.** Cero hex sueltos, cero `rgb()` crudo, cero estilo en
línea, las dos hojas dentro de `@layer`, `<main>` y `<h1>` presentes, y 16 primitivas `aia-*` en uso
real. La hoja propia gasta **3** `!important` en 626 líneas.

Los **40 restantes viven en el adaptador de Handsontable**, y **36 apuntan al vendor**: eso es
exactamente para lo que existe un adaptador, y el contrato lo exime a propósito
(`docs/design-system/README.md` §Gate: «solo los adaptadores en `public/css/design-system/adapters/`
están exentos porque deben fijar la prioridad frente a CSS de proveedores»). No es deuda.

Lo que queda anotado:

- **Los 8 ids «duplicados» que detecta el escáner son falso positivo**, y se deja escrito para que
  nadie los persiga: `pgLegend` y los siete `count-*` aparecen dos veces en el archivo porque viven
  en las dos ramas de un `if/else` de PHP (`$area === 'Pre-Construccion'`), excluyentes en el HTML
  rendido. Un escáner que lee el archivo no puede distinguirlo; verificado a mano en
  `views/programa-general/programa_general.view.php:76-95`. → `F0-011`, severidad `sin-problema`
- **Esas dos ramas nombran el mismo estado de siete formas distintas**: «Con Restricción Pendiente /
  Por Iniciar / En Ejecución / Completada» en Pre-Construcción contra «Con Alerta Restricciones /
  Debe Iniciar / En Curso / Terminada» en el resto. Mismos `data-filter`, mismo color, dos
  vocabularios. **Se registra y no se decide**: el vocabulario es DS-F1. → `F0-012`

## Lo que no se pudo medir aquí

Los escenarios `hover`, `focus`, `selección` y `error` de la grilla los pinta
`public/js/modules/programa_general/hot.js` en tiempo de render, y su resultado solo se ve resuelto
en navegador. La lectura estática no los alcanza. → `bloqueadoPor: runtime-budgets-al-ci`
