# Plan de Refactorizacion: Replicar Ajustes de Programa General en Programacion Intermedia

## Estado del plan

Este documento define el plan completo para llevar a Programacion Intermedia (PI) los ajustes recientes aplicados en Programa General (PG) sobre Handsontable, rendimiento, eventos, cache visual, anchos, alturas y consistencia de UI.

Estado: ejecutado el 2026-06-01 sobre `public/js/modules/programacion_intermedia/hot.js`, con ajuste mobile complementario en la vista PI y trazabilidad en `ROADMAP.md`.

## Objetivo

Restaurar y optimizar el modulo Handsontable de PI tomando como referencia los patrones ya estabilizados en PG, sin copiar mecanicamente su configuracion. PI tiene columnas, editores, restricciones, estados operativos y flujos de lote propios, por lo que cada cambio debe adaptarse a esas particularidades.

## Alcance

Archivos principales a intervenir cuando se apruebe la ejecucion:

| Tipo | Ruta | Proposito |
|---|---|---|
| JS principal PI | `public/js/modules/programacion_intermedia/hot.js` | Restaurar y optimizar modulo Handsontable PI |
| State machine PI | `public/js/modules/programacion_intermedia/stateMachine.js` | Conservar como fuente canonica de estado operativo |
| Vista PI | `views/programacion-intermedia/programacion_intermedia.view.php` | Ajustes menores de carga, estilos o cache-busting si son necesarios |
| Controlador PI | `src/Controllers/Programacion/ProgramacionIntermediaController.php` | Solo si una validacion demuestra que falta informacion o respuesta para el frontend |
| CSS global Handsontable | `public/css/handsontable-module.css` | Solo si se requiere ajuste transversal menor |

Fuentes de referencia:

| Fuente | Uso |
|---|---|
| `public/js/modules/programa_general/hot.js` | Patron actual de PG optimizado |
| `085b519^:public/js/modules/programacion_intermedia/hot.js` | Base historica para restaurar PI antes del borrado accidental |
| `docs/ESTADOS-PG-PI-PS.md` | Catalogo de estados PG/PI/PS |
| `docs/last-planner-programacion-intermedia-estados.md` | Taxonomia especifica de estados operativos PI |

## Fuera de alcance

| Tema | Razon |
|---|---|
| Reescribir PI desde cero | Riesgo alto y no necesario para recuperar rendimiento |
| Migrar TomSelect a otro editor | PI depende de editores custom y opciones de creacion |
| Cambiar reglas de negocio de restricciones | El objetivo es rendimiento y consistencia visual, no cambiar Last Planner |
| Modificar codigo legacy de negocio | El repo prohibe desarrollar features nuevas en legacy |
| Cambiar endpoints publicos sin necesidad | Puede romper reportes, autoprogramacion o flujos existentes |
| Ejecutar el plan en este documento | Este archivo es solo plan aprobado/pendiente |

## Diagnostico actual

1. `public/js/modules/programacion_intermedia/hot.js` esta vacio en `HEAD`.
2. `views/programacion-intermedia/programacion_intermedia.view.php` carga ese archivo y espera `window.PIHotModule.init()`.
3. La vista PI conserva estructura visual, filtros, modales y carga de dependencias, pero la grilla no puede inicializarse sin el modulo JS.
4. La version historica de PI previa al borrado accidental contiene la mayoria de comportamientos necesarios: carga de datos, filtros, TomSelect, seleccion compartida, drawer operativo, tooltips de restricciones y guardado.
5. PG ya resolvio problemas relevantes que PI tambien necesita evitar: recalculos por celda, colores persistentes obsoletos, renders redundantes, salto de scroll, anchos desalineados y perdida de clases base.

## Decision de enfoque

Se adopta la opcion confirmada por el usuario:

| Opcion | Decision | Motivo |
|---|---|---|
| Restaurar historico y optimizar encima | Aprobada | Recupera funcionalidad con menor riesgo y permite portar patrones ya probados |
| Reescribir PI desde cero | Rechazada | Aumenta riesgo funcional en restricciones, TomSelect y lote compartido |
| Crear modulo minimo temporal | Rechazada | Recupera parcialmente, pero aplaza demasiada deuda critica |

## Cuadro comparativo de cambios considerados

