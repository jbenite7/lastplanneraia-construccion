---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-03-09
fuente: docs/MIGRATION_FINDINGS.md
resumen: 10 módulos pendientes → 0 módulos pendientes - Tiempo estimado: 2-3 semanas (1-2 módulos/día considerando complejidad) - Reducción de código: ~120 líneas…
---

# Hallazgos y Patrones de Migración a Front Controller

> **Última actualización:** 2026-02-04  
> **Módulos migrados:** 5/15 (33%)  
> **Base:** PDO ya implementado ✅ | Router con FastRoute ✅ | Front Controller en `/public/index.php` ✅

Este documento consolida los hallazgos técnicos y patrones identificados durante la migración de módulos al patrón **Front Controller**. Debe utilizarse como guía definitiva para migrar los módulos restantes.

---

## Cronología de Migraciones

### Fase 1: Infraestructura Base

- **Commit:** `5fa13ea` - "feat(core): implement front controller infrastructure"
  - Creación de `/src/Core/Router.php` con FastRoute
  - Configuración de `/public/index.php` como punto de entrada único
  - Implementación de `.htaccess` con soporte legacy

### Fase 2: Módulos Críticos

- **Commit:** `64e5e73` - "feat(auth): migrate login module to front controller"
  - Primer controlador: `LoginController.php` (159 líneas)
  - Rutas: `/login` (GET/POST), `/logout`
  - Adaptación de vistas legacy con `PROJECT_ROOT`

- **Commit:** `831d147` - "feat(core): implement Front Controller and refactor login"
  - Refinamiento de infraestructura
  - Creación de `DashboardController.php`

- **Commit:** `555feda` - "feat(programa-general): migrate module to Front Controller"
  - `ProgramaGeneralController.php` (104 líneas)
  - Patrón de inyección de variables a vistas
  - Gestión de consultas SQL complejas en controlador

### Fase 3: Módulos de Programación

- **Commit:** `347a6bb` - "fix(prog-intermedia): fix table loading via front controller and clean ajax urls"
  - Corrección de rutas AJAX absolutas
  - Implementación de patrón de actualización de sesión por GET

- **Commit:** `641a018` - "feat(prog-semanal): migrate module to front controller architecture"
  - `ProgramacionSemanalController.php` (95 líneas)
  - **Patrón de submódulos:** CNP, CNC, CIC como métodos separados
  - Método privado `checkSession()` para reutilización

---

## 1. Patrón de Controlador (Controller Pattern)

Los scripts legacy calculaban variables (como `$semana`, `$permiso`, `$db`) en archivos incluidos dispersos. En el Front Controller, esto falla o es implícito.

### Solución Implementada

**Centralizar en el Controller:** El método `index()` (o métodos específicos) debe obtener y preparar TODAS las variables necesarias antes de cargar la vista.

```php
// Ejemplo: ProgramaGeneralController.php (líneas 17-102)
public function index()
{
    // 1. Validar sesión
    if (!isset($_SESSION['usuario'])) {
        header('Location: /login');
        exit;
    }

    // 2. Gestionar timeout (duplicado - pendiente mejora)
    $inactividad = 3600;
    if (isset($_SESSION["timeout"])) {
        $sessionTTL = time() - $_SESSION["timeout"];
        if ($sessionTTL > $inactividad) {
            session_destroy();
            echo "<script>alert('...');window.location.href='/login';</script>";
            exit;
        }
    }
    $_SESSION["timeout"] = time();

    // 3. Preparar variables para la vista
    $dbName = $_SESSION['db'] ?? '';
    $semana = (int)($_SESSION['semana'] ?? 0);
    $proyecto = $_SESSION['proyecto'] ?? '';

    // 4. Consultas SQL si es necesario
    $maxSemana = 0;
    $fechaInicioSem = '';
    // ... más variables ...

    try {
        $sqlUltima = "SELECT ... FROM {$dbName}_semanas_activas";
        $stmt = $this->db->query($sqlUltima);
        // ... procesamiento ...
    } catch (\PDOException $e) {
        error_log("Error: " . $e->getMessage());
    }

    // 5. Cargar vista con variables en scope
    require PROJECT_ROOT . '/construccion/programa_general/views/programa_general.view.nuevaBarra.php';
}
```

