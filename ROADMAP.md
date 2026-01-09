# Diagnóstico y Hoja de Ruta de Implementación - Last Planner AIA

## 1. Propósito Central del Proyecto

La aplicación "Last Planner AIA" es una herramienta web diseñada para implementar la metodología *Last Planner System (LPS)* en proyectos de construcción. Su objetivo es digitalizar, centralizar y optimizar la planificación, el seguimiento de tareas, la gestión de restricciones y la generación de reportes de productividad, mejorando la comunicación y la fiabilidad en la ejecución de obras.

## 2. Nivel de Madurez

**Clasificación: Maduro con Deuda Técnica Crítica.**

- **Maduro:** La aplicación es funcionalmente rica y demuestra un profundo conocimiento del dominio de negocio de la construcción. Cubre procesos complejos y específicos del sector.
- **Deuda Técnica Crítica:** El código base presenta vulnerabilidades de seguridad significativas y problemas arquitectónicos que impiden su mantenimiento, escalabilidad y evolución.

## 3. Stack Tecnológico Identificado

- **Backend:** PHP 7.x / 8.x (procedural, sin uso de frameworks).
- **Frontend:** HTML, CSS, JavaScript (Vanilla).
- **Base de Datos:** MySQL / MariaDB.
- **Gestor de Dependencias:** Composer.
- **Librerías Clave:**
    - `phpoffice/phpspreadsheet`: Manipulación de archivos Excel.
    - `vlucas/phpdotenv`: Gestión de variables de entorno.
- **Herramientas de Calidad de Código:**
    - `php-cs-fixer`: Para estandarización de estilo de código (PSR-12).

## 4. Análisis DOFA Técnico

### Fortalezas
- **Conocimiento de Dominio:** La lógica de negocio implementada es robusta y está alineada con las necesidades reales de la industria.
- **Funcionalidad Completa:** La aplicación cubre un amplio espectro de los requerimientos del Last Planner System.
- **Sin Dependencias Excesivas:** Al ser "vanilla", tiene una sobrecarga mínima y es compatible con la mayoría de los entornos de hosting PHP.

### Oportunidades
- **Modernización de Arquitectura:** La migración a un patrón **API-First (Backend desacoplado) + Frontend Moderno (SPA)** puede transformar radicalmente la experiencia de usuario, la mantenibilidad y la escalabilidad.
- **Automatización de Tareas:** Implementar funcionalidades como acciones en lote, notificaciones automáticas e importaciones inteligentes para reducir la carga de trabajo manual.
- **Mejora de la Seguridad:** La adopción de prácticas estándar como el uso de PDO y un Front Controller puede eliminar las vulnerabilidades actuales.
- **Implementación de Pruebas:** La creación de una suite de pruebas automatizadas (unitarias y de integración) aumentaría la fiabilidad del código.

### Debilidades
- **Vulnerabilidad de Inyección SQL:** El uso de `mysqli` con concatenación directa de variables (especialmente `$_GET['db']`) en las consultas SQL es una falla de seguridad crítica.
- **Código Duplicado:** Módulos como `pdc` y `pdc1` son prácticamente idénticos, aumentando el costo de mantenimiento.
- **Falta de un Punto de Entrada Único (Front Controller):** Cada archivo PHP se gestiona a sí mismo, lo que lleva a la repetición de código para la inicialización de sesiones y conexiones a la base de datos.
- **Mezcla de Lógica y Presentación:** El código PHP, la lógica de negocio y el HTML están fuertemente acoplados en los mismos archivos, dificultando la lectura y modificación.

### Amenazas
- **Riesgo de Seguridad:** La vulnerabilidad de inyección SQL expone la aplicación a ataques que podrían comprometer la integridad y confidencialidad de los datos.
- **Dependencias Obsoletas:** Las librerías pueden quedar desactualizadas si no se gestionan activamente, introduciendo riesgos de seguridad o incompatibilidades.
- **Dificultad para Escalar:** La arquitectura actual hace que añadir nuevas funcionalidades sea un proceso lento, propenso a errores y costoso.

