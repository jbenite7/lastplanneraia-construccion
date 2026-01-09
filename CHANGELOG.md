# Registro de Cambios (Changelog)

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Sin publicar]

### Añadido
- **Sistema de Auditoría de Acciones:**
  - Creación de la tabla `general_auditoria_acciones` para el registro persistente de actividad de usuarios.
  - Implementación del método `Database::logActivity()` para centralizar el registro de acciones desde cualquier módulo.
  - Rediseño del Dashboard administrativo para incluir una tabla de Auditoría de Acciones separada de los logs de error del sistema.
  - Soporte para detección automática de usuario e IP en registros de auditoría.

### Cambiado
- **Refactorización de Scripts de Reportes y Soporte:**
  - Migración de scripts legados en `construccion/` para utilizar el Singleton `Database` y sentencias preparadas (PDO).
  - Mejora de la seguridad contra inyección SQL en la generación masiva de reportes y actualización de ejecución semanal.
  - Optimización de los scripts de generación de Curvas S, Curvas S PDC y Reportes Consolidados.
  - Implementación de logging de actividad (`logActivity`) en procesos críticos de generación de datos.

### Añadido
- **Tablero de Salud del Sistema:**
  - **Monitoreo en Tiempo Real:** Integración de escaneo del log de errores de PHP con conteo diario automático y flujo de actividad que cubre todo el día actual.
  - **Herramientas de Integridad de Datos:** Verificación automatizada de las tablas estructurales requeridas para cada proyecto, incluyendo una vista detallada de tablas faltantes a un solo clic.
  - **Limpieza de Base de Datos:** Implementación de un detector de "Tablas Huérfanas" para identificar y eliminar tablas sobrantes de proyectos borrados, con protección explícita para las tablas globales `general_`.
  - **Respaldos Completos:** Añadida funcionalidad para generar y almacenar instantáneas SQL completas de la base de datos mediante streaming directo a disco para prevenir agotamiento de memoria.
  - **Información del Entorno del Servidor:** Añadida visibilidad de límites críticos de PHP (`upload_max_filesize`, `memory_limit`, etc.) para facilitar el soporte técnico.
- **Mejoras de UI/UX:**
  - **Filtrado de Eventos:** Añadida capacidad para filtrar el feed de actividad por tipo (Errores, Rutas, Otros) mediante botones dinámicos en el encabezado.
  - **Tooltips Interactivos:** Estandarización de iconos de información (`i`) en todas las métricas con explicaciones detalladas de conceptos y lógica de medición.
  - **Seguimiento de Proyectos Activos:** Mejora del recuadro de proyectos para mostrar el conteo `Activos / Totales` con una lista desplegable de los proyectos actuales en formato de viñetas.
  - **Modales Integrados:** Uso de **SweetAlert2** con soporte de fondo (backdrop) para todas las confirmaciones de limpieza, detalles de integridad y notificaciones de progreso.
- **Mejoras en Gestión de Usuarios:**
  - Sugerencia automática de nombre de usuario basada en nombre o prefijo de email con detección de duplicados.
  - Prevención de duplicados para nombres completos y correos electrónicos durante la creación de usuarios.
  - Integración de **Select2** para cargos con búsqueda AJAX y soporte para etiquetas.
  - Generador de contraseñas y alternancia de visibilidad en el formulario de creación de usuarios.
- **Optimizaciones de Interfaz:**
  - **Encabezados Fijos (Sticky Headers):** Implementados en las listas de Proyectos y Usuarios.
  - **Optimización de Espacio Horizontal:** Aplicación de estilos compactos (`table-sm`), fuentes más pequeñas y reglas de `text-break` para eliminar el desplazamiento horizontal.
  - **Corrección de DataTables:** Resuelto el error "table.buttons is not a function" incluyendo correctamente los activos de la extensión Buttons.
  - **Resolución de CORS:** Traducción al español de DataTables integrada directamente para evitar problemas de peticiones de origen cruzado en entornos locales.

### Corregido
- **Fallo de Memoria en Respaldos:** Corregido error 500 al realizar respaldos completos mediante la implementación de escritura por flujo (streaming) en disco.
- **Advertencias de SweetAlert2:** Resuelta la advertencia de consola relacionada con el parámetro `allowOutsideClick` asegurando el uso de `backdrop: true`.
- **Sensibilidad de Búsqueda en Logs:** Unificados criterios de escaneo de errores (insensible a mayúsculas) para que el contador diario coincida exactamente con el feed de eventos.
- **Estabilidad del PDC (Plan de Compras):**
  - Refactorizado `actualizar_pdc_nueva_semana.php` para erradicar errores de sintaxis en consultas SQL dinámicas mediante el uso de la sintaxis `HEREDOC`.
  - Implementación de `IFNULL` en cálculos de `DATE_SUB` de MySQL para prevenir errores de intervalo cuando las duraciones de contratación no están definidas.
  - Corrección de desajuste en el conteo de placeholders de consultas preparadas PDO.
  - Estandarización de limpieza de strings dinámicos para evitar conflictos de comillas simples/dobles en concatenaciones de SQL.

