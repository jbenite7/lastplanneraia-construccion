# Registro de Cambios (Changelog)

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [1.1.1] - 2026-03-31

### Añadido

- **Indicadores de Desviación (Delta) en PDC:** Nuevo motor de cálculo asíncrono en `PdcApiController` que determina el retraso o avance real de cada paquete de contratación frente a su línea base teórica.
- **Visualización de Estatus Premium (PDC):** Refactorización de los renderizadores de celda en `pdc.view.php` para inyectar badges informativos (`deltaHtml`) y tooltips de desviación directamente en los iconos de estado.
- **Refuerzo de Estilos PDC:** Nuevas utilidades CSS en `styles.css` para soportar la visualización de deltas con colores semánticos (peligro para retrasos, info para avances).

## [1.1.0] - 2026-03-31

### Añadido

- **IA Agile Operative OS 2026:** Integración completa de la "Constitución IA" en `GEMINI.md`, definiendo el Protocolo Sniper, Kill Switch y planificación por fases (SDD/PDCA) para una operación de ingeniería de alta precisión.
- **Apple-Style Design System (Fase 1):** Implementación de una arquitectura de diseño premium basada en CSS nativo y variables OKLCH/HSL en `styles.css` y `buttons.css`.
- **Modernización de Vista PDC:** Refactorización estética y funcional de `views/pdc/pdc.view.php` con tipografías premium (Montserrat/Inter) y componentes visuales alineados al manual de marca AIA.
- **Documentación de Flujo IA:** Incorporación de nuevos walkthroughs estratégicos para la operación del agente en entornos complejos.

### Cambiado

- **Refactor CSS Modular:** Migración de estilos hacia un motor de variables centralizado, facilitando la consistencia estética entre los módulos MVC y Legacy.
- **Actualización de Gobernanza:** Endurecimiento de las reglas de edición y validación del agente para garantizar la integridad del código fuente.

## [Sin publicar]

### Añadido

- **Sistema de Recuperación de Contraseña:** Implementación completa del flujo "Olvidé mi contraseña" con envío de correos (MailService), tokens de un solo uso (PasswordService) y vistas dedicadas tanto en la aplicación principal como en el panel administrativo.
- **Infraestructura SMTP para Recuperación:** Integración de `phpmailer/phpmailer`, variables `APP_URL` + `MAIL_*` y plantilla Docker de entorno para soportar el envío de enlaces de restablecimiento.
- **Protección CSRF para Auth:** Nuevo `CsrfTokenManager` para blindar los formularios de inicio de sesión y recuperación de credenciales contra ataques de falsificación de petición en sitios cruzados.
- **Expansión de Capacidades RBAC:** Adición de 7 nuevos flags de capacidad en `RbacManager` (`canManageGeneralProgram`, `canEditPastGeneralProgram`, `canManageWeeklyProgram`, etc.) para un control granular más preciso en los módulos LPS y Contratos.
- **Soporte de Utilidades Globales:** Nuevo namespace `App\Support` con `ModuleRequestContext` para la resolución segura y centralizada de parámetros de contexto (Proyecto, Base de Datos, Semana) en módulos legacy y modernos.

### Cambiado

- **Shell Unificado de Modales en Compras:** Listado de Actividades, Contratos y PDC ahora comparten una base visual consistente para headers, formularios, acciones y estados responsive, reduciendo desalineaciones entre modales operativos.
- **Refactor de Seguridad PDC:** El script `actualizar_pdc.php` ahora utiliza `ModuleRequestContext` para resolver la base de datos y semana, e integra `rbac_guard_require_permission` para validar permisos antes de cualquier operación de escritura.
- **Hardening de APIs LPS:** Actualización de `PdcApiController`, `ContratosApiController` y `ListadoActividadesApiController` para mejorar la resolución de contexto y el manejo de excepciones mediante bloques `Throwable`.
- **Blindaje de Escrituras por Semana Activa:** Las operaciones sensibles en Contratos, Listado de Actividades y PDC ahora se acotan al contexto operativo resuelto por `ModuleRequestContext` para reducir cruces de semana/proyecto.
- **UI de Contratos Más Clara y Responsive:** El modal de edición de contratos fue remaquetado con una grilla consistente, mejor integración de Select2 y una presentación más estable en desktop y móvil.
- **Listado de Actividades Alineado con la Semana Vigente:** La vista y sus flujos AJAX ahora priorizan `Max_Semana`, reutilizan un único set de opciones para la actividad de inicio y endurecen la edición inline según el rol operativo.
- **Mejoras en Login UX:** Integración de enlaces de recuperación de contraseña y avisos de éxito tras el restablecimiento de credenciales en las vistas de acceso.
- **Refactor de LoginController:** Simplificación del método `updatePassword` delegando la lógica de validación y hash al nuevo `PasswordService`.

### Corregido