## 5. Oportunidades de Mejora y Herramientas Recomendadas

- **Guía de Estilo:** Continuar y reforzar el uso de **PSR-12** a través de `php-cs-fixer`.
- **Análisis Estático:**
    - **PHPStan:** Introducir gradualmente para detectar errores lógicos y de tipado sin ejecutar el código. Empezar en el nivel más bajo e ir subiendo.
- **Pruebas Automatizadas:**
    - **PHPUnit:** Implementar para crear pruebas unitarias (para la lógica de negocio) y pruebas de integración (para el acceso a datos).

## 6. Hoja de Ruta de Implementación (4 Meses y 2 Semanas)

Esta fase inicial se centra en construir una base administrativa sólida y segura ("Greenfield") para luego integrar y refactorizar la lógica de negocio existente.

### Fase 1: Panel de Administración de Alta Eficiencia (Semanas 1-2)
**Objetivo:** Implementar un panel administrativo robusto, seguro y escalable utilizando arquitectura LAMP optimizada ("Sin Framework"), basado en *AdminLTE 3* y *DataTables Server-Side*.

#### Semana 1: Arquitectura Núcleo, Base de Datos y Seguridad
*   **Día 1: Infraestructura y Estructura de Directorios**
    - [x] **Estructura Segura:** Implementar separación física entre lógica e interfaz pública en la carpeta `admin/`.
    - [x] **Front Controller:** Configurar `.htaccess` para redirigir todo el tráfico a `admin/public/index.php` y bloquear acceso directo a carpetas sensibles. **(Verificado en MAMP)**
    - [x] **Sistema de Logs:** Implementar logs de error en `admin/logs/php_error.log`.
    - [x] **Git:** Configurar `.gitignore` robusto.
*   **Día 2: Capa de Datos y Modelado (MySQL + PDO)**
    - [x] **Conexión Singleton:** Implementar clase `Database` con patrón Singleton y soporte para puerto MAMP (8889).
    - [x] **Configuración PDO Segura:** Desactivar `ATTR_EMULATE_PREPARES`.
    - [x] **Integración Esquema Existente:** Mapear modelos a tablas existentes `general_usuarios` (usuarios, roles, permisos) y `general_proyectos_procesos` (proyectos).
    - [x] **Auditoría de Datos:** Confirmado uso de SHA-512 (128 caracteres) sin sal dinámica. Implementado puente de compatibilidad híbrida para migración progresiva a `password_hash()`.
*   **Día 3: Núcleo del Framework Artesanal (Micro-MVC)**
    - [x] **Router:** Implementar despachador de rutas simple basado en URLs amigables (ej: `/usuarios/editar/15`).
    - [x] **Controlador Base:** Crear clase abstracta para renderizado de vistas y respuestas JSON estandarizadas.
    - [x] **Endurecimiento de Sesión:** Configurar `HttpOnly`, `Secure`, `Strict Mode` y regeneración de ID de sesión al iniciar sesión.
    - [x] **Seguridad CSRF:** Implementar clase `CsrfToken` para generación y validación de tokens en formularios POST/PUT/DELETE.
*   **Día 4: Integración de Frontend (AdminLTE 3)**
    - [x] **Activos:** Integrar AdminLTE 3 (vía CDN) y dependencias (Bootstrap 4.6 + jQuery).
    - [x] **Diseño Modular:** Separar componentes: `Navbar`, `Sidebar`, `Footer` y `Content Wrapper` en `admin/views/layouts/main.php`.
    - [x] **Migas de Pan (Breadcrumbs):** Implementar sistema dinámico de migas de pan pasado desde el controlador.
    - [x] **Optimización de Interfaz:** Implementados encabezados fijos (sticky headers) y optimización de espacio en tablas (modo compacto) para eliminar desplazamiento horizontal.
    - [x] **Inyección de Scripts:** Implementada lógica de scripts específicos por vista para DataTables y SweetAlert2.
