# Flujos de trabajo y matriz QA

Documento maestro para entender los flujos operativos antes de crear o modificar tests.

Estado: borrador operativo inicial.
Alcance: app principal y panel Admin.
Regla: no agregar ni cambiar tests hasta revisar este documento.

## 1. Contexto de desarrollo

### Entorno local

- App: `http://localhost:8081`
- Admin: `http://localhost:8081/admin`
- Adminer: `http://localhost:8082`
- MySQL host: `127.0.0.1:3307`
- Ejecucion PHP: `docker compose exec app php <archivo>`
- Ejecucion Playwright: `npx playwright test`

### Credenciales dev

Las pruebas resuelven las credenciales en tiempo de ejecucion; no se deben duplicar valores privilegiados en documentacion ni tests.

| Superficie | Fuente autorizada |
|---|---|
| App principal | Fixture compartido `CREDENTIALS` de `tests/browser/fixtures/projects.mjs`; override opcional y pareado mediante `E2E_APP_USERNAME` y `E2E_APP_PASSWORD` cuando el spec requiera otra visibilidad de proyectos |
| Panel Admin | Variables de entorno `E2E_ADMIN_USERNAME` y `E2E_ADMIN_PASSWORD` |
| Base de datos | `.env` o variables del contenedor; dentro de `db`, usar `MYSQL_ROOT_PASSWORD` y `MYSQL_DATABASE` |

### Proyectos fixture usados por E2E

| Proyecto | Tipo | `project_id` | `dbPrefix` | Semana fixture | Modulos principales |
|---|---|---:|---|---:|---|
| Da Porto | Construccion | 73 | `da_porto` | 1 | PG, PI, PS, CNP, CNC, CIC, Listado, Contratos, PDC, Profesionales, Subcontratistas, Indicadores, Control Cambios, Reportes |
| Optimización Aeropuerto JMC | Construccion | 68 | `optimizacionJMC` | 5 (LPS), 6 (Compras) | PG, PI, PS, CNP, CNC, CIC, Listado, Contratos, PDC |
| Preconstrucción Da Porto | Pre-Construccion | 76 | `preconstruccion_da_porto_pc` | 1 | PG, PI, PS, Interesados Externos; sin Listado, Contratos ni PDC |
| Aeropuerto Regional PC | Pre-Construccion | 75 | `da_aeropuerto_pc` | 3 | PG, PI, PS, Profesionales, Interesados Externos, Indicadores, Control Cambios |

### Roles y permisos relevantes

Roles canonicos:

- `A`: Administrador.
- `D`: Director de Obra.
- `R`: Residente de Obra.
- `DCV`: Profesional DCV.
- `OT`: Oficina Tecnica / Compras.
- `G`: Ambiental.
- `S`: Seguridad SST.
- `SG`: SST + Ambiental.
- `C`: Subcontratista.
- `V`: Visualizador.

Capacidades operativas:

- Gestion de semanas: `A`, `D`, `OT`, `R`, `DCV`.
- Edicion Programa General: `A`, `D`, `R`, `DCV`.
- Edicion Programacion Intermedia: `A`, `D`, `R`, `DCV`.
- Edicion Programacion Semanal: `A`, `D`, `R`, `S`, `G`, `SG`.
- Contratos/PDC: `A`, `D`, `OT`, `R`.
- Roles de solo lectura: `V`, `C`.

## 2. Reglas de persistencia y riesgo

- La arquitectura vigente es global-only: tablas compartidas con `project_id`.
- `Base_de_Datos` y `dbPrefix` son compatibilidad historica, no permiso para crear consultas runtime a tablas `{prefix}_*`.
- Los flujos de migracion, backup, limpieza de huerfanas y reportes masivos son de alto impacto.
- `USE_GLOBAL_TABLES=false` no es rollback seguro despues de limpiar tablas legacy locales, salvo restaurando backup externo.
- Antes de automatizar un flujo que muta BD, debe existir snapshot, limpieza o fixture reproducible.

Tablas globales principales:

| Area | Tablas |
|---|---|
| Programa | `programa`, `programa_consolidado`, `programacion_semanal`, `semanas_activas`, `auto_program_log`, `pg_tracking` |
| Restricciones | `pi_shared_constraints`, `pi_shared_constraint_links` |
| Control semanal | `cnp`, `cnc`, `cic`, `indicadores_generales` |
| Gestion | `actividades` (incluye datos de contratos), `pdc`, `papelera_pdc`, `profesionales`, `subcontratistas`, `cambios` |
| LPS operativo | `lps_drawer_comentarios`, `lps_escalamientos` |
| Semi-auto | `semi_auto_runs`, `semi_auto_suggestions`, `semi_auto_decisions`, `semi_auto_feedback`, `semi_auto_assistant_feedback`, `semi_auto_project_config`, `semi_auto_learning_candidates`, `semi_auto_learning_rules`, `semi_auto_proactive_queue` |

## 3. Flujos transversales

### 3.1 Login app principal

Tipo: usuario normal.

Pasos:

1. Entrar a `/login`.
2. Ingresar usuario y clave.
3. Enviar `POST /login`.
4. Si las credenciales son validas, redirigir a `/proyectos`.
5. Si el usuario debe cambiar clave, mostrar flujo de cambio forzado.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/login` |
| API/ruta | `GET /login`, `POST /login`, `POST /password/update` |
| Controlador | `App\Controllers\Auth\LoginController` |
| Persistencia | usuarios/sesion; cambio de clave cuando aplique |
| Tests actuales | Cubierto indirectamente por la mayoria de E2E |
| Riesgo | Seguro, salvo cambio de clave |

### 3.2 Recuperacion de contraseña app principal

Tipo: usuario normal.

Pasos:

1. Entrar a `/password/forgot`.
2. Enviar email con CSRF.
3. Recibir enlace generado con `APP_URL`.
4. Entrar a `/password/reset`.
5. Enviar nueva clave.
6. Volver a login.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/password/forgot`, `/password/reset` |
| API/ruta | `POST /password/forgot`, `POST /password/reset` |
| Controlador | `App\Controllers\Auth\PasswordResetController` |
| Persistencia | `password_reset_tokens`, usuario |
| Dependencia | SMTP en `.env`, patch de tokens aplicado |
| Tests actuales | Sin cobertura E2E dedicada |
| Riesgo | Muta clave; requiere SMTP |

### 3.3 Selector de proyecto

Tipo: usuario normal.

Pasos:

1. Despues de login, entrar a `/proyectos`.
2. Ver tarjetas de proyectos permitidos.
3. Seleccionar proyecto con `POST /proyecto/seleccionar`.
4. Guardar proyecto, `dbPrefix`, area y permisos en sesion.
5. Redirigir al modulo inicial del tipo de proyecto.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/proyectos` |
| API/ruta | `POST /proyecto/seleccionar` |
| Controlador | `App\Controllers\Core\ProjectSelectorController` |
| Persistencia | Sesion |
| Tests actuales | `tests/browser/full-app-flow.spec.mjs`, helpers `session.mjs` |
| Riesgo | Seguro |

### 3.4 Cambio de semana

Tipo: usuario normal.

Pasos:

1. Usuario cambia semana desde UI o helper.
2. Enviar `POST /context/week`.
3. Guardar semana en sesion.
4. Recargar destino operativo.
5. Consultas siguientes usan semana seleccionada.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| API/ruta | `POST /context/week`, `POST /context/clear-week` |
| Controlador | `App\Controllers\Core\ContextController` |
| Persistencia | Sesion |
| Tests actuales | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs` |
| Riesgo | Seguro |

### 3.5 Logout y sesion

Tipo: usuario normal.

Pasos:

1. Entrar a `/logout`.
2. Destruir sesion.
3. Redirigir a login o raiz publica.
4. Rutas protegidas vuelven a exigir sesion.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| API/ruta | `GET /logout`, `POST /session/touch` |
| Controlador | `LoginController`, `SessionController` |
| Persistencia | Sesion |
| Tests actuales | `full-app-flow.spec.mjs` |
| Riesgo | Seguro |

## 4. Flujos Construccion

### 4.0 Detalle operativo Construccion

Esta seccion baja los flujos de Construccion al nivel de controles visibles, campos y funciones. Los nombres entre backticks son IDs, funciones JS, rutas o campos reales del repo.

#### Navegacion comun de obra

1. El usuario entra por `/login`, selecciona Da Porto y queda con `project_id=73`, `dbPrefix=da_porto`, `area=Construccion`.
2. `cargarDatosGeneralesPagina(seccion)` inyecta navbar, contexto de proyecto, semana, modulos y permisos.
3. La barra superior expone:
   - `Información General`: `info_profesionales`, `info_subcontratistas`, `info_listadoActividades`, `info_contratos`, `planCompras`, `informe_lps`, `actualizarCronograma`.
   - `Integración`: `controlCambios`.
   - `Semanas del Proyecto`: selector de semanas, `Nueva Semana`, eliminar semana segun permisos.
   - `Programa General`, `Liberación de Restricciones`, `Programación Semanal`.
4. En desktop se usan menus superiores; en mobile se abre `drawerToggle`, se cierra con `drawerClose` o `drawerOverlay`.
5. `maestroPermisos(permiso)` oculta botones segun rol. En roles `V`/`C` no se deben mostrar acciones de escritura.

#### PG - Programa General

Entrada: `/programa-general`.

Botones y filtros:

| Control | ID/clase | Funcion |
|---|---|---|
| Leyenda | `.leyenda_colores` | Abre `#modal_leyenda_colores` y renderiza guia con `renderLegendModal()` |
| Actualizar Ejecucion | `#actualizarEjecucion` | Ejecuta `actualizarEjecucion()` contra `/api/general/update-batch` |
| Descargar Corte | `#descargarCorteProgramacion` | Ejecuta `descargarCorteProgramacion()` contra `/reportes/corte-programacion` |
| Exportar CSV | `#btn-export` | Ejecuta `exportCsv()` desde Handsontable |
| Recargar | `#btn-refresh` | Ejecuta `loadData()`/recarga grilla |
| Filtros mobile | `.btn-filter-toggle` | Expande `#pdcFiltersMobile` |
| Chips de leyenda | `.pdc-legend-item[data-filter]` | Ejecutan `toggleLegendFilter()`/`window.filterPDC()` |

Columnas/campos visibles de la grilla:

| Campo | Uso | Edicion |
|---|---|---|
| `Id` | WBS visible | Solo lectura |
| `codigo_actividad` | Codigo estandar de actividad | Dropdown si el rol puede editar |
| `Actividad` | Nombre/descripcion renderizada | Solo lectura |
| `Semanas_Inicio` | Semana relativa al inicio | Solo lectura |
| `Fecha_Inicio` | Fecha inicio plan | Editable por roles PG |
| `Fecha_Fin` | Fecha fin plan | Editable por roles PG |
| `Ruta_Critica` | Marcador ruta critica | Solo lectura |
| `unidad` | Unidad de medida | Dropdown; cambio puede pedir confirmacion si hay cantidad |
| `cantidad_ppto` | Cantidad presupuesto | Numerico editable |
| `Ejecutado_Teorico` | Avance teorico | Solo lectura |
| `EjecutadoDisplay` | Avance ejecutado visible | Editable, normalizado a ratio |
| `Estado` | Estado calculado | Solo lectura |
| `Estado_Restricciones` | Restricciones liberadas | Solo lectura |

Funciones principales:

- `PGHotModule.init()`: inicializa permisos, configuracion de restricciones, handlers y datos.
- `fetchRestrictionConfig()`: trae `/api/general/restriction-config`.
- `fetchCodigosActividad()`: trae `/api/general/codigos`.
- `fetchFilterFlags()` y `requestList()`: leen filtros y datos.
- `buildListUrl(extraFlags)`: arma `/api/general/list?db=...&semana=...`.
- `classifyPGRow(data)`: calcula estado visual y alerta.
- `normalizeCellValue(prop, value)`: normaliza fecha, unidad, porcentaje y numericos.
- `buildUpdatePayload(rowData, prop, overrides)`: prepara payload de guardado.
- `saveRow(visualRow, prop, oldValue, source, options)`: persiste en `/api/general/update`.
- `revertCell()`: revierte si falla validacion/API.
- `applyFiltersAndRender()` y `updateLegendCounts()`: actualizan filtros y contadores.
- `LPSContextualDrawer.init(hot, 'programa-general', classifyPGRow)`: habilita comentarios/crisis por fila.

Flujo paso a paso:

1. Abrir PG.
2. `cargaParametros()` llama `PGHotModule.init()`.
3. Se leen hidden fields: `baseDatos_PHP`, `semana_PHP`, `permiso_canonico`, `area_PHP`.
4. Se carga configuracion de restricciones y codigos.
5. Se llama `/api/general/list`.
6. Se renderiza Handsontable en `#hot-container`.
7. Usuario filtra por chips: Con Alerta Restricciones, Debe Iniciar, Actividad Futura, En Curso, Atrasada, Terminada, Sin Datos.
8. Usuario edita campo permitido.
9. `afterChange` normaliza y llama `saveRow()`.
10. Si API responde OK, aparece `#save-status`; si falla, `#save-error` y se revierte.
11. Si descarga corte, se pide `/reportes/corte-programacion` y se navega a la URL generada.

#### Programa General Actualizar

Entrada: `/programa-general-actualizar`.

Campos principales de la grilla de actualizacion:

| Campo | Uso |
|---|---|
| `unique_id` | Identidad tecnica de actividad |
| `Id` | WBS visible |
| `Actividad` | Actividad actual |
| `programaAnteriorAsociar` | Mapeo contra programa anterior |
| `Fecha_Inicio`, `Fecha_Fin` | Nuevas fechas |
| `unidad`, `cantidad_ppto` | Presupuesto/cantidad |
| `Estado_Restricciones` | Estado de liberacion |
| `Ejecutado` | Ejecucion real |

Funciones principales:

- `HOTActualizarModule.init()`: inicializa vista.
- `loadData()`: carga datos de actualizacion.
- `autoSaveRow()`: guarda cambios de una fila.
- `flushPendingChanges()`: envia cambios pendientes.
- `runAutoAssociate()`: llama `/api/general/auto-associate`.
- `showReviewModal(results)`: muestra revision de asociaciones.
- `_saveReviewDecisions()`: registra decisiones en `/api/general/decision-log`.

Flujo paso a paso:

1. Entrar desde navbar `Actualizar Cronograma`.
2. Cargar grilla HOT de actualizacion.
3. Revisar asociaciones con programa anterior.
4. Ajustar `Fecha_Inicio`, `Fecha_Fin`, `unidad`, `cantidad_ppto` o `Ejecutado` segun permisos.
5. El autosave envia `/api/general/update?db=...&semana_objetivo=...`.
6. Si se usa auto-asociacion, revisar candidatos, confianza y decidir aceptar/rechazar.
7. Guardar decisiones para trazabilidad.

#### PI - Liberacion de Restricciones

Entrada: `/programacion-intermedia`.

Botones y controles:

| Control | ID/clase | Funcion |
|---|---|---|
| Exportar CSV | `#btn-export` | `exportCsv()` |
| Descargar Corte | `#btn_informe_compromisos` | `descargarReporte()` contra `/reportes/restricciones` |
| Refrescar listas | `#btn-refresh-listas` | Recarga listas de profesionales/subcontratistas |
| Limpiar buscador | `#btn_limpiar_buscador` | Limpia filtro rapido |
| Ver todas | `set-view-all` | Cambia modo de visualizacion |
| Selector compartido | `__shared_selected` | Marca filas para aplicacion masiva |
| Usar marcadas | `#btn_pi_shared_use_marked` | Carga IDs marcados |
| Usar visibles | `#btn_pi_shared_use_visible` | Carga IDs visibles/filtrados |
| Limpiar IDs | `#btn_pi_shared_clear_ids` | Limpia seleccion masiva |
| Todas restricciones | `#btn_pi_shared_select_all_restrictions` | Marca restricciones compartidas |
| Limpiar restricciones | `#btn_pi_shared_clear_restrictions` | Desmarca restricciones compartidas |
| Ver Conflictos | `#btn_pi_shared_preview` | Llama preview |
| Aplicar | `#btn_pi_shared_apply` | Aplica cambios compartidos |

Columnas/campos:

| Campo | Uso |
|---|---|
| `Id` | WBS visible |
| `__shared_selected` | Checkbox de seleccion masiva |
| `Actividad` | Actividad |
| `Sub_Contratista` | TomSelect multiple |
| `Responsable_AIA` | TomSelect single |
| `Semanas_Inicio` | Distancia temporal |
| `Ejecutado` | Avance |
| Restricciones duras | Construccion: `D_y_E`, `Materiales`, `MdeO`, `Equipos`, `Predecesora` |
| Restricciones blandas | Construccion: `Pdto_Cons`, `Modelo` |
| `Estado_Restricciones` | Porcentaje calculado |
| `estado_operativo` | Estado operativo con drawer |
| `Observaciones` | Texto libre |

Funciones principales:

- `fetchRestrictionConfig()`: lee configuracion.
- `buildColumnDefinitions()` y `buildColumnHeaders()`: construyen columnas dinamicas.
- `normalizeRestrictionValue()` y `normalizeRestrictionForPayload()`: normalizan valores.
- `saveRow()`: persiste `/api/pi/save`.
- `requestSharedConstraintPreview()`: llama `/programacion-intermedia/shared-constraints/preview`.
- `requestSharedConstraintApply()`: llama `/programacion-intermedia/shared-constraints/apply`.
- `autoPopulateHomogeneousRestrictions()`: precarga valores uniformes de filas marcadas.
- `openOperationalStateDrawer()`: abre detalle operativo.
- `exportCsv()` y `descargarReporte()`: exportacion/reporte.

Flujo paso a paso:

1. Abrir PI.
2. Cargar restricciones, profesionales y subcontratistas.
3. Listar actividades con `/api/pi/list`.
4. Editar `Sub_Contratista`, `Responsable_AIA`, restricciones u `Observaciones`.
5. En cada cambio, `afterChange` normaliza valor y llama `saveRow()`.
6. Si se requiere cambio masivo, marcar filas en la columna checkbox.
7. Abrir bloque compartido, elegir si se aplican restricciones, asignaciones o ambos.
8. Elegir restricciones y valores.
9. Presionar `Ver Conflictos`.
10. Revisar actividades afectadas, conflictos y mensajes.
11. Presionar `Aplicar`; si la configuracion cambio despues del preview, la UI advierte.
12. Recargar/listar para validar persistencia.

#### PS - Programacion Semanal

Entrada: `/programacion-semanal`.

> **Rol `V` (Visualizador): no ve los botones de accion, y ademas el servidor los rechaza.**
> Medido el 2026-07-30 en `PDC Sandbox E2E` (990100), viewport 1180x820. Con `V`,
> `#btn_autoprogramar`, `#btn_agregar_actividad`, `#btn_cerrar_compromisos_semana`,
> `#btn_reabrir_semana` y `#btn_tnp` quedan ocultos; con `R`, los tres primeros visibles.
> Hasta ese dia los tres primeros salian **visibles pero `disabled`** para `V`: `maestroPermisos('V')`
> en `public/js/cargarDatosGeneralesPagina2.js` les ponia `display:none` y despues `syncPhaseUI()` en
> `public/js/modules/programacion_semanal/hot.js` los volvia a mostrar con un `.show()` incondicional.
> Ese `.show()` ahora esta condicionado a `canManageToolbarActions()`, la misma funcion que decide el
> `disabled`, para que visibilidad y habilitacion no puedan divergir.
> Era inconsistencia cosmetica, no escalada: el servidor rechaza con **403** todas las
> mutaciones (`save` con `autoprogramar|bloquear_compromisos|tnp|nuevo`, `reabrir` y `auto-program`),
> porque los tres entrypoints llaman `rbac_guard_require_permission('lps.programacion_semanal.editar')`
> y `V` no tiene ese permiso. Contrastado con `R`, que recibe 200 en los mismos endpoints.
> El candado vive en `tests/test_semanal_rbac_solo_lectura.php`.

Botones y navegacion:

| Control | ID | Funcion |
|---|---|---|
| Leyenda | `.leyenda_colores` | Abre `#modal_leyenda_colores_ps` |
| Autoprogramar Actividades | `#btn_autoprogramar` | Llama `/api/semanal/auto-program` |
| Agregar Actividad | `#btn_agregar_actividad` | Abre modal de nueva actividad |
| Confirmar Compromisos | `#btn_cerrar_compromisos_semana` | Abre `#modal_cerrar_compromisos` |
| Registrar TNP | `#btn_tnp` | Abre `#modal_tnp` |
| Imprimir | `#btn_informe_compromisos` | Llama `/reportes/compromisos` |
| Exportar CSV | `#btn-export` | Exporta Handsontable |
| Recargar | `#btn-refresh` | Recarga lista |
| Dropdown Actividades | `#btn_Actividades` | Mantiene PS |
| Causas No Programacion | `#btn_CNP` | Navega a `/programacion-semanal/cnp` |
| Causas No Cumplimiento | `#btn_CNC` | Navega a `/programacion-semanal/cnc` |
| Calificacion Proveedores | `#btn_Cal_Proveedores` | Navega a `/programacion-semanal/cic` |

Columnas/campos:

| Campo | Uso |
|---|---|
| `Consecutivo`, `Id`, `codigo_actividad` | Identidad |
| `Actividad`, `Ubicacion` | Descripcion y ubicacion |
| `Prog_Sin_Restricciones_100` | Liberacion PI |
| `Sub_Contratista`, `Responsable_AIA` | Responsables |
| `Empresa`, `Unidad`, `cantidad_ppto` | Datos base |
| `Ejecutado`, `Ejecutado_Fin_Semana` | Avance heredado/calculado |
| `cantidad_sugerida_auto` | Sugerencia de autoprogramacion |
| `Compromiso` | Cantidad comprometida |
| `Ejecutado_Real` | Avance real semanal |
| `PAC`, `P_Completado` | Cumplimiento |
| `Categoria_CNC`, `CNC`, `Observaciones_CNC` | Cierre y causa |
| `estado_operativo` | Estado con drawer |
| Acciones | Renderer `psActionsRenderer` |

Modales y campos:

- `#modal_cerrar_compromisos`: botones `#btn_confirmar_compromisos_semana`, `#btn_cancelar_compromisos_semana`.
- `#modal_aceptar_cerrar_compromisos`: cierre de alerta.
- Bandeja no autoprogramadas: `#btn_recargar_bandeja_no_autoprogramadas`.
- Nueva actividad: `#btn_guardar_nueva_actividad`, `#btn_listar`.
- Eliminar actividad: `#btn_confirmar_eliminar_actividad`, campos de justificacion de eliminacion.
- CNC inline: `#modal_cnc_hot`, `#btn_guardar_cnc_hot`, `#btn_cancelar_cnc_hot`.
- TNP: `#modal_tnp`, `#btn_guardar_tnp`; campos de actividad no planificada, responsable, cantidad y observacion.

Funciones principales:

- `loadData()`/AJAX `/api/semanal/list`.
- `saveRow()`: guarda campo editado.
- `psActionsRenderer`: pinta acciones por fila.
- `openOperationalStateDrawer()`: detalle operativo.
- `autoprogramar`: usa `/api/semanal/auto-program`.
- `loadTnpActivities()`: usa `/api/semanal/tnp-actividades`.
- `guardar_cnc_hot`/modal CNC: usa `/api/cnc/reasons` y `/api/semanal/save`.
- `descargarReporte()`: usa `/reportes/compromisos`.

Flujo paso a paso:

1. Abrir PS.
2. Cargar actividades liberadas y compromisos de la semana.
3. Revisar alertas visuales.
4. Si faltan actividades, usar `Autoprogramar Actividades`.
5. Revisar bandeja de no autoprogramadas si aparece.
6. Editar `Sub_Contratista`, `Responsable_AIA`, `Compromiso` o `Ejecutado_Real`.
7. Guardado automatico via `/api/semanal/save`.
8. Si se va a cerrar semana, abrir `Confirmar Compromisos`, confirmar y validar mensaje.
9. Al cierre, si una actividad no cumple, abrir CNC y elegir categoria/razon/observacion.
10. Si hubo trabajo no planeado, usar `Registrar TNP`, completar campos y guardar.
11. Imprimir compromisos si se requiere archivo.

#### CNP, CNC y CIC

| Modulo | Ruta | Botones/campos | Funcion/API |
|---|---|---|---|
| CNP | `/programacion-semanal/cnp` | Tabla DataTable, acciones de reprogramacion | `/api/cnp/list`, `/api/cnp/save`, `/api/cnp/reprogramar` |
| CNC | `/programacion-semanal/cnc` | Categoria, razon, observaciones | `/api/cnc/list`, `/api/cnc/reasons`, `/api/cnc/save` |
| CIC | `/programacion-semanal/cic` | Calificacion integral por proveedor/especialidad | `/api/cic/list`, `/api/cic/save` |

Flujo CNP:

1. Abrir desde dropdown PS.
2. Listar causas no programadas.
3. Revisar actividad, semana, responsable y categoria.
4. Completar causa/observacion.
5. Si aplica, ejecutar reprogramacion.
6. Guardar.

Flujo CNC:

1. Abrir desde dropdown PS o modal CNC.
2. Seleccionar categoria.
3. Cargar razones con `/api/cnc/reasons`.
4. Seleccionar causa especifica.
5. Completar observacion.
6. Guardar y confirmar.

Flujo CIC:

1. Abrir Calificacion Proveedores.
2. Listar proveedores de la semana.
3. Completar calificacion por criterio visible.
4. Guardar.
5. Validar actualizacion de metricas.

#### Listado de Actividades

Entrada: `/listado-actividades`.

Tabla visible:

| Columna | Uso |
|---|---|
| Acciones | Eliminar en tabla; Editar/Eliminar en tarjeta mobile |
| `codigo` | Codigo de familia |
| `actividad` | Nombre actividad |
| `descripcionActividad` | Descripcion |
| `actividadInicio`/`nombreActividadInicio` | Actividad PG vinculada |
| `fechaInicio` | Fecha derivada de la actividad PG seleccionada |
| `tipoContrato` | Tipo asociado |
| `semanaActualizacion` | Semana interna |

Botones y modales:

- `#btn_cargarActividadesExcel`: abre `#modalCargarExcel`.
- `#btn_nueva_actividad`: abre `#modalNuevaActividad`.
- `#btn_auto_generar_listado`: abre `SemiAutoReview.open('listado-actividades')` si esta disponible.
- `#btn_Actividades`, `#btn_contratos`, `#btn_planCompras`: switcher Listado/Contratos/PDC.
- En tablet/desktop, las celdas autorizadas se editan directamente en Handsontable; la columna de acciones conserva solo Eliminar.
- En mobile, cada registro es una tarjeta; `Editar` habilita actividad de inicio y modalidad, `Guardar` persiste y `Cancelar` descarta el estado local.
- `Eliminar` abre `#modalEliminar` y exige confirmacion.

Campos de nueva actividad:

- `actividad`.
- `descripcionActividad`.
- `actividadInicio`: select2 con actividades de `programa_consolidado`.
- `fechaInicio`: se actualiza con `actualizarFechaInicio('nuevo')`.
- `btn_guardar_actividad`, `btn_listar`.

Campos de Excel:

- Descargar plantilla: `#descargarArchivoBase` contra `/api/listado-actividades/template`.
- Archivo: `archivoExcel`, acepta `.csv`.
- Guardar: submit con `opcion=cargarExcel`.

Funciones:

- `listar()`: prepara toolbar, permisos y `ListadoActividadesHotModule.init()`.
- `ListadoActividadesHotModule.loadData()`: carga `/api/listado-actividades/list` y sincroniza HOT/tarjetas.
- `afterChange`: guarda celdas por `/api/listado-actividades/update-cell` y revierte errores.
- El submit de `#modalNuevaActividad form` envia `opcion=registrar`.
- El submit de `#formCargarExcel` valida y envia el CSV completo.
- La edicion desktop/tablet usa editores nativos HOT; la edicion mobile se limita a la tarjeta activa.
- `obtener_id_eliminar()` y `eliminar()`: confirman y eliminan.
- `actualizarFechaInicio(funcion)`: sincroniza fecha desde actividad PG.
- `recargarTabla(opcion)`: solicita datos de nuevo sin destruir ni duplicar la instancia HOT.

#### Contratos

Entrada: `/contratos`.

Matriz funcional detallada: `docs/qa/contratos-escenarios.md`.

Tabla visible:

| Campo | Uso |
|---|---|
| Acciones | Editar |
| `codigo`, `actividad`, `descripcionActividad` | Base de actividad |
| `fechaInicio` | Inicio |
| `tipoContrato` | Modalidades asociadas |
| `SI1..SI5`, `S1..S5`, `MO1..MO5`, `OC1..OC5` | Insumos/recursos ocultos |
| `paqueteSI1..5`, `paqueteS1..5`, `paqueteMO1..5`, `paqueteOC1..5` | Paquetes ocultos |
| Cantidad por paquete | Numero de contratos requerido para cada paquete |
| `contratosAsociados` | Resumen visible |

Modal `#modalEditarContratos`:

- Checkboxes de modalidad:
  - `#modalidadSI`: Suministro e Instalacion.
  - `#modalidadMO`: Mano de Obra.
  - `#modalidadS`: Suministro.
  - `#modalidadOC`: Orden de Compra.
- Hidden fields:
  - `tipoContrato`.
  - `actividadModificar`.
- Por cada modalidad hay 5 filas:
  - Paquete: `paqueteSI1..5`, `paqueteMO1..5`, `paqueteS1..5`, `paqueteOC1..5`.
  - Recursos multiples: `SI1..5`, `MO1..5`, `S1..5`, `OC1..5`.
  - Cantidad de contratos por paquete: entero controlado por stepper.
- Botones:
  - `#btn_guardar_contratos`.
  - `#btn_cancelar_contratos`.

Reglas funcionales:

- `SI` es exclusivo; `MO`, `S` y `OC` pueden combinarse.
- El usuario decide la cantidad de contratos por paquete; no se infiere automaticamente.
- El sistema registra en que semana cambia `actividadInicio`, `fechaInicio`, modalidad, paquetes, recursos o cantidades.
- Si falta duracion contractual, se debe solicitar definicion explicita de las 7 duraciones antes de dejar el contrato completo.
- `/pdc/` recibe los datos ya definidos por `/contratos/` y usa su flujo existente de recalculo.

Auto-definir:

- Boton `#btn_auto_asignar_contratos`.
- Badge `#badgePendientesContratos`.
- Abre `SemiAutoReview.open('contratos')`.
- Los endpoints legacy `/api/contratos/auto-define*` responden deprecacion `410` y apuntan al asistente moderno.

Funciones:

- `listar()`: carga `/api/contratos/list`.
- `obtener_data_editar()`: abre modal y carga paquetes/recursos.
- `syncHiddenTipoContrato()`: sincroniza checkboxes con campo hidden.
- `updateSections()`: muestra secciones por modalidad.
- `guardar_modificar()`: guarda con `/api/contratos/save`.
- El preview, apply y undo automatizado viven en el asistente semi-auto compartido.
- `actualizarBadgePendientesContratos()`: recalcula pendientes.

#### PDC / Plan de Compras

Entrada: `/pdc`.

Toolbar:

| Control | ID | Funcion |
|---|---|---|
| Actualizar | `#btn_actualizarPDC` | `actualizarPDC()` |
| Desglosar | `#btn_definirContratosPDC` | `obtener_data_definirContratos()` |
| Solo Alertas | `#btn_soloAlertas` | `toggleSoloAlertas()` |
| Switcher | `#btn_Actividades`, `#btn_contratos`, `#btn_planCompras` | Navegacion entre modulos |

Tabla PDC:

| Campo | Uso |
|---|---|
| `boton` | Editar/eliminar o icono de estado |
| `tipoPaquete` | Tipo de paquete |
| `paqueteContratacion` | Nombre paquete |
| `contratos` | Contratos asociados |
| `estado` | Estado calculado |
| `fechaElaboracionPliegos` | Primera fecha visible |
| Fechas/dias ocultos | Duraciones y fechas por etapa |
| `fechaInicio`, `fechaInicioProyectada`, `fechaRealInicio` | Inicio programado/proyectado/real |
| `observacionesContrato` | Observaciones |
| `ordenVisual` | Orden interno |

Modal `#modalContrato`, secciones y campos:

1. Descripcion del Proceso:
   - `actividadesDelContrato` readonly.
   - `fechaActual` readonly.
   - `fechaInicioContrato` readonly.
   - `valorPresupuesto` moneda.
2. Proceso de Contratacion, por etapa:
   - Etapas: `ElaboracionPliegos`, `EntregaPliegos`, `ReciboPropuestas`, `CuadrosComparativos`, `LegalizacionContrato`, `Fabricacion`, `InsumosObra`, `InicioProyectadaContrato`.
   - Por etapa: `dias...`, `fecha...Teorica`, `fecha...`, `fechaReal...`.
   - Cambios recalculan con `calcularProcesoContratacionTeorico(etapa)`.
3. Proveedor adjudicado:
   - `nitAdjudicado`, `subcontratistaAdjudicado`, `correoAdjudicado`, `tipoProveedorAdjudicado`.
   - `verificarProveedor('nitAdjudicado')` bloquea datos si ya existe.
4. Contrato adjudicado:
   - `numeroContrato`, `aplicaPolizas`, `fechaVencimientoPolizas`.
   - `valorPrimeraNegociacion`, `valorAdjudicado`, `valorAnticipo`, `valorAhorroPerdida`.
5. Seguimiento:
   - `valorReclamado`, `valorDevoluciones`, `tasaDevoluciones`.
6. Observaciones:
   - `observacionesContrato`.
7. Botones:
   - `#btn_guardar_pdc`.
   - `#btn_cancelar_editar`.

Modal `#modalDefinirContratos`:

- Tabla `#dt_definirContratos`.
- Campos: Consecutivo, Tipo de Contrato, Paquete de Contratacion, Numero de Contratos Asociados, `subcontratoPaquete`, `ordenVisual`.
- Botones: `#btn_guardar_definirContratos`, `#btn_cancelar_definirContratos`.

Funciones:

- `listar()`: carga `/api/pdc/list`.
- `actualizarPDC()`: actualiza items desde contratos.
- `getPDCState()` y `applyPDCRowState()`: calculan estado visual.
- `populatePdcSelectFilterOptions()` y `bindPdcColumnFilters()`: filtros por columna.
- `filterPDC(state, event)`: filtra por leyenda.
- `guardar_DefinirContratos()`: guarda desglose.
- `guardar_pdc`/handler de `#btn_guardar_pdc`: persiste `/api/pdc/save`.
- `toggleSoloAlertas()`: limita a paquetes con riesgo.

#### Profesionales

Entrada: `/profesionales`.

Campos esperados:

- Nombre.
- Email.
- Cargo.
- Acciones de crear, guardar cambios y eliminar.

Funciones/flujo:

1. `loadData()` llama `/api/profesionales/list`.
2. Handsontable muestra catalogo.
3. Usuario edita celda o agrega fila.
4. `saveRowChanges()`/autosave manda `/api/profesionales/save`.
5. Si se elimina, se envia `opcion=eliminar`.

#### Subcontratistas

Entrada: `/subcontratistas`.

Campos Construccion:

- `subcontratista`.
- `NIT`.
- `alcance`.
- `tipo_proveedor`.
- `correo_contacto`.
- Accion eliminar o candado si tiene registros asociados.

Funciones:

- `loadData()`: `/api/subcontratistas/list` con `opcion=listar`.
- `buildSubcontratistaPayload()`: arma payload.
- `collectSubcontratistaValidationErrors()`: valida campos.
- `createSubcontratista()`: crea nuevo registro.
- `saveRowChanges()` y `autosave()`: guardan ediciones.
- `deleteRow()`: elimina.
- `exportCSV()`: exporta catalogo.
- Mobile: `renderMobileCards()`, `addMobileSubcontratista()`, `updateMobileRow()`, `deleteMobileRow()`.

#### Indicadores

Entrada: `/indicadores`.

Flujo:

1. Abrir modulo.
2. Ejecutar generacion con `/api/indicadores/generar`.
3. Backend lee semana/proyecto y calcula indicadores LPS.
4. Mostrar resultado o archivo/respuesta segun vista.

Campos/funciones a validar en tests futuros:

- Proyecto y semana activos.
- Respuesta JSON parseable.
- Persistencia en `indicadores_generales` cuando aplique.
- No mezclar `project_id`.

#### Control de Cambios

Entrada: `/control-cambios`.

Tabla:

- Lista ordenes de cambio con `/api/control-cambios/list`.
- Acciones: crear, editar, eliminar.

Modal `#modalordenDeCambio`, campos:

- Identificacion: `inputConsecutivo`, `inputProyecto`, `inputDirector`, `inputFechaSolicitud`.
- Solicitante: `inputSolicitanteCambioObra`, `Cliente`, `Interventoria`, `Otro`, y `inputDetalleSolicitanteOtro`.
- Prioridad: `inputPrioridadAlta`, `Media`, `Baja`.
- Tipo de cambio: `inputTipoCambioAlcance`, `Cronograma`, `Costo`, `Calidad`, `Riesgo`, `Recurso`.
- Responsable solucion: `inputResponsableSolucionObra`, `Cliente`, `Interventoria`, `Otro`, y `inputDetalleResponsableSolucion`.
- Textos: `inputJustificacion`, `inputDescripcion`, `inputIncidenciaAlcance`, `inputIncidenciaCronograma`, `inputIncidenciaPresupuesto`, `inputIncidenciaCalidad`, `inputIncidenciaRiesgo`, `inputIncidenciaRecurso`.
- Cronograma: `inputTiempoCronograma`, `inputTiempoCronogramaAfectado`, `inputPorcentajeAfectacionCronograma`.
- Presupuesto: `inputValorPresupuesto`, `inputCostoDirecto`, `inputCostoDirectoAIU`, `inputCostoDirectoAIUIVA`, `inputValorAprobado`, `inputPorcentajeAfectacionPresupuesto`.
- Fechas: `inputFechaEntregaInterventoria`, `inputFechaTentativaDefinicion`, `inputFechaDefinicion`.
- Aprobacion: `inputAprobacionEstudio`, `Aprobado`, `AprobadoRestricciones`, `NoAprobado`, `Desistido`.
- Soportes: `agregarSoporte()`.
- Botones: `#btn_guardarOrden`, `#btn_generarPDFOrden`, `#btn_cancelarOrden`.

Funciones:

- `guardar_modificar()`: crea o actualiza con `/api/control-cambios/save`.
- `eliminar()`: elimina orden seleccionada.
- `recargarTabla('listar')`: refresca DataTable.
- `cerrarTodosModales()`: limpia modales abiertos.

### 4.1 Programa General (PG)

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programa-general`.
2. Cargar grilla Handsontable.
3. Consultar datos con `/api/general/list`.
4. Consultar configuracion de restricciones con `/api/general/restriction-config`.
5. Editar celdas permitidas segun rol.
6. Guardar con `/api/general/update` o `/api/general/update-batch`.
7. Importar Excel o eliminar actualizaciones si el rol lo permite.
8. Mantener identidad visible WBS (`Id`) y tecnica (`unique_id`/`row_id`).

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programa-general`, `/programa-general/filtros`, `/programa-general/set-filtro` |
| API | `/api/general/list`, `/api/general/update`, `/api/general/update-batch`, `/api/general/import`, `/api/general/delete-update`, `/api/general/codigos`, `/api/general/auto-associate`, `/api/general/decision-log`, `/api/pg/breadcrumb-*` |
| Controladores | `ProgramaGeneralController`, `GeneralApiController`, `PgBreadcrumbController` |
| Tablas | `programa`, `programa_consolidado`, `pg_tracking` |
| Tests actuales | `full-app-flow.spec.mjs`, `program-unique-id-refactor.mjs`, `test-pg-color-fix.mjs`, `test_program_unique_id_refactor.php`, `test_unique_id_runtime_gate.php` |
| Riesgo | Muta datos si se edita/importa |

### 4.2 Programa General Actualizar

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programa-general-actualizar`.
2. Cargar vista para actualizacion del programa.
3. Usar APIs de Programa General segun accion disponible.
4. Persistir cambios en tablas globales.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programa-general-actualizar` |
| Controlador | `ProgramaGeneralActualizarController` |
| Tablas | `programa`, `programa_consolidado`, `pg_tracking` |
| Tests actuales | Cobertura indirecta por gates de PG; sin E2E dedicado claro |
| Riesgo | Muta datos |

### 4.3 Programacion Intermedia (PI)

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programacion-intermedia`.
2. Cargar Handsontable.
3. Consultar datos con `/api/pi/list`.
4. Editar restricciones si el rol tiene permiso.
5. Guardar con `/api/pi/save`.
6. Para restricciones compartidas, ejecutar preview.
7. Revisar resultado.
8. Aplicar con `/programacion-intermedia/shared-constraints/apply`.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programacion-intermedia`, filtros y `set-view-all` |
| API | `/api/pi/list`, `/api/pi/save`, `/programacion-intermedia/shared-constraints/preview`, `/apply` |
| Controlador | `ProgramacionIntermediaController` |
| Tablas | `programa_consolidado`, `pi_shared_constraints`, `pi_shared_constraint_links` |
| Tests actuales | `full-app-flow.spec.mjs`, `test_pi_shared_payload_smoke.php` |
| Riesgo | Muta restricciones |

### 4.4 Programacion Semanal (PS)

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programacion-semanal`.
2. Cargar compromisos semanales.
3. Consultar datos con `/api/semanal/list`.
4. Editar compromisos o ejecucion segun rol.
5. Guardar con `/api/semanal/save`.
6. Auto-programar con `/api/semanal/auto-program` cuando aplique.
7. Consultar log y actividades TNP.
8. Validar gobernanza semanal y arrastre de avance real.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programacion-semanal` |
| API | `/api/semanal/list`, `/api/semanal/save`, `/api/semanal/auto-program`, `/api/semanal/auto-program-log`, `/api/semanal/tnp-actividades` |
| Controladores/servicios | `ProgramacionSemanalController`, `SemanalApiController`, `ProgramChangeDetector`, `WeeklyRealProgressCarryoverService` |
| Tablas | `programacion_semanal`, `programa`, `auto_program_log`, `semanas_activas` |
| Tests actuales | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs`, `test_weekly_governance.php` |
| Riesgo | Muta datos operativos |

### 4.5 CNP

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programacion-semanal/cnp`.
2. Consultar CNP con `/api/cnp/list`.
3. Guardar cambios con `/api/cnp/save`.
4. Reprogramar con `/api/cnp/reprogramar` cuando aplique.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programacion-semanal/cnp` |
| API | `/api/cnp/list`, `/api/cnp/save`, `/api/cnp/reprogramar` |
| Controlador | `CnpApiController` |
| Tablas | `programacion_semanal`, `auto_program_log` |
| Tests actuales | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs` |
| Riesgo | Muta datos/reprograma |