- **Select2 Desanclado en Nueva Actividad:** El selector de tarea inicial vuelve a desplegarse ligado al campo correcto dentro del modal, con dropdown estable y sin desorganizar el layout al escribir o elegir opciones largas.
- **Autoactualización de PDC al Navegar Entre Módulos:** El flujo hacia `/pdc` ahora conserva la semana operativa válida, marca el origen de navegación y ejecuta una sincronización one-shot del plan de compras al llegar desde Programa General, Actividades o Contratos.
- **Compatibilidad SQL en Detección de Tablas:** Corrección del uso de placeholders en `SHOW TABLES LIKE` mediante el uso de `quote()` nativo de PDO, asegurando compatibilidad con el driver de base de datos en entornos SiteGround/Docker.
- **Error 500 en Remoción de Proyectos:** Implementación de manejo de excepciones (`try-catch`) en `UserController::removeProject` y `ProjectController::removeMember` para evitar fallos catastróficos si el servicio de sincronización falla, devolviendo ahora errores descriptivos.
- **Estabilidad en Gestión de Usuarios y Miembros:** Resolución de Error 500 al intentar eliminar usuarios o miembros, unificando el uso de `tableExists` seguro en el Core de la Base de Datos y blindando controladores con `try-catch`.
- **Sincronización:** Refactorización de `ProjectProfessionalsSyncService` eliminando el uso inseguro de `SHOW TABLES LIKE`, previniendo errores en esquemas de proyectos incompletos.
- **Gestión Flexible (Zero Projects):** Eliminación de restricciones de base de datos y frontend que impedían dejar usuarios sin proyectos asignados.
- **Sobreposición de Miembros en Admin:** Resolución de desbordamiento horizontal en la tabla de miembros asignados mediante contenedor responsivo y quiebre de palabras forzado para strings largos.
- **Actividad de Inicio en Listado:** La API y la vista de Listado de Actividades vuelven a resolver `actividadInicio` por `Consecutivo_en_Programa`, sincronizan la fecha desde la semana activa real y evitan inconsistencias al registrar, editar o consultar actividades.
- **Consolidación de PDC por Tipo de Contrato:** `actualizar_pdc.php` ahora agrupa y calcula fechas con alias explícitos de subactividades y usa `general_dias_procesos_contratacion` filtrado por tipo de paquete para evitar cruces y resultados erróneos.

- **Runtime Frontend Config Global:** Nuevo endpoint `'/runtime/frontend-config.js'`, servicio `src/Services/FeatureFlagService.php` y documento `docs/20260325_general_feature_flags.md` para exponer feature flags publicos sin acoplar las vistas al backend.
- **Cambio Obligatorio de Contraseña:** Nuevo flujo de seguridad con bandera `force_password_change`, endpoint `'/password/update'`, modal bloqueante en login con `AIA.Notice` y accion administrativa para forzar la rotacion masiva de credenciales desde el dashboard.
- **Sincronización de Profesionales por Proyecto:** Nuevo servicio `src/Services/ProjectProfessionalsSyncService.php` para reconciliar `admin/` contra `*_profesionales`, mapear roles `A/D/DCV/G/OT/R/S/SG`, consolidar duplicados por correo y preservar el historial por proyecto.
- **Flujo de Timeout de Sesion (Fase 1):** La expiracion por inactividad ahora redirige al login con un aviso visual en `AIA.Notice`, mejorando la experiencia tras sesiones vencidas.
- **AIA Notice Global:** Nuevo core `public/js/core/AiaAlertInterceptor.js` para centralizar alertas, toasts y badges de guardado sobre SweetAlert2 en Admin, login y modulos LPS.
- **TomSelect Premium AIA:** Implementación de arquitectura de estilos corporativos (Naranja Construcción) en `tom-select-premium-aia.css`. Incluye tipografía Montserrat/Inter, chips adaptables con word-wrap y un botón de limpieza elegante integrado siguiendo el manual de marca 2026.
- **Herencia de Restricciones (Manual/Excel):** Sincronización automática de las 7 restricciones individuales (D y E, Materiales, MdeO, etc.) tanto en procesos de importación masiva como en la asociación manual por dropdown. (AIA 2026).
- **Persistencia de Mapeo Físico:** El botón "Eliminar Actualización" ahora realiza un `DELETE` físico de borradores en la base de datos, garantizando un flujo de trabajo limpio y permitiendo reintentar mapeos desde cero.
- **Página de Mantenimiento AIA:** Nueva página HTML standalone (`public/mantenimiento-aia.html`) con identidad corporativa, spinner animado y tagline de marca.
- **Plan de Desacoplamiento Visual CSS:** Documento técnico (`docs/css-desacoplamiento-plan.md`) para migración aditiva de estilos CSS sin tocar lógica ni romper legacy.

### Cambiado

