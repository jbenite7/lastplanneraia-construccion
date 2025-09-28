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

## 6. Roadmap de Implementación (3 Meses)

Esta fase se centra en **estabilizar la plataforma, eliminar la deuda técnica crítica y sentar las bases para la modernización futura.**

### Mes 1: Mitigación de Riesgos y Unificación del Código
- **Semana 1-2: ERRADICAR VULNERABILIDADES DE INYECCIÓN SQL (Prioridad Máxima).**
    - **Acción:** Refactorizar `conexion.php` para utilizar **PDO** en lugar de `mysqli`. - **(HECHO)**
    - **Acción:** Crear una clase `Database` que centralice la conexión y la ejecución de consultas, forzando el uso de **consultas preparadas**. - **(HECHO)**
    - **Acción:** Auditar y refactorizar **TODAS** las consultas SQL del proyecto para que utilicen el nuevo sistema de consultas preparadas. - **(EN PROGRESO)**
        - *Avance: Módulo `login` completado.*
        - *Avance: Script `generarReporteSubcontratistas.php` completado.*
    - **Resultado Clave:** Cierre de la brecha de seguridad más crítica.
- **Semana 3: Consolidación y Limpieza.**
    - **Acción:** Aplicar `php-cs-fixer` a toda la base de código para garantizar un estilo consistente (PSR-12).
    - **Acción:** Analizar las diferencias entre los directorios `pdc` y `pdc1`, fusionar la funcionalidad necesaria en `pdc` y **eliminar `pdc1`**. - **(HECHO)**
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

## Mensaje de Commit Sugerido

```
feat: Add initial project diagnosis and roadmap

This commit introduces a comprehensive set of documentation to establish a baseline for the project's evolution.

- Creates a detailed ROADMAP.md with a 3-month plan to address critical technical debt and lay the foundation for modernization.
- Updates README.md with clearer installation instructions and project structure.
- Initializes CHANGELOG.md to track future changes.
- Establishes a .gitignore file based on best practices for PHP projects.
```