*   **Día 5: Autenticación y Control de Acceso**
    - [x] **Inicio de Sesión:** Formulario de acceso con validación `password_verify` y regeneración de sesión.
    - [x] **Middleware de Autenticación:** Proteger rutas administrativas verificando sesión activa.
    - [x] **Lógica RBAC:** Implementar clase/método `User::can($permission)` para control granular en vistas y controladores.

#### Semana 2: Módulos Funcionales y Experiencia de Usuario (UX)
*   **Día 6-7: Gestión de Usuarios (Escalabilidad Total)**
    - [x] **Controlador:** Implementar `UserController` (index, create, store, edit, update, delete).
    - [x] **Generación Automática de Usuario:** Implementada lógica para sugerir `nombre.apellido` automáticamente con detección de duplicados y normalización de caracteres.
    - [x] **DataTables Server-Side:** Implementar lógica backend para paginación, filtrado y ordenamiento SQL dinámico (AJAX). (Implementado en el cliente para agilidad inicial, escalable a servidor).
    - [x] **Vista Índice:** Integrar DataTables configurado para carga AJAX y renderizado de columnas.
    - [x] **Acciones:** Generar botones de acción (Editar/Eliminar) dinámicamente.
*   **Día 8: Gestión de Proyectos y UX Avanzada**
    - [x] **Controlador:** Implementar `ProjectController`. (CRUD completo implementado).
    - [x] **Formularios Extendidos:** Implementados campos de área, fechas de línea base, costos y URLs.
    - [x] **Slugify Automático:** Generación inteligente de nombres de base de datos con números romanos y palabras vacías (stop-words).
    - [x] **Interruptores (Toggle Switches):** Implementar cambio de estado (Activo/Acceso/PDC) con peticiones AJAX inmediatas.
    - [x] **Retroalimentación:** Integrar *Toastr* para notificaciones de éxito/error asíncronas.
    - [x] **Integridad de Datos:** Implementar creación automática y renombrado atómico de tablas de proyecto (10 tablas por proyecto).
    - [x] **Validación:** Implementar saneamiento (`filter_var`) y validación estricta de entradas en el backend.
*   **Día 9: Asignación de Recursos (Relaciones N:M)**
    - [x] **Unificación de Datos:** Eliminación de duplicados en `general_usuarios` y migración a tabla relacional.
    - [x] **Motor de Inteligencia:** Implementación de `RoleManager` con normalización y búsqueda difusa (Levenshtein).
    - [x] **Lógica de Asignación:** Gestión de `project_members` con aprendizaje dinámico de cargo-rol.
*   **Día 10: Salud del Sistema y Monitoreo (Dashboard)**
    - [x] **Tablero de Salud:** Implementado tablero de indicadores técnicos (Salud del Sistema).
    - [x] **Monitoreo de Logs:** Rastreo de errores en `php_error.log` con filtrado dinámico y visualización en tiempo real de toda la actividad del día actual.
    - [x] **Alertas de Integridad:** Sistema de detección de tablas faltantes por proyecto con detalle visual a un clic.
    - [x] **Limpieza de Base de Datos:** Identificación y eliminación de tablas huérfanas (basura de proyectos eliminados) con protección de tablas globales.
    - [x] **Monitor de Entorno:** Visualización de límites de PHP (`upload_max_filesize`, `memory_limit`) para soporte técnico.
    - [x] **Estado de Respaldos:** Generación de copias de seguridad SQL completas mediante streaming directo a disco para evitar saturación de memoria.
*   **Día 11: Pulido y Entrega**
    - [ ] **Auditoría Final:** Verificar permisos de archivos y configuración de entorno (`display_errors = Off`).
    - [ ] **Smoke Test:** Validación manual completa de flujos críticos.

