---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/modulos/ds-f0-laboratorio-design-system.md
resumen: Módulo · Laboratorio del design system
---

# Módulo · Laboratorio del design system

**Estado declarado:** `laboratory.json` existe y **no tiene fila en `modules[]`**, así que el módulo
no declara `status` (`F0-002`) · **Pantalla:** `/internal/design-system` · **Escenario:** sí

## Hojas de estilo

| Archivo | Líneas | `!important` | hex en código | `rgb()`/`hsl()` | `@layer` | tokens que consume |
|---|---:|---:|---:|---:|---:|---:|
| `public/css/design-system/lab-entrypoint.css` | 20 | 0 | 0 | 0 | sí | 0 |
| `public/css/design-system/lab.css` | 1966 | 0 | 0 | 0 | sí | 84 |
| `public/css/design-system/laboratory-foundation.css` | 47 | 0 | 0 | 0 | sí | 19 |

## Vistas

| Archivo | Líneas | `style=` | `<style>` | `<main>` | `<h1>` | ids duplicados | primitivas `aia-*` |
|---|---:|---:|---:|:-:|:-:|---:|---:|
| `views/design-system/families/actions.php` | 33 | 0 | 0 | — | — | 0 | 4 |
| `views/design-system/families/bi-primitives.php` | 87 | 0 | 0 | — | — | 0 | 19 |
| `views/design-system/families/data-display.php` | 83 | 0 | 0 | — | — | 0 | 4 |
| `views/design-system/families/forms-filters.php` | 53 | 0 | 0 | — | — | 0 | 10 |
| `views/design-system/families/foundations.php` | 32 | 0 | 0 | — | — | 0 | 4 |
| `views/design-system/families/overlays.php` | 21 | 0 | 0 | — | — | 0 | 0 |
| `views/design-system/families/page-structure.php` | 20 | 0 | 0 | — | — | 0 | 3 |
| `views/design-system/families/shell-navigation.php` | 66 | 0 | 0 | — | — | 0 | 5 |
| `views/design-system/families/states-feedback.php` | 90 | 1 | 0 | — | — | 0 | 9 |
| `views/design-system/families/vendor-adapters.php` | 67 | 0 | 0 | — | — | 0 | 16 |
| `views/design-system/lab.view.php` | 109 | 0 | 0 | ✓ | ✓ | 0 | 7 |
| `views/design-system/operational-fixtures.php` | 142 | 0 | 0 | — | — | 0 | 15 |
| `views/design-system/ui-group-index.php` | 18 | 0 | 0 | — | — | 0 | 0 |

## Selectores de vendor que este módulo toca

- `select2` — 15 selectores
- `tom-select` — 13 selectores
- `handsontable` — 6 selectores
- `sweetalert2` — 3 selectores

## Lectura

**Cero `!important`, cero hex, cero `rgb()` crudo en 2 033 líneas de CSS.** Es el único conjunto de
hojas del repositorio con las tres cifras en cero, y `lab.css` es la hoja más grande del proyecto
después de `bi-control-tower.css`. La regla `noImportantStyles` de Biome está activa para el
laboratorio y ninguna de estas hojas está exenta, así que ese cero **está defendido por un gate**,
no es una casualidad. Es el mejor material de referencia que tiene el sistema. → `F0-110`,
`sin-problema`

Las trece vistas de familia no declaran `<main>` ni `<h1>` porque son parciales que compone
`lab.view.php`, que sí los declara. → `sin-problema`.

### El único `style=` del laboratorio

`views/design-system/families/states-feedback.php` tiene un atributo `style=`. Es la familia que el
README ya señala como excepción: sus dos escenarios **validan geometría, contraste y overflow sin
snapshot**, y sus recortes son de los dos únicos escenarios `element` de la lista blanca
`ELEMENT_CAPTURE_ALLOWLIST`. La familia que concentra las excepciones del contrato de evidencia es
también la única con estilo en línea. → `F0-111`, `cosmetico`

### Lo que este módulo revela sobre el sistema, no sobre sí mismo

El laboratorio consume 84 tokens y demuestra 10 familias. Comparado con el resto del inventario, la
distancia no está en la calidad de las primitivas: **está en que los módulos no las usan**.
`escalamientos` tiene 2 primitivas en 170 líneas de vista, las 14 de `admin/` tienen 2 cada una, y
`views/bi/_nav.php` tiene 0 y cinco utilidades de Tailwind. El catálogo existe, está limpio y está
probado; el consumo es el que falta. → `F0-112`

## Lo que no se pudo medir aquí

El gate visual del laboratorio consume **18 goldens** (nueve familias × dos viewports) y
`states-feedback` conserva dos contratos visuales **sin golden**. Si esos 18 están hoy en verde o en
rojo es una cifra de ejecutar el gate. → `bloqueadoPor: runtime-budgets-al-ci`