- **Sincronización Productiva SiteGround:** La rama `main` quedó desplegada en `prueba-lps.lastplanneraia.com` y la base de datos remota fue clonada desde local sobre `dbhif4pdimjtxe`, preservando estructura y datos de prueba para validación operativa.
- **Gestión de Usuarios sin Borrado Físico:** El admin ya no elimina usuarios de `general_usuarios`; ahora los conserva, bloquea acceso con `Activo/Inactivo`, invalida sesiones de cuentas desactivadas y mantiene la trazabilidad histórica aunque se revoquen todos sus proyectos.
- **Contexto Inteligente de Aterrizaje por Proyecto:** Refinamiento del servicio `src/Services/ProjectLandingService.php` para resolver la semana operativa con búsqueda descendente (priorizando semanas más recientes), invirtiendo la prioridad para favorecer semanas abiertas sobre semanas confirmadas con calificaciones pendientes. Esto mejora la experiencia al aterrizar directamente en la semana de trabajo actual. `DashboardController`, `ProjectSelectorController`, `ProgramaGeneralController` y `ProgramacionSemanalController` sanean el contexto semanal antes de redirigir o renderizar vistas.
- **Switch Global de Console Logs:** El dashboard admin ahora permite activar o silenciar `console.log` en todo el frontend, con persistencia centralizada y recarga uniforme de la configuracion en login, selector de proyecto y vistas legacy/MVC.
- **Profesionales Gobernados desde Admin:** El módulo `Profesionales` ahora usa el correo como identidad real, permite nombres repetidos, sincroniza cargos desde `admin/`, bloquea edición de nombre/correo/cargo y deja `Activo` como control local solo para miembros vigentes del proyecto.
- **Normalización Canónica de Nombres en Profesionales:** Las tablas `*_profesionales` ahora guardan el `nombre` oficial de `general_usuarios` cuando el correo tiene una coincidencia única en Admin, tanto en la sincronización automática como en el alta/edición manual; si no existe match confiable, se conserva el nombre local capturado.
- **Bajas y Trazabilidad de Profesionales:** Retirar un miembro del proyecto o eliminarlo desde `admin/` ya no destruye su historial operativo; el sistema bloquea el registro local y evita el borrado del usuario maestro cuando existe trazabilidad en proyectos.
- **Subcontratistas Live Edit:** Desktop, mobile y PDC ahora exigen filas completas antes de crear registros y validan el estado final completo antes de guardar cambios parciales.
- **Autoguardado Unificado al Estilo PI:** Programa General, Programación Semanal, Programa General Actualizar, Subcontratistas y Profesionales ahora muestran el badge inline de `AIA.Notice` en lugar de `toastr` o fades locales, alineando el feedback visual con Programación Intermedia.
- **Estandarización Final de AIA.Notice:** La capa `AIA.Notice` ahora cubre confirmaciones, diálogos y mensajes multilínea en Admin y módulos LPS, reemplazando `Swal.fire`, `window.confirm` y fallbacks `alert()` residuales con una API consistente.
- **Fase 2 de Unificacion de Notificaciones:** Se completo la migracion de `alert()` a `AIA.Notice` en `funcionesGenerales6.js`, `ContextManager.js` y `cargarDatosGeneralesPagina2.js`, unificando bloqueos de negocio y errores AJAX en helpers compartidos.
- **Validacion Universal de Sesion:** El front controller verifica timeout en rutas protegidas antes de despachar la aplicacion.
- **Unificacion de Notificaciones:** `programaGeneralActualizar.view.php` continua la migracion desde `alert()` hacia `AIA.Notice` con fallback seguro.
- **Motor de Herencia Robusto y Unificado:** Re-ingeniería del método `getPreviousWeekData` en `GeneralApiController.php` para implementar una lógica de priorización inteligente: el sistema ahora identifica y prefiere registros con datos reales (Unidad/Cantidad) sobre registros anómalos o vacíos en caso de duplicidad.
- **Herencia Agnóstica a HTML:** Refactorización de la lógica en `nueva_semana.php` y `GeneralApiController.php` para que el sistema ignore etiquetas como `<b>` y `<small>` al comparar nombres de actividades, asegurando la herencia correcta de PDC y Responsables tanto en importación como en mapeo manual.
- **Robustez en Carga de Parámetros:** Inyección de protecciones `try-catch` y logs descriptivos en `cargarDatosGeneralesPagina2.js` para interceptar fallos en la función `cargaParametros()`, mejorando la observabilidad en producción.
- **Unificación de Notificaciones:** Inicio formal de la eliminación total de `toastr` en el repositorio. Los modulos principales y vistas administrativas ahora consumen `AIA.Notice` como capa oficial para toasts, errores y badges de guardado.
- **Ajuste de Timeout de Sesión:** Incrementado el tiempo de inactividad de 10 segundos (test) a 3600 segundos (1 hora) para despliegue productivo, alineando el comportamiento del backend con la configuración global de la aplicación.

### Corregido

- **Fix 404 Añadir Miembros:** Se renombró el endpoint de `/admin/proyectos/miembros/añadir` a `/agregar` para prevenir fallos 404 ocasionados por codificación de la `ñ`.
- **Carryover PS -> PG al Crear Semana:** La nueva semana ahora arrastra `Ejecutado_Real`, `Responsable_AIA`, `Sub_Contratista`, `unidad` y `cantidad_ppto` desde Programación Semanal hacia Programa General, respetando subdivisiones, mapeo por `programaAnteriorAsociar` y normalizando a `%` cuando las medidas son inconsistentes.
- **Programación Semanal - Bloqueo por Asignaciones Incompletas:** El estado operativo ya no marca una actividad como `Lista para Confirmar` si falta `Responsable_AIA` o `Sub_Contratista`; el chip, el cierre semanal y la API ahora tratan esos casos como bloqueantes operativos.
- **Fix CNP al Abrir la Vista:** La columna `¿Liberada?` en `views/programacion-semanal/CNP.view.php` ahora tolera valores `null` de `Prog_Sin_Restricciones_100` y la autoprogramacion semanal vuelve a recalcular ese flag para evitar warnings TN/4 de DataTables al abrir `/programacion-semanal/cnp`.
- **Fix Falsos Duplicados en Profesionales:** La fila borrador ya no se valida contra sí misma, los homónimos dejan de bloquear el alta y el guardado local solo rechaza correos realmente repetidos.
- **Fix Carga de Profesionales al Renombrar Dependencias:** La sincronización de `*_profesionales` ahora verifica correctamente las tablas dependientes al propagar nombres canonizados, evitando el error SQL 1064 que impedía abrir `/profesionales`.
- **Fix Integridad de Subcontratistas y PDC:** Se reemplazaron alertas SQL crudas por validaciones de negocio para campos obligatorios y duplicados por nombre, correo y NIT.
- **Fix Consistencia Admin/Proyecto:** Los roles `A` ahora sincronizan como `Administrador` en `Profesionales`, manteniendo el mismo criterio operativo entre `admin/` y el proyecto.
- **Recuperación Global de AIA.Notice:** Se restauró la carga de SweetAlert2 y `AiaAlertInterceptor.js` en legacy, admin y login, corrigiendo la regresión que dejaba sin alertas de guardado, cambios de semana y avisos de sesión a varias vistas operativas.
- **Programa General - Cambio de Unidad a Porcentaje:** Al convertir actividades con unidad fisica a `%` (o vacio), el sistema ahora preserva el ratio canonico de `Ejecutado`, limpia `cantidad_ppto` y reconstruye `Ejecutado Real` como porcentaje persistente tras guardar y recargar.
- **Fix Tecla ESC en Grilla:** Resolución del `TypeError` y pérdida accidental de valores al presionar ESC en los editores TomSelect. Se sustituyó el método inexistente por `cancelEditing()`, restaurando el estado previo de la celda de forma segura.
- **Sincronización de Assets:** Actualización de headers y links de fuentes en las vistas de actualización para garantizar la carga de tipografías premium y el bypass de caché mediante versionamiento de scripts (`v=tomselect30`).
- **Alineación Vertical y Filas:** Eliminación de la columna de numeración redundante en Handsontable y ajuste de `line-height` en `handsontable-module.css` para resolver el desalineamiento visual de las celdas.
- **Fix Sincronización TomSelect:** Ajuste de selectores de clase (`.ts-option` → `.option`) y habilitación de `closeAfterSelect` para un comportamiento intuitivo del dropdown.