| En PI | En PG | Cambio a efectuar en PI |
|---|---|---|
| `hot.js` esta vacio en `HEAD` | `hot.js` PG esta funcional y optimizado | Restaurar `public/js/modules/programacion_intermedia/hot.js` desde `085b519^` como base de trabajo |
| La vista PI espera `window.PIHotModule.init()` | PG expone `window.PGHotModule.init()` y `getHotInstance()` | Asegurar que PI exponga `init()` y `getHotInstance()` |
| PI carga `stateMachine.js` antes de `hot.js` | PG tiene clasificacion interna en `classifyPGRow()` | Conservar `PIStateMachine` como fuente canonica de estado operativo |
| PI usa `PI_HOT_OPTIONS` para subcontratistas/profesionales | PG carga `codigosActividad` via API | Conservar `PI_HOT_OPTIONS` y validar que llegue antes de inicializar HOT |
| PI historico recalcula `getState(row)` en muchas celdas | PG usa cache `_rowClassCache`, `_rowMetaCache` | Crear cache PI para estado, clases, editabilidad y seleccion |
| PI no tiene `_canEditGlobal` cacheado actualmente por estar vacio | PG calcula `_canEditGlobal` una vez por render | Agregar `_canEditGlobal = isUserAllowedToEdit()` al construir cache |
| PI usa `PIStateMachine.getState()` por celda | PG evita reclasificar por celda con `buildRowClassCache()` | Crear `buildRowClassCache(data)` adaptado a estados PI |
| PI tiene renderer operativo con HTML/chips | PG renderers son mas simples | Crear `_stateViewCache` para no reconstruir `getStateView()` por celda |
| PI necesita clases `pi-state-*`, `pi-cell-*`, `pi-row-*` | PG compone clases por fila en `cells()` | Mantener composicion en `cells()`, pero alimentada por cache |
| PI historico preserva clases base de columnas | PG corrigio perdida de clases base en `cells()` | Asegurar que `baseClass` de cada columna se mantenga siempre |
| PI requiere `pi-soft-restriction-cell` | PG no tiene restricciones blandas | Preservar estilos de `Pdto_Cons` y `Modelo` sin que los estados los pisen |
| PI usa `__shared_selected` para seleccion de lote | PG no tiene seleccion compartida | Incluir seleccion compartida en cache/meta de fila |
| PI usa `pi-row-shared-picked` | PG no aplica | Recalcular clase al marcar/desmarcar seleccion compartida |
| PI debe bloquear restricciones sin `Responsable_AIA` | PG no tiene esa regla | Mantener validacion antes de guardar restricciones |
| PI usa TomSelect para `Sub_Contratista` y `Responsable_AIA` | PG usa dropdown simple | Conservar editores TomSelect y no reemplazarlos por dropdown PG |
| PI tiene opcion "Crear Subcontratista/Profesional" | PG no tiene flujo equivalente | Mantener flujo de apertura de `/subcontratistas` y `/profesionales` |
| PI historico usa `MutationObserver` para opciones "Crear" | PG no lo necesita | Conservar, pero evaluar debounce/limite para evitar costo innecesario |
| PI historico usaba `getSourceDataAtRow()` en varios puntos | PG optimizado evita copias O(N) | Auditar y reemplazar accesos residuales a `getSourceData()` cuando aplique |
| PI historico usaba `hot.loadData(data)` preservando filtros | PG preserva filtros y viewport | Conservar `captureHotFilterConditions()` y `restoreHotFilterConditions()` |
| PI historico preserva viewport | PG preserva viewport al recargar | Conservar `captureViewportState()` y `restoreViewportState()` |
| PI historico recalcula `% Liberacion` localmente | PG normaliza datos antes de guardar | Mantener `calculateRestrictionStateRatio()` y recalcular tras cambios |
| PI historico actualiza varias props tras guardar | PG usa `suspendRender()` / `resumeRender()` | Envolver updates de save success PI en `suspendRender()` / `resumeRender()` |
| PI historico llama `hot.render()` tras guardar | PG hace un solo render al final del lote | Reducir PI a un solo `hot.render()` despues de `resumeRender()` |
| PI historico no blinda cache antes de `setDataAtRowProp` | PG invalida cache antes de actualizar datos | Agregar `invalidatePIRowCache()` antes de updates internos |
| PI historico puede conservar colores/clases viejas | PG fuerza actualizacion de `cellMeta` por fila | Agregar refresco explicito de `cellMeta` para toda la fila modificada |
| PI actualiza `Estado_Restricciones` | PG actualiza `Estado`, `Semanas_Inicio`, `Ejecutado` | Actualizar objeto crudo PI antes de `setDataAtRowProp()` |
| PI actualiza `Semanas_Inicio` desde respuesta | PG actualiza `Semanas_Inicio` en batch visual | Invalidar cache y refrescar fila cuando cambie `Semanas_Inicio` |
| PI recalcula `estado_operativo` | PG recalcula clasificacion visual | Recalcular `estado_operativo` despues de cambios en restricciones o semanas |
| PI historico tiene `autoColumnSize:false` | PG tiene `autoColumnSize:false` | Mantener `autoColumnSize:false` |
| PI historico tiene `autoRowSize:false` | PG paso a `autoRowSize:false` | Mantener `autoRowSize:false` |
| PI historico no fija `rowHeights` final | PG fijo `rowHeights:45` | Definir altura propia para PI, probablemente mayor por chips operativos |
| PI tiene contenido mas alto en `Estado Operativo` | PG tiene filas mas simples | Probar `rowHeights:56` como punto inicial, ajustar visualmente |
| PI historico tenia `colWidths` estatico de 17 columnas | PG cambio a `colWidths:function` porcentual | Crear `colWidths:function` especifico para 17 columnas PI |
| PI tiene 17 columnas | PG tiene 13 columnas | No copiar porcentajes PG; disenar distribucion propia |
| PI necesita columna `Actividad` amplia | PG da mas peso a `Actividad` | Dar prioridad de ancho a `Actividad`, `Estado Operativo`, `Observaciones` |
| PI necesita columnas de restricciones compactas | PG no tiene 7 restricciones editables | Compactar columnas de restricciones con minimos legibles |
| PI historico usa `applyResponsiveColumnWidths()` | PG dejo piloto comentado al usar porcentajes | Desactivar recalculo responsive redundante si `colWidths:function` funciona |
| PI historico resta `width - 20` | PG resta `width - 60` por scrollbar/sidebar | Usar descuento adaptado a PI considerando drawer/sidebar y scrollbar |
| PI usa `wordWrap:true` | PG activo `wordWrap:true` | Mantener `wordWrap:true` |
| PI tiene `viewportRowRenderingOffset:20` | PG usa `viewportRowRenderingOffset:20` | Mantener offset inicial y ajustar solo si medicion lo exige |
| PI tiene `viewportColumnRenderingOffset:10` | PG usa `viewportColumnRenderingOffset:10` | Mantener offset inicial |
| PI usa filtros de leyenda `#piLegend` | PG usa `#pgLegend` con estado visual | Mantener toggle simple y contador movil |
| PI usa filtros por actividad, semanas, liberada, subcontratista, responsable | PG usa filtros por actividad, semanas, critica, estado | Conservar filtros propios de PI |
| PI filtra `Actividad` con HTML | PG usa texto plano para filtros | Mantener `getActividadPlainText()` en filtros y menu HOT |
| PI tiene `modifyFiltersMultiSelectValue` para `Actividad` | PG tambien lo usa | Conservar para evitar filtrar por HTML crudo |
| PI exporta CSV | PG exporta CSV | Mantener `exportFile` y nombre `programacion_intermedia` |
| PI descarga reporte de restricciones | PG descarga corte de programacion | Mantener endpoint `/reportes/restricciones` |
| PI usa modal de restriccion compartida | PG no tiene equivalente | Conservar flujo completo de preview/aplicacion |
| PI usa `collectSelectedActivityIds()` y visibles | PG no aplica | Validar que seleccion marcada/visible sobreviva a filtros |
| PI usa drawer operativo propio | PG usa drawer contextual LPS | Conservar drawer operativo y `LPSContextualDrawer.init()` |
| PI usa tooltips Bootstrap en headers de restricciones | PG solo tiene menu de filtros | Mantener tooltips y validar conflicto jQuery UI/Bootstrap |
| PI vista ya tiene `.aia-modal` | PG unifico modales AIA | Conservar modales AIA y `modal-dialog-centered` |
| PI vista carga TomSelect desde CDN | PG usa vendors locales para jQuery/Bootstrap | No tocar en esta fase salvo que validacion muestre problema |
| PI debe probar movil `375px` | PG ajustes fueron visuales y responsive | Validar mobile first despues de restaurar/optimizar |
| PI no tiene test especifico | PG tiene pruebas/manuales auxiliares de cache/render | Crear validacion manual reproducible; test automatizado solo si se aprueba |
| PI puede estar roto por borrado accidental | PG esta estable en `main` | Primera entrega debe ser recuperacion funcional, luego optimizacion |

