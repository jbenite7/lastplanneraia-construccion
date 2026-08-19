# Módulos · Profesionales, Subcontratistas y Control de Cambios

Se auditan juntos porque comparten forma —una tabla de gestión sobre el shell canónico— y porque
los tres dan el mismo resultado: **son la parte sana del sistema**. Se registran a propósito: un
inventario que solo lista deuda no dice dónde no la hay, y esa mitad del mapa es la que DS-F1
necesita para saber qué ya funciona.

- **Profesionales** — `pilot` (`profesionales.json`), `/profesionales`, con escenario.
- **Subcontratistas** — `pilot` (`subcontratistas.json`), `/subcontratistas`, con escenario.
- **Control de Cambios** — `pilot` (`control-cambios.json`), `/control-cambios`, con escenario.

## Profesionales

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/profesionales.css` | 173 | 1 | 0 | 0 | sí | 19 |

### A qué apunta cada uno de los 1 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 1 | 100% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/profesionales/profesionales.view.php` | 575 | 0 | 0 | ✓ | ✓ | 0 | 10 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 5 selectores

## Subcontratistas

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/subcontratistas.css` | 183 | 1 | 0 | 0 | sí | 19 |

### A qué apunta cada uno de los 1 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| propio-del-modulo | 1 | 100% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/subcontratistas/subcontratistas.view.php` | 611 | 0 | 0 | ✓ | ✓ | 0 | 10 |

## Selectores de vendor que este módulo toca

- `bootstrap-adminlte` — 5 selectores

## Control de Cambios

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/control-cambios.css` | 64 | 0 | 0 | 0 | sí | 5 |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/control-cambios/controlCambios.view.php` | 925 | 0 | 0 | ✓ | ✓ | 0 | 12 |

## Lectura

Los tres: **cero hex, cero `rgb()` crudo, cero estilo en línea, cero bloques `<style>`, cero ids
duplicados, `@layer` declarado, `<main>` y `<h1>` presentes, y presupuesto cero en todas las reglas
que declaran**. Entre los tres suman **dos** `!important` en 420 líneas de CSS.

Control de Cambios es el caso extremo: **64 líneas de CSS propio, ningún `!important`, cinco
tokens**. Una vista de 925 líneas con doce primitivas `aia-*` y una hoja de estilo que casi no
existe es exactamente lo que el contrato de migración por módulo describe. → `F0-060`,
severidad `sin-problema`

### El único hallazgo pendiente de los tres es de copy, no de CSS

`F-6` de `docs/DESIGN-AUDIT.md` sigue en pie y se confirma en el código:

```php
// views/control-cambios/controlCambios.view.php:729-731
"sEmptyTable": "Las solicitudes de cambio nacen en obra: cuando el diseño, el cliente o la
                interventoría piden algo distinto de lo contratado, regístralo aquí…",
"sInfoEmpty":  "Sin solicitudes",
```

DataTables pinta los dos: el estado vacío largo y, justo debajo, «Sin solicitudes», que no añade
nada y le quita fuerza al primero. El registro anota que la ratificación de la frase larga **ya
llegó** (2026-08-10) y que `F-6` seguía sin aplicar. Sigue sin aplicar, y aquí tampoco se aplica.
→ `F0-061`

## Lo que no se pudo medir aquí

Los estados `hover`, `focus` y `selección` de las tres tablas los pinta DataTables, y su apariencia
final depende de la hoja de vendor y de los adaptadores — no de estos tres archivos. Se audita en
la ficha del vendor. → `vendors/ds-f0-datatables.md`