---

## 2. Inyección de Datos a la Vista (View Bridge)

El JavaScript legacy espera encontrar valores en inputs ocultos (a veces vacíos y llenados dinámicamente, lo cual falla en la carga inicial).

### Solución

**Inputs Ocultos con Sufijo `_PHP`:** Inyectar los valores calculados por el controlador en inputs `type="hidden"`.

```html
<!-- En la vista -->
<input type="hidden" id="semana_PHP" value="<?= $semana ?>" />
<input type="hidden" id="baseDatos_PHP" value="<?= $dbName ?>" />
<input type="hidden" id="proyecto_PHP" value="<?= $proyecto ?>" />
```

**IDs Únicos:** Usar sufijos (ej. `_PHP`) para evitar conflictos con inputs generados por JS antiguo.

---

## 3. Adaptación de JavaScript (JS Refactoring)

El JS original leía valores de inputs que a veces no existían o estaban vacíos al cargar vía Front Controller.

### Solución

**Priorizar IDs `_PHP`:** Modificar funciones clave (`listar()`, `cargaParametros()`, `recargarTabla()`) para buscar primero los inputs `_PHP`.

```javascript
// Patrón de lectura resiliente
var semana = $('#semana_PHP').val() || $('#semana').val();
var db = $('#baseDatos_PHP').val() || $('#baseDatos').val();
```

**Evitar Argumentos Hardcodeados:** En los modales, reemplazar llamadas con argumentos explícitos (`onclick="func(5, 'db')"`) por llamadas que lean del DOM (`onclick="func()"`), reduciendo fragilidad.

---

## 4. Rutas Absolutas (CRÍTICO)

El error más común (404 Not Found) se debe a rutas relativas (`../`) que dejan de funcionar cuando la URL base cambia.

### Problema

```text
❌ Antes: /construccion/modulo/file.php → paths: ../otro/script.php
✅ Ahora: /programa-general → paths: ../otro/script.php (404!)
```

### Solución

**Reemplazo Global:** Buscar `url: "../..."` en AJAX y reemplazar por rutas absolutas desde la raíz del servidor web.

```javascript
// ❌ MAL
url: '../programa_general/guardar.php';

// ✅ BIEN
url: '/construccion/programa_general/guardar.php';
```

**Hallazgo del Commit 347a6bb:** Durante la migración de Programación Intermedia se corrigieron 45 líneas cambiando rutas relativas a absolutas.

---

## 5. Scripts Standalone (AJAX Endpoints)

Los archivos PHP llamados directamente por AJAX (ej. `descargarCorte.php`, `guardar.php`) fallan con **Error 500** porque no pasan por el `index.php` principal y pierden el contexto (`vendor/autoload.php`, constantes como `PROJECT_ROOT`).

### Solución

**Bootstrap Manual:** Al inicio del script AJAX, definir `PROJECT_ROOT` y cargar el autoloader si es necesario.

```php
<?php
// Al inicio de scripts AJAX standalone
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/construccion/conexion.php';

session_start(); // La sesión debe iniciarse en scripts AJAX también
```

**Guardado de Archivos:** Usar `PROJECT_ROOT` para rutas de sistema de archivos (`$writer->save(...)`) y rutas absolutas web para URLs de descarga.

---

## 6. Patrón de Submódulos (Nuevo Hallazgo)

**Hallazgo del Commit 641a018:** Programación Semanal tiene 4 vistas relacionadas (principal + CNP, CNC, CIC).

### Solución Implementada

