---
capa: wiki
tipo: referencia
estado: vigente
fecha: 2026-09-30
areas: [lps, arquitectura, qa]
fuente: frontend/src/modules/programa-general, src/Controllers/Api/GeneralApiController.php, verificación en navegador del 2026-09-30
resumen: "Programa General objeto por objeto: funciones, flujos, protocolos de permisos y de verificación, y lo medido en navegador el 2026-09-30"
---
# Programa General: funciones, flujos y protocolos

Ficha completa del módulo [[programa-general]]: qué hace cada objeto de la pantalla, qué hace el
servidor con cada acción, qué reglas de negocio aplica, cómo se recorre de punta a punta y cómo se
verifica. **Es la plantilla de ficha para todos los módulos** de la migración: la estructura
(secciones 1 a 8) se repite en cada uno.

Medido contra el código de `main` en `b59f879f` y en navegador el 2026-09-30, con la copia local de
Da Porto (semana 2, 282 actividades y 42 capítulos) y las cuentas `test.A`, `test.R` y `test.V`.
Las referencias `archivo:línea` cuelgan de la raíz del repo. Precedencia: si esta ficha y el código
no coinciden, gana el código (ver [[index]]).

## 1. Qué es y quién lo usa

El cronograma maestro de la obra, semana a semana. Cada semana guarda una copia completa del
programa en `programa_consolidado` (una fila por actividad y semana). Lo ven todos los roles del
proyecto menos C; lo editan A, D, R y DCV; la semana pasada solo A y D.

- Página React: `frontend/src/modules/programa-general/` (ruta `/programa-general` y su alias
  `/app/programa-general`, montada en `frontend/src/shell/rutas.tsx:468-469`).
- Servidor: `src/Controllers/Api/GeneralApiController.php`, `src/Services/ProgramaGeneralContextService.php`,
  `src/Security/ProgramaGeneralActionPolicy.php`.
- Spec: [[2026-08-30-s05-programa-general-react-design]]; deuda: [[2026-09-26-s05-deuda-cierre-design]];
  rediseño (propuesta sin visto): [[2026-09-28-s05-rediseno-programa-general-design]].

## 2. Objetos de la pantalla

Cada fila: objeto → qué hace → llamada al servidor → cuándo aparece o se deshabilita.

### 2.1 Riel lateral y semanas (shell)
| Objeto | Función | Servidor | Condición |
|---|---|---|---|
| «Semanas del Proyecto» | Abre su menú flotante con el cursor, el clic o el foco | — | Deshabilitado sin semana |
| «+ Nueva semana» | Abre «Crear Semana N+1» con la fecha sugerida | POST `/api/context/weeks/create` `{startsOn}` | Solo con permiso de crear |
| «Semana N» (menús flotantes) | Cambia la semana; desde Intermedia o Semanal lleva a ese módulo | POST `/context/week` `{semana}` | Sin permiso de seleccionar: deshabilitado |
| Papelera | Abre «Eliminar Semana N» | POST `/api/context/weeks/delete-last` `{week}` | Solo la última semana y con permiso |
| Chip «Semana N» (barra de contexto) | Menú para cambiar de semana | POST `/context/week` | La barra no se pinta sin semana |
| «Tema», «Cuenta», «Colapsar menú» | Tema claro/oscuro, cambiar proyecto, cerrar sesión, plegar riel | POST `/api/auth/logout` (cerrar sesión) | Siempre |

### 2.2 Barra de herramientas (`components/ProgramaToolbar.tsx`)
| Objeto | Función | Servidor | Condición |
|---|---|---|---|
| «8 Cols Esenciales» / «13 Cols Reales» | Cambian el modo de la tabla | — | Siempre |
| «Drawer LPS» | Abre el cajón con la primera actividad del total | — | Sin actividades no hace nada |
| «Leyenda» | Modal «Guía Operativa - Programa General» | — | Siempre |
| «Actualizar Ejecución» | Lote de arrastre de avance de la semana | POST `/api/general/update-batch?db=&semana=` | Solo con permiso de lote |
| «CSV» | Descarga las 13 columnas (se genera en el navegador) | — | Siempre |
| «Corte XLSX» | Genera el corte oficial y abre su URL | POST `/reportes/corte-programacion` `semana` | Sin permiso de reportes: deshabilitado |
| «Recargar» | Vuelve a pedir contexto y lista | GET contexto + lista | Siempre |
| «BI Programa» | Enlace a `/bi/programa-general` | — | Solo con acceso a BI |

