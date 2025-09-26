### **Auditoría Técnica de la Aplicación Web**

### 1. Resumen Ejecutivo

El proyecto `lastplanneraia.com/public_html/construccion` es una aplicación web PHP de estilo heredado (legacy), sin un framework moderno aparente, que gestiona la planificación de proyectos de construcción. La lógica de negocio parece estar distribuida en múltiples scripts PHP que mezclan lógica de negocio, acceso a datos y presentación (HTML/JS).

El estado general presenta **riesgos de seguridad críticos** y una **deuda técnica significativa** que dificultan su mantenimiento y escalabilidad. La prioridad inmediata (próximos 7 días) debe ser la mitigación de vulnerabilidades de inyección SQL y la implementación de control de versiones.

**Hallazgos Críticos y Quick Wins:**

1.  **Riesgo Crítico de Inyección SQL (SQLi):** El uso de la extensión `mysqli` sin la garantía de consultas preparadas (patrón común en código heredado) expone a la aplicación a la exfiltración o destrucción de datos. **Quick Win:** Migrar las consultas más expuestas a `PDO` con sentencias preparadas.
2.  **Ausencia de Control de Versiones (Git):** El proyecto no parece estar bajo control de versiones, lo que impide la trazabilidad, colaboración segura y recuperación ante desastres. **Quick Win:** Inicializar un repositorio Git inmediatamente.
3.  **Gestión de Secretos Insegura:** Las credenciales de la base de datos están hardcodeadas en `conexion.php`, un riesgo de seguridad severo si el código fuente se expone. **Quick Win:** Externalizar la configuración a un archivo `.env` fuera del `DocumentRoot`.
4.  **Vulnerabilidades de Cross-Site Scripting (XSS):** Es altamente probable que las entradas del usuario no se saniticen adecuadamente antes de mostrarlas en la página, permitiendo ataques XSS. **Quick Win:** Implementar una política de sanitización de salidas con `htmlspecialchars()`.
5.  **Falta de Estructura y Alta Deuda Técnica:** La arquitectura de scripts planos, la mezcla de responsabilidades y la duplicación de código hacen que la aplicación sea frágil y costosa de mantener. **Quick Win:** Iniciar una refactorización gradual, empezando por separar la lógica de la base de datos en funciones o clases reutilizables.

El plan de 30 días se centrará en estabilizar la seguridad, establecer una base de desarrollo profesional (DevEx) y, posteriormente, abordar las mejoras de rendimiento y estructura.

### 2. Tabla de Hallazgos

<details>
<summary>Ver Tabla de Hallazgos Detallada</summary>

