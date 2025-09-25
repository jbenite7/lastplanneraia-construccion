# Diagnóstico y Roadmap de Implementación - Last Planner AIA (Análisis Detallado)

**Nota Importante:** Este análisis y roadmap se basan en una revisión exhaustiva del código fuente y están diseñados para ser implementados en un **hosting compartido (SiteGround)**.

## 1. Propósito Central del Proyecto

El proyecto es una aplicación web de gestión para la industria de la construcción, enfocada en la metodología *Last Planner System (LPS)*. Su propósito es digitalizar y centralizar la planificación, el seguimiento de compromisos y la generación de reportes.

## 2. Nivel de Madurez

**Clasificación: Maduro con Deuda Técnica Crítica.**

- **Maduro:** La aplicación es funcional y extensa, demostrando un profundo conocimiento del dominio de negocio.
- **Deuda Técnica Crítica:** El análisis revela problemas estructurales y de seguridad que representan un riesgo significativo y dificultan la evolución del software.

## 3. Stack Tecnológico

- **Backend:** PHP "Vanilla" (procedural), utilizando la extensión `mysqli`.
- **Frontend:** HTML, CSS, JavaScript (Vanilla).

## 4. Evidencia Concreta del Análisis Técnico

1.  **Vulnerabilidad Sistémica de Inyección SQL:** Se encontraron **115 coincidencias** de `$_GET` donde, en la mayoría de los casos, se asigna `$_GET['db']` a una variable `$db` que luego se concatena directamente en las consultas SQL.
2.  **Manejo Descentralizado de la Conexión:** Se encontraron **120 instancias** de `require("../conexion.php")`, demostrando que cada script gestiona su propia conexión.
3.  **Duplicación de Código:** La comparación de los directorios `pdc` y `pdc1` confirma que son funcionalmente idénticos.
4.  **Mezcla de Lógica y Presentación:** Se encontraron **8 archivos PHP** fuera de los directorios `views` que contienen la etiqueta `<body>`.

## 5. Visión Estratégica: Hacia una Aplicación Moderna y Automatizada

El objetivo a largo plazo es transformar la aplicación para que sea más intuitiva, fácil de usar y proactiva, reduciendo la carga de trabajo manual de los usuarios. Esto se logrará mediante un cambio de paradigma arquitectónico:

- **De:** Una aplicación monolítica donde cada script PHP es una página.
- **A:** Una **API de Backend desacoplada** que solo gestiona la lógica de negocio y los datos, y un **Frontend Moderno (Single Page Application - SPA)** que se encarga de toda la experiencia de usuario.

### Objetivos Clave de Automatización para el Usuario:
- **Acciones en Lote (Bulk Actions):** Permitir a los usuarios actualizar el estado o asignar responsables a múltiples actividades a la vez, eliminando tareas repetitivas.
- **Importación Inteligente de Datos:** Facilitar la carga masiva de listados de actividades desde archivos Excel, con validación y vista previa.
- **Cálculos y Proyecciones en Tiempo Real:** Actualizar dashboards (como la Curva S) instantáneamente y proveer alertas proactivas sobre posibles retrasos.
- **Notificaciones Automáticas:** Informar a los usuarios por correo o dentro de la app sobre eventos relevantes (ej. una restricción liberada).

## 6. Roadmap de Implementación (Fase 1: Fundación - 3 Meses)

Esta fase es **crítica e indispensable**. Se enfoca en resolver la deuda técnica para crear una base estable y segura sobre la cual se pueda construir la aplicación moderna.

### Mes 1: Mitigación de Riesgos y Estabilización
- **Semana 1-2: ERRADICAR INYECCIÓN SQL (Prioridad Máxima).**
    - **Acción:** Modificar `conexion.php` para usar PDO. Crear una clase `Database` que centralice la ejecución de consultas preparadas.
    - **Acción:** Auditar los **115+ puntos identificados** y refactorizar **TODAS** las consultas para usar exclusivamente consultas preparadas con PDO. Validar `$db` contra una lista blanca.
    - **Victoria:** Cierre de la vulnerabilidad de seguridad más crítica del sistema.