## Fases de ejecucion propuestas

### Fase 0: resguardo y confirmacion de base

Objetivo: asegurar que la ejecucion parte de un estado entendido y que no pisa trabajo ajeno.

Pasos:

1. Revisar `git status --short`.
2. Confirmar que `public/js/modules/programacion_intermedia/hot.js` sigue vacio.
3. Confirmar que no hay cambios locales del usuario en ese archivo.
4. Identificar si existen archivos no versionados de pruebas o backups relacionados con PG y dejarlos intactos.
5. Confirmar que la vista PI carga `stateMachine.js`, `HandsontableTomSelectEditor.js`, `lps_drawer.js` y `hot.js` en orden correcto.

Criterio de salida:

- Estado de trabajo comprendido.
- No se toca ningun archivo ajeno al alcance.

### Fase 1: restauracion funcional de PI

Objetivo: recuperar `PIHotModule` completo antes de cualquier optimizacion profunda.

Pasos:

1. Restaurar `public/js/modules/programacion_intermedia/hot.js` desde `085b519^:public/js/modules/programacion_intermedia/hot.js`.
2. Verificar que el archivo restaurado expone `window.PIHotModule`.
3. Verificar que expone `init()`.
4. Verificar que expone `getHotInstance()`.
5. Verificar que mantiene `window.__piPendingNav` para navegacion de editores.
6. Verificar que conserva `editableProps`, `restrictionProps`, `hardRestrictionProps`, `softRestrictionProps` y `trackedStates`.
7. Verificar que conserva renderers `piPercentRenderer`, `piActividadRenderer` y `piStateRenderer`.
8. Verificar que conserva bindings de filtros, acciones, resize, tooltips y modal compartido.