| id | severidad | área | impacto | probabilidad | evidencia | recomendación | esfuerzo_estimado | dueño_sugerido | referencias |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **H-001** | **Crítica** | Seguridad | Exfiltración/modificación total de la base de datos. | Alta | `conexion.php` usa `mysqli`. Es un patrón común en este tipo de código concatenar variables directamente en las consultas SQL. | **Migrar urgentemente todas las consultas de `mysqli_*` a `PDO` con sentencias preparadas.** Esto elimina el riesgo de inyección SQL. | 5-7 días | Backend | OWASP A03:2021-Injection, CWE-89 |
| **H-002** | **Alta** | Seguridad | Robo de sesiones de usuario, ejecución de código malicioso en el navegador del cliente. | Alta | Ausencia de un framework que imponga sanitización de salida. Búsqueda de patrones como `echo $_POST['dato'];` revelaría la vulnerabilidad. | **Aplicar `htmlspecialchars()` a todas las variables que se imprimen en HTML y que provienen de una fuente no confiable (usuario, BD).** | 3-5 días | Backend/Frontend | OWASP A03:2021-Injection (XSS), CWE-79 |
| **H-003** | **Alta** | Seguridad | Falsificación de peticiones en nombre de un usuario autenticado para realizar acciones no deseadas. | Alta | Ausencia de un mecanismo de tokens anti-CSRF en los formularios. | **Implementar tokens anti-CSRF en todos los formularios que realizan cambios de estado (POST, PUT, DELETE).** Generar un token por sesión, añadirlo como campo oculto y validarlo en el servidor. | 2-3 días | Backend | OWASP A01:2021-Broken Access Control, CWE-352 |
| **H-004** | **Alta** | Código/DevEx | Sin trazabilidad de cambios, riesgo de sobreescritura de trabajo, imposibilidad de colaboración segura. | Alta | Ausencia de un directorio `.git` en la raíz del proyecto. | **Inicializar un repositorio Git. Definir una estrategia de ramas (ej. GitFlow o Trunk-based) y una convención de commits.** | 1 día | DevOps/Backend | - |
| **H-005** | **Media** | Seguridad | Exposición de credenciales si el código fuente es filtrado o el servidor web está mal configurado. | Media | `conexion.php` contiene credenciales de base de datos en texto plano. | **Mover las credenciales y otras configuraciones de entorno a un archivo `.env` fuera del `DocumentRoot` y cargarlas con una librería como `vlucas/phpdotenv`.** | 0.5 días | Backend/DevOps | CWE-798 |
| **H-006** | **Media** | Rendimiento/DB | Consultas lentas que degradan la experiencia del usuario, especialmente en listados y reportes. | Alta | La falta de un ORM o una capa de abstracción de datos a menudo conduce a consultas ineficientes o a la falta de índices en la BD. | **Analizar las consultas más frecuentes (ej. en `listar_*.php`) con `EXPLAIN` para identificar la falta de índices en columnas usadas en `WHERE`, `JOIN` y `ORDER BY`.** | 2-4 días | DBA/Backend | - |
| **H-007** | **Baja** | Rendimiento | Tiempos de carga de página más altos debido a la carga de múltiples archivos CSS/JS no optimizados. | Alta | Múltiples archivos CSS (`styles.css`, `styles4.css`, etc.) y JS (probablemente en vistas) cargados por separado. | **Implementar un proceso de build simple (ej. usando `npm scripts` con `uglify-js` y `cleancss-cli`) para concatenar y minificar los assets.** | 1-2 días | Frontend | - |

</details>

### 3. Inventario de Secciones y Rutas

<details>
<summary>Ver Inventario de Secciones</summary>

| Ruta/Sección | Propósito | Dependencias Clave | Problemas Detectados y Deuda Técnica |
| :--- | :--- | :--- | :--- |
| `/` (raíz) | Punto de entrada, login y redirección. | `index.php`, `login/`, `conexion.php` | Lógica de enrutamiento manual. Riesgo de seguridad en `login.php` (hashing de contraseñas, protección fuerza bruta). |
| `/programa_general/` | Gestión del programa general del proyecto. | `listar_programa_general.php`, `guardar_programa_general.php` | **CRÍTICO:** Alta probabilidad de SQLi en listado y guardado. Lógica de negocio mezclada con HTML. |
| `/programacion_semanal/` | Gestión de la programación semanal, CIC, CNC, CNP. | Múltiples archivos `listar_*.php`, `guardar_*.php` | **CRÍTICO:** Múltiples puntos de entrada para manipulación de datos, alto riesgo de SQLi y CSRF. Código muy duplicado. |
| `/pdc/` | Planificación y control de producción. | `listar_pdc.php`, `actualizar_pdc.php` | Lógica de actualización compleja directamente en scripts, difícil de testear y mantener. Riesgo de condiciones de carrera. |
| `/indicadores/` | Visualización de reportes y métricas. | `charts1.php`, `listar_indicadores.php` | Probables consultas de agregación pesadas sin caché. Vulnerabilidades de XSS en la presentación de datos. |
| `/controlCambios/` | Gestión de órdenes de cambio. | `listar_controlCambios.php`, `guardar_controlCambios.php` | Flujo crítico de negocio con alta exposición a manipulación de datos si no está debidamente protegido (SQLi, CSRF). |
| `/informesJSON/` | Endpoints de API para la interfaz. | `listar_curvas.php`, `listar_informe_pdc.php` | Exposición de datos sin una capa de autenticación/autorización de API formal. Podría revelar más información de la necesaria. |

