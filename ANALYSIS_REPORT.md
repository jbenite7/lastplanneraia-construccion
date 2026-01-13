# Reporte de Análisis del Proyecto - Last Planner AIA

## 1. Resumen Ejecutivo
El proyecto se encuentra en una fase de transición ("Hybrid State"). Coexisten dos arquitecturas completamente diferentes:
1.  **Módulo Admin (`admin/`)**: Una arquitectura moderna, segura y estructurada (Micro-MVC, PDO, PSR-12).
2.  **Módulo Construcción (`construccion/`)**: Código legado ("Legacy"), procedural, inseguro y con alta deuda técnica.

El Roadmap indica que la Fase 1 (Admin) está prácticamente completa, y el foco actual debe ser la **Fase 2: Mitigación de Riesgos**, específicamente la erradicación de inyecciones SQL en el código legado.

## 2. Análisis Arquitectónico

### A. Módulo Moderno (`admin/`)
-   **Estructura**: Separación clara de responsabilidades (`public`, `src`, `views`).
-   **Patrón**: Front Controller con un Router básico.
-   **Base de Datos**: Usa una clase `Database` (Singleton) que envuelve PDO.
-   **Seguridad**:
    -   Consultas preparadas obligatorias.
    -   Protección CSRF implementada.
    -   Manejo seguro de sesiones.
-   **Estilo**: Cumple con PSR-12.

### B. Módulo Legado (`construccion/`)
-   **Estructura**: Plana/Espagueti. Archivos PHP mezclan lógica de negocio, acceso a datos y presentación HTML/JSON.
-   **Acceso a Datos**:
    -   Usa `mysqli` procedural.
    -   **Vulnerabilidad Crítica**: Construcción dinámica de consultas concatenando variables POST/GET sin sanitizar.
    -   Uso de nombres de tablas dinámicos basados en inputs del usuario (ej: `$db . "_tabla"`), lo cual es un riesgo mayor.
-   **Seguridad**:
    -   Sin protección CSRF aparente.
    -   Inyección SQL omnipresente.
    -   Validación de datos manual y dispersa (`str_replace`, etc.).

## 3. Hallazgos Críticos de Seguridad (Ejemplos)

### Archivo: `construccion/pdc/guardar_pdc.php`
Este archivo ejemplifica los problemas del código legado:
1.  **Inyección SQL Directa**:
    ```php
    // Línea 5: Recibe input sin validar
    $db = $_POST['db'];
    // ...
    // Línea 373: Concatenación directa en UPDATE
    $query = "UPDATE ".$db."_pdc SET ...";
    ```
    Un atacante puede manipular `$_POST['db']` para alterar otras tablas o inyectar comandos SQL.

2.  **Falta de Consultas Preparadas**:
    A pesar de usar `mysqli`, no se usan `prepare statements`. Se confía en la "limpieza" manual de variables, que es propensa a errores.

3.  **Lógica Compleja**:
    El archivo maneja múltiples opciones (`modificar`, `nueva_sem`, `eliminar`) en un solo script gigante (Switch case), dificultando la auditoría.

### Archivo: `construccion/indicadores/listar_indicadores.php`
-   Similar al anterior, utiliza `$_GET['db']` directamente en `SELECT COUNT(*) FROM $db"."_programacion_semanal`.
-   Mezcla lógica de presentación (echo JSON) con lógica de base de datos.

## 4. Estado de la Base de Datos
-   La aplicación depende de la creación dinámica de tablas por proyecto (prefijos). Esto complica el mantenimiento y la migración a un modelo más relacional y estándar.
-   La clase `Database` en `construccion/src/Database.php` es un buen paso adelante, pero la mayoría de los archivos en `construccion/` **NO la están usando** todavía, prefiriendo incluir `../conexion.php` y usar `mysqli` nativo.

## 5. Recomendaciones y Próximos Pasos (Prioridad Alta)

Siguiendo la "Fase 2" del Roadmap:

1.  **Refactorización Masiva de Seguridad**:
    -   Reemplazar `require("../conexion.php")` por `require_once __DIR__ . '/src/Database.php'; $db = Database::getInstance();` en los archivos legados.
    -   Reescribir las consultas SQL para usar `$db->query($sql, $params)` (PDO Prepared Statements).
    -   Eliminar la dependencia de `$db` (nombre de base de datos) como variable de entrada insegura, o validarla contra una lista blanca estricta (Whitelist).

2.  **Audit Logs**:
    -   Implementar `$db->logActivity(...)` en todas las operaciones de escritura (INSERT, UPDATE, DELETE) en `construccion/`.

3.  **Limpieza**:
    -   Continuar eliminando archivos muertos o redundantes mencionados en el Roadmap.

## 6. Conclusión
El proyecto tiene una base sólida en `admin`, pero `construccion` representa un riesgo de seguridad inaceptable en producción. La prioridad absoluta debe ser parchear las inyecciones SQL antes de añadir nuevas funcionalidades.
