# Mapa / inventario — estado del shell sidebar por módulo

Fuente: inventario con 3 subagentes Explore (grupos A/B/C) sobre las vistas, controladores y JS. Referencia correcta = `/programacion-intermedia` (único con el shell hoy). Ninguno de los 11 objetivo tiene `aia-shell--sidebar` ni está en `foundation-shell.json → routes` (solo PI).

## Cómo se suprime el navbar legacy
El navbar superior legacy lo inyecta en cliente `public/js/cargarDatosGeneralesPagina2.js`. Se **suprime** con `window.__AIA_SHELL_SIDEBAR__ = true` (guardas en ese JS). La "receta PI" ya lo hace. Migrar = activar ese flag + incluir `partials/shell_sidebar.php` + body `aia-shell--sidebar` + `renderScript('/js/modules/aia_ui/sidebar_navigation.js')` + pasar `$shellActive/$shellModuleLabel/$shellWeeks` + limpiar el chrome legacy muerto.

## Tabla de estado

| Módulo | Ruta | Nav legacy hoy | Cajón LPS | Grilla | Week-scoped | Complejidad | Grupo |
|---|---|---|---|---|---|---|---|
| Programación Intermedia | /programacion-intermedia | **shell ✅ (hecho)** | sí | Handsontable | sí | — (referencia) | — |
| Programa General | /programa-general | **ninguno** (ya sin navbar; body `aia-shell` sin `--sidebar`, sin context-bar) | sí | Handsontable | parcial | **Baja** (el más cercano) | B |
| Profesionales | /profesionales | navbar JS legacy | no | Handsontable (inline) | no | Baja-Media | A |
| Subcontratistas | /subcontratistas | navbar JS legacy | no | Handsontable (HOT 14.6.1 CDN — desalineado) | no | Baja-Media | A |
| Control de Cambios | /control-cambios | navbar JS legacy | no | **DataTables + Tabulator + charts** (no HOT); doble jQuery/Bootstrap | no | **Media-Alta** (stack CDN divergente) | A |
| Actualizar Cronograma | /programa-general-actualizar | navbar JS legacy | no | Handsontable | sí | Media (codifica alto navbar `100vh-80px`; jQuery CDN) | B |
| Familias de Actividades | /listado-actividades | **doble** (navbar + info_general_nav) | no | Handsontable | sí (autogestiona; escribe `#ctxProyecto/#ctxModulo`) | Media (colisión context-bar; SQL inline en head) | B |
| Contratos | /contratos | **doble** (navbar + info_general_nav) | no | Handsontable | no | Media (doble nav; sin meta csrf) | B |
| PDC / Plan de Compras | /pdc | **doble** (navbar + info_general_nav) | no | Handsontable | parcial | Media (doble nav; param `&origen=`) | B |
| Indicadores | /indicadores | navbar JS legacy | no | embed Power BI (`ajustarInformePowerBI()`) | no (embed público) | **Alta** (dimensiona por `innerWidth`+`getBoundingClientRect().top`; full-bleed `100vw`) | C |
| Control Tower | /bi/control-tower (+7 rutas /bi/*) | **layout BI propio** (`bi/_layout.php`): SPA con su **propia `bi-sidebar`** Tailwind, sub-nav `_nav.php` (switchView), NO usa `DesignSystemHeadComponent::render()` | no (sidebar izq. propia) | Chart.js SPA | sí | **Muy alta** (dos sidebars; SPA aparte; stack Tailwind CDN) | C |

## Brechas y decisiones que el mapa revela (más allá del plan original)

1. **Programa General** ya está "desnudo" (sin navbar legacy) → migración casi trivial: falta `--sidebar`, context-bar y el flag. Buen primer módulo para estabilizar la receta.
2. **Programación Semanal NO es 1 página sino 4**: la base + **CIC/CNC/CNP son páginas completas independientes** (DataTables, su propio navbar cada una, rutas `/programacion-semanal/cic|cnc|cnp`). Migrar el layout base **no** cubre las subvistas → 4 migraciones, con DataTables (no HOT).
3. **Compras (listado/contratos/pdc)** tienen **doble navegación** (navbar legacy + `info_general_nav` de 3 ítems). Decisión: ¿el info-nav de Compras se retira (la sidebar ya lista los 3) o se conserva como sub-nav? Recomendación: retirarlo (redundante con la sidebar). Ojo con el parámetro `&origen=` de PDC.
4. **listado-actividades** escribe a mano en `#ctxProyecto/#ctxModulo` → colisiona con la context-bar de la sidebar; hay que reconciliar esos IDs.
5. **Cajón LPS** solo existe en PG, PS (y PI). Decisión: ¿se generaliza a los demás o se mantiene solo donde ya está? Recomendación: mantener solo donde ya está (no es requisito del goal).
6. **Indicadores**: reanclar `ajustarInformePowerBI()` al ancho del contenido (no `innerWidth`) y quitar el hack `100vw/margin-left:-50vw` para que el embed no se desborde bajo la sidebar.
7. **Control Tower** es el caso más divergente: es una **SPA con su propia sidebar** y no usa el head-wiring del DS. Meter la sidebar de app implica **dos sidebars** o rediseñar su chrome. **Candidato fuerte a sub-goal aparte** (el plan ya lo flaggeó); recomiendo dejarlo al final y confirmarte el enfoque antes de tocarlo.

## Orden de rollout sugerido (riesgo creciente)
1. **Programa General** (casi listo) → valida la receta.
2. **Grupo A**: Profesionales, Subcontratistas (HOT simple) → luego **Control de Cambios** (stack CDN, más cuidado).
3. **Grupo B Compras**: Contratos, PDC, Familias (doble nav + Handsontable).
4. **Actualizar Cronograma** (recalcular geometría).
5. **Programación Semanal** base + **CIC/CNC/CNP** (4 páginas, DataTables).
6. **Indicadores** (reanclar embed).
7. **Control Tower** (sub-goal / decisión de diseño previa).