- **Semana 3: Unificación de Código y Limpieza.**
    - **Acción:** Configurar `PHP-CS-Fixer` con PSR-12 y formatear toda la base de código localmente.
    - **Acción:** Fusionar cualquier diferencia necesaria de `pdc1` en `pdc` y **eliminar el directorio `pdc1`**.
    - **Victoria:** Código unificado y reducción de la superficie de mantenimiento.
- **Semana 4: Detección Temprana de Errores.**
    - **Acción:** Instalar `PHPStan` en nivel 1 y corregir los errores más sencillos localmente.
    - **Victoria:** Se establece una primera línea de defensa contra bugs comunes.

### Mes 2: Refactorización Estructural
- **Semana 5-6: Centralización del Punto de Entrada.**
    - **Acción:** Implementar el patrón Front Controller con `.htaccess` y `nikic/fast-route`.
    - **Acción:** Migrar los 3 módulos más críticos (ej. `programa_general`, `programacion_semanal`, `login`) al nuevo enrutador.
    - **Victoria:** Se establece la base para una API RESTful. Se elimina la necesidad de `session_start()` y `require("conexion.php")` en cada archivo, centralizando la lógica.
- **Semana 7-8: Abstracción de Lógica de Negocio.**
    - **Acción:** Extraer los cálculos complejos (ej. `CASE` en `guardar_programa_general.php`) a funciones puras en un nuevo directorio `src/`.
    - **Victoria:** La lógica de negocio crítica se vuelve legible, reutilizable y, fundamentalmente, **testeable**.

### Mes 3: Capa de Acceso a Datos y Pruebas
- **Semana 9-10: Implementación del Patrón Repositorio.**
    - **Acción:** Crear una clase `SubcontratistaRepository` que encapsule todas las consultas a la base de datos para ese módulo.
    - **Victoria:** Se crea un plano para una capa de acceso a datos limpia y fácil de mantener, un paso clave para la futura API.
- **Semana 11-12: Creación de la Red de Seguridad.**
    - **Acción:** Instalar **PHPUnit** localmente. Escribir pruebas unitarias para la lógica de negocio extraída y pruebas de integración para el `SubcontratistaRepository`.
    - **Victoria:** Se crea una red de seguridad automatizada que permite realizar cambios futuros con confianza.

---

## 7. Roadmap de Implementación (Fase 2: Modernización y Automatización - Post 3 Meses)

Con la base estabilizada, comienza la transformación visible para el usuario.

### Mes 4-5: Construcción del Primer Módulo Moderno (Prueba de Concepto)
- **Tecnologías:** **Vue.js** como framework de frontend y **Vuetify** o **BootstrapVue** como librería de componentes de UI.
- **Acción:** Elegir un módulo de bajo riesgo pero de uso frecuente (ej. "Subcontratistas" o "Profesionales").
- **Acción:** Construir los endpoints de la API RESTful en el backend para el CRUD de ese módulo.
- **Acción:** Desarrollar una pequeña Single Page Application (SPA) con Vue.js que consuma la nueva API para gestionar el módulo de forma interactiva.
- **Acción:** Reemplazar el enlace del menú antiguo para que apunte a la nueva interfaz (Patrón Strangler Fig).
- **Victoria:** Se valida la nueva arquitectura y se entrega la primera mejora tangible de UX a los usuarios.

### Mes 6+: Expansión y Automatización Avanzada
- **Acción:** Migrar incrementalmente los módulos más complejos (`Programa General`, `Programación Semanal`) a la nueva arquitectura de API + SPA.
- **Acción:** Con la nueva interfaz, implementar las funcionalidades de **automatización clave**:
    - Introducir **acciones en lote** en las tablas principales.
    - Desarrollar el flujo de **importación inteligente desde Excel**.
- **Acción:** Configurar un sistema de tareas programadas (cron job) en el servidor para las **notificaciones y alertas automáticas**.
- **Acción (Opcional):** Configurar un flujo de CI/CD con **GitHub Actions** para automatizar las pruebas y los despliegues a SiteGround.
- **Victoria:** La aplicación se ha transformado en una herramienta moderna, intuitiva y proactiva que ahorra tiempo y reduce errores para el usuario final.