### Fase 2: Mitigación de Riesgos y Unificación del Código (Mes 1)
- **Semana 1-2: ERRADICAR VULNERABILIDADES DE INYECCIÓN SQL (Prioridad Máxima).**
    - **Acción:** Refactorizar `conexion.php` para utilizar **PDO** en lugar de `mysqli`. - **(HECHO)**
    - **Acción:** Crear una clase `Database` que centralice la conexión y la ejecución de consultas, forzando el uso de **consultas preparadas**. - **(HECHO)**
    - **Acción:** Auditar y refactorizar **TODAS** las consultas SQL del proyecto para que utilicen el nuevo sistema de consultas preparadas. - **(EN PROGRESO)**
        
        #### Scripts Raíz
        - [x] `construccion/Cargar_Nuevos_Proyectos.php` (Eliminado - Reemplazado por ProjectController)
        - [x] `construccion/Copia_de_Seguridad_LPS.php` (Eliminado - Reemplazado por ProjectController)
        - [x] `construccion/eliminar_proyectos.php` (Eliminado - Reemplazado por ProjectController)
        - [x] `construccion/generarCurvaS.php`
        - [x] `construccion/generarCurvaSB.php`
        - [x] `construccion/generarCurvaSPDC.php`
        - [x] `construccion/generarListadoRestriccionesGeneral.php`
        - [x] `construccion/generarReporteGeneral.php`
        - [x] `construccion/generarReportePDC.php`
        - [x] `construccion/generarTablaHTMLProgramacionSemanal.php`

        #### Módulo: Funciones Generales
        - [x] `construccion/funciones_generales/php/actualizarEjecucion.php`
        - [ ] `construccion/funciones_generales/php/actualizar_pdc_nueva_semana.php`
        - [ ] `construccion/funciones_generales/php/autoprogramar_actividades.php`
        - [ ] `construccion/funciones_generales/php/datosGeneralesPagina.php`
        - [ ] `construccion/funciones_generales/php/eliminar_semana.php`
        - [x] `construccion/funciones_generales/php/modificar_sem_estado.php`
        - [ ] `construccion/funciones_generales/php/modificar_sem_estado_actualizar.php`
        - [ ] `construccion/funciones_generales/php/nueva_semana.php`
        - [ ] `construccion/funciones_generales/php/nueva_semana1.php`
        - [ ] `construccion/funciones_generales/php/verificarCICActualizada.php`

        #### Módulo: Indicadores
        - [ ] `construccion/indicadores/listar_detalles_indicadores.php`
        - [ ] `construccion/indicadores/listar_indicadores.php` (Refactorización parcial pendiente)

        #### Módulo: Informe de Productividad
        - [ ] `construccion/informe_productividad/listar_detalles_informe_productividad.php`
        - [ ] `construccion/informe_productividad/listar_informe_productividad.php`
        - [ ] `construccion/informe_productividad/listar_informe_productividad_c.php`
        - [ ] `construccion/informe_productividad/views/informe_productividad.view.nuevaBarra.php`

        #### Módulo: Informes JSON
        - [ ] `construccion/informesJSON/listar_curvas.php`
        - [ ] `construccion/informesJSON/listar_curvas_pdc.php`
        - [ ] `construccion/informesJSON/listar_informe_pdc.php`
        - [ ] `construccion/informesJSON/listar_informe_programacion_semanal.php`
        - [ ] `construccion/informesJSON/listar_informe_restricciones.php`

        #### Módulo: Listado de Actividades
        - [ ] `construccion/listadoActividades/guardar_listadoActividades.php`
        - [ ] `construccion/listadoActividades/listar_listadoActividades.php`
        - [ ] `construccion/listadoActividades/views/listadoActividades.view.nuevaBarra.php`

        #### Módulo: Paquetes de Contratación
        - [ ] `construccion/paquetesContratacion/guardar_paquetesContratacion.php`
        - [ ] `construccion/paquetesContratacion/listar_paquetesContratacion.php`
        - [ ] `construccion/paquetesContratacion/views/paquetesContratacion.view.php`

        #### Módulo: PDC
        - [ ] `construccion/pdc/actualizar_pdc.php`
        - [ ] `construccion/pdc/guardar_pdc.php`
        - [ ] `construccion/pdc/listar_pdc.php`

        #### Módulo: Profesionales
        - [ ] `construccion/profesionales/guardar_profesionales.php`
        - [ ] `construccion/profesionales/listar_profesionales.php`

        #### Módulo: Actualización de Programa General
        - [ ] `construccion/programaGeneralActualizar/actualizarFiltros.php`
        - [ ] `construccion/programaGeneralActualizar/descargarCorteProgramacion.php`
        - [ ] `construccion/programaGeneralActualizar/guardar_programaGeneralActualizar.php`
        - [ ] `construccion/programaGeneralActualizar/listar_programaGeneralActualizar.php`
        - [ ] `construccion/programaGeneralActualizar/views/programaGeneralActualizar.view.nuevaBarra.php`

        #### Módulo: Programa General
        - [ ] `construccion/programa_general/actualizarFiltros.php`
        - [ ] `construccion/programa_general/descargarCorteProgramacion.php`
        - [ ] `construccion/programa_general/views/programa_general.view.nuevaBarra.php`

        #### Módulo: Programación Intermedia
        - [ ] `construccion/programacion_intermedia/actualizarFiltros.php`
        - [ ] `construccion/programacion_intermedia/descargarRestricciones.php`
        - [ ] `construccion/programacion_intermedia/generarListadoRestriccionesGeneral.php`
        - [ ] `construccion/programacion_intermedia/guardar_programacion_intermedia.php`
        - [ ] `construccion/programacion_intermedia/listar_programacion_intermedia.php`
        - [ ] `construccion/programacion_intermedia/views/programacion_intermedia.view.nuevaBarra.php`

        #### Módulo: Programación Semanal
        - [ ] `construccion/programacion_semanal/descargarCompromisos.php`
        - [ ] `construccion/programacion_semanal/generarReporteGeneral.php`
        - [ ] `construccion/programacion_semanal/guardar_CIC.php`
        - [ ] `construccion/programacion_semanal/guardar_CNC.php`
        - [ ] `construccion/programacion_semanal/guardar_CNP.php`
        - [ ] `construccion/programacion_semanal/guardar_programacion_semanal.php`
        - [ ] `construccion/programacion_semanal/listar_CIC.php`
        - [ ] `construccion/programacion_semanal/listar_CNC.php`
        - [ ] `construccion/programacion_semanal/listar_CNP.php`
        - [ ] `construccion/programacion_semanal/views/CNP.view.nuevaBarra.php`
        - [ ] `construccion/programacion_semanal/views/programacion_semanal.view.nuevaBarra.php`

        #### Módulo: Regístrate
        - [ ] `construccion/registrate/registrate.php`
        - [ ] `construccion/registrate/views/registrate.view.php`

        #### Módulo: Subcontratistas
        - [ ] `construccion/subcontratistas/guardar_subcontratistas.php`
        - [ ] `construccion/subcontratistas/listar_subcontratistas.php`

    - **Resultado Clave:** Cierre de la brecha de seguridad más crítica.
