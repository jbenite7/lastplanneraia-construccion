# Diagnóstico y Roadmap de Implementación - Last Planner AIA

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

## 6. Roadmap de Implementación (4 Meses y 2 Semanas)

Esta fase inicial se centra en construir una base administrativa sólida y segura ("Greenfield") para luego integrar y refactorizar la lógica de negocio existente.

### Fase 1: Panel de Administración de Alta Eficiencia (Semanas 1-2)
**Objetivo:** Implementar un panel administrativo robusto, seguro y escalable utilizando arquitectura LAMP optimizada ("No-Framework"), basado en *AdminLTE 3* y *DataTables Server-Side*.

#### Semana 1: Arquitectura Core, Base de Datos y Seguridad
*   **Día 1: Infraestructura y Estructura de Directorios**
    - [x] **Estructura Segura:** Implementar separación física entre lógica e interfaz pública en la carpeta `admin/`.
    - [x] **Front Controller:** Configurar `.htaccess` para redirigir todo el tráfico a `admin/public/index.php` y bloquear acceso directo a carpetas sensibles. **(Verificado en MAMP)**
    - [x] **Sistema de Logs:** Implementar logs de error en `admin/logs/php_error.log`.
    - [x] **Git:** Configurar `.gitignore` robusto.
*   **Día 2: Capa de Datos y Modelado (MySQL + PDO)**
    - [x] **Conexión Singleton:** Implementar clase `Database` con patrón Singleton y soporte para puerto MAMP (8889).
    - [x] **Configuración PDO Segura:** Desactivar `ATTR_EMULATE_PREPARES`.
    - [x] **Integración Esquema Existente:** Mapear modelos a tablas existentes `general_usuarios` (usuarios, roles, permisos) y `general_proyectos_procesos` (proyectos).
    - [x] **Auditoría de Datos:** Confirmado uso de SHA-512 (128 chars) sin salt dinámico. Implementado puente de compatibilidad híbrida para migración progresiva a `password_hash()`.
*   **Día 3: Núcleo del Framework Artesanal (Micro-MVC)**
    - [x] **Router:** Implementar despachador de rutas simple basado en URL amigables (ej: `/usuarios/editar/15`). - **(HECHO)**
    - [x] **Controller Base:** Crear clase abstracta para renderizado de vistas y respuestas JSON estandarizadas. - **(HECHO)**
    - [x] **Session Hardening:** Configurar `HttpOnly`, `Secure`, `Strict Mode` y regeneración de ID de sesión al login.
    - [x] **Seguridad CSRF:** Implementar clase `CsrfToken` para generación y validación de tokens en formularios POST/PUT/DELETE.
*   **Día 4: Integración de Frontend (AdminLTE 3)**
    - [x] **Assets:** Integrar AdminLTE 3 (vía CDN) y dependencias (Bootstrap 4.6 + jQuery). - **(HECHO)**
    - [x] **Layout Modular:** Separar componentes: `Navbar`, `Sidebar`, `Footer` y `Content Wrapper` en `admin/views/layouts/main.php`. - **(HECHO)**
    - [x] **Breadcrumbs:** Implementar sistema dinámico de migas de pan pasado desde el controlador. - **(HECHO)**
    - [ ] **Optimización de Assets:** Implementar inyección de scripts por vista (cargar JS pesado solo donde se use).
*   **Día 5: Autenticación y Control de Acceso**
    - [x] **Login:** Formulario de acceso con validación `password_verify` y regeneración de sesión.
    - [x] **Middleware Auth:** Proteger rutas administrativas verificando sesión activa.
    - [x] **Lógica RBAC:** Implementar clase/método `User::can($permission)` para control granular en Vistas y Controladores.

#### Semana 2: Módulos Funcionales y Experiencia de Usuario (UX)
*   **Día 6-7: Gestión de Usuarios (Escalabilidad Total)**
    - [x] **Controlador:** Implementar `UserController` (index, create, store, edit, update, delete).
    - [x] **DataTables Server-Side:** Implementar lógica backend para paginación, filtrado y ordenamiento SQL dinámico (AJAX). (Implementado Client-side para agilidad inicial, escalable a Server-side).
    - [x] **Vista Index:** Integrar DataTables configurado para carga AJAX y renderizado de columnas.
    - [x] **Acciones:** Generar botones de acción (Editar/Eliminar) dinámicamente.
*   **Día 8: Gestión de Proyectos y UX Avanzada**
    - [x] **Controlador:** Implementar `ProjectController`. (CRUD Completo implementado)
    - [x] **Formularios Extendidos:** Implementados campos de área, fechas de línea base, costos y URLs.
    - [x] **Slugify Automático:** Generación inteligente de nombres de BD con números romanos y stop-words.
    - [x] **Toggle Switches:** Implementar cambio de estado (Activo/Acceso/PDC) con peticiones AJAX inmediatas.
    - [x] **Feedback:** Integrar *Toastr* para notificaciones de éxito/error asíncronas.
    - [x] **Integridad de Datos:** Implementar creación automática y renombrado atómico de tablas de proyecto (10 tablas por proyecto).
    - [x] **Validación:** Implementar saneamiento (`filter_var`) y validación estricta de entradas en el backend.