Criterio de salida:

- PI vuelve a cargar sin error `PIHotModule undefined`.
- La grilla renderiza datos con el comportamiento historico restaurado.

### Fase 2: cache de filas y reduccion de recomputo

Objetivo: reducir trabajo repetido en `cells()` y renderers.

Pasos:

1. Declarar caches de PI cerca del estado global del modulo:
   - `_rowStateCache`
   - `_rowClassCache`
   - `_rowMetaCache`
   - `_stateViewCache`
   - `_canEditGlobal`
2. Implementar `buildRowClassCache(data)`.
3. En `buildRowClassCache(data)`, calcular `_canEditGlobal` una sola vez.
4. En `buildRowClassCache(data)`, calcular estado por fila usando `getState(rowData)`.
5. En `buildRowClassCache(data)`, componer clase de estado `pi-state-*` o `pdc-header`.
6. En `buildRowClassCache(data)`, marcar si la fila esta seleccionada por restriccion compartida.
7. En `buildRowClassCache(data)`, marcar si la fila esta en crisis (`alerta_crisis`).
8. En `buildRowClassCache(data)`, calcular si es header.
9. En `buildRowClassCache(data)`, precalcular `getStateView(rowData)` para la columna `Estado Operativo`.
10. Llamar `buildRowClassCache(data)` antes de crear o recargar HOT.
11. Modificar `cells()` para consumir caches por fila fisica.
12. Mantener fallback seguro si una fila no existe en cache.

Criterio de salida:

- `PIStateMachine.getState()` no se invoca innecesariamente por cada celda visible cuando puede resolverse por cache.
- Las clases visuales siguen siendo equivalentes al comportamiento historico.

### Fase 3: invalidacion de cache y consistencia visual post-edicion

Objetivo: evitar clases/colores obsoletos tras cambios de restricciones, semanas, seleccion o estado.

Pasos:

1. Crear `invalidatePIRowCache(physicalRow, rowData)`.
2. Invalidar `_rowStateCache[physicalRow]`.
3. Invalidar `_rowClassCache[physicalRow]`.
4. Invalidar `_rowMetaCache[physicalRow]`.
5. Invalidar `_stateViewCache[physicalRow]`.
6. Remover cualquier propiedad memoizada equivalente si se introduce en row data.
7. Llamar invalidacion antes de `setDataAtRowProp()` cuando cambien restricciones.
8. Llamar invalidacion antes de `setDataAtRowProp()` cuando cambie `Estado_Restricciones`.
9. Llamar invalidacion antes de `setDataAtRowProp()` cuando cambie `Semanas_Inicio`.
10. Llamar invalidacion antes de `setDataAtRowProp()` cuando cambie `Estado`.
11. Llamar invalidacion antes de `setDataAtRowProp()` cuando cambie `estado_operativo`.
12. Llamar invalidacion al marcar/desmarcar `__shared_selected`.
13. Crear helper para recomponer `cellMeta` de una fila completa.
14. Usar el helper tras save exitoso.
15. Usar el helper tras seleccion compartida si no se recarga la tabla.

Criterio de salida:

- Una fila editada cambia de color/estado inmediatamente.
- No quedan colores persistentes anteriores por reuso de DOM o metadata de Handsontable.

### Fase 4: batch rendering en guardado

Objetivo: reducir renders redundantes y saltos de scroll durante guardados.

Pasos:

1. En `saveRow().done()`, envolver updates internos con `hot.suspendRender()`.
2. Usar `try/finally` para garantizar `hot.resumeRender()`.
3. Actualizar el objeto crudo de la fila antes de llamar `setDataAtRowProp()`.
4. Actualizar `Estado_Restricciones` si viene en respuesta.
5. Actualizar `Semanas_Inicio` si viene en respuesta.
6. Actualizar `Estado` si viene en respuesta.
7. Recalcular `estado_operativo` despues de aplicar cambios crudos.
8. Aplicar `setDataAtRowProp()` con source `internal-update`.
9. Refrescar metadata de la fila.
10. Ejecutar un solo `hot.render()` despues de `resumeRender()`.
11. Actualizar contadores de leyenda despues del render final.
12. Mantener revert seguro en errores.

Criterio de salida:

- El guardado no dispara render por cada propiedad modificada.
- El scroll y seleccion se mantienen estables durante la edicion.

### Fase 5: dimensiones y rendimiento de layout

Objetivo: portar los ajustes de rendimiento visual de PG adaptados a las 17 columnas de PI.

Pasos:

1. Mantener `autoColumnSize:false`.
2. Mantener `autoRowSize:false`.
3. Evaluar `rowHeights:56` como punto inicial por chips de estado operativo.
4. Ajustar `rowHeights` si el contenido queda cortado o si el scroll salta.
5. Mantener `renderAllRows:false`.
6. Mantener `viewportRowRenderingOffset:20` inicialmente.
7. Mantener `viewportColumnRenderingOffset:10` inicialmente.
8. Disenar `colWidths:function(index)` para 17 columnas.
9. Restar margen por scrollbar/sidebar en el ancho base.
10. Dar prioridad de ancho a `Actividad`, `Estado Operativo` y `Observaciones`.
11. Compactar restricciones a anchos minimos legibles.
12. Confirmar que `Lote`/seleccion compartida queda estrecha.
13. Desactivar `applyResponsiveColumnWidths()` si queda redundante con porcentajes.
14. Mantener `syncContainerHeight()` y `scheduleLayoutRefresh()`.
15. Mantener captura/restauracion de viewport al recargar.

Criterio de salida:

- No hay saltos de scroll por calculo de altura automatico.
- La tabla ocupa el ancho disponible sin generar overflow horizontal innecesario en desktop.
- En movil, la tabla sigue siendo usable dentro de las limitaciones de 17 columnas.

### Fase 6: filtros, seleccion compartida y acciones de lote

Objetivo: garantizar que los flujos propios de PI no se rompan al optimizar.

Pasos:

1. Mantener filtros por actividad.
2. Mantener filtros por semanas de inicio.
3. Mantener filtros por liberada/no liberada.
4. Mantener filtros por subcontratista.
5. Mantener filtros por responsable AIA.
6. Mantener filtros por leyenda `#piLegend`.
7. Mantener contador `#mobileFilterCount`.
8. Mantener `getActividadPlainText()` para filtrar HTML.
9. Mantener `modifyFiltersMultiSelectValue()` para `Actividad`.
10. Mantener `sharedSelectionIndex`.
11. Mantener seleccion visible para restriccion compartida.
12. Mantener limpieza de seleccion compartida.
13. Mantener carga de marcadas al modal.
14. Mantener carga de visibles al modal.
15. Validar que aplicar lote actualiza datos y contadores.

Criterio de salida:

- Los filtros no pierden seleccion compartida.
- El modal de restriccion compartida opera con las filas correctas.

### Fase 7: editores, tooltips y eventos

Objetivo: conservar la interaccion avanzada de PI mientras se reduce costo de eventos.

Pasos:

1. Mantener TomSelect multiple para `Sub_Contratista`.
2. Mantener TomSelect single para `Responsable_AIA`.
3. Mantener apertura de editor con click en celdas dropdown.
4. Mantener apertura de editor por navegacion con teclado.
5. Mantener `beforeKeyDown` y `afterSelectionEnd`.
6. Mantener `window.__piPendingNav`.
7. Mantener flujo de creacion de subcontratistas.
8. Mantener flujo de creacion de profesionales.
9. Revisar `MutationObserver` de opciones "Crear".
10. Agregar debounce al observer solo si se evidencia costo alto.
11. Mantener tooltips de headers de restricciones.
12. Confirmar conflicto jQuery UI/Bootstrap resuelto por la vista.
13. Mantener drawer operativo al hacer click en estado.
14. Mantener cierre del drawer con Escape.
15. Mantener `hot.listen()` y listener `mousedown` para foco.

Criterio de salida:

- Los editores siguen abriendo de forma predecible.
- No aparecen errores de tooltip ni de TomSelect en consola.

### Fase 8: validacion unificada

Objetivo: verificar funcionalidad, rendimiento y regresiones antes de cerrar.

Validaciones tecnicas:

1. Ejecutar validacion sintactica de PHP si se toca algun PHP.
2. Ejecutar validacion sintactica o parseo de JS si se dispone de herramienta local.
3. Verificar que el archivo PI no queda vacio.
4. Verificar que `PIHotModule` existe en navegador.
5. Verificar que no hay errores de consola al cargar PI.
6. Verificar que `/api/pi/list` responde data valida.
7. Verificar que `/api/pi/save` conserva contrato de respuesta esperado.

Validaciones funcionales:

1. Cargar `/programacion-intermedia`.
2. Confirmar que se muestran 17 columnas.
3. Editar una restriccion dura.
4. Confirmar guardado exitoso.
5. Confirmar cambio inmediato de `% Liberacion`.
6. Confirmar cambio inmediato de `Estado Operativo` si aplica.
7. Confirmar cambio inmediato de color de fila.
8. Confirmar que no persiste color viejo.
9. Editar `Sub_Contratista`.
10. Editar `Responsable_AIA`.
11. Editar `Observaciones`.
12. Intentar restriccion sin responsable y confirmar bloqueo.
13. Usar filtros de leyenda.
14. Usar filtros superiores.
15. Exportar CSV.
16. Descargar corte de restricciones.
17. Abrir drawer operativo.
18. Abrir modal de restriccion compartida.
19. Ejecutar preview de lote.
20. Aplicar lote en entorno de prueba si hay datos seguros.

Validaciones responsive:

1. Probar desktop ancho normal.
2. Probar tablet con escala del proyecto.
3. Probar movil `375px`.
4. Confirmar que el boton de filtros mobile muestra contador.
5. Confirmar que el colapso de filtros recalcula layout.
6. Confirmar que no hay salto de scroll al editar.

## Checklist atomica de cumplimiento

### Restauracion base

- [x] Confirmar `public/js/modules/programacion_intermedia/hot.js` vacio antes de intervenir.
- [x] Restaurar contenido desde `085b519^:public/js/modules/programacion_intermedia/hot.js`.
- [x] Confirmar que `window.PIHotModule` queda definido.
- [x] Confirmar que `PIHotModule.init` existe.
- [x] Confirmar que `PIHotModule.getHotInstance` existe.
- [x] Confirmar que no se modifica PG durante la restauracion.
- [x] Confirmar que no se modifican archivos no versionados de backups/pruebas.