### Cambiado

- **Migración de Vistas al MVC Moderno (Fase 2):** 17 vistas distribuidas en los subdirectorios legacy `construccion/*/views/` fueron resituadas dentro del patrón arquitectónico en el nuevo directorio raíz `views/`. Los controladores de `src/Controllers/` fueron paralelamente recompilados para resolver los path dinámicos hacia sus equivalentes modernos.
- **Importación de Cronogramas (Fase 4):**
  - **Selector de Fecha Inicial:** Implementación de un selector de fecha dinámico en el modal de importación exclusivamente para proyectos nuevos (Semana 0), permitiendo alinear el cronograma con el calendario real.
  - **Notificación y Redirección AIA:** Diseño de un flujo de éxito premium (Manual de Marca AIA) que confirma la creación del cronograma y redirige automáticamente al Programa General (Semana 1) para una visualización inmediata.
  - **Detección Dinámica de Esquema:** Motor de búsqueda inteligente para la columna de jerarquía (WBS) en Excel, eliminando la dependencia de un orden estricto de columnas.
- **Sanitización de Invocaciones:** Se blindó el indexado de assets (css, imagenes, archivos bases) en los archivos views migrados desde `../` hacia paths absolutos en `/construccion/…` para evitar crashes por niveles de directorios variables.
- **Apificación de Módulos (Fase 3):**
  - [x] Contratos (`ContratosApiController`)
  - [x] Listado de Actividades (`ListadoActividadesApiController`)
  - [x] Plan de Compras (`PdcApiController`)
  - [x] Profesionales (`ProfesionalesApiController`)
  - [x] Control de Cambios (`ControlCambiosApiController`)
  - [x] Subcontratistas (`SubcontratistasApiController`)
- **Migración LPS Core (Fase 4):**
  - [x] Programación Semanal (`SemanalApiController`) — list, save, autoprogramar
  - [x] CNC (`CncApiController`) — listado y guardado
  - [x] CNP (`CnpApiController`) — listado y guardado
  - [x] CIC (`CicApiController`) — listado UNION, guardado con cálculo de disciplinas
  - [x] Programación Intermedia (`ProgramacionIntermediaController`) — list y save
  - [x] Programación General (`GeneralApiController`) — list, update, updateBatch y codigos
- **UI & Bugs (Scroll Lock):** Se sustituyó completamente el plugin Select2 (jQuery) por Tom Select (Vanilla JS) en la grilla de Programación Intermedia. El uso de la nueva clase `HandsontableTomSelectEditor.js` aísla los eventos del DOM previniendo el secuestro global del `wheel` que congelaba el scroll tras cerrar los menús desplegables.
- **Kill Switch Legacy (Fases 5 y 6):** Culminación de la erradicación del código heredado.
  - **Assets:** Se migraron masivamente imágenes, CSS y JS desde la carpeta `/construccion/` a `/public/`. Además, se actualizaron con `sed` los paths relativos en las vistas renderizadas.
  - **Endpoints Huerfanos:** Se mudaron scripts POST/GET solitarios como `actualizar_pdc.php` o `cambiar_pagina.php` al sandbox `/src/Legacy/Endpoints/` interceptándolos a través del Front Controller vía `$router->post` fallback rules.
  - **Eliminación Física:** Borrado definitivo de la mega-carpeta `/construccion/` limpiando el footprint fundamental del sistema viejo sin breaking changes.

- **Unificación Script PDC:** Reintegración y refactorización del script legacy `actualizar_pdc.php`. Se fusionó la validación esencial de negocio (`pdcActivo`) extraída del código original de 2022 con la eficiencia de sentencias preparadas (PDO) y manejo de excepciones JSON desarrolladas recientemente, consolidando todo en `src/Legacy/actualizar_pdc.php` y eliminando la versión redundante `actualizar_pdc_nueva_semana.php`.

### Corregido

- **Fix Error 500 en Admin Login (Database Path):** Se resolvió un error fatal que impedía acceder al panel administrativo integrado moderno (`/admin/login`). El controlador frontal de la consola de administración (`admin/public/index.php`) y el mapa de autoloader de Composer (`composer.json`) seguían apuntando a la ruta legacy de `Database.php` (`construccion/src/Database.php`) que fue eliminada por el Kill Switch. Se actualizó el entrypoint hacia la ubicación actual `src/Core/Database.php` y se regeneró el mapa de clases.
- **Fix Assets 404 en Admin Panel (Global):** Corrección masiva de dependencias estáticas (logo AIA `florAIA.png`, `tablet-viewport-scale.js` y `login-brand-unified.css`) tanto en la vista de login como en el layout principal (`admin/views/layouts/main.php`). Se mutaron los paths huérfanos `/construccion/` por el directorio absoluto `/public/`.
- **Fix JS ReferenceError en Tabla de Usuarios:** Se corrigió un error de ejecución en `admin/views/pages/users/index.php` donde el objeto `table` no estaba definido al intentar inicializar los botones de exportación de DataTables.
- **Update Seed Script:** Se actualizó `seed_test_users.php` para apuntar a la nueva infraestructura de base de datos en `src/Core/Database.php` eliminando la dependencia de `construccion/conexion.php`.

