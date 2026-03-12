# Registro de Cambios (Changelog)

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Sin publicar]

### Cambiado

- **Migración de Vistas al MVC Moderno (Fase 2):** 17 vistas distribuidas en los subdirectorios legacy `construccion/*/views/` fueron resituadas dentro del patrón arquitectónico en el nuevo directorio raíz `views/`. Los controladores de `src/Controllers/` fueron paralelamente recompilados para resolver los path dinámicos hacia sus equivalentes modernos.
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

### Corregido

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