### Cache y rendering

- [x] Declarar `_rowStateCache`.
- [x] Declarar `_rowClassCache`.
- [x] Declarar `_rowMetaCache`.
- [x] Declarar `_stateViewCache`.
- [x] Declarar `_canEditGlobal`.
- [x] Implementar `buildRowClassCache(data)`.
- [x] Invocar `buildRowClassCache(data)` antes de inicializar HOT.
- [x] Invocar `buildRowClassCache(data)` antes de `hot.loadData(data)`.
- [x] Implementar `invalidatePIRowCache(physicalRow, rowData)`.
- [x] Usar caches en `cells()`.
- [x] Usar cache de vista en `piStateRenderer`.
- [x] Mantener fallback si cache no existe.
- [x] Evitar recomputo innecesario de permisos por celda.

### Guardado y consistencia visual

- [x] Envolver save success en `hot.suspendRender()`.
- [x] Garantizar `hot.resumeRender()` con `finally`.
- [x] Actualizar objeto crudo antes de `setDataAtRowProp()`.
- [x] Invalidar cache antes de modificar `Estado_Restricciones`.
- [x] Invalidar cache antes de modificar `Semanas_Inicio`.
- [x] Invalidar cache antes de modificar `Estado`.
- [x] Invalidar cache antes de modificar `estado_operativo`.
- [x] Refrescar `cellMeta` de toda la fila modificada.
- [x] Ejecutar un solo `hot.render()` final.
- [x] Actualizar contadores despues del render final.
- [x] Mantener revert en error de validacion.
- [x] Mantener revert en error de red.

### Layout y dimensiones

- [x] Mantener `autoColumnSize:false`.
- [x] Mantener `autoRowSize:false`.
- [x] Definir `rowHeights` inicial para PI.
- [x] Validar visualmente si `rowHeights:56` es suficiente.
- [x] Mantener `renderAllRows:false`.
- [x] Mantener `viewportRowRenderingOffset:20` inicialmente.
- [x] Mantener `viewportColumnRenderingOffset:10` inicialmente.
- [x] Crear `colWidths:function(index)` para 17 columnas.
- [x] Evitar copiar porcentajes de PG.
- [x] Priorizar `Actividad`.
- [x] Priorizar `Estado Operativo`.
- [x] Priorizar `Observaciones`.
- [x] Compactar columnas de restricciones.
- [x] Ajustar descuento de ancho por scrollbar/sidebar.
- [x] Desactivar recalculo responsive redundante si aplica.

### Filtros y seleccion compartida

- [x] Mantener filtros de leyenda `#piLegend`.
- [x] Mantener contador `#mobileFilterCount`.
- [ ] Mantener filtros por actividad.
- [ ] Mantener filtros por semanas.
- [ ] Mantener filtros por liberada.
- [ ] Mantener filtros por subcontratista.
- [ ] Mantener filtros por responsable.
- [x] Mantener `getActividadPlainText()`.
- [x] Mantener seleccion compartida marcada.
- [x] Mantener seleccion de visibles.
- [x] Mantener limpieza de seleccion.
- [x] Validar que filtros no pierden seleccion compartida.

### Editores y eventos

- [x] Mantener editor TomSelect multiple.
- [x] Mantener editor TomSelect single.
- [x] Mantener apertura por click.
- [x] Mantener apertura por teclado.
- [x] Mantener `beforeKeyDown`.
- [x] Mantener `afterSelectionEnd`.
- [x] Mantener `window.__piPendingNav`.
- [x] Mantener opciones de creacion.
- [x] Mantener `MutationObserver` o reemplazarlo por version debounced si se justifica.
- [x] Mantener tooltips de headers.
- [x] Mantener drawer operativo.
- [x] Mantener `hot.listen()`.

### Validacion final

