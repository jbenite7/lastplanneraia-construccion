---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-06
areas: [pdc]
fuente: docs/superpowers/specs/2026-08-06-pdc-filtros-y-buscadores-design.md
resumen: Módulo: Plan de Compras v2 (pdc-app/, ver docs/pdc-v2.md)
---

# Plan de Compras: filtros de columna, buscadores rápidos y selects buscables

Fecha: 2026-08-06
Estado: aprobado en brainstorming, pendiente de plan de implementación
Módulo: Plan de Compras v2 (`pdc-app/`, ver `docs/pdc-v2.md`)

## Problema

Las once grillas del PDC no tienen ninguna forma de filtrar ni de buscar. El presupuesto de
una obra trae miles de filas, así que hoy la única manera de llegar a un dato es
desplazarse. Los `<select>` de las páginas —unos quince— son nativos, y varios listan
cientos de opciones (paquete destino, capítulo, insumo, frente, versión) sin manera de
escribir para acotar.

El resto de la aplicación (Programa General, Programación Semanal) sí filtra: cada
cabecera de columna de Handsontable trae un gatillo `changeType` con la lista de valores de
esa columna y casillas. El PDC debe ofrecer lo mismo.

## Alcance

Las once grillas AG Grid del módulo:

| Página | Grillas |
|---|---|
| `ImportarPresupuesto.tsx` | errores de importación, versiones |
| `VisorPresupuesto.tsx` | presupuesto |
| `ComparativoPresupuesto.tsx` | comparativo, insumos con diferencia |
| `MaestroInsumos.tsx` | equipos sin clasificar, vínculos, maestro |
| `PaquetesContratacion.tsx` | insumos del paquete |
| `PlanFechas.tsx` | plan |
| `Seguimiento.tsx` | seguimiento |

Y los quince `<select>` del módulo: `Seguimiento` (4), `VisorPresupuesto` (4),
`PaquetesContratacion` (5), `PasosContratacion` (2), `ComparativoPresupuesto` (2, uno de
ellos por el ayudante de la línea 144), `PlanFechas` (1, el de «sin frente» que se repite
por fila). Al empezar el paso 2 se recuenta con `grep`, porque el módulo se mueve.

Restricción del repositorio (AGENTS.md): **desktop ≥1180 px, dark mode únicamente**. El
viewport canónico de validación es 1180×820. No se produce trabajo ni evidencia para
mobile, tablet ni el tema `linen`.

## Decisiones tomadas

1. **«changetype» = filtro por columna en la cabecera**, con el aspecto del design system.
   No es un cambio de tipo de dato.
2. **Los filtros que ya existen encima de las tablas se quedan** (Visor: tipo de insumo,
   unidad, capítulo; Seguimiento: frente, estado; Paquetes: estado, agrupación). Se
   combinan con los de columna mediante Y lógico, y una barra de filtros activos con chips
   y «Limpiar todo» hace visible qué está aplicado y lo borra todo de una vez.
3. **El buscador rápido busca en las columnas visibles**, sin distinguir mayúsculas ni
   tildes, y se combina con los filtros. No busca en columnas ocultas ni resalta la
   coincidencia dentro de la celda.
4. **Un único componente de lista sustituye a todos los `<select>`**, y la caja de búsqueda
   aparece automáticamente a partir de **8 opciones**. Por debajo de ese umbral el control
   se ve y se comporta igual, pero sin lupa.
5. **Nada se recuerda**: al cambiar de pestaña o recargar, filtros y búsqueda vuelven
   limpios. No hay persistencia en `localStorage` ni en sesión.

## Restricción técnica que condiciona el diseño

AG Grid 36.0.2 **Community** —la versión instalada— incluye `TextFilterModule`,
`NumberFilterModule`, `DateFilterModule`, `CustomFilterModule`, `QuickFilterModule`,
`ExternalFilterModule` y `LocaleModule`. **No incluye el *set filter*** (la lista de valores
con casillas): es Enterprise, de pago.

Por eso la lista de valores se escribe con `CustomFilterModule`. Y como esa misma lista con
buscador es lo que necesitan los `<select>`, se escribe **una sola vez** y se usa en ambos
sitios.

## Arquitectura

### Piezas nuevas

**`lib/coincide.ts`** — normalización de texto para búsqueda: minúsculas, sin tildes, sin
dobles espacios. Expone `coincide(texto, busqueda): boolean` y la normalización suelta para
quien la necesite. La usan el buscador rápido, el filtro de lista y la lupa del selector,
de modo que los tres entienden «cemento» = «Cemento» = «cementó».

**`components/ListaBuscable.tsx`** — la pieza central. No tiene estado de negocio: recibe
las opciones, lo seleccionado y un callback. Dos modos:

- `una`: elegir una opción (uso: selector).
- `varias`: casillas con «todas / ninguna» (uso: filtro de columna).