- **Fix Error 500 Rutas Duplicadas:** Se purgó el enrutador principal (`public/index.php`) de un bloque de rutas obsoletas y fantasmas dirigidas a `src/Legacy/Endpoints/` que causaba colisión de declaración y caída total del servidor al intentar resolver la ruta de actualización del PDC.

- **Fix Desalineamiento de Tom Select:** Corrección de la posición y ancho en `HandsontableTomSelectEditor.js` para que el input del dropdown solape perfectamente la celda de Handsontable (`top`/`left` relativos exactos y `width` dinámico) pero permitiendo a su vez que la lista emergente se expanda horizontalmente (`min-width: max(300px, tdRect.width)`) para no truncar los nombres largos de empresas.

- **Fix DataTables POST vs GET Methods (APIs):** Se modificó `public/index.php` para resolver un Error 405 (Method Not Allowed) en las tablas heredadas de jQuery DataTables (CNP, CNC, Contratos, etc.). Se restauró la definición a `POST` (estándar requerido por el AJAX interno de los listados legacy), manteniendo la dualidad `GET`/`POST` exclusivamente para la nueva grilla de Handsontable en `/api/semanal/list`.
- **Refactor SemanalApiController (Proyecciones):** Se delegó el cálculo asintótico del remanente al iterador de listado de Programación Semanal a través del `LpsService::calculateWeeklyProjections` eliminando código duplicado en el controlador.
- **Fix Error 404 Control de Cambios:** Se corrigieron referencias residuales a scripts legacy (`listar_controlCambios.php` y `guardar_controlCambios.php`) en `controlCambios.js` que causaban fallos en la carga de la tabla y obtención del director de obra tras la migración a la API.
- **Fix CIC Evaluaciones No Persistidas:** Se reescribió `CicApiController::updateMetrics()` que era un stub incompleto: no calculaba los promedios por disciplina (Calidad, GSA, SST, ADM), no ejecutaba el segundo UPDATE de disciplinas, y buscaba `$_POST['Observaciones']` cuando el frontend envía `mdo_Observaciones`/`si_Observaciones`.
- **Fix CIC Listado (Campo semanasEnProyecto):** Se corrigió `CicApiController::list()` que hacía un `SELECT *` simple en vez de la consulta UNION del legacy que calcula `semanasEnProyecto`, selecciona la última semana con datos por subcontratista y filtra proveedores de suministro.
- **Cache-Busting Dinámico:** Se implementó un parámetro de versión `?v=<?= time() ?>` en la carga de `controlCambios.js` dentro de la vista para asegurar que los usuarios reciban las actualizaciones de las rutas AJAX inmediatamente sin intervención manual en el navegador.
- **Fix Error 403 y Migración a Front Controller Puro (Fase 1/2):** Al eliminar `construccion/index.php` en la Fase 1, las visitas al dominio principal caían en un Error 403 Forbidden por parte de Apache (`DirectoryIndex` ausente + `RewriteCond %{REQUEST_FILENAME} -d`). Para solucionarlo bajo arquitectura moderna, se eliminó la excepción de compilación física de carpetas en archivo `.htaccess`. Se erradicaron los archivos dinámicos obsoletos (`index.php` y `construccion/index.php`) y en su lugar el enrutador (`public/index.php`) aprendió a engullir todo intento de acceso global, asignando `/` correctamente al `LoginController`. Así logramos un **Front Controller Puro** en el servidor sin interrupciones ni redirects heredados.
- **Fix Navegación Select2 (Causa Raíz):** Corregida la referencia `this.instance` → `this.hot` en `HandsontableSelect2Editor.js`. En Handsontable 14.6.1 la instancia del grid se expone como `this.hot`, por lo que toda la lógica de navegación por teclado (Tab, flechas) nunca se ejecutaba al estar accediendo una propiedad inexistente.
- **Fix Paridad Local (HTTP 404 Assets):** Tras la erradicación del prefijo `/construccion/`, los assets de imagen, css y js quedaban huérfanos del `DocumentRoot` en entornos locales Docker (donde la raíz es el proyecto entero, no `/public/`). Se inyectó una reescritura silenciosa en el `.htaccess` global para reenviar llamadas estáticas transparentemente a `/public/` logrando así funcionalidad 1:1 con SiteGround sin alterar sintaxis HTML.
- **Fix Error 405 Method Not Allowed (Modal Nueva Semana):** Corrección logística de métodos en el Front Controller (`public/index.php`). Las URL como `/legacy/funciones_generales/php/nueva_semana.php` y `verificarCICActualizada` estaban registradas usando `$router->get()`, pero el JavaScript heredado las despachaba vía Ajax en método `method: 'POST'`, forzando el rechazo por método no admitido. Se realinearon las rutas del enrutador.

### Añadido

- **Auto-apertura de Dropdowns (PI):** Al navegar con Tab o flechas a cualquier celda con dropdown (Select2 o nativo), el desplegable se abre automáticamente sin necesidad de doble click. Funciona tanto para navegación nativa de Handsontable como para la que viene desde un editor Select2 abierto.


## [1.0.0-rc4] - 2026-03-04

### Añadido