- [x] PI carga sin error de consola.
- [x] La grilla muestra datos.
- [x] La grilla muestra 17 columnas.
- [ ] Editar restriccion dura guarda correctamente.
- [ ] Editar restriccion blanda guarda correctamente.
- [ ] Editar responsable guarda correctamente.
- [ ] Editar subcontratista guarda correctamente.
- [ ] Editar observaciones guarda correctamente.
- [ ] Cambio de estado actualiza color inmediatamente.
- [ ] No persiste color viejo despues de guardar.
- [ ] Scroll no salta al editar.
- [x] Filtros de leyenda funcionan.
- [ ] Filtros superiores funcionan.
- [ ] CSV se exporta.
- [ ] Corte se descarga.
- [x] Drawer operativo abre.
- [x] Modal de restriccion compartida abre.
- [ ] Preview de lote funciona.
- [ ] Aplicacion de lote funciona en entorno seguro.
- [x] Desktop validado.
- [x] Tablet validado.
- [x] Movil `375px` validado.

### Resultado del checkeo 2026-06-01

- Se corrigio un faltante detectado durante la auditoria: `renderAllRows:false` quedo explicito en PI.
- Se corrigio el overflow residual de escritorio: los ratios sumaban 100%, pero los minimos por columna sumaban mas que el ancho disponible. `colWidths()` ahora usa minimo compacto de `20px`, siguiendo el criterio efectivo de PG, y conserva la reserva de `60px` para scrollbar/sidebar LPS.
- Validacion navegador: PI carga con `PIHotModule`, 17 columnas y datos; no hay overflow horizontal del body ni del holder en desktop normal, tablet `1024px` ni con drawer LPS abierto. En movil `375px`, la grilla permanece visible y el scroll queda contenido dentro de Handsontable.
- Pendiente real: los filtros superiores `#buscadorActividad`, `#buscadorSemanasInicio`, `#buscadorLiberada`, `#buscadorSubcontratista` y `#buscadorResponsableAIA` siguen referenciados en JS, pero no existen en la vista PI renderizada.
- No se marcaron como terminadas las pruebas de edicion, exportacion, descarga de corte, preview ni aplicacion de lote porque requieren mutar datos o descargar archivos en un entorno de prueba controlado.

## Riesgos y mitigaciones

| Riesgo | Impacto | Mitigacion |
|---|---|---|
| Restaurar codigo historico con comportamiento ya superado | Medio | Restaurar primero, validar y luego aplicar optimizaciones controladas |
| Copiar porcentajes de PG a PI | Alto | Disenar anchos especificos para 17 columnas PI |
| Fijar filas demasiado bajas | Alto | Probar altura propia por chips operativos, iniciar con `56` y validar visualmente |
| Romper TomSelect | Alto | No reemplazar editores; conservar contrato actual |
| Romper seleccion compartida al filtrar | Alto | Validar `sharedSelectionIndex` con filtros activos |
| Colores persistentes obsoletos | Alto | Invalidacion de cache antes de updates y refresco de `cellMeta` por fila |
| Renders redundantes que causan saltos de scroll | Medio | Usar `suspendRender()` y un solo `hot.render()` final |
| Tooltips Bootstrap chocan con jQuery UI | Medio | Mantener bloque de resolucion existente en la vista PI |
| Endpoint legacy responde con forma inesperada | Medio | Mantener compatibilidad con `respuesta === 'BIEN'` y campos opcionales |

## Metricas de exito

| Metrica | Resultado esperado |
|---|---|
| Carga inicial PI | Sin error `PIHotModule undefined` |
| Consola navegador | Sin errores JavaScript al cargar y editar |
| Edicion de restriccion | Guardado y actualizacion visual inmediata |
| Render post-save | Un solo render final por save exitoso |
| Scroll | Sin salto visible despues de editar |
| Colores | Sin persistencia de estado anterior |
| Filtros | Contadores y resultados consistentes |
| Mobile | Usable en viewport `375px` |

## Orden recomendado de commits

Si se decide implementar en commits separados:

1. `fix(PI): restaurar modulo Handsontable de programacion intermedia`
2. `perf(PI): cachear estado operativo y clases de filas`
3. `fix(PI): invalidar cache y metadata tras guardar restricciones`
4. `perf(PI): ajustar dimensiones Handsontable y reducir renders`
5. `test(PI): validar flujo Handsontable y restricciones compartidas`

## Criterio de ejecucion

La ejecucion se considero cerrada cuando `hot.js` recupero `PIHotModule`, la grilla cargo con 17 columnas, el filtro de leyenda funciono, el contenedor HOT quedo visible en mobile `375px` y las validaciones sintacticas/locales pasaron sin errores.