Crear **métodos públicos separados** para cada submódulo en el mismo controlador:

```php
class ProgramacionSemanalController
{
    private function checkSession() { /* ... */ }

    public function index() {
        $this->checkSession();
        require PROJECT_ROOT . '/construccion/programacion_semanal/views/programacion_semanal.view.nuevaBarra.php';
    }

    public function cnp() {
        $this->checkSession();
        require PROJECT_ROOT . '/construccion/programacion_semanal/views/CNP.view.nuevaBarra.php';
    }

    public function cnc() { /* similar */ }
    public function cic() { /* similar */ }
}
```

**Rutas:**

- `/programacion-semanal` → `index()`
- `/programacion-semanal/cnp` → `cnp()`
- `/programacion-semanal/cnc` → `cnc()`
- `/programacion-semanal/cic` → `cic()`

---

## Hallazgos Arquitecturales (Deuda Técnica Identificada)

### 🔴 Problema: Duplicación de Lógica de Sesión

**Cada controlador replica el mismo código:**

- Validación de `$_SESSION['usuario']`
- Gestión de timeout (3600 segundos)
- Regeneración de `$_SESSION["timeout"]`
- Actualización de semana por GET (`$_GET['semana']`)

**Evidencia:**

- `LoginController.php`: NO tiene validación (es el punto de entrada)
- `ProgramaGeneralController.php` (líneas 19-40): Código completo de sesión/timeout
- `ProgramacionSemanalController.php` (líneas 16-44): Método `checkSession()` con lógica duplicada
- `ProgramacionIntermediaController.php` (líneas 19-48): Mismo patrón

**Líneas de código duplicadas:** ~30 líneas × 4 controladores = 120 líneas duplicadas

---

## Mejoras Recomendadas (Próxima Fase)

### 1. SessionMiddleware.php

Crear middleware centralizado para eliminar duplicación:

```php
namespace App\Core;

class SessionMiddleware
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit;
        }

        // Gestión de timeout
        $inactividad = 3600;
        if (isset($_SESSION["timeout"])) {
            $sessionTTL = time() - $_SESSION["timeout"];
            if ($sessionTTL > $inactividad) {
                session_unset();
                session_destroy();
                echo "<script>alert('Se cerrará la sesión...');window.location.href='/login';</script>";
                exit;
            }
        }
        $_SESSION["timeout"] = time();

        // Actualizar semana si viene por GET
        if (isset($_GET['semana'])) {
            $_SESSION['semana'] = (int)$_GET['semana'];
        }
    }
}
```

**Uso en controladores:**

```php
public function index()
{
    SessionMiddleware::check(); // ✅ 1 línea en lugar de 30

    // ... resto de lógica ...
}
```

### 2. BaseController.php

Crear clase base para eliminar más duplicación:

```php
namespace App\Controllers;

use Database;
use App\Core\SessionMiddleware;

abstract class BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function requireAuth()
    {
        SessionMiddleware::check();
    }

    protected function getSessionVars()
    {
        return [
            'dbName' => $_SESSION['db'] ?? '',
            'semana' => (int)($_SESSION['semana'] ?? 0),
            'proyecto' => $_SESSION['proyecto'] ?? '',
            'permiso' => $_SESSION['permiso'] ?? '',
            'pdcActivo' => $_SESSION['pdcActivo'] ?? '',
            'nombreUsuario' => $_SESSION['nombreUsuario'] ?? ''
        ];
    }

    protected function render($viewPath, $data = [])
    {
        extract($data);
        require PROJECT_ROOT . $viewPath;
    }
}
```

**Uso:**

```php
class ProgramaGeneralController extends BaseController
{
    public function index()
    {
        $this->requireAuth(); // SessionMiddleware automático

        $vars = $this->getSessionVars();
        extract($vars); // $dbName, $semana, etc.

        // ... lógica específica del módulo ...

        $this->render('/construccion/programa_general/views/programa_general.view.nuevaBarra.php', compact('maxSemana', 'fechaInicioSem'));
    }
}
```