### 2.3 Señales, buscador y filtros
- **Ocho chips de estado** (Atrasada, Con Alerta, Debe Iniciar, En Curso, Actividad Futura,
  Terminada, Fuera de Ventana, Sin Datos): filtran por estado; pulsar el activo lo quita.
- **Buscador** («Buscar actividad, código o responsable...»): texto, código o WBS, responsable y
  subcontratista. «⌘K» es decorativo.
- **«Actividades visibles: X de Y»** y **«Avance macro obra»** (promedio simple de las tareas).
- **Barra «Filtros activos»** con píldoras que se quitan y «Limpiar filtros».

### 2.4 Tabla, capítulos y tarjetas
- **Modo 8 columnas:** ID · CÓD. · ACTIVIDAD · F. INICIO · F. FIN · PPTO TOTAL · AV. REAL / TEÓR · ESTADO.
- **Modo 13 columnas:** añade RC, INICIO REL., CANT., UND., AV. REAL, AV. TEÓR y RESTR. LIB.
- **CÓD.:** el código guardado; si falta, la numeración WBS del cronograma, atenuada y sin guardarse.
- **Capítulos:** ID, CÓD., carpeta, nombre e insignia «Capítulo»; no se abren.
- **Fila de actividad:** clic, Enter o Espacio abren el cajón.
- **Tarjetas (768 px o menos):** la misma información por actividad; abren el cajón.

### 2.5 Cajón de detalle (`components/ProgramaDrawer.tsx`)
| Sección | Objetos | Editable si |
|---|---|---|
| Cabecera | Capítulo › Actividad, título, código, estado, RC, «Actividad i de N», Anterior `[` / Siguiente `]` | — |
| Plazos y Cronograma | Fecha Inicio, Fecha Fin, «Semana contractual», aviso de plazo vencido | Permiso de editar |
| Responsables & Asignaciones | Profesional AIA Responsable, Empresa Subcontratista | Permiso de editar |
| Presupuesto y Avance Físico | Unidad, Cantidad PPTO, Avance Teórico (solo lectura), Avance Real, Desviación | Permiso de editar |
| Recursos de liberación | Siete recursos con «Liberada», «Pendiente» o «No aplica» | Solo lectura |
| Bitácora SOS | Observación (solo lectura) y «Declarar Crisis SOS LPS» | SOS: permiso del cajón |
| Pie | «Descartar (Esc)» y «Guardar Cambios (⌘S)» | Guardar: permiso de editar |

Teclado del cajón: Escape cierra (pregunta si hay cambios), Tab queda atrapado, ⌘S guarda,
`[` y `]` navegan (preguntan si hay cambios).

### 2.6 Estados de la pantalla
«Cargando Programa General...», error inicial (sin botón de reintento), «Recargando…»,
«Datos desactualizados» con «Reintentar», «Algo salió mal» (error de pintado) y el aviso flotante.
No hay un estado vacío propio: sin resultados quedan las cabeceras y los capítulos.

## 3. Funciones del servidor

| Acción | Endpoint | Acceso | Lee | Escribe y efectos |
|---|---|---|---|---|
| Contexto | GET `/api/programa-general/context` | Ver PG | `semanas_activas`, `profesionales`, `subcontratistas`, áreas | Sesión: `semana` y tokens CSRF |
| Lista | GET `/api/general/list?semana=N` | Ver PG | `programa_consolidado` de la semana | Calcula el avance teórico |
| Guardar | POST `/api/general/update?semana_objetivo=N` | Editar PG + CSRF `programa_general_save`; semana pasada solo A y D | Fila | Fila de `programa_consolidado`; `pg_avance_edicion_manual` si cambia el avance; `medir_productividad=0` en todo el proyecto; recalcula estado y desfase; normaliza capítulos |
| Lote | POST `/api/general/update-batch` | Igual que guardar | Semana N−1 de PG y de Semanal | Avance, arrastre, responsable, subcontratista, unidad y cantidad de la semana N; estado y desfase de todas las filas |
| Corte XLSX | POST `/reportes/corte-programacion` | Generar reportes (sin CSRF) | 12 columnas de la semana | Archivo en `public/storage/cortesProgramacion/` |
| SOS | POST `/api/lps/crisis/register` | Editar Semanal + CSRF `lps_drawer` | Semana de la actividad | `lps_escalamientos` y `alerta_crisis=1` en PG y Semanal |
| Cambiar semana | POST `/context/week` | Sesión + CSRF `shell_api` | `semanas_activas` | Sesión |
| Crear semana | POST `/api/context/weeks/create` | Crear semana | CIC, confirmación anterior, programa maestro | `semanas_activas` y copia de la semana anterior (sin transacción ni auditoría) |
| Eliminar semana | POST `/api/context/weeks/delete-last` | Eliminar semana (DCV no) | — | Borra la semana en `semanas_activas`, `programa_consolidado`, `programacion_semanal`, `cic` y, en cascada, sus comentarios y alertas; deja auditoría |