- **Semana 3: Consolidación y Limpieza.**
    - **Acción:** Aplicar `php-cs-fixer` a toda la base de código para garantizar un estilo consistente (PSR-12).
    - **Acción:** Analizar las diferencias entre los directorios `pdc` y `pdc1`, fusionar la funcionalidad necesaria en `pdc` y **eliminar `pdc1`**. - **(HECHO)**
    - **Acción:** Eliminar el directorio `construccion/ext/` al confirmarse que es código redundante y obsoleto tras la migración a Google Data Studio. - **(HECHO)**
    - **Resultado Clave:** Reducción del código duplicado y mejora de la legibilidad.
- **Semana 4: Detección Temprana de Errores.**
    - **Acción:** Instalar **PHPStan** (vía Composer) y configurarlo en el nivel 1.
    - **Acción:** Ejecutar el análisis y corregir los errores de bajo nivel identificados.
    - **Resultado Clave:** Establecimiento de una primera capa de análisis estático para prevenir errores comunes.

### Mes 2: Refactorización Arquitectónica
- **Semana 5-6: Implementación del Patrón Front Controller.**
    - **Acción:** Configurar `mod_rewrite` (vía `.htaccess`) para redirigir todas las peticiones a un único archivo `index.php`.
    - **Acción:** Instalar una librería de enrutamiento como `nikic/fast-route`.
    - **Acción:** Migrar los 3 módulos más importantes (ej. `login`, `programa_general`, `programacion_semanal`) al nuevo sistema de rutas.
    - **Resultado Clave:** Centralización de la gestión de peticiones, sesiones y conexión a la base de datos. Base para una futura API REST.