### 3. Desactivar Acceso Legacy (Opcional)

Modificar `.htaccess` para forzar el uso del Front Controller:

```apache
# Bloquear acceso directo a carpetas de módulos (excepto assets)
RewriteCond %{REQUEST_URI} ^/construccion/[^/]+/[^/]+\.php$
RewriteCond %{REQUEST_URI} !^/construccion/(css|js|imagenes)/
RewriteRule ^ - [F,L]
```

---

## Estado Consolidado de Módulos

### ✅ Migrados (5 módulos, 8 vistas)

1. **login** - `Auth/LoginController.php`
2. **dashboard** - `Core/DashboardController.php`
3. **programa_general** - `Programacion/ProgramaGeneralController.php`
4. **programacion_semanal** - `Programacion/ProgramacionSemanalController.php` (incluye CNP, CNC, CIC)
5. **programacion_intermedia** - `Programacion/ProgramacionIntermediaController.php`

### ❌ Pendientes (10 módulos)

**Prioridad Alta (complejidad alta o impacto crítico):**

1. **pdc** - PDC Principal (requiere UI refactorizada 2026)
2. **controlCambios** - Control de Cambios con submódulo ODC
3. **profesionales** - Con Handsontable (Live Editing)

**Prioridad Media:** 4. **contratos** 5. **listadoActividades** 6. **paquetesContratacion** 7. **subcontratistas** 8. **programaGeneralActualizar**

**Prioridad Baja (visualización):** 9. **indicadores** 10. **informe_productividad** 11. **registrate**

---

## Resumen de Pasos para Migrar un Módulo

### Checklist de Migración

1. ✅ **Crear Controller:**
   - Ubicación: `/src/Controllers/[Categoria]/[Modulo]Controller.php`
   - Namespace: `App\Controllers\[Categoria]`
   - Heredar de `BaseController` (una vez creado)

2. ✅ **Copiar Lógica de Variables:**
   - Extraer cálculos del archivo legacy `[modulo].php`
   - Preparar todas las variables que la vista necesita
   - Usar `$this->db` para consultas SQL

3. ✅ **Inyectar Inputs `_PHP`:**
   - En la vista, agregar inputs hidden con sufijo `_PHP`
   - Ejemplo: `<input type="hidden" id="semana_PHP" value="<?= $semana ?>">`

4. ✅ **Actualizar JavaScript:**
   - Buscar todas las referencias a `$("#semana").val()` y similar
   - Reemplazar por patrón resiliente: `$("#semana_PHP").val() || $("#semana").val()`

5. ✅ **Corregir Rutas AJAX:**
   - Buscar `url: "../` en archivos JS
   - Reemplazar por `/construccion/...`

6. ✅ **Blindar Scripts AJAX:**
   - En `guardar_*.php`, `listar_*.php`, etc.
   - Agregar bootstrap: `PROJECT_ROOT`, autoloader, `conexion.php`

7. ✅ **Registrar Rutas:**
   - Editar `/public/index.php`
   - Agregar: `$router->get('/ruta', [Controller::class, 'method'])`

8. ✅ **Probar:**
   - Navegar a la ruta nueva
   - Validar carga de tabla/datos
   - Probar guardado/edición
   - Validar descarga de reportes (si aplica)

---

## Métricas de Éxito

- **Objetivo:** 10 módulos pendientes → 0 módulos pendientes
- **Tiempo estimado:** 2-3 semanas (1-2 módulos/día considerando complejidad)
- **Reducción de código:** ~120 líneas duplicadas con Middleware + BaseController
- **Beneficio:** Arquitectura escalable, mantenible y lista para API REST futura

**Próximo paso recomendado:** Implementar `SessionMiddleware` y `BaseController` antes de migrar más módulos.