### 4.6 CNC

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programacion-semanal/cnc`.
2. Consultar CNC con `/api/cnc/list`.
3. Consultar razones con `/api/cnc/reasons`.
4. Guardar clasificacion con `/api/cnc/save`.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programacion-semanal/cnc` |
| API | `/api/cnc/list`, `/api/cnc/save`, `/api/cnc/reasons` |
| Controlador | `CncApiController` |
| Tablas | `programacion_semanal`, `indicadores_generales` |
| Tests actuales | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs` |
| Riesgo | Muta clasificacion operativa |

### 4.7 CIC

Tipo: frontend/backend.

Pasos:

1. Entrar a `/programacion-semanal/cic`.
2. Consultar CIC con `/api/cic/list`.
3. Generar subcontratistas faltantes si la API lo requiere.
4. Guardar cambios con `/api/cic/save`.
5. Actualizar metricas internas.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/programacion-semanal/cic` |
| API | `/api/cic/list`, `/api/cic/save` |
| Controlador | `CicApiController` |
| Tablas | `cic`, `subcontratistas`, `indicadores_generales` |
| Tests actuales | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs` |
| Riesgo | Muta control de compromisos |

### 4.8 Listado de Actividades

Tipo: frontend/backend/semi-auto.

Pasos:

1. Entrar a `/listado-actividades`.
2. Cargar una unica instancia Handsontable o tarjetas mobile desde la misma fuente.
3. Validar paridad con `/api/listado-actividades/list`.
4. Crear/eliminar con `/save`, editar celdas con `/update-cell` y tarjetas con `/update-card`.
5. Descargar plantilla si aplica.
6. Generar actividades desde PG con `/api/listado-actividades/auto-generate`.
7. Usar semi-auto con preview/apply/undo/feedback.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/listado-actividades` |
| API | `/api/listado-actividades/template`, `/list`, `/save`, `/update-cell`, `/update-card`, `/auto-generate`, `/auto/*` |
| Controladores/servicios | `ListadoActividadesApiController`, `SemiAutoController`, `SemiAutoService` |
| Tablas | `actividades`, `programa`, semi-auto tables |
| Tests actuales | `tests/browser/listado-actividades-handsontable.mjs`, `e2e/tests/workflows/listado-full.spec.mjs`, `test_listado_actividades_project_scope.php` |
| Riesgo | Muta actividades |

### 4.9 Contratos

Tipo: frontend/backend/semi-auto.

Pasos:

1. Entrar a `/contratos`.
2. Listar con `/api/contratos/list`; abrir filtros desde los encabezados, combinarlos y limpiar ambos desde el menú visible.
3. Guardar cambios con `/api/contratos/save`.
4. Abrir el modal del registro correcto, editar modalidades, paquetes progresivos hasta cinco, cantidades, recursos y siete duraciones.
5. Cancelar sin escrituras y guardar con una sola petición; recargar y comprobar persistencia.
6. Ejecutar semi-auto `preview`, revisar/editar, seleccionar, aplicar, recargar y deshacer de forma atómica.
7. Verificar sesiones reales editor/readOnly, restauración y paridad semántica HOT/tarjetas/API.
8. Repetir Mobile, Tablet horizontal y Desktop en Dark/Linen sin DataTables, overflow, recorte, HTML crudo ni fallos inesperados.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/contratos` |
| API | `/api/contratos/list`, `/save`, `/auto-assign`, `/auto/*`, `/auto/assistant/*`, `/auto/learning/*`; `/auto-define*` solo deprecacion |
| Controladores/servicios | `ContratosApiController`, `SemiAutoController`, `SemiAutoService`, `ActivityMatcher` |
| Tablas | `actividades`, `contratos_trazabilidad`, `general_dias_procesos_contratacion`, `general_auditoria_acciones` y tablas `semi_auto_*` |
| Tests actuales | `tests/browser/contratos-handsontable.mjs`, `tests/browser/auto-definir-contratos.mjs`, `tests/browser/contratos-slot-quantities.mjs`, `e2e/tests/workflows/contratos-full.spec.mjs` y contratos PHP de RBAC, cantidades, duraciones, vacío y semi-auto |
| Riesgo | Muta contratos/actividades |

### 4.10 PDC

Tipo: frontend/backend/semi-auto.

Pasos:

1. Entrar a `/pdc`.
2. Listar con `/api/pdc/list`.
3. Guardar cambios con `/api/pdc/save`.
4. Consultar duracion sugerida o plantillas si aplica.
5. Auto-generar desde contratos.
6. Ejecutar semi-auto preview/apply/undo/feedback.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/pdc` |
| API | `/api/pdc/list`, `/save`, `/duracion-sugerida`, `/plantillas*`, `/auto/apply-from-contratos`, `/auto/*`, `/auto/assistant/*`, `/auto/learning/*` |
| Controladores/servicios | `PdcApiController`, `PdcPlantillaController`, `PdcAutoGenerateController`, `SemiAutoController` |
| Tablas | `pdc`, `papelera_pdc`, `actividades`, semi-auto tables |
| Tests actuales | `full-app-flow.spec.mjs`, `test-pdc.mjs`, `semi-auto-review.mjs` |
| Riesgo | Muta PDC |

### 4.11 Profesionales

Tipo: frontend/backend.

Pasos:

1. Entrar a `/profesionales`.
2. Listar profesionales con `/api/profesionales/list`.
3. Crear profesional.
4. Editar datos.
5. Eliminar fixture temporal si es prueba.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/profesionales` |
| API | `/api/profesionales/list`, `/api/profesionales/save` |
| Controlador | `ProfesionalesApiController` |
| Tablas | `profesionales` |
| Tests actuales | `full-app-flow.spec.mjs` |
| Riesgo | Muta catalogo |

### 4.12 Subcontratistas

Tipo: frontend/backend.

Pasos:

1. Entrar a `/subcontratistas`.
2. En construccion, mostrar titulo "Subcontratistas".
3. Listar con `/api/subcontratistas/list`.
4. Crear, editar y eliminar segun permisos.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/subcontratistas` |
| API | `/api/subcontratistas/list`, `/api/subcontratistas/save` |
| Controlador | `SubcontratistasApiController` |
| Tablas | `subcontratistas` |
| Tests actuales | `full-app-flow.spec.mjs` |
| Riesgo | Muta catalogo |

### 4.13 Indicadores

Tipo: backend/reportes.

Pasos:

1. Entrar a `/indicadores`.
2. Solicitar generacion con `/api/indicadores/generar`.
3. Calcular indicadores de semana/proyecto.
4. Persistir o devolver resultado segun flujo.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/indicadores` |
| API | `/api/indicadores/generar` |
| Controlador | `IndicadoresApiController` |
| Tablas | `indicadores_generales`, tablas operativas de programa |
| Tests actuales | `full-app-flow.spec.mjs` |
| Riesgo | Puede mutar indicadores |

### 4.14 Control de Cambios

Tipo: frontend/backend.

Pasos:

1. Entrar a `/control-cambios`.
2. Listar con `/api/control-cambios/list`.
3. Crear solicitud de cambio.
4. Modificar solicitud.
5. Eliminar solicitud temporal si es prueba.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/control-cambios` |
| API | `/api/control-cambios/list`, `/api/control-cambios/save` |
| Controlador | `ControlCambiosApiController` |
| Tablas | `cambios` |
| Tests actuales | `full-app-flow.spec.mjs` |
| Riesgo | Muta cambios |

### 4.15 Reportes de construccion

Tipo: reportes/riesgo.

Pasos:

1. Entrar a ruta `/reportes/{tipo}` con semana/proyecto.
2. Generar reporte JSON o archivo descargable.
3. Guardar archivo generado en storage si aplica.
4. Descargar URL resultante.
5. Validar que el archivo no este vacio.

Tipos cubiertos por fixtures:

- JSON: `curva-s`, `general`, `restricciones-general`, `pdc`, `subcontratistas`, `run-all`.
- Descargas: `corte-programacion`, `restricciones`, `compromisos`, `consolidado-odc`.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `GET|POST /reportes/{tipo}` |
| Controladores/servicios | `ReportController`, `ReportProcessor` |
| Tablas | `programa`, `programacion_semanal`, `pdc`, `subcontratistas`, `cic`, report tables |
| Tests actuales | `full-app-flow.spec.mjs`, `test_report_processor_project_scope.php` |
| Riesgo | Genera archivos; puede ser pesado |

## 5. Flujos Pre-Construccion

### 5.0 Detalle operativo Pre-Construccion

Pre-Construccion reutiliza la misma arquitectura de rutas y componentes, pero cambia el alcance funcional, textos y configuracion de restricciones. El fixture principal es Aeropuerto Regional PC con `project_id=75`, `dbPrefix=da_aeropuerto_pc`, `area=Pre-Construccion`.

#### Shell y permisos PC

1. Usuario inicia sesion.
2. Selecciona Aeropuerto Regional PC.
3. `ProjectSelectorController::select()` deja el area en sesion.
4. `cargarDatosGeneralesPagina(seccion)` detecta `window.__PROJECT_AREA__`/hidden `area_PHP`.
5. Navbar muestra modulos PC:
   - Programa General.
   - Liberacion de Restricciones.
   - Programacion Semanal.
   - Profesionales.
   - Subcontratistas renombrado a Interesados Externos.
   - Indicadores.
   - Control de Cambios.
6. Navbar oculta modulos de Construccion no aplicables:
   - `info_listadoActividades`.
   - `info_contratos`.
   - `planCompras`.
   - PDC/Plan de Compras.
7. En mobile aplica el mismo drawer, pero sin los items ocultos.

#### PG PC

Entrada: `/programa-general`.

Botones iguales a Construccion:

- Leyenda.
- Actualizar Ejecucion.
- Descargar Corte.
- Exportar CSV.
- Recargar.
- Filtros mobile.

Diferencias visibles:

- La vista muestra etiqueta `Pre-Construcción`.
- La leyenda cambia textos:
  - Con Restriccion Pendiente.
  - Por Iniciar.
  - Actividad Futura.
  - En Ejecucion.
  - Atrasada.
  - Completada.
  - Sin Datos.
- La configuracion de restricciones viene de `window.__RESTRICTION_CONFIG__` cuando existe.

Campos:

| Campo | Uso PC |
|---|---|
| `Id`, `Actividad` | Estructura del cronograma PC |
| `Fecha_Inicio`, `Fecha_Fin` | Fechas de planificacion PC |
| `unidad`, `cantidad_ppto` | Presupuesto o unidad de control |
| `EjecutadoDisplay` | Avance visible |
| `Estado_Restricciones` | Liberacion calculada con restricciones PC |
| `Estado` | Estado del cronograma PC |

Funciones criticas:

- `fetchRestrictionConfig()` debe devolver hard/soft restrictions PC.
- `classifyPGRow()` debe usar labels PC sin asumir Construccion.
- `renderLegendModal()` debe mostrar Guia Operativa - Programa General (Pre-Construccion).
- `saveRow()` sigue usando `/api/general/update` con `project_id` del contexto.

Flujo paso a paso PC:

1. Abrir PG en Aeropuerto Regional PC.
2. Validar que aparece la etiqueta Pre-Construccion.
3. Cargar datos con `/api/general/list?db=da_aeropuerto_pc&semana=3`.
4. Cargar `/api/general/restriction-config`.
5. Validar que las restricciones y estados no muestran labels de Construccion.
6. Filtrar por chips de leyenda PC.
7. Editar solo si rol permitido.
8. Guardar y validar que no toca `project_id=73`.

#### PI PC

Entrada: `/programacion-intermedia`.

Campos base iguales:

- `Id`.
- `__shared_selected`.
- `Actividad`.
- `Sub_Contratista`.
- `Responsable_AIA`.
- `Semanas_Inicio`.
- `Ejecutado`.
- `Estado_Restricciones`.
- `estado_operativo`.
- `Observaciones`.

Restricciones PC esperadas por fixture:

- Dura: `restriccion_pc_1`.
- Blandas: `restriccion_pc_2`, `restriccion_pc_3`, `restriccion_pc_4`.

Diferencias operativas:

1. Las restricciones PC representan liberacion de disenos, modelacion, presupuesto, contratacion y tramites, no necesariamente materiales/mano de obra/equipos.
2. `Pdto. Constructivo` y `Modelo BIM` de Construccion no deben aparecer como contrato fijo si la config PC las reemplaza.
3. El estado operativo debe calcularse con thresholds de la configuracion PC.
4. La aplicacion compartida usa los mismos botones, pero los valores disponibles salen de la config PC.

Flujo paso a paso PC:

1. Abrir PI.
2. Confirmar que la grilla tiene restricciones PC.
3. Seleccionar una fila en `__shared_selected`.
4. Abrir bloque de restricciones compartidas.
5. Elegir una restriccion PC y valor permitido.
6. Presionar `Ver Conflictos`.
7. Revisar preview.
8. Aplicar solo si el resultado coincide con el flujo esperado.
9. Verificar que `/api/pi/save` o apply guardan en tablas globales con `project_id=75`.

Funciones:

- `buildColumnDefinitions()` debe construir columnas desde config PC.
- `getAllowedRestrictionRatios()` debe usar opciones PC.
- `normalizeRestrictionForPayload()` debe enviar valores validos para PC.
- `requestSharedConstraintPreview()` y `requestSharedConstraintApply()` no deben asumir restricciones de Construccion.

#### PS PC

Entrada: `/programacion-semanal`.

Botones visibles:

- Leyenda.
- Autoprogramar Actividades si permisos lo permiten.
- Agregar Actividad.
- Confirmar Compromisos.
- Registrar TNP si aplica.
- Imprimir.
- Exportar CSV.
- Recargar.
- Dropdown a Actividades/CNP/CNC/Calificacion Proveedores.

Diferencias esperadas:

1. La fuente de liberacion viene de PI PC.
2. Responsables y catalogos pueden representar equipo de preconstruccion.
3. Restricciones y readiness no deben depender de insumos de obra de Construccion.
4. La UI no debe exponer Listado/Contratos/PDC desde el navbar principal para PC.

Campos a revisar:

- `Actividad`, `Ubicacion`.
- `Prog_Sin_Restricciones_100`.
- `Sub_Contratista`/Interesado si aplica.
- `Responsable_AIA`.
- `Compromiso`.
- `Ejecutado_Real`.
- `PAC`, `P_Completado`.
- `Categoria_CNC`, `CNC`, `Observaciones_CNC`.
- `estado_operativo`.

Flujo paso a paso PC:

1. Abrir PS.
2. Confirmar que carga `/api/semanal/list` para `project_id=75`.
3. Revisar estados y alertas.
4. Editar responsable/compromiso si rol permite.
5. Guardar con `/api/semanal/save`.
6. Si se imprime, usar `/reportes/compromisos` en contexto PC.
7. Si se navega a CNP/CNC/CIC, validar que las rutas funcionan y no muestran datos Da Porto.

#### Profesionales PC

Entrada: `/profesionales`.

Campos:

- Nombre.
- Email.
- Cargo, con fixture `Gerente de Proyecto`.

Flujo:

1. Abrir modulo.
2. Listar con `/api/profesionales/list?db=da_aeropuerto_pc`.
3. Crear profesional temporal si hay snapshot.
4. Editar nombre/email/cargo.
5. Guardar con `/api/profesionales/save`.
6. Eliminar fixture temporal.
7. Confirmar que no aparecen profesionales exclusivos de Da Porto salvo compartidos permitidos por datos.

#### Interesados Externos PC

Entrada: `/subcontratistas`.

Texto visible:

- Titulo: `Interesados Externos`.
- No debe decir Subcontratistas como concepto principal.

Headers PC:

| Header | Campo |
|---|---|
| Interesado | `subcontratista` |
| Identificacion | `NIT` |
| Rol/Interes | `alcance` |
| Tipo de Interesado | `tipo_proveedor` |
| Correo | `correo_contacto` |

Flujo:

1. Abrir modulo.
2. `loadData()` llama `/api/subcontratistas/list`.
3. Crear interesado con tipo `Consultor`.
4. Validar NIT/identificacion y correo.
5. Autosave en edicion de celda.
6. Si tiene registros asociados, mostrar candado y no permitir eliminar.
7. Si es fixture temporal sin dependencias, eliminar.

Funciones:

- `buildSubcontratistaPayload()` debe usar labels PC pero payload comun.
- `collectSubcontratistaValidationErrors()` valida completitud.
- `createSubcontratista()` crea interesado.
- `saveRowChanges()`/`autosave()` guardan.
- `renderMobileCards()` debe mostrar textos PC.

#### Indicadores PC

Entrada: `/indicadores`.

Flujo:

1. Abrir modulo.
2. Enviar `/api/indicadores/generar` con `db=da_aeropuerto_pc` y semana 3.
3. Calcular metricas desde tablas globales del proyecto PC.
4. Verificar respuesta JSON sin errores.
5. Validar que no mezcla semanas/proyecto de Construccion.

Campos de aceptacion:

- Proyecto activo correcto en contexto visual.
- Semana activa correcta.
- No aparecen labels de PDC/Contratos/Listado como dependencias obligatorias.

#### Control de Cambios PC

Entrada: `/control-cambios`.

El formulario es el mismo de Construccion. Campos criticos:

- `inputConsecutivo`, `inputProyecto`, `inputDirector`, `inputFechaSolicitud`.
- Solicitante: Obra, Cliente, Interventoria, Otro.
- Prioridad: Alta, Media, Baja.
- Tipo cambio: Alcance, Cronograma, Costo, Calidad, Riesgo, Recurso.
- Responsable solucion: Obra, Cliente, Interventoria, Otro.
- Justificacion, descripcion e incidencias.
- Fechas de entrega/definicion.
- Aprobacion: Estudio, Aprobado, Aprobado con restricciones, No aprobado, Desistido.

Flujo:

1. Abrir Control de Cambios en PC.
2. Listar con `/api/control-cambios/list?db=da_aeropuerto_pc`.
3. Crear una orden temporal si hay snapshot.
4. Completar campos obligatorios.
5. Guardar con `guardar_modificar()` y `/api/control-cambios/save`.
6. Generar PDF solo si se quiere validar descarga.
7. Eliminar fixture temporal.
8. Confirmar que el registro queda bajo `project_id=75`.

#### Reportes PC

Estado actual:

- PC no esta en `REPORTS.constructionDownloads`.
- No se debe asumir que todos los reportes de Construccion aplican a PC.
- Tests futuros deben separar reportes PC de reportes Construccion.

Flujo minimo verificable:

1. Abrir indicadores o reportes habilitados para PC.
2. Ejecutar endpoint solo si la UI lo expone.
3. Validar JSON/archivo sin mezclar `project_id`.
4. No correr `run-all` de Construccion contra PC sin confirmar alcance funcional.

### 5.1 Shell y navegacion PC

Tipo: frontend.

Pasos:

1. Login.
2. Seleccionar Aeropuerto Regional PC.
3. Redirigir a modulo inicial.
4. Mostrar modulos permitidos para Pre-Construccion.
5. Ocultar Listado, Contratos y PDC.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| Proyecto fixture | Aeropuerto Regional PC |
| UI | `/programa-general`, `/programacion-intermedia`, `/programacion-semanal`, `/profesionales`, `/subcontratistas`, `/indicadores`, `/control-cambios` |
| Tests actuales | `preconstruccion-full-cycle.mjs`, `full-app-flow.spec.mjs` con `E2E_INCLUDE_PRECONSTRUCTION=1` o `E2E_PROJECT_KEYS=pc` |
| Riesgo | Seguro si no edita |

### 5.2 PG/PI/PS PC

Tipo: frontend/backend.

Pasos:

1. Cargar PG PC.
2. Validar restricciones PC.
3. Cargar PI PC.
4. Cargar PS PC.
5. Validar que estructura de restricciones y labels corresponden a Pre-Construccion.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| APIs | `/api/general/list`, `/api/general/restriction-config`, `/api/pi/list`, `/api/semanal/list` |
| Controladores | `GeneralApiController`, `ProgramacionIntermediaController`, `SemanalApiController` |
| Tablas | Tablas globales de programa con `project_id=75` |
| Tests actuales | `preconstruccion-full-cycle.mjs` |
| Riesgo | Seguro si no edita |

### 5.3 Interesados Externos PC

Tipo: frontend/backend.

Pasos:

1. Entrar a `/subcontratistas` en proyecto PC.
2. Mostrar titulo "Interesados Externos".
3. Usar headers PC: Interesado, Identificacion, Rol/Interes, Tipo de Interesado.
4. Listar, crear, editar o eliminar interesados segun permisos.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/subcontratistas`, `/api/subcontratistas/list`, `/save` |
| Controlador | `SubcontratistasApiController` |
| Tablas | `subcontratistas` |
| Tests actuales | `preconstruccion-full-cycle.mjs`, `full-app-flow.spec.mjs` si PC incluido |
| Riesgo | Muta catalogo |

### 5.4 Profesionales, Indicadores y Control Cambios PC

Tipo: frontend/backend.

Pasos:

1. Entrar a cada modulo habilitado.
2. Listar datos scoping por `project_id=75`.
3. Crear/editar/eliminar fixtures temporales solo en E2E con snapshot.
4. Validar que no aparecen datos de construccion.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/profesionales`, `/indicadores`, `/control-cambios` |
| Controladores | `ProfesionalesApiController`, `IndicadoresApiController`, `ControlCambiosApiController` |
| Tablas | `profesionales`, `indicadores_generales`, `cambios` |
| Tests actuales | `preconstruccion-full-cycle.mjs`, `full-app-flow.spec.mjs` si PC incluido |
| Riesgo | Muta catalogos/cambios si se edita |

## 6. Flujos semi-auto

Los modulos Listado, Contratos y PDC comparten contrato funcional:

1. Usuario entra al modulo.
2. Presiona accion Auto.
3. UI llama `auto/preview`.
4. Backend crea corrida y sugerencias.
5. UI muestra analisis, resumen y cards.
6. Usuario aplica sugerencias, deshace o envia feedback.
7. Admin puede consultar detalle tecnico o learning candidates.

### 6.1 Endpoints compartidos por modulo

Para cada modulo `listado-actividades`, `contratos`, `pdc`:

| Accion | Ruta |
|---|---|
| Preview | `/api/<modulo>/auto/preview` |
| Status | `/api/<modulo>/auto/status` |
| Apply | `/api/<modulo>/auto/apply` |
| Undo | `/api/<modulo>/auto/undo` |
| Feedback | `/api/<modulo>/auto/feedback` |
| Metrics | `/api/<modulo>/auto/metrics` |
| Assistant inbox | `/api/<modulo>/auto/assistant/inbox` |
| Assistant ack | `/api/<modulo>/auto/assistant/ack` |
| Assistant feedback | `/api/<modulo>/auto/assistant/feedback` |
| Learning candidates | `/api/<modulo>/auto/learning/candidates` |
| Learning approve | `/api/<modulo>/auto/learning/approve` |
| Learning reject | `/api/<modulo>/auto/learning/reject` |

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| Controlador | `SemiAutoController` |
| Servicio | `SemiAutoService`, `SemiAutoAssistantService` |
| UI compartida | `public/js/modules/semi_auto_review.js` |
| Tablas | Todas las `semi_auto_*` |
| Tests actuales | `semi-auto-review.mjs`, `auto-definir-contratos.mjs`, `test_semi_auto_service.php`, `test_semi_auto_global_tables.php`, `test_semi_auto_da_porto_feedback.php` |
| Riesgo | Muta sugerencias, decisiones, feedback y datos destino al aplicar |

### 6.2 Reglas de confianza

- `80-100`: listo para aplicar.
- `50-79`: requiere revision.
- `<50`: no recomendado.
- UI normal oculta run IDs y payload tecnico.
- Admin puede inspeccionar detalle tecnico.

## 7. Flujos Admin

### 7.1 Login Admin

Tipo: administrador.

Pasos:

1. Entrar a `/admin/login`.
2. Enviar credenciales.
3. Crear sesion Admin.
4. Redirigir a `/admin/` o `/admin/dashboard`.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI | `/admin/login` |
| Controlador | `Admin\Controllers\AuthController` |
| Tests actuales | `admin-global-panel.mjs` |
| Riesgo | Seguro |

### 7.2 Recuperacion de contraseña Admin

Tipo: administrador.

Pasos:

1. Entrar a `/admin/password/forgot`.
2. Solicitar enlace.
3. Entrar a `/admin/password/reset`.
4. Guardar nueva clave.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/admin/password/forgot`, `/admin/password/reset` |
| Controlador | `Admin\Controllers\PasswordResetController` |
| Dependencia | SMTP y tokens |
| Tests actuales | Sin E2E dedicado |
| Riesgo | Muta clave |

### 7.3 Dashboard Admin

Tipo: admin critico.

Pasos:

1. Entrar a `/admin/dashboard`.
2. Ver panel de control e integridad.
3. Alternar logs de consola si se requiere.
4. Activar/desactivar mantenimiento si se requiere.
5. Forzar cambio de clave si se requiere.
6. Ejecutar reportes masivos bajo advertencia.
7. Consultar progreso de reportes.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/admin/`, `/admin/dashboard`, `/dashboard/toggle-console-logs`, `/dashboard/toggle-maintenance`, `/dashboard/forzar-cambio-clave`, `/dashboard/run-reportes`, `/dashboard/report-progress` |
| Controlador | `Admin\Controllers\DashboardController` |
| Tablas/config | Usuarios, proyectos, configuracion de mantenimiento, storage reportes |
| Tests actuales | `admin-global-panel.mjs` cubre dashboard basico |
| Riesgo | Admin critico; reportes masivos pueden ser pesados |

### 7.4 Usuarios Admin

Tipo: admin.

Pasos:

1. Entrar a `/admin/usuarios`.
2. Crear usuario desde `/admin/usuarios/crear`.
3. Sugerir usuario/cargos si aplica.
4. Guardar usuario.
5. Editar usuario.
6. Activar/desactivar.
7. Forzar cambio de clave.
8. Revocar todos los proyectos.
9. Quitar proyecto especifico.
10. Eliminar usuario.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/admin/usuarios*` |
| Controlador | `Admin\Controllers\UserController` |
| Tablas | Usuarios, relaciones usuario-proyecto/permisos |
| Tests actuales | Sin E2E dedicado completo |
| Riesgo | Admin critico; muta acceso |

### 7.5 Proyectos Admin

Tipo: admin/riesgo BD.

Pasos:

1. Entrar a `/admin/proyectos`.
2. Crear proyecto.
3. Editar proyecto.
4. Activar/desactivar.
5. Gestionar miembros.
6. Descargar respaldo.
7. Ejecutar respaldo completo.
8. Limpiar tablas huerfanas bajo advertencia.
9. Eliminar proyecto solo bajo reglas de seguridad.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/admin/proyectos*`, `/admin/proyectos/miembros*` |
| Controlador/modelo | `Admin\Controllers\ProjectController`, `Admin\Models\Project` |
| Tablas | `general_proyectos_procesos`, `semanas_activas`, global tables por `project_id` |
| Tests actuales | `admin-global-panel.mjs`, `test_admin_global_project_model.php` |
| Riesgo | Alto; backups y limpieza de huerfanas requieren validacion local y no produccion |

### 7.6 Matching config Admin

Tipo: admin.

Pasos:

1. Entrar a `/admin/matching/config`.
2. Revisar reglas de matching.
3. Guardar cambios con `POST /admin/matching/config`.
4. Validar impacto en semi-auto.

Auditoria tecnica:

| Campo | Detalle |
|---|---|
| UI/API | `/admin/matching/config` |
| Controlador | `Admin\Controllers\ConfigController` |
| Tablas | Reglas/configuracion de matching y semi-auto |
| Tests actuales | Cobertura indirecta por semi-auto; sin E2E admin dedicado |
| Riesgo | Muta comportamiento de sugerencias |

## 8. Rutas clasificadas

### App principal

| Grupo | Rutas |
|---|---|
| Publicas/auth | `/`, `/login`, `/logout`, `/password/forgot`, `/password/reset`, `/password/update`, `/runtime/frontend-config.js` |
| Proyecto/contexto | `/proyectos`, `/proyecto/seleccionar`, `/context/week`, `/context/clear-week`, `/session/touch` |
| Programacion | `/programa-general`, `/programa-general/filtros`, `/programa-general/set-filtro`, `/programa-general-actualizar`, `/programacion-intermedia`, `/programacion-intermedia/filtros`, `/programacion-intermedia/set-filtro`, `/programacion-intermedia/set-view-all`, `/programacion-semanal`, `/programacion-semanal/cnp`, `/programacion-semanal/cnc`, `/programacion-semanal/cic` |
| Gestion | `/pdc`, `/profesionales`, `/subcontratistas`, `/contratos`, `/listado-actividades`, `/indicadores`, `/control-cambios` |
| Reportes | `/reportes/{tipo}` |
| APIs PG/PI/PS | `/api/general/*`, `/api/pi/list`, `/api/pi/save`, `/api/semanal/*`, `/api/cnp/*`, `/api/cnc/*`, `/api/cic/*`, `/api/pg/breadcrumb-*` |
| APIs gestion | `/api/listado-actividades/*`, `/api/contratos/*`, `/api/pdc/*`, `/api/profesionales/*`, `/api/subcontratistas/*`, `/api/control-cambios/*`, `/api/indicadores/generar` |
| LPS contextual | `/api/lps/comments`, `/api/lps/comments/add`, `/api/lps/crisis`, `/api/lps/crisis/register`, `/api/lps/crisis/close`, `/dashboard/escalamientos` |
| Notificaciones | `/api/notifications/unread`, `/api/notifications/read` |
| Legacy soporte | `/legacy/cambiar_pagina.php`, `/legacy/funciones_generales/php/datosGeneralesPagina.php`, `/legacy/funciones_generales/php/nueva_semana.php`, `/legacy/funciones_generales/php/verificarCICActualizada.php`, `/legacy/funciones_generales/php/eliminar_semana.php`, `/legacy/funciones_generales/php/buscadorTabla.php`, `/legacy/pdc/actualizar_pdc.php` |
| Mantenimiento | Ruta secreta `MaintenanceMode::SECRET_PATH` |

### Admin

| Grupo | Rutas |
|---|---|
| Auth | `/admin/login`, `/admin/logout`, `/admin/password/forgot`, `/admin/password/reset` |
| Dashboard | `/admin/`, `/admin/dashboard`, `/admin/dashboard/toggle-console-logs`, `/admin/dashboard/toggle-maintenance`, `/admin/dashboard/forzar-cambio-clave`, `/admin/dashboard/run-reportes`, `/admin/dashboard/report-progress` |
| Usuarios | `/admin/usuarios`, `/admin/usuarios/crear`, `/admin/usuarios/sugerir-usuario`, `/admin/usuarios/cargos`, `/admin/usuarios/guardar`, `/admin/usuarios/editar`, `/admin/usuarios/actualizar`, `/admin/usuarios/toggle-active`, `/admin/usuarios/toggle-force-password-change`, `/admin/usuarios/revocar-todos-proyectos`, `/admin/usuarios/eliminar`, `/admin/usuarios/quitar-proyecto` |
| Proyectos | `/admin/proyectos`, `/admin/proyectos/crear`, `/admin/proyectos/guardar`, `/admin/proyectos/editar`, `/admin/proyectos/actualizar`, `/admin/proyectos/eliminar`, `/admin/proyectos/limpiar-huerfanas`, `/admin/proyectos/respaldo-completo`, `/admin/proyectos/respaldar`, `/admin/proyectos/toggle-status` |
| Miembros | `/admin/proyectos/miembros`, `/admin/proyectos/miembros/agregar`, `/admin/proyectos/miembros/quitar`, `/admin/proyectos/sugerir-rol` |
| Matching | `/admin/matching/config` |

## 9. Matriz QA

| Flujo | Tipo | Test existente | Brecha | Riesgo | Recomendacion |
|---|---|---|---|---|---|
| Login app | Frontend | La mayoria de E2E | Falta test dedicado de errores/login invalido | Seguro | Mantener cobertura indirecta y agregar negativo luego |
| Recuperacion clave app | Frontend/backend | Sin cobertura dedicada | SMTP/tokens sin E2E | Muta clave | No automatizar sin fixture SMTP |
| Selector proyecto | Frontend | `full-app-flow.spec.mjs` | Falta usuario sin proyectos | Seguro | Mantener |
| Cambio semana | Frontend/backend | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs` | Falta invalid week | Seguro | Ampliar |
| PG Construccion | Frontend/backend/BD | `full-app-flow.spec.mjs`, `program-unique-id-refactor.mjs`, `test-pg-color-fix.mjs`, PHP unique gates | Falta import Excel E2E | Muta datos | Mantener y separar import |
| PG Actualizar | Frontend/backend | Indirecta | Sin E2E dedicado | Muta datos | Agregar despues de documentar detalle |
| PI Construccion | Frontend/backend | `full-app-flow.spec.mjs`, `test_pi_shared_payload_smoke.php` | Falta apply compartidas E2E completo | Muta restricciones | Ampliar con snapshot |
| PS Construccion | Frontend/backend | `full-app-flow.spec.mjs`, `weekly-operational-actions.mjs`, `last-planner-two-week-cycle.mjs`, `test_weekly_governance.php` | Flujos largos mezclados | Muta datos | Separar smoke vs operativo |
| CNP | Frontend/backend | `full-app-flow.spec.mjs`, weekly tests | Reprogramacion necesita caso aislado | Muta datos | Ampliar con snapshot |
| CNC | Frontend/backend | `full-app-flow.spec.mjs`, weekly tests | Falta guardado negativo | Muta datos | Ampliar |
| CIC | Frontend/backend | `full-app-flow.spec.mjs`, weekly tests | Falta validacion de metricas | Muta datos | Ampliar |
| Listado Actividades | Frontend/backend/semi-auto | `listado-actividades-handsontable.mjs`, `e2e/tests/workflows/listado-full.spec.mjs`, contratos PHP de backend/alcance/loader | CRUD, CSV y semi-auto aislados con snapshot y huella | Muta datos | Mantener suite dedicada y restauración estricta |
| Contratos | Frontend/backend/semi-auto | `contratos-handsontable.mjs`, `auto-definir-contratos.mjs`, `contratos-slot-quantities.mjs`, `e2e/tests/workflows/contratos-full.spec.mjs`, contratos PHP enfocados | HOT, tarjetas, modal, persistencia, RBAC y semi-auto aislados con snapshot; evidencia visual nativa en el goal | Muta datos | Mantener suites dedicadas y restauración atómica |
| PDC | Frontend/backend/semi-auto | `full-app-flow.spec.mjs`, `test-pdc.mjs`, `semi-auto-review.mjs` | Falta duracion/plantillas dedicado | Muta datos | Ampliar |
| Profesionales | Frontend/backend | `full-app-flow.spec.mjs` | Cobertura basica CRUD temporal | Muta catalogo | Mantener |
| Subcontratistas | Frontend/backend | `full-app-flow.spec.mjs` | Falta roles con permisos limitados | Muta catalogo | Ampliar |
| Indicadores | Backend/reportes | `full-app-flow.spec.mjs` | Falta validacion de calculos | Puede mutar | Ampliar backend |
| Control Cambios | Frontend/backend | `full-app-flow.spec.mjs` | Falta permisos/adjuntos | Muta datos | Ampliar |
| Reportes Construccion | Reportes | `full-app-flow.spec.mjs`, `test_report_processor_project_scope.php` | Falta validacion XLSX profunda | Genera archivos | Mantener y separar pesado |
| PC shell/navegacion | Frontend | `preconstruccion-full-cycle.mjs`, opcional `full-app-flow.spec.mjs` | PC no corre por defecto salvo env | Seguro | Mantener suite opt-in |
| PC PG/PI/PS | Frontend/backend | `preconstruccion-full-cycle.mjs` | Falta edicion controlada | Muta si edita | Ampliar con snapshot |
| PC Interesados Externos | Frontend/backend | `preconstruccion-full-cycle.mjs`, opcional full flow | Falta CRUD aislado | Muta catalogo | Ampliar |
| PC Indicadores/Control Cambios | Frontend/backend | `preconstruccion-full-cycle.mjs`, opcional full flow | Falta calculo/CRUD profundo | Muta datos | Ampliar |
| Semi-auto Listado | Semi-auto/backend | `semi-auto-review.mjs`, `test_semi_auto_service.php` | Falta learning approve/reject visible | Muta sugerencias | Separar assistant/learning |
| Semi-auto Contratos | Semi-auto/backend | `auto-definir-contratos.mjs`, `contratos-full.spec.mjs`, PHP semi-auto | Apply/recarga/undo con huella y restauración exacta | Muta sugerencias/destino | Mantener snapshot y rechazo explícito de undo vacío |
| Semi-auto PDC | Semi-auto/backend | `semi-auto-review.mjs`, `test-pdc.mjs` | Falta feedback por familia completo | Muta sugerencias/destino | Ampliar |
| Admin login/dashboard | Admin | `admin-global-panel.mjs` | Falta login invalido | Seguro | Mantener y ampliar |
| Admin usuarios | Admin | Sin E2E dedicado | CRUD/permisos sin cobertura | Admin critico | Agregar con fixture aislado |
| Admin proyectos | Admin/BD | `admin-global-panel.mjs`, `test_admin_global_project_model.php` | Crear/editar/eliminar no completamente E2E | Alto | Separar suite admin-riesgo |
| Admin miembros | Admin | Sin E2E dedicado | Asignacion/remocion sin cobertura | Admin critico | Agregar con fixture |
| Admin matching config | Admin/semi-auto | Indirecta por semi-auto | Sin test de config | Muta comportamiento | Agregar backend primero |
| Admin mantenimiento | Admin critico | Sin test dedicado | Activar/desactivar mantenimiento sin cobertura | Alto | No automatizar sin entorno aislado |
| Admin backups | Admin/BD | `admin-global-panel.mjs` valida descarga puntual | Respaldo completo sin prueba profunda | Alto | Mantener smoke, no correr masivo en suite diaria |
| Limpieza huerfanas | BD/riesgo | Memoria y pruebas de seguridad global | No debe correr sin backup | Alto/destructivo | No automatizar en suite normal |
| Migracion legacy a global | Migracion/BD | `test_migrate_legacy_to_global.php`, `test_global_table_safety.php`, `test_global_table_reconciliation.php` | Pesado y dependiente de estado | Alto | Suite manual/local-only |
| Unique ID runtime gate | BD/runtime | `test_unique_id_runtime_gate.php`, `test_program_unique_id_refactor.php` | Migracion aplica cambios | Migracion | Separar gate estatico de migracion |

## 10. Inventario de soporte de tests

| Archivo | Uso |
|---|---|
| `playwright.config.mjs` | Configura `tests/browser`, ignora `fixtures` y `support`, baseURL `http://localhost:8081` |
| `tests/browser/fixtures/projects.mjs` | Define proyectos Da Porto y Aeropuerto Regional PC, roles esperados, tablas globales y reportes |
| `tests/browser/support/session.mjs` | Login, logout, seleccion de proyecto, cambio de semana, helpers HTTP |
| `tests/browser/support/assertions.mjs` | Asserts de contexto, navbar, errores runtime y restricciones |
| `tests/browser/support/dbSnapshot.mjs` | Snapshots/restores para E2E que mutan BD |
| `tests/browser/support/moduleFlows.mjs` | Contrato operativo reutilizable por modulo |

| `tests/browser/support/dbSnapshot.mjs` | Snapshots/restores para E2E que mutan BD |
| `tests/browser/support/moduleFlows.mjs` | Contrato operativo reutilizable por modulo |

### 10.1 Ciclo operacional completo Last Planner y Compras

La suite canónica es `tests/browser/full-operational-cycle.spec.mjs` y se ejecuta en serie:

```bash
npx playwright test tests/browser/full-operational-cycle.spec.mjs --workers=1
```

El escenario usa `test.A`, concede administración temporal solo cuando hace falta y restaura la membresía original en `finally`. Antes de mutar captura las tablas globales, `actividad_programa_fuentes` y `contratos_trazabilidad`; al terminar exige la misma huella y ausencia de filas `E2E`.

Alcance por proyecto:

- Da Porto (`project_id=73`): PG, PI, PS, CNP, CNC y CIC en semana 1; Listado, Contratos, asistente preview/apply/reload/undo y PDC en semana 1.
- Optimización Aeropuerto JMC (`project_id=68`): Last Planner en semana operativa 5; Compras usa la semana máxima 6 porque Listado, Contratos y PDC resuelven `Max_Semana` en el flujo visible.
- Preconstrucción Da Porto (`project_id=76`): PG, PI y PS en semana 1, restricción PC `restriccion_pc_1` e Interesados Externos. Sus restricciones blandas no aparecen porque el proyecto no tiene nombres personalizados; Listado, Contratos y PDC deben permanecer ocultos.


## 11. Criterios antes de crear nuevos tests

Antes de agregar o modificar tests:

1. Identificar flujo exacto en este documento.
2. Confirmar tipo de riesgo.
3. Confirmar proyecto fixture y rol.
4. Definir si requiere snapshot/restore.
5. Elegir suite: smoke, operativo, admin, semi-auto, BD, migracion.
6. Evitar que tests destructivos corran en suite diaria.
7. Mantener produccion fuera de cualquier limpieza o migracion local.

## 12. Dudas abiertas

- El flujo de `Programa General Actualizar` necesita validacion funcional mas detallada antes de proponer tests nuevos.
- Recuperacion de contraseña requiere decidir si se usara SMTP real, mock SMTP o solo test backend sin envio.
- Admin usuarios/proyectos necesita fixture aislado para evitar alterar cuentas/proyectos reales de desarrollo.
- Limpieza de huerfanas y backups deben permanecer fuera de suites automaticas hasta definir entorno local descartable.