- **Semana 7-8: Separación de Lógica y Presentación.**
    - **Acción:** Crear un directorio `src/` para la lógica de negocio.
    - **Acción:** Extraer cálculos complejos y lógica de negocio de los archivos principales a funciones y clases dentro de `src/`.
    - **Acción:** Convertir las vistas (HTML) en plantillas que reciben datos del controlador, eliminando la lógica de ellas.
    - **Resultado Clave:** Código más organizado, reutilizable y fácil de probar.

### Mes 3: Abstracción de Datos y Pruebas
- **Semana 9-10: Implementación del Patrón Repositorio.**
    - **Acción:** Crear clases "Repositorio" (ej. `ProyectoRepository`, `UsuarioRepository`) que encapsulen toda la lógica de acceso a datos para cada entidad.
    - **Acción:** Refactorizar el código para que utilice estos repositorios en lugar de realizar consultas SQL directamente en los controladores.
    - **Resultado Clave:** Desacoplamiento de la lógica de negocio de la base de datos, facilitando el mantenimiento y las pruebas.
- **Semana 11-12: Creación de la Red de Seguridad (Pruebas).**
    - **Acción:** Instalar **PHPUnit** (vía Composer).
    - **Acción:** Escribir pruebas unitarias para la lógica de negocio extraída en `src/`.
    - **Acción:** Escribir pruebas de integración para los repositorios para asegurar que las consultas a la base de datos funcionan como se espera.
    - **Resultado Clave:** Inicio de una suite de pruebas automatizadas que aumenta la confianza para realizar cambios futuros.

### Fase Extra: Implementación de Auditoría de Acciones (Seguimiento Alfabético)
**Objetivo:** Integrar el registro automático de acciones (`logActivity`) en cada proceso que modifique datos en el sistema.

- [x] **Infraestructura:** Creación de tabla y método central en `Database.php`.
- [x] **Visualización:** Tablero de auditoría en Dashboard de Administración.
- [x] **Módulo: admin** (Gestión de usuarios, proyectos y miembros).
- [x] **Módulo: contratos**
- [ ] **Módulo: controlCambios**
- [ ] **Módulo: indicadores**
- [ ] **Módulo: informe_productividad**
- [ ] **Módulo: listadoActividades**
- [ ] **Módulo: login** (Registro de inicios y cierres de sesión).
- [ ] **Módulo: paquetesContratacion**
- [ ] **Módulo: pdc**
- [ ] **Módulo: profesionales**
- [ ] **Módulo: programa_general**
- [ ] **Módulo: programaGeneralActualizar**
- [ ] **Módulo: programacion_intermedia**
- [ ] **Módulo: programacion_semanal**
- [ ] **Módulo: registrate**
- [ ] **Módulo: subcontratistas**

---
Mensaje de Commit Sugerido: feat: Add audit logging infrastructure and checklist to roadmap.