- **Bloqueo Inteligente de Ceros Virtuales (PS):** Implementación de una validación estricta en el hook `beforeChange` de Handsontable y en `guardar_programacion_semanal.php` para impedir la inyección de cantidades `< 0.001` en el Compromiso. Esto previene evasiones y fuerza obligatoriamente el desencadenamiento del flujo "Causa de No Programación (CNP)".
- **Sistema de Alertas de Restricciones (PS):** Implementación de una segunda compuerta de validación en la autoprogramación. Ahora, tras la ejecución, el sistema detecta y notifica mediante un modal informativo las actividades que no se programaron por tener restricciones pendientes (< 95%), detallando específicamente los rubros (Diseño, Materiales, etc.) que bloquean el inicio.

### Cambiado

- **Leyenda Programa General:** Se removió la aclaración "(Last Planner 6 semanas)" del título del modal de leyenda visual de la grilla principal para simplificar la interfaz.

### Corregido

- **Fix Redondeo Botones (PS):** Resolución nuclear de especificidad CSS en las vistas principales (CNC, CNP y CIC). Se forzó quirúrgicamente el uso de selectores ID combinados y `border-radius: 4px !important` junto a `-webkit-appearance: none` y `appearance: none` para romper la herencia de "pill shapes" nativos, garantizar los botones estilo bloque cuadrado (Apple-style design system) y asegurar compatibilidad estándar cross-browser eliminando advertencias de linter.
- **Autoprogramación con Subcontratistas Múltiples:** Se reparó un bug crítico en `autoprogramar_actividades.php` donde el proceso de autoprogramación fallaba al intentar insertar actividades que habían sido divididas previamente entre múltiples subcontratistas en la Programación Intermedia. Ahora el sistema respeta la asignación individual del subcontratista durante la inserción masiva.
- **Handsontable Select2 Tab Navigation:** Se corrigió la interrupción del flujo ("focus trap") al presionar `Tab` dentro del editor múltiple Select2 (`HandsontableSelect2Editor.js`). Ahora la tecla navega intuitiva y correctamente hacia la siguiente celda adyacente sin perder el foco en la grilla.
- **Navegación Avanzada Select2:** Afinamiento del motor de `HandsontableSelect2Editor.js` para restaurar explícitamente el foco al `textarea` interno de Handsontable tras el cierre rápido del dropdown, habilitando navegación bidireccional continua con teclado y flechas direccionales.
- **Techo de Esfuerzo en Autoprogramación Semanal:** Se ajustó la validación en `hot.js` para asegurar que la sumatoria total del "Ejecutado Fin Semana" (y el avance real) considerando todas las porciones asignadas a distintos contratistas nunca exceda el 100% o la "Cantidad PPTO" de la tarea madre.
- **Validación Cruzada de Sobreasignación (PS):** Implementación de validación híbrida en la grilla de Programación Semanal que suma dinámicamente el `Compromiso` y `Ejecutado_Real` de todas las filas hermanas de una misma actividad. Impide estrictamente que la asignación combinada supere matemáticamente el 100% o la Cantidad PPTO.
- **Bloqueo Backend de Ceros Virtuales:** Se blindó el endpoint PHP (`guardar_programacion_semanal.php`) rechazando cualquier guardado con `Compromiso <= 0`, exigiendo que cualquier desprogramación fluya mandatariamente por el panel de Causas de No Programación (CNP).
- **Condición de Carrera en Creación de Semanas:** Se erradicó un bug de *Double Submit* en el UI de `funcionesGenerales.js` que registraba múltiples listeners al botón "Guardar". Esto causaba una falsa alarma visual de "semana no confirmada" tras insertar lógicamente la semana de forma exitosa.
- **Stale State en Navegación Menú (PS):** Se eliminó el uso de variables renderizadas estáticas (`+ semana`) en los dropdowns HTML de CNP, CNC y CIC en favor de interpolación en tiempo de clic. Esto previene que la navegación secundaria retroceda al inicio al perder estado.
- **Highlight Activo en Dropdown de Semanas (PS):** Se amplió el mapeo de URL en `cargarDatosGeneralesPagina2.js`, de forma que al navegar en los submódulos (CNP, CNC, CIC), el sistema de menús siga reconociéndolos como parentescos de `programacion_semanal` y resalte correctamente la semana actual en la barra de navegación del sitio.

## [1.0.0-rc3] - 2026-03-04

### Cambiado

- **Limpieza de Código Muerto:** Eliminación masiva de artefactos residuales de IA (`~/.gemini/brain`), librerías de prueba sin uso (tutoriales FPDF) y directorios estériles marcados como `_DEAD_PLAN_CALIDAD`. Se conservaron controladores explícitos de descargas y vistas secundarias con referencias dinámicas.
- **Erradicación Visual de DataTables:** Desacoplamiento final de DataTables y consolidación de Handsontable como el standard-bearer de las 3 programaciones principales (General, Intermedia, Semanal). Los archivos de la librería permanecen aislados solo para módulos legacy menores.

### Corregido

- **Validación Autoprogramación:** Removida la obligación en `guardar_programacion_semanal.php` de exigir el campo `Cantidad PPTO` cuando la unidad técnica asignada de la actividad de origen era exactamente `%`.
- **Interferencias Visuales en Unidades:** Se suprimió la dependencia estructural de `programa_general/hot.js` que forzaba visualmente la inyección de `%` a pesar de que el registro maestro original poseía vacíos estructurales.
- **Handsontable + Select2 Conflict:** Se resolvió de raíz la colisión de eventos tipo "outside click". Handsontable cerraba abruptamente las grillas múltiples de Select2 al hacer clic en opciones o chips. El `$wrapper` de `HandsontableSelect2Editor.js` ahora muta el DOM y se encapsula dinámicamente **dentro del TD activo** cada vez que se abre la lista, aislando el comportamiento de captura de click externo.
- **Integración Select Múltiple (PI):** Reestructurados los validadores `afterChange` en la vista de Programación Intermedia para iterar correctamente arreglos separados por coma y rescatar pills elegidos del estado deshecho si el usuario gatillaba las anclas estáticas de creación (`+ Crear Subcontratista/Responsable`).
- **Handsontable Select2 UI/UX:** Corrección intensiva de estilos inyectados CSS en tiempo de ejecución. Resolvimos el solapamiento de tags y limitación de altura (`max-height`, `flex-wrap`), así como los gaps absolutos indeseados entre el input container y el dropdown. Adición formal del editor derivado `Select2Single` y refactorización forzosa por Cache-Busting (`v=hotcustom3`).

