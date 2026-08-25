---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/vendors/ds-f0-handsontable.md
resumen: Va en ficha aparte porque su patrón es distinto: no es un módulo que se desvía del contrato, es un sistema entero que nunca entró.
---

# Vendor · Handsontable

Va en ficha aparte porque **su patrón es distinto**: no es un módulo que se desvía del contrato, es
un sistema entero que nunca entró.

## El censo: 63 de los 85 selectores del vendor no los alcanza ninguna hoja nuestra

```
$ grep -oE "\.(ht|handsontable|wt)[A-Za-z0-9_-]*" public/vendor/handsontable/handsontable.full.min.css | sort -u | wc -l
85
$ # los mismos, buscados en las ocho hojas nuestras que tocan la grilla
22
```

Los **63 sin alcanzar** no son residuos: son la mitad de la superficie interactiva de la grilla.

| Familia | Selectores sin alcanzar |
|---|---|
| **Menú de filtros** (15) | `.htFiltersActive`, `.htFiltersMenuActionBar`, `.htFiltersMenuCondition`, `.htFiltersMenuLabel`, `.htFiltersMenuOperators`, `.htFiltersMenuValue`, `.htMenuFiltering`, `.htUIMultipleSelect`, `.htUIMultipleSelectSearch`, `.htUISelectAll`, `.htUIClearAll`, `.htUISelectCaption`, `.htUISelectDropdown`, `.htUISelectionControls`, `.htUISelect` |
| **Controles de UI** (7) | `.htUIButton`, `.htUIButtonOK`, `.htUIInput`, `.htUIInputIcon`, `.htUIRadio`, `.htCheckboxRendererLabel`, `.htSelectEditor` |
| **Menú contextual** (4) | `.htContextMenu`, `.htSubmenu`, `.htSeparator`, `.htCustomMenuRenderer` |
| **Comentarios** (5) | `.htComments`, `.htCommentCell`, `.htCommentTextArea`, `.htCommentsContainer`, `.htItemWrapper` |
| **Estados de celda** (6) | `.htInvalid`, `.htDisabled`, `.htPlaceholder`, `.htSearchResult`, `.ht__highlight`, `.ht__active_highlight` |
| **Anidamiento y movimiento** (14) | `.ht_nesting*` (6), `.ht__manualColumnMove*` (3), `.ht__manualRowMove*` (3), `.ht__selection--rows`, `.wtBorder` |
| **Clones y estructura** (7) | `.ht_clone_master`, `.ht_clone_top_inline_start_corner`, `.ht_clone_bottom`, `.ht_clone_bottom_inline_start_corner`, `.ht_clone_inline_start`, `.htRowHeaders`, `.htFocusCatcher` |
| **Resto** (5) | `.htAutoSize`, `.htHidden`, `.htJustify`, `.htNoWrap`, `.ht_editor_hidden`/`.ht_editor_visible` |

→ `F0-200`

## Lo que eso significa en un producto dark

El vendor **trae su propia paleta, y es clara**:

```
$ grep -oE "#[0-9a-fA-F]{3,6}" public/vendor/handsontable/handsontable.full.min.css | sort | uniq -c | sort -rn
  29 #ccc     20 #fff     11 #999      9 #777      8 #eee      7 #bbb
   7 #5292f7   5 #000      3 #e6e6e6   3 #d2d1d1   3 #3af
```

Grises claros y un azul de selección (`#5292f7`) que no pertenece a ninguna escalera del sistema. Cada
uno de los 63 selectores sin alcanzar se pinta con esos valores. **La grilla en reposo está
tematizada; el menú de filtros, el menú contextual, los comentarios y los editores no.** → `F0-201`

Y no es deuda repartida: es **una decisión que nunca se tomó**. Las cuatro hojas que tocan
Handsontable —`handsontable-module.css` (1 022 líneas), `handsontable-header-global.css` (188),
`adapters/handsontable.css` (144) y `adapters/programa-general-handsontable.css` (191)— suman 1 545
líneas y 247 `!important` para gobernar 22 selectores. Alcanzar los otros 63 con el mismo método
multiplicaría por tres ese CSS. → `F0-202`

## Cinco módulos dependen de esto

`programa-general`, `programacion-intermedia`, `programacion-semanal`, `profesionales` y
`subcontratistas` montan Handsontable. Los dos últimos tienen las hojas más limpias del repositorio
—un `!important` cada uno— y aun así **heredan los 63 selectores sin tematizar**, porque la deuda no
está en su CSS: está en el que no existe.

Ese es el argumento para tratarlo como sistema y no como deuda por módulo: **arreglarlo en
`profesionales.css` sería escribir por quinta vez lo mismo**.

## Lo ya medido que cae aquí

- **`F-7`** — la casilla nativa mide 13×13 px, la mitad del piso de 24 de WCAG 2.5.8. Medida en
  `/profesionales` (4 casos), `/subcontratistas` (2) y `/programacion-intermedia` (1). Su selector,
  `.htCheckboxRendererInput`, es uno de los 63. La salida conocida cambia el alto de celda y por
  tanto los goldens, y **no se aplica aquí**. → `F0-203`, `bloqueadoPor: runtime-budgets-al-ci`
- **`C-16`** — el `.colHeader` renderiza 33 px donde el `th` mide 56: 23 px desperdiciados por
  columna. → `F0-204`, `bloqueadoPor: runtime-budgets-al-ci`
- **`C-37`** — 12 de los 24 gatillos `.changeType` de Programa General no llevan `aria-hidden`.
  → `F0-205`

## Lo que no se pudo medir aquí

Los 63 selectores sin alcanzar solo se ven pintados **abriendo el menú, el editor o el comentario**.
Ninguno tiene golden, ninguno aparece en un escenario declarado, y su contraste real contra el fondo
oscuro es una medición de navegador. → `bloqueadoPor: runtime-budgets-al-ci`