### Eliminado
- **Limpieza de Archivos Redundantes:** 
  - Eliminados múltiples archivos de prueba y respaldo en `/construccion`, incluyendo `prueba.php`, `listar_programacion_semanal1.php`, `pruebaSpreadsheets.php`, `charts1.php` y el directorio `prueba_correos/`.
  - **Nueva Limpieza:** Eliminados scripts de gestión legados reemplazados por el MVC administrativo: `Cargar_Nuevos_Proyectos.php`, `eliminar_proyectos.php` y `Copia_de_Seguridad_LPS.php`.
  - **Redundancia:** Eliminadas redirecciones raíz `cerrar.php`, `login/login.php` (y su directorio) y `check_schema.php`.
  - **Mantenimiento:** Eliminados `admin/test_models.php`, `admin/test_create_project.php` y la vista duplicada `construccion/pdc/pdc.view.nuevaBarra.php`.

### Cambiado
- **Refinamiento de Permisos:** Simplificación de la visualización de permisos globales en la lista de usuarios para mostrar etiquetas de "Administrador" o "Usuario" basadas en códigos globales (A/U).
- **Limpieza:** Eliminada la columna redundante "Proyecto" de la lista global de usuarios para enfocar los datos en el perfil del usuario.

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

### Añadido
- **Módulo de Proyectos:**
  - Implementación inicial de `ProjectController`.
  - Vista de lista de proyectos avanzada con DataTables (búsqueda, exportación, paginación).
  - Enrutamiento limpio para la gestión de proyectos (/admin/proyectos).
- **Navegación y Compatibilidad:**
  - Estandarización de todos los enlaces administrativos para usar URLs amigables.
  - Implementación de `admin/index.php` como puente para prevenir el listado de directorios y mejorar el manejo del punto de entrada.
  - Endurecimiento de la seguridad de `admin/.htaccess`.

### Corregido
- Corregido el problema de 403 Prohibido/Listado de Directorios al acceder a `/admin/`.
- Corregidos los enlaces de la barra lateral que apuntaban a archivos .php inexistentes.

### Añadido
- **Seguridad y Compatibilidad:**
  - **Autenticación Híbrida de Contraseñas:** Actualizado `construccion/login/login.php` para soportar tanto hashes SHA-512 legados como BCRYPT moderno (`password_verify`). Esto asegura que los nuevos usuarios creados en el Panel de Administración puedan acceder al sistema legado.
  - **Auditoría de Integridad de Datos:** Verificado el uso de SHA-512 en `general_usuarios` y documentada la ruta de migración para la seguridad de contraseñas.
- **Infraestructura del Panel de Administración:**
  - Front Controller implementado en `admin/public/index.php`.
  - Reescritura de URLs configurada en `admin/public/.htaccess` para rutas limpias.
  - Core Router implementado en `admin/src/Core/Router.php`.
  - Endurecimiento de seguridad para rutas administrativas y sesiones.
  - Controlador Base y Modelo de Usuario implementados en `admin/src/`.
- **Frontend y UI:**
  - Integrado AdminLTE 3 (CDN) para el tablero administrativo.
  - Sistema de diseño modular implementado en `admin/views/layouts/main.php`.
  - Sistema dinámico de migas de pan para páginas administrativas.
- **Capa de Base de Datos:**
  - Implementado patrón Singleton en la clase `Database` para una gestión eficiente de conexiones.
  - Refactorizado el legado `construccion/conexion.php` para usar el Singleton `Database::getInstance()`, unificando la lógica de conexión.
  - Carga automática de variables de entorno para credenciales de base de datos vía `phpdotenv`.

### Cambiado
- **Arquitectura:** Transición de la gestión de conexiones procedural a un patrón Singleton centralizado.
- Actualizado `.gitignore` para incluir logs administrativos y proteger datos sensibles.

### Corregido
- Corregido el error 500 Internal Server Error en el panel administrativo inicializando correctamente la instancia de Database.
- Corregida la resolución de rutas para la carga de `.env` en scripts administrativos.