### Añadido

- **Auto-Corrección Transversal de Unidades (%):** Inyección en el Endpoint de listado global (`construccion/api/program/list.php`). Ahora, y de manera automatizada, cuando cualquier miembro accede al listado maestro de Programa General, el sistema detecta actividades sin asignación de `unidad` y las auto-configura implícitamente a `%` sobre la base de datos para erradicar inconsistencias transaccionales futuras.

## [1.0.0-rc2] - 2026-03-03

### Añadido

- **Estrategia CSS 2026 (Refactor):** Fase 1 de la adopción del esquema de cascada `@layer`. Se aislaron `tokens.css` (`@layer theme`) y `access.css` (`@layer utilities`) para reducir colisiones de especificidad con los assets legados de Bootstrap.
- **UI Modal Premium:** Recuperación del formulario "Nueva Actividad" con formato avanzado de doble columna. Integración de la "Bandeja de Excepciones No Autoprogramadas" con enlace AJAX (JS) para autocompletar actividades pendientes.
- **Responsive Navigation:** Implementado el punto de quiebre `xl` (1200px) y tipografía fluida `clamp()` en Navbar para prevenir colapsos horizontales (overflow) del menú principal en resoluciones de tablet o pantallas intermedias.
- **Visual:** Nuevo diseño premium para el Sidebar Móvil (Drawer) usando capa CSS `aia-premium-drawer` con efecto Glassmorphism y animaciones spring para reemplazar el menú móvil genérico.
- **UX:** "Isla de Usuario" flotante (Thumb Zone UI) incorporada en el límite inferior del Drawer móvil para un acceso rápido y cómodo al Perfil, Cambio de Proyecto, Notificaciones y Cierre de Sesión.
- **Notificaciones PI (Fases 1-5):** Implementado un ecosistema completo para emitir asíncronamente (Upsert en la tabla `system_notifications`) los eventos críticos del ciclo de vida PI a través del `NotificationService`. Abarca Notificaciones de Semáforo (`blocked-overdue`, `execution-blocked`, etc.), Modificaciones de Restricciones explícitas, Asignaciones Manuales de Subcontratistas/Responsables y aplicaciones de Restricciones Compartidas (Lote MVC). Se incrustaron en `guardar_programacion_intermedia.php` (AJAX Legacy) y en `ProgramacionIntermediaController.php` (MVC Moderno).
- **Badge Notificaciones UI:** Actualización de `notifications.js` para sumar conteos internos `item_count` en tiempo real y mostrar dinámicamente notificaciones contraíbles en la campana sin necesidad de recargar la vista, incluyendo el ruteo seguro a `/api/notifications/unread`.
- **Limpieza de Deuda Técnica (DataTables):** Eliminación física de los archivos base legacy (`*.view.nuevaBarra.php`) de Programación General, Programación Intermedia y Programación Semanal, certificando a Handsontable como el único motor de renderizado de cuadrícula en el _Core_ de la aplicación.

### Corregido

- **Fix Navegación Select2:** Se solucionó el `"focus trap"` en `HandsontableSelect2Editor.js` interceptando las teclas `Tab`, `Esc` y `Flechas Horizontales`, devolviendo el foco correctamente a la grilla y permitiendo una navegación fluida entre celdas.
- **Cálculo Ejecutado Fin Semana (Prog. Semanal):** Se reemplazó la proyección estática (`7 / diasTotales`) por un cálculo asintótico en `listar_programacion_semanal.php`, de forma que el esfuerzo máximo sugerido y el `Ejecutado Fin Semana` respeten el remanente real y jamás superen el 100%.
- **Notificaciones Legacy/Race Condition:** Arreglado bug donde el dropdown del Navbar (componente Outbox) se quedaba en "Cargando..." en vistas Legacy (`Programa General`, etc.) debido a inyecciones asíncronas de HTML fallidas. Componente de JS adaptado para inicializarse independientemente vía `document.readyState`.
- **UI Elementos:** Reparado modal de leyenda (ReferenceError `renderLegendModal`) para la grilla Handsontable en Programa General.
- **Viewport Tablet:** Relajamiento del responsive scale para tablets (de `0.7` agresivo a `0.85` con escalado permitido), junto con breaking points intermedios (`xl`) en Navbar.
- **Compresión Vista Modal:** Refactor CSS Grid con estructura de 12 columnas en `programacion_semanal.view.handsontable.php`, empacando inputs para eliminar el scroll y mantener el botón guardar expuesto.
- **Sidebar Glitch iPad Air:** Corrección de breakpoints al colapsar el menú de navegación al modo hamburguesa para que el fondo asuma el _AIA Green_ y no negro.

### Cambiado

- **Ajuste Terminología LPS:** Renombrado el estado "No activa" a "Programada Manualmente" en Programación Semanal para diferenciar claramente las actividades de creación manual de las autoprogramadas.

## [1.0.0-rc1] - 2026-03-02

### Añadido