*   **Día 9: Asignación de Recursos (Relaciones N:M)**
    - [ ] **Select2 AJAX:** Integrar *Select2* para búsqueda remota de usuarios (evitar cargar lista completa en DOM).
    - [ ] **Lógica de Asignación:** Gestionar guardado de `project_members` utilizando transacciones de base de datos para integridad.
*   **Día 10: Pulido, Despliegue y Entrega**
    - [ ] **Auditoría Final:** Verificar permisos de archivos y configuración de entorno (`display_errors = Off`).
    - [ ] **Backups:** Implementar script PHP para backup de BD (mysqldump) disparado por Cron.
    - [ ] **Smoke Test:** Validación manual completa de flujos críticos (Login -> ABM Usuarios -> ABM Proyectos -> Logout).

### Fase 2: Mitigación de Riesgos y Unificación del Código (Mes 1)
- **Semana 1-2: ERRADICAR VULNERABILIDADES DE INYECCIÓN SQL (Prioridad Máxima).**
    - **Acción:** Refactorizar `conexion.php` para utilizar **PDO** en lugar de `mysqli`. - **(HECHO)**
    - **Acción:** Crear una clase `Database` que centralice la conexión y la ejecución de consultas, forzando el uso de **consultas preparadas**. - **(HECHO)**
    - **Acción:** Auditar y refactorizar **TODAS** las consultas SQL del proyecto para que utilicen el nuevo sistema de consultas preparadas. - **(EN PROGRESO)**
        
        #### Scripts Raíz
        - [ ] `construccion/Cargar_Nuevos_Proyectos.php`
        - [ ] `construccion/Copia_de_Seguridad_LPS.php`
        - [ ] `construccion/eliminar_proyectos.php`
        - [ ] `construccion/generarCurvaS.php`
        - [ ] `construccion/generarCurvaSB.php`
        - [ ] `construccion/generarCurvaSPDC.php`
        - [ ] `construccion/generarListadoRestriccionesGeneral.php`
        - [ ] `construccion/generarReporteGeneral.php`
        - [ ] `construccion/generarReportePDC.php`
        - [ ] `construccion/generarTablaHTMLProgramacionSemanal.php`

        #### Módulo: Funciones Generales
        - [ ] `construccion/funciones_generales/php/actualizarEjecucion.php`
        - [ ] `construccion/funciones_generales/php/actualizar_pdc_nueva_semana.php`
        - [ ] `construccion/funciones_generales/php/autoprogramar_actividades.php`
        - [ ] `construccion/funciones_generales/php/datosGeneralesPagina.php`
        - [ ] `construccion/funciones_generales/php/eliminar_semana.php`
        - [ ] `construccion/funciones_generales/php/modificar_sem_estado.php`
        - [ ] `construccion/funciones_generales/php/modificar_sem_estado_actualizar.php`
        - [ ] `construccion/funciones_generales/php/nueva_semana.php`
        - [ ] `construccion/funciones_generales/php/nueva_semana1.php`
        - [ ] `construccion/funciones_generales/php/verificarCICActualizada.php`

        #### Módulo: Indicadores
        - [ ] `construccion/indicadores/listar_detalles_indicadores.php`
        - [ ] `construccion/indicadores/listar_indicadores.php` (Refactorización parcial pendiente)

        #### Módulo: Informe Productividad
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

        #### Módulo: Listado Actividades
        - [ ] `construccion/listadoActividades/guardar_listadoActividades.php`
        - [ ] `construccion/listadoActividades/listar_listadoActividades.php`
        - [ ] `construccion/listadoActividades/views/listadoActividades.view.nuevaBarra.php`

        #### Módulo: Paquetes Contratación
        - [ ] `construccion/paquetesContratacion/guardar_paquetesContratacion.php`
        - [ ] `construccion/paquetesContratacion/listar_paquetesContratacion.php`
        - [ ] `construccion/paquetesContratacion/views/paquetesContratacion.view.php`

        #### Módulo: PDC
        - [ ] `construccion/pdc/actualizar_pdc.php`
        - [ ] `construccion/pdc/guardar_pdc.php`
        - [ ] `construccion/pdc/listar_pdc.php`
        - [ ] `construccion/pdc/prueba.php`

        #### Módulo: Profesionales
        - [ ] `construccion/profesionales/guardar_profesionales.php`
        - [ ] `construccion/profesionales/listar_profesionales.php`

        #### Módulo: Programa General Actualizar
        - [ ] `construccion/programaGeneralActualizar/actualizarFiltros.php`
        - [ ] `construccion/programaGeneralActualizar/descargarCorteProgramacion.php`
        - [ ] `construccion/programaGeneralActualizar/guardar_programaGeneralActualizar.php`
        - [ ] `construccion/programaGeneralActualizar/listar_programaGeneralActualizar.php`
        - [ ] `construccion/programaGeneralActualizar/views/programaGeneralActualizar.view.nuevaBarra.php`

        #### Módulo: Programa General
        - [ ] `construccion/programa_general/actualizarFiltros.php`
        - [ ] `construccion/programa_general/descargarCorteProgramacion.php`
        - [ ] `construccion/programa_general/pruebaSpreadsheets.php`
        - [ ] `construccion/programa_general/views/programa_general.view.nuevaBarra.php`

        #### Módulo: Programación Intermedia
        - [ ] `construccion/programacion_intermedia/actualizarFiltros.php`
        - [ ] `construccion/programacion_intermedia/descargarRestricciones.php`
        - [ ] `construccion/programacion_intermedia/generarListadoRestriccionesGeneral.php`
        - [ ] `construccion/programacion_intermedia/guardar_programacion_intermedia.php`
        - [ ] `construccion/programacion_intermedia/listar_programacion_intermedia.php`
        - [ ] `construccion/programacion_intermedia/prueba.php`
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
        - [ ] `construccion/programacion_semanal/listar_programacion_semanal1.php`
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
    - **Resultado Clave:** Centralización de la gestión de peticiones, sesiones y conexión a la BD. Base para una futura API REST.