La caja de búsqueda se pinta sola cuando hay 8 opciones o más. Teclado completo: flechas
arriba/abajo, Enter para elegir, Escape para cerrar, `role="listbox"` con
`aria-activedescendant`.

**`components/Selector.tsx`** — envuelve `ListaBuscable` con el botón que abre el
desplegable y su `<label>`. Su firma imita la de un `<select>` controlado (`value`,
`onChange`, `options`, `aria-label`, `data-testid`) para que la sustitución en las páginas
sea mecánica y no obligue a rehacer los tests existentes que localizan por `data-testid`.

**`components/FiltroLista.tsx`** — envuelve `ListaBuscable` como filtro de columna de AG
Grid vía `CustomFilterModule`. Deriva los valores distintos de la columna y su
`doesFilterPass` deja pasar las filas cuyo valor está marcado. Sin selección, no filtra.

**`components/BarraFiltros.tsx`** — chips de lo que está activo (filtros de columna leídos
en `onFilterChanged`, filtros propios de la página y texto del buscador) y el botón
«Limpiar todo», que borra ambas familias a la vez.

### Cambios en `lib/agGrid.ts`

Es donde el diseño se paga solo. Se añaden a `MODULOS_TABLA`: `TextFilterModule`,
`NumberFilterModule`, `DateFilterModule`, `CustomFilterModule`, `QuickFilterModule` y
`LocaleModule`. El registro sigue siendo selectivo: **no** se pasa a `AllCommunityModule`.

`LocaleModule` con las cadenas en español es obligatorio, no cosmético: sin él el menú de
filtro dice «Contains», «Apply» y «Reset» en una aplicación que está entera en español.

Los presets de columna que ya existen ganan su `filter`:

| Preset | Filtro |
|---|---|
| `CIFRA`, `columnaMoneda`, `columnaNumero` | número |
| `COLUMNA_FECHA` | fecha |
| `COLUMNA_CATEGORIA`, `COLUMNA_CORTA` | `FiltroLista` |
| `TEXTO_LARGO`, `columnaTexto` | texto |

Como las once tablas construyen sus columnas con esos presets, **heredan el filtro correcto
sin editarlas una a una**. Solo hay que tocar a mano las columnas que no salen de un preset.

Aviso medido y ya documentado en ese archivo: `columnasQueCaben` estima en
`ANCHO_COLUMNA_POR_DEFECTO` toda columna sin `minWidth`. El icono de filtro añade ancho a la
cabecera, así que hay que comprobar que ninguna tabla recupere scroll horizontal a 1180.

### Buscador rápido

`QuickFilterModule` más una caja `<input type="search">` por tabla. **Donde ya existe una
caja de búsqueda se reutiliza, no se añade una segunda**: el Visor ya tiene
`pdc-visor-buscar`, que filtra el árbol antes de que llegue a la grilla. Se revisa página
por página antes de añadir nada.

### Aspecto

El gatillo de la cabecera y el popup se pintan con los tokens `--ds-*`, alineados con el
`changeType` de Programa General: mismo tamaño, mismo tono, `aria-hidden="true"` en el icono
decorativo (la inconsistencia C-37 de PG no se replica aquí). Al menú de AG Grid se le
aplica el skin en vez de duplicar el control. Todo va en `pdc-app/src/styles.css`, junto al
resto del módulo, **sin hex nuevos**.

## Orden de trabajo

1. `coincide.ts` y `ListaBuscable` con sus tests. Sostienen todo lo demás.
2. `Selector`, y sustitución de los `<select>` página por página.
3. Módulos y `filter` en los presets de `agGrid.ts`: las once tablas ganan filtro de golpe.
4. `FiltroLista` en las columnas categóricas.
5. Buscador rápido por tabla, reutilizando las cajas existentes.
6. `BarraFiltros` y «Limpiar todo».
7. Skin, y repaso en navegador de las ocho páginas a 1180×820 en dark.

## Pruebas

- `vitest` para `coincide` y para la lógica de `FiltroLista` (qué filas deja pasar), que es
  donde está la sustancia.
- `ListaBuscable` se prueba con Testing Library si ya está disponible en el proyecto; si no,
  su lógica pura (recorte por búsqueda, navegación por teclado, umbral de 8) se extrae a
  `lib/listaBuscable.ts` y se prueba ahí. Es el patrón que ya sigue el módulo con
  `paquetesState` y `planFechas`.
- Lo visual se valida en el navegador contra el contenedor Docker a 1180×820 en dark,
  revisando consola. No se generan ni regeneran snapshots.

## Fuera de alcance

Paginación, exportación a CSV, resaltado de la coincidencia dentro de la celda, persistencia
de filtros, filtrado en servidor, y cualquier trabajo para mobile, tablet o tema `linen`.