Todas las consultas pasan por `ProjectSqlGuard`, que fija el `project_id` del proyecto activo.

## 4. Reglas de negocio

| Regla | Dónde vive | Estado |
|---|---|---|
| El estado lo decide el servidor; la pantalla solo lo pinta | `src/Core/Lps/LpsService.php:124` | Cumple |
| Orden del estado: Capítulo → Terminada (≥ 99,9 %) → Sin Datos → Atrasada (teórico − real > 0,1 pp) → Debe Iniciar → En Curso → Fuera de Ventana (≥ 7 semanas) → Actividad Futura | `LpsService.php:133-198` | Cumple |
| «Con Alerta» no es un estado (retirado el 2026-08-20) | `filtros.ts:24` lo cuenta | Contradice: el chip siempre marca 0 |
| Avance teórico = días transcurridos hasta el inicio de la semana ÷ duración | `LpsService.php:201-227` | Cumple en el servidor |
| «Plazo vencido» se mide contra la fecha de referencia | `domain/modelo.ts:35` usa 2026-08-23 fijo | Contradice |
| `Semanas_Inicio` es un desfase, no un número de semana | Deuda de cierre S05 | Cumple en tabla y CSV; contradice en el cajón |
| La semana confirmada bloquea la planificación | Spec S05-AC-12 | Solo en pantalla; el servidor no la bloquea |
| La semana pasada solo la editan A y D | `GeneralApiController.php:1788-1806` | Cumple |
| Las restricciones duras y blandas salen del catálogo del área | `ProgramaGeneralContextService.php:121` | Cumple |
| Responsable y subcontratista viajan a Intermedia | Misma fila de `programa_consolidado` | Cumple (es la misma fila, no una copia) |
| Fecha de fin no anterior a la de inicio; avance dentro del rango | Spec S05 `:1018-1034` | No implementa en el cliente; el servidor solo valida el rango de avance |
| El SOS se registra en la semana que se ve | `LpsLegacyGeneralActivityAdapter.php:36` | Contradice: toma cualquier semana |

## 5. Flujos de punta a punta

| Flujo | Pasos | Resultado esperado |
|---|---|---|
| Revisar la semana | Entrar → chips, «Actividades visibles», «Avance macro» y tabla | Solo lectura |
| Actualizar la ejecución | «Actualizar Ejecución» → lote | Aviso con filas y arrastres; avance de Semanal N−1 llevado a PG N |
| Editar y guardar | Abrir actividad → fechas, unidad, cantidad, avance, responsable o subcontratista → «Guardar Cambios» | Aviso y lista recargada con estado recalculado |
| Declarar crisis SOS | Cajón → «Declarar Crisis SOS LPS» | Alerta registrada; botón «Alerta SOS LPS Activa» |
| Filtrar y buscar | Chips de estado y buscador → «Limpiar filtros» | Filtrado en el navegador, sin peticiones |
| Exportar | «CSV» o «Corte XLSX» | Archivo de la semana; el corte, el oficial del servidor |
| Cambiar de semana | Chip o menú del riel | La página se recarga en la semana elegida |
| Crear semana | «+ Nueva semana» → fecha → «Crear semana» | Semana nueva copiada de la anterior (exige la anterior confirmada salvo Admin, sin CIC pendiente) |
| Eliminar la última semana | Papelera → confirmación | Borrado en cascada de la semana |
| Pasar a Intermedia y Semanal | — | Intermedia lee las filas con desfase de 6 semanas o menos de la misma tabla; Semanal N−1 alimenta el avance de PG N |

## 6. Protocolos

### 6.1 Permisos por rol (tabla de respaldo de `RbacCatalog.php`)
| Acción | Pueden | No pueden |
|---|---|---|
| Ver | A, D, R, DCV, OT, G, S, SG, V | C |
| Editar y lote | A, D, R, DCV | OT, G, S, SG, V, C |
| Editar semana pasada | A, D | resto |
| Corte XLSX | A, D, R, DCV, OT, V | G, S, SG, C |
| SOS | A, D, R, DCV | resto |
| Crear semana | A, D, R, DCV, OT | resto |
| Eliminar semana | A, D, R, OT | DCV y resto |