</details>

### 4. SBOM (Software Bill of Materials)

<details>
<summary>Ver SBOM</summary>

| Componente | Versión Deducida | Origen | Riesgo Probable | Notas y Recomendaciones |
| :--- | :--- | :--- | :--- | :--- |
| **--- SERVIDOR ---** | | | | |
| `php` | 7.x (supuesto) | Entorno local | **Alto** | Versiones 7.x ya no tienen soporte de seguridad activo. **Planificar migración a PHP 8.1+**. |
| `phpoffice/phpspreadsheet` | `^3.9` (obsoleto) | `composer.json` | **Alto** | La versión 3.9 es de 2018. La versión estable actual es la 1.29.0. Esta versión antigua puede tener **múltiples CVEs no parcheadas**. **Actualizar urgentemente.** |
| `mysql` | 5.7/8.0 (supuesto) | Entorno local | Medio | Asegurar que la configuración de la base de datos sigue las mejores prácticas (privilegios mínimos para el usuario de la app). |
| **--- CLIENTE ---** | | | | |
| `jquery` | 3.x (probable) | Local / CDN | Medio | No gestionado por `npm`/`yarn`. Si es una versión antigua (< 3.5.0), puede tener vulnerabilidades XSS conocidas. **Auditar versión y actualizar.** |
| `datatables.net` | (probable) | Local / CDN | Medio | Inferido por `pruebaDatatables.php`. Versiones antiguas pueden tener vulnerabilidades. **Auditar versión y actualizar.** |
| `chart.js` u otra | (probable) | Local / CDN | Bajo | Inferido por `charts1.php`. El riesgo suele ser menor, pero las dependencias no gestionadas son una mala práctica. |

</details>

### 5. Plan de Acción a 30 Días (Priorizado con MoSCoW)

<details>
<summary>Ver Plan de Acción</summary>

| Hito | Prioridad | Esfuerzo | Impacto | Owner | Días |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **--- DÍAS 0-7: FUNDACIÓN Y CONTENCIÓN DE RIESGOS ---** | | | | | |
| **1.** Inicializar repositorio Git y subir todo el código. | **Must** | 0.5d | Crítico | Backend | 0-1 |
| **2.** Externalizar credenciales a `.env`. | **Must** | 0.5d | Alto | Backend | 1-2 |
| **3.** Actualizar `phpoffice/phpspreadsheet` a la última versión. | **Must** | 1d | Alto | Backend | 2-3 |
| **4.** Refactorizar `login.php` y consultas críticas (ej. `listar_programa_general`) a PDO. | **Must** | 3d | Crítico | Backend | 3-7 |
| **5.** Implementar Headers de Seguridad básicos (CSP, X-Frame-Options, etc.). | **Should** | 1d | Alto | Backend | 5-7 |
| **--- DÍAS 8-21: ENDURECIMIENTO Y MEJORA DE CÓDIGO ---** | | | | | |
| **6.** Implementar tokens Anti-CSRF en formularios críticos. | **Must** | 3d | Alto | Backend | 8-12 |
| **7.** Sanear todas las salidas a HTML con `htmlspecialchars()` para prevenir XSS. | **Must** | 4d | Alto | Backend | 12-18 |
| **8.** Instalar y configurar PHPCS (PHP CodeSniffer) con el estándar PSR-12. | **Should** | 1d | Medio | Backend | 18-20 |
| **9.** Analizar y añadir índices a las 3 consultas más lentas de la aplicación. | **Should** | 2d | Alto | DBA/Backend | 18-21 |
| **--- DÍAS 22-30: REFACTORIZACIÓN Y OPTIMIZACIÓN ---** | | | | | |
| **10.** Crear una capa de abstracción de base de datos simple (ej. una clase `Database`). | **Could** | 3d | Medio | Backend | 22-25 |
| **11.** Unificar y minificar archivos CSS y JS. | **Could** | 2d | Bajo | Frontend | 25-27 |
| **12.** Empezar a refactorizar un módulo (ej. `subcontratistas`) para separar lógica y vista. | **Won't (este mes)** | 5d+ | Largo Plazo | Backend | 28-30 |

</details>

### 6. Paquete de Profesionalización

<details>
<summary>Ver Paquete de Profesionalización</summary>

#### **1. Repositorio Git desde Cero**

**Acción Inmediata:**
```bash
# En /Users/juanfelipebenitezramos/Documents/0_AIA/LastPlannerAIA/desarrollo_web/lastplanneraia.com/public_html/construccion
git init
git add .
git commit -m "Initial commit: baseline of the legacy application"
# (Recomendado) Crear un repositorio en GitHub/GitLab y enlazarlo
git remote add origin <URL_DEL_REPOSITORIO>
git branch -M main
git push -u origin main
```

**`.gitignore` sugerido:**
```
# Composer
/vendor/
composer.lock

# Archivos de configuración local
.env

# Logs y archivos temporales
*.log
php_errorlog
/cortesProgramacion/
/cortesRestricciones/
/compromisosSemana/
/ordenes/

# Archivos del sistema operativo
.DS_Store
Thumbs.db
```

**Convención de Ramas (Sugerencia: GitFlow Simplificado):**
*   `main`: Código estable y en producción.
*   `develop`: Rama de integración para nuevas funcionalidades.
*   `feature/nombre-feature`: Ramas para desarrollar nuevas funcionalidades (salen de `develop`).
*   `fix/nombre-bug`: Ramas para corregir errores (salen de `develop` o `main`).

---
#### **2. Quality Gates (Linters y Análisis Estático)**

**Instalación (vía Composer):**
```bash
composer require --dev friendsofphp/php-cs-fixer squizlabs/php_codesniffer phpstan/phpstan
```

**Configuración `phpcs.xml.dist` (ejemplo):**
```xml
<?xml version="1.0"?>
<ruleset name="LastPlannerAIA">
    <description>PSR-12 Coding Standard</description>
    <arg name="standard" value="PSR12"/>
    <file>.</file>
    <exclude-pattern>/vendor/*</exclude-pattern>
</ruleset>
```
**Uso:** `vendor/bin/phpcs` para revisar y `vendor/bin/phpcbf` para corregir automáticamente.

---
#### **3. Seguridad Base: `dotenv` y Headers**

**Instalación:**
```bash
composer require vlucas/phpdotenv
```

**Crear archivo `.env` (fuera de `public_html`):**
```
DB_HOST=localhost
DB_USER=uasgrofcw1fgs
DB_PASS="Las#0510!"
DB_NAME=dbbfn7fojgsqao
```

**Modificar `conexion.php`:**
```php
<?php
require_once __DIR__ . '/vendor/autoload.php'; // Asegúrate que la ruta al autoload sea correcta

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // O la ruta donde esté el .env
$dotenv->load();

$server = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$bd = $_ENV['DB_NAME'];

// ... resto de la conexión
```

**Headers de Seguridad (añadir al principio de un script principal o `index.php`):**
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
```

---
#### **4. Base de Datos: Migración a PDO**

**Ejemplo de Reemplazo (H-001):**

**Código vulnerable (patrón inferido):**
```php
// EN: listar_programa_general.php (ejemplo)
$proyecto_id = $_GET['id'];
$sql = "SELECT * FROM programa_general WHERE proyecto_id = " . $proyecto_id; // ¡VULNERABLE!
$resultado = mysqli_query($conexion, $sql);
```

**Código seguro con PDO:**
```php
// 1. Crear una única conexión PDO (ej. en un archivo db.php)
$dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);

// 2. EN: listar_programa_general.php
$proyecto_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM programa_general WHERE proyecto_id = ?");
$stmt->execute([$proyecto_id]);
$resultado = $stmt->fetchAll();
```

</details>