- **Semana 7-8: Separación de Lógica y Presentación.**
    - **Acción:** Crear un directorio `src/` para la lógica de negocio.
    - **Acción:** Extraer cálculos complejos y lógica de negocio de los archivos principales a funciones y clases dentro de `src/`.
    - **Acción:** Convertir las vistas (HTML) en plantillas que reciben datos del controlador, eliminando la lógica de ellas.
    - **Resultado Clave:** Código más organizado, reutilizable y testeable.

### Mes 3: Abstracción de Datos y Pruebas
- **Semana 9-10: Implementación del Patrón Repositorio.**
    - **Acción:** Crear clases "Repositorio" (ej. `ProyectoRepository`, `UsuarioRepository`) que encapsulen toda la lógica de acceso a datos para cada entidad.
    - **Acción:** Refactorizar el código para que utilice estos repositorios en lugar de realizar consultas SQL directamente en los controladores.
    - **Resultado Clave:** Desacoplamiento de la lógica de negocio de la base de datos, facilitando el mantenimiento y las pruebas.
- **Semana 11-12: Creación de la Red de Seguridad (Testing).**
    - **Acción:** Instalar **PHPUnit** (vía Composer).
    - **Acción:** Escribir pruebas unitarias para la lógica de negocio extraída en `src/`.
    - **Acción:** Escribir pruebas de integración para los Repositorios para asegurar que las consultas a la BD funcionan como se espera.
    - **Resultado Clave:** Inicio de una suite de pruebas automatizadas que aumenta la confianza para realizar cambios futuros.

### Mes 4: Panel de Administración y Gestión de Accesos
- **Semana 13-14: Gestión de Entidades Core (CRUD).**
    - **Acción:** Desarrollar el módulo de **Gestión de Proyectos**: interfaz para crear, editar, bloquear (activar/desactivar) y eliminar proyectos.
    - **Acción:** Desarrollar el módulo de **Gestión de Usuarios**: interfaz para el alta, baja y modificación de datos de usuarios.
    - **Resultado Clave:** Control administrativo centralizado sobre los datos maestros de la aplicación.
- **Semana 15-16: Sistema de Permisos y Seguridad Avanzada.**
    - **Acción:** Implementar un **Control de Acceso Basado en Roles (RBAC)**: definir permisos específicos por tipo de usuario (Admin, Residente, Consulta).
    - **Acción:** Integrar la validación de permisos en el Front Controller para proteger las rutas y acciones del sistema.
    - **Acción:** Crear una interfaz visual para la asignación dinámica de permisos y roles.
    - **Resultado Clave:** Sistema seguro y granular donde cada usuario accede solo a la información autorizada.

## Mensaje de Commit Sugerido

```
feat: Add initial project diagnosis and roadmap

This commit introduces a comprehensive set of documentation to establish a baseline for the project's evolution.

- Creates a detailed ROADMAP.md with a 3-month plan to address critical technical debt and lay the foundation for modernization.
- Updates README.md with clearer installation instructions and project structure.
- Initializes CHANGELOG.md to track future changes.
- Establishes a .gitignore file based on best practices for PHP projects.
```