### 6.2 Protocolo de verificación
Se prueba con el protocolo común de verificación integral de módulos, que vive como contrato en
`docs/qa/` (en preparación, propuesta del 2026-09-30). Lo propio de este módulo: datos en la copia
local de Da Porto, cuentas `test.A`, `test.R` y `test.V`, y estas acciones con escritura que no se
deshacen desde la aplicación: SOS y «Actualizar Ejecución». Trampas del entorno:
[[env-enlazado-se-rompe-dentro-del-contenedor]] y [[recarga-normal-sirve-la-hoja-css-vieja]].

## 7. Estado de la verificación (2026-09-30, `b59f879f`, Da Porto)

**Funciona:** carga sin errores; guardar y recalcular estado; lote (en Da Porto cambió 1 campo de
324 filas); crear y eliminar semana sin restos; leyenda; protección del borrador con Escape y
`[` `]`; permisos de V y R en pantalla y en el servidor.

**Fallos confirmados en navegador:**
| # | Gravedad | Fallo |
|---|---|---|
| 1 | Crítico | El SOS se registra en otra semana (viendo la 2 quedó en la 1). **Arreglado en `main` el 2026-10-01 (PR #100, merge `2bebf864`):** la semana viaja y el servidor la verifica; probado en navegador, la alerta quedó en la semana 2 |
| 2 | Crítico | Un SOS exitoso se muestra como «Tu sesión venció… la crisis no se registró» (el servidor devuelve `alertId: 0`: `lastInsertId()` sobre una tabla con id por proyecto, ver [[lastinsertid-en-tablas-con-id-por-proyecto]]). **Arreglado en el mismo PR:** el id sale de `insertedId()` y la pantalla confirma «Alerta registrada.» |
| 3 | Alto | «Plazo vencido» se calcula contra el 23/08/2026 fijo |
| 4 | Alto | El corte XLSX se genera, pero el navegador bloquea la pestaña y nadie lo recibe |
| 5 | Alto | La semana confirmada solo se bloquea en pantalla; por la API se edita |
| 6 | Alto | Crear semana deja «Inicio relativo» desfasado en 281 de 282 actividades |
| 7 | Alto | Se acepta una fecha de fin anterior a la de inicio |
| 8 | Alto | Cambiar de semana con un borrador lo descarta sin preguntar |
| 9 | Medio | El aviso flotante es ilegible en los dos temas (texto del mismo color que el fondo) |
| 10 | Medio | Los errores del servidor llegan como «…respondió 400» en vez de su mensaje |
| 11 | Medio | El diálogo de crear semana promete que se vuelve la activa, y no ocurre |
| 12 | Medio | Guardar borra la asociación con la semana anterior y cambia el código de nulo a vacío |
| 13 | Medio | El CSV trae el HTML crudo del nombre y fecha UTC en el nombre del archivo |
| 14 | Bajo | El buscador mira el HTML oculto («small» encuentra todo) |
| 15 | Bajo | «Drawer LPS» y `[` `]` ignoran los filtros activos |
| 16 | Bajo | «8 Cols» activa cambia a 13; «⌘K» no hace nada |
| 17 | Bajo | «Semana contractual: Sem 0» muestra un desfase como número de semana |
| 18 | Bajo | Móvil: la tarjeta abierta no se marca y el foco no vuelve a ella |
| 19 | Bajo | Un 403 de permiso llega como página HTML, no como JSON |

## 8. Brechas

- **Sin prueba automática:** SOS en PG (semana de destino), lote con datos, corte XLSX en el
  servidor, semana confirmada, cambio de semana con borrador, `Semanas_Inicio` tras crear semana.
- **En la spec y sin código:** validación de cliente y servidor, conteos facetados, filtros en la
  URL, búsqueda sin acentos, alertas R0-R6, textos de Preconstrucción, hilo y cierre de crisis en
  el cajón, «Guardando…», estados de pantalla de error.
- **Rutas vivas sin dueño en React:** `delete-update`, `auto-associate`, `decision-log` (siempre
  falla), `breadcrumb-*` (sin CSRF), `/legacy/cambiar_pagina.php` (cambia la semana sin CSRF).

Vecinos: [[programa-general]] · [[flujo-lps]] · [[escalamientos-y-crisis]] ·
[[programacion-intermedia]] · [[programacion-semanal]].