- **Sistema RBAC & Seguridad:** Implementación completa de Arquitectura Híbrida RBAC (Roles, Capacidades, `RbacService`), validación estricta server-side para `guardar_programacion_semanal`, normalización de cargos legacy y visualización del rol en el Navbar.
- **Migración de Endpoints API (Fase 3):** Consolidación de 6 módulos (Contratos, Actividades, PDC, Profesionales, Subcontratistas y Control de Cambios) bajo controladores MVC.
- **UI/UX 2026:** Migración exitosa de grillas a **Handsontable** iterativo con autoguardado, adición de paleta OKLCH corporativa (AIA Brand) y alertas dinámicas Toast.
- **Documentación Extendida:** Creación de diccionario `GLOSARIO.md` (100 conceptos clave) y mapa maestro de APIs `ROUTES.md`.

### Cambiado

- **Arquitectura Backend & Reportes:** Centralización absoluta del Output de inteligencia hacia `ReportController`, eliminando 11 scripts PHP obsoletos. Apunte del autoloader legacy a la raíz.
- **Documentos Maestros Mnemotécnicos:** Reestructuración profunda de `README.md` (El Viaje del Héroe), `ROADMAP.md` interactivo y `CHANGELOG.md`.
- **Mantenimiento y Deuda Técnica:** Purga global del "FilterManager" deprecado, refactor CSS Mobile-First de vistas `.view.handsontable.php`, y versionamiento de DB volcados.

### Corregido

- **Foco GUI & Estabilidad:** Corrección global de pérdida de foco en modales interactivos sobre grillas HOT y estabilización del Autoupdate on-cell-change.
- **Estandarización UTF-8 Fix:** Parche transversal de codificación de caracteres `Ń`/`ñ` a lo largo de registros SQL clave e interfaces LPS UI.
- **Linting de Core MD:** Formateo intensivo Prettier para superar los umbrales de caracteres máximos y listados mal compuestos.

### Eliminado

- Remoción total del antiguo módulo `PaquetesContratacion` en favor del gestor canónico moderno `/contratos`.
- Descarte formal documentado del POC de arquitectura SPA Lite.

## [0.5.0] - 2026-02-03

### Añadido

- **Estandarización de Código (PSR-12):**
  - Implementación de `php-cs-fixer` en todo el proyecto.
  - Formateo automático de 159 archivos para cumplir con estándares internacionales.
  - Creación de configuración personalizada `.php-cs-fixer.dist.php`.

- **Análisis Estático (PHPStan):**
  - Instalación y configuración de `PHPStan` (Nivel 1).
  - Generación de línea base de errores (599 reportes) para guiar la refactorización arquitectónica.
  - Creación de configuración `phpstan.neon`.

- **Documentación:**
  - Cierre formal de la Fase 1 en `ROADMAP.md`.
  - Creación de `walkthrough.md` con evidencia de pruebas de humo.

## [0.4.0] - 2026-01-08

### Añadido

- **Gestión de Miembros:** Implementación completa del sistema de membresía para vincular usuarios únicos a múltiples proyectos.
- **Inteligencia de Roles:**
  - Motor de normalización de cargos (limpieza de acentos, géneros y artículos).
  - Búsqueda difusa (Fuzzy Matching) mediante algoritmo de Levenshtein para tolerancia a errores de ortografía.
  - Sistema de aprendizaje persistente en la tabla `role_intelligence` que evoluciona con el uso del administrador.
- **UI Proyectos:** Nueva interfaz para asignar y revocar acceso a proyectos con sugerencias inteligentes en tiempo real.
- **Seguridad:** Protocolo de "Seguridad por Defecto" que asigna rol de Visualizador ante cargos desconocidos.

### Cambiado

- **Normalización de Datos:** Unificación de la tabla `general_usuarios` eliminando más de 100 registros duplicados y consolidando sus accesos en la nueva tabla `project_members`.
- **Arquitectura:** Centralización de la lógica de permisos en la clase `RoleManager`.

## [0.3.0] - 2026-01-08

### Añadido

- **Integridad de Datos:** Creación automática de 10 tablas relacionales por cada proyecto nuevo.
- **Gestión de Prefijos:** Renombrado atómico de tablas de base de datos cuando se modifica el prefijo del proyecto.
- **Respaldos:** Funcionalidad para exportar y descargar un volcado SQL completo de las tablas de un proyecto.
- **Eliminación Segura:** Flujo de trabajo con SweetAlert2 que descarga un respaldo antes de eliminar físicamente las tablas.
- **UI Proyectos:** Integración completa de DataTables con traducción al español, Toastr para feedback asíncrono y corrección de solapamiento en el layout.

### Corregido

- Error de espacio de nombres en la generación del token CSRF en el diseño principal (`\Admin\Core\Security`).
- Delegación de eventos en DataTables para asegurar que los botones funcionen tras búsquedas o cambios de página.

## [0.2.0] - 2026-01-08

### Añadido

- **Mejoras en Gestión de Proyectos:**
  - Esquema de proyecto ampliado con nuevos campos: Área (Construcción/PI), Control de Acceso, Estado de PDC, Fechas de Línea Base (Inicio/Fin), Costo de Retraso y URL de Control de Cambios.
  - Generación Automática de Nombres de Base de Datos: Implementada una lógica robusta de `slugify` que:
    - Elimina palabras vacías en español (el, de la, la, etc.).
    - Convierte números (1-10) a números romanos (i, ii, iii...).
    - Maneja la transliteración y separa las palabras con guiones bajos.
    - Añade automáticamente el sufijo `_pi` para proyectos del área PI.
  - Implementación completa de CRUD:
    - Nueva vista de creación de proyectos con campos avanzados.
    - Vista de edición de proyectos con capacidad de anulación manual del nombre de la base de datos.
    - Lista de proyectos actualizada con columna de Área y estilo mejorado.
  - Enrutamiento seguro para todas las operaciones CRUD de proyectos con protección CSRF.

## [0.1.0] - 2026-01-08

- Versión inicial del proyecto.
