---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-programacion-intermedia.md
resumen: Módulo · Programación Intermedia
---

# Módulo · Programación Intermedia

**Estado declarado:** `pilot` (`programacion-intermedia.json`) · **Pantalla:**
`/programacion-intermedia` · **Escenario:** sí

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/programacion-intermedia.css` | 1662 | 51 | 5 | 2 | sí | 70 |

### A qué apunta cada uno de los 51 `!important`

| Familia del selector | Cuántos | % |
|---|---:|---:|
| handsontable | 22 | 43% |
| propio-del-modulo | 12 | 24% |
| bootstrap/adminlte | 10 | 20% |
| primitiva-aia | 7 | 14% |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/programacion-intermedia/programacion_intermedia.view.php` | 372 | 1 | 0 | ✓ | ✓ | 0 | 13 |

## Selectores de vendor que este módulo toca

- `handsontable` — 34 selectores
- `bootstrap-adminlte` — 13 selectores

## Lectura

**Es el único módulo del repositorio con presupuesto de deuda distinto de cero en doce reglas.**
De los 18 presupuestos de `exceptions.json`, quince están a cero en todo; `programacion-intermedia`
tolera 428 violaciones — el 68% de toda la deuda tolerada por módulo del repositorio:

| Regla | Tolerado |
|---|---:|
| `unauthorized-important` | 175 |
| `off-scale-spacing` | 82 |
| `off-scale-typography` | 47 |
| `hardcoded-hex` | 45 |
| `local-vendor-override` | 39 |
| `hardcoded-radius` | 27 |
| `hardcoded-color-function` · `off-scale-shadow` | 5 · 5 |
| `inline-style` · `duplicate-canonical-primitive` | 3 · 1 |

Eso no es un defecto por sí solo —un presupuesto es deuda **declarada**, que es mejor que deuda
callada— pero sí explica por qué el gate está verde con lo que sigue. → `F0-031`

### Cinco hex de la paleta clara, con `!important`, en un módulo dark

`public/css/programacion-intermedia.css:1319-1331`, bajo el comentario
`/* programacion_intermedia.view.php style block 2 */` —o sea, migrados desde un bloque `<style>` de
la vista sin tokenizar por el camino:

```css
/* Brand Manual AIA: Green (#1a5633) para acciones de creación */
.pi-create-option {
  background-color: #e8f0eb !important;   /* verde muy claro */
  color: #1a5633 !important;
  border-top: 1px solid #c8ddd0 !important;
}
.pi-create-option:hover {
  background-color: #1a5633 !important;
  color: #fff !important;
}
```

Es un fondo casi blanco con texto verde oscuro, fijado con `!important`, en el único tema que el
producto implementa, que es dark. El verde de marca existe como token. → `F0-032`

### Los dos `rgb()` crudos

- `:262` — `box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.24)` en `.pi-soft-restriction-cell`.
  Es **`F-9` de `docs/DESIGN-AUDIT.md`**, todavía en pie: el mismo ámbar ya existe como `--pi-due-bg`,
  y el censo de color del gate no lo persigue porque va dentro de un `box-shadow`. → `F0-033`
- `:378` — `box-shadow: -20px 0 40px rgba(15, 23, 42, 0.18)`, sombra de panel con un azul de la
  paleta clara. Mismo mecanismo, no registrado en ninguna fuente anterior. → `F0-034`

### El resto

7 de los 51 `!important` apuntan a primitivas `aia-*`. El `style="display:none;"` de
`views/programacion-intermedia/programacion_intermedia.view.php:92` está dentro del presupuesto de
3 `inline-style` y es un ocultado inicial, no estilo visual. → anotado, `cosmetico`.

## Lo que no se pudo medir aquí

`hover`, `focus` y `selección` sobre la grilla, y el escenario de restricción compartida, los pinta
`public/js/modules/programacion_intermedia/hot.js`. → `bloqueadoPor: runtime-budgets-al-ci`
