# Reparto de lienzos de la Torre por rol — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** El Residente de Obra entra a la Torre de Control con el lienzo de obra (aterrizando en
Programación Intermedia), filtrado por defecto a sus propios compromisos en la hoja Responsables
con un interruptor para ver toda la obra; el Director sigue igual; el Admin elige libremente entre
el lienzo de gerencia y el de obra y el sistema recuerda su última elección.

**Architecture:** Cuatro cambios independientes sobre código ya existente. No hay tablas nuevas ni
rutas nuevas: las 8 rutas `/bi/*` y su gate único (`internal.bi.preview`) ya existen; lo que cambia
es (1) a quién se le concede la capacidad, (2) a qué ruta apunta el enlace de entrada según el rol,
(3) qué ruta recuerda el Admin entre visitas, y (4) qué filtro trae por defecto la hoja Responsables
para el Residente.

**Tech Stack:** PHP 8.3 en Docker · MySQL 8.0 · pruebas sueltas `tests/test_*.php` con
`// @requiere: <nivel>` · `scripts/run-php-tests.php` como entrada única.

**Spec:** `docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md`

## Global Constraints

- **Sentencias preparadas siempre**, a través de `App\Core\Database`. Nunca SQL con datos de
  usuario concatenados.
- **Toda consulta operativa se aísla por `project_id`.**
- **No se inventa una capacidad RBAC nueva sin pasar por `RbacCatalog`** — esta ronda reutiliza
  `PERM_INTERNAL_BI_PREVIEW`, ya existente.
- **Verificación obligatoria de RBAC:** un rol permitido y uno denegado, por `/dev/entrar`.
- **Comandos dentro del contenedor:** `docker compose exec app ...`. Nunca un PHP del host.
- **El `.env` del worktree es un enlace**, no una copia.
- **Ningún cambio de esquema.** Las cuatro tareas son código de aplicación puro.

---

## Estructura de archivos

| Archivo | Responsabilidad | Tarea |
|---|---|---|
| `src/Security/RbacManager.php` | Conceder `internal.bi.preview` al Residente | 1 |
| `tests/test_bi_preview_gate.php` | Actualizar los casos de R (pasa de "no la tiene" a "sí la tiene") | 1 |
| `src/View/Components/BiAccessComponent.php` | `defaultModuleForRole()` y mapa reportKey → módulo | 2, 3 |
| `views/partials/shell_sidebar.php` | Usar `defaultModuleForRole()` en el enlace de entrada | 2 |
| `src/Controllers/Bi/BiViewController.php` | Recordar el último módulo del Admin; inyectar el filtro por defecto del Residente en Responsables | 3, 4 |
| `views/bi/control-tower.php` | Botón "Ver toda la obra" en la sección de Responsables | 4 |
| `tests/test_bi_lienzo_por_rol.php` | Prueba del aterrizaje por rol, la memoria del Admin y el filtro del Residente | 2, 3, 4 |

---

## Task 1: El Residente recibe la capacidad `internal.bi.preview`

**Contexto que el ejecutor necesita.** Hoy `RbacManager::getCapabilities()` concede
`PERM_INTERNAL_BI_PREVIEW` solo a `A` y `D` (línea 32). Es el único punto de gate para todo el
módulo BI — lo consume `BiPreviewAccessPolicy::canOpen()`, que a su vez usa
`BiAccessComponent::canAccess()` para pintar o no cualquier enlace. Extender la capacidad basta
para que el Residente pueda abrir las 8 rutas; el interruptor global `bi.control_tower.visible`
(`FlagsService`) sigue aplicando igual que a Director — si está apagado, el Residente tampoco
entra, exactamente como pasa hoy con Director.

**Files:**
- Modify: `src/Security/RbacManager.php:14-32`
- Test: `tests/test_bi_preview_gate.php`

**Interfaces:**
- Consumes: nada nuevo.
- Produces: `RbacManager::hasCapability('R', RbacCatalog::PERM_INTERNAL_BI_PREVIEW)` pasa a `true`.
  Todo lo que ya usa `BiPreviewAccessPolicy::canOpen()` y `BiAccessComponent::canAccess()` recibe
  el cambio automáticamente, sin tocarlos.

- [ ] **Step 1: Confirmar el estado actual con la prueba existente**

Run: `docker compose exec app php tests/test_bi_preview_gate.php`
Esperado: PASA completo, con `rol R no la tiene` y `sesion de Residente no abre` en verde (eso es
lo que hay que cambiar).

- [ ] **Step 2: Editar la prueba para declarar el nuevo comportamiento (falla primero)**

En `tests/test_bi_preview_gate.php`, reemplazar el bloque de la capacidad:

```php
echo "Capacidad internal.bi.preview:\n";
comprobar(
    'rol A la tiene',
    RbacManager::hasCapability('A', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
comprobar(
    'rol D la tiene (ampliado el 2026-08-20)',
    RbacManager::hasCapability('D', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
comprobar(
    'rol R la tiene (ampliado el 2026-08-24)',
    RbacManager::hasCapability('R', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
foreach (['V', 'C', 'DCV', 'OT', 'S', 'G', 'SG'] as $rol) {
    comprobar("rol {$rol} no la tiene", RbacManager::hasCapability($rol, RbacCatalog::PERM_INTERNAL_BI_PREVIEW), false);
}
```

Y el bloque del gate de rutas:

```php
comprobar(
    'sesion de Residente abre (ampliado el 2026-08-24)',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    true
);
comprobar(
    'sesion de Visualizador no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.V'], 'V'),
    false
);
```

Y en el bloque del interruptor global, cambiar el caso que hoy dice
`'flag encendido: Residente sigue sin entrar (no tiene la capacidad)'` por:

```php
comprobar(
    'flag encendido: Residente entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    true
);
comprobar(
    'flag apagado: Residente NO entra (no es Admin)',
    (function () {
        \App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
        $resultado = \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R');
        \App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => true]);
        return $resultado;
    })(),
    false
);
```

- [ ] **Step 3: Correr la prueba y verificar que ahora falla**

Run: `docker compose exec app php tests/test_bi_preview_gate.php`
Esperado: FALLA en los tres casos de Residente recién editados (el código todavía no concede la
capacidad).

- [ ] **Step 4: Conceder la capacidad**

En `src/Security/RbacManager.php`, línea 32:

```php
// Ampliado a Director de Obra el 2026-08-20 y a Residente el 2026-08-24 (decisión de
// Felipe, docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md): el
// módulo sigue oculto para el resto de roles mientras se desarrolla.
RbacCatalog::PERM_INTERNAL_BI_PREVIEW => $isSystemAdmin || $isDirector || $isResident,
```

- [ ] **Step 5: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_bi_preview_gate.php`
Esperado: PASA completo.

- [ ] **Step 6: Verificar en el navegador — rol permitido y rol denegado**

Abrir `http://localhost:8081/dev/entrar?u=test.R` y luego `/bi/control-tower`: debe cargar, no dar
404. Abrir `http://localhost:8081/dev/entrar?u=test.V` y luego `/bi/control-tower`: debe seguir
dando 404 (Visualizador no tiene la capacidad).

- [ ] **Step 7: Commitear**

```bash
git add src/Security/RbacManager.php tests/test_bi_preview_gate.php
git commit -m "feat(bi): el Residente de Obra entra a la Torre de Control"
```

---

## Task 2: La puerta de entrada aterriza en el módulo correcto según el rol

**Contexto que el ejecutor necesita.** Las 8 rutas `/bi/*` ya existen y cada una activa su propia
pestaña al cargar (`bi-spa.js:250-251`, `VIEW_FROM_REPORT_KEY[reportKey]`): visitar `/bi/intermedia`
ya abre con Programación Intermedia activa, sin trabajo nuevo ahí. Lo que falta es que **el enlace
de entrada** (`views/partials/shell_sidebar.php:86`) apunte a la ruta correcta según quién está
mirando, en vez de estar fijo en `'control-tower'` (Resumen Ejecutivo) para todos. Hoy ese enlace
usa `\App\View\Components\BiAccessComponent::url('control-tower')`.

**Files:**
- Modify: `src/View/Components/BiAccessComponent.php`
- Modify: `views/partials/shell_sidebar.php:86`
- Test: `tests/test_bi_lienzo_por_rol.php` (nuevo)

**Interfaces:**
- Consumes: `RbacService::normalizeRole()` (ya existente) para resolver el rol de sesión.
- Produces: `BiAccessComponent::defaultModuleForRole(string $role): string`, que devuelve una clave
  de `self::ROUTES` (`'control-tower'`, `'intermedia'`, etc.). La Tarea 3 la vuelve a llamar para
  el caso Admin, así que la firma debe quedar estable desde aquí.

- [ ] **Step 1: Escribir la prueba que falla**

```php
<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Prueba: qué módulo trae el enlace de entrada de la Torre según el rol.
 * Ver docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\BiAccessComponent;

$fallos = 0;
$total = 0;

function comprobar(string $caso, $obtenido, $esperado): void
{
    global $fallos, $total;
    $total++;
    if ($obtenido === $esperado) {
        echo "  OK   {$caso}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba " . var_export($esperado, true)
        . ", obtuvo " . var_export($obtenido, true) . "\n";
}

echo "Aterrizaje por rol:\n";
comprobar('Director aterriza en Intermedia', BiAccessComponent::defaultModuleForRole('D'), 'intermedia');
comprobar('Residente aterriza en Intermedia', BiAccessComponent::defaultModuleForRole('R'), 'intermedia');
comprobar(
    'Admin sin elección previa aterriza en gerencia (control-tower)',
    BiAccessComponent::defaultModuleForRole('A'),
    'control-tower'
);

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correr la prueba y verificar que falla**

Run: `docker compose exec app php tests/test_bi_lienzo_por_rol.php`
Esperado: FALLA con "Call to undefined method ... defaultModuleForRole()".

- [ ] **Step 3: Añadir `defaultModuleForRole()` a `BiAccessComponent`**

En `src/View/Components/BiAccessComponent.php`, después de la constante `ROUTES`:

```php
/**
 * A qué módulo aterriza cada rol al abrir la Torre desde el enlace de entrada.
 * Decisión de Felipe, 2026-08-24 (reemplaza D72 "sin conmutador" para R y A):
 * docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md.
 */
public static function defaultModuleForRole(string $role): string
{
    $role = strtoupper(trim($role));

    if ($role === 'D' || $role === 'R') {
        return 'intermedia';
    }

    if ($role === 'A') {
        return self::adminLastModule() ?? 'control-tower';
    }

    return 'control-tower';
}

/**
 * Último módulo que el Admin visitó en esta sesión, o null si no ha entrado
 * todavía. Lo escribe BiViewController::renderView() en cada visita. Ver Tarea 3.
 */
private static function adminLastModule(): ?string
{
    $reportKey = $_SESSION['bi_admin_last_module'] ?? null;
    if (!is_string($reportKey) || $reportKey === '') {
        return null;
    }

    return self::REPORT_KEY_TO_MODULE[$reportKey] ?? null;
}
```

Y, junto a la constante `ROUTES`, el mapa inverso que usará también la Tarea 3:

```php
/**
 * `BiViewController` identifica cada pantalla por reportKey ('overview', 'cip', ...);
 * ROUTES identifica cada módulo por el nombre que usan los enlaces ('control-tower',
 * 'profesionales', ...). Los dos vocabularios no coinciden 1:1 (cic → subcontratistas,
 * cip → profesionales, overview → control-tower), así que hace falta este mapa.
 */
private const REPORT_KEY_TO_MODULE = [
    'overview' => 'control-tower',
    'programa-general' => 'programa-general',
    'intermedia' => 'intermedia',
    'semanal' => 'semanal',
    'pdc' => 'pdc',
    'cic' => 'subcontratistas',
    'cip' => 'profesionales',
    'curva-s' => 'indicadores',
];
```

- [ ] **Step 4: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_bi_lienzo_por_rol.php`
Esperado: PASA — el caso Admin pasa porque `$_SESSION` está vacío en la prueba `puro`, así que
`adminLastModule()` devuelve `null` y cae al default `'control-tower'`.

- [ ] **Step 5: Usar la resolución por rol en el enlace de entrada del sidebar**

En `views/partials/shell_sidebar.php`, línea 86, cambiar:

```php
? ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => \App\View\Components\BiAccessComponent::url('control-tower'), 'icon' => 'chart']
```

por:

```php
? ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => \App\View\Components\BiAccessComponent::url(\App\View\Components\BiAccessComponent::defaultModuleForRole($_SESSION['rol_normalizado'] ?? '')), 'icon' => 'chart']
```

Si `$_SESSION['rol_normalizado']` no existe en ese contexto, usar el mismo patrón que ya resuelve
el rol en esa vista — revisar las líneas previas a 86 en `shell_sidebar.php` para tomar la variable
de rol ya calculada ahí, en vez de leer `$_SESSION` de nuevo con una clave distinta a la que el
resto del archivo usa.

- [ ] **Step 6: Verificar en el navegador — rol permitido y rol denegado**

Abrir `http://localhost:8081/dev/entrar?u=test.R` y mirar el enlace "Control Tower - Informes" en
el sidebar: su `href` debe ser `/bi/intermedia...`, no `/bi/control-tower...`. Repetir con
`test.A`: debe seguir siendo `/bi/control-tower...` (primera visita, sin elección previa).

- [ ] **Step 7: Correr la suite y commitear**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=puro
git add src/View/Components/BiAccessComponent.php views/partials/shell_sidebar.php tests/test_bi_lienzo_por_rol.php
git commit -m "feat(bi): el enlace de entrada aterriza en Intermedia para Director y Residente"
```

---

## Task 3: El Admin elige libremente y el sistema recuerda su última elección

**Contexto que el ejecutor necesita.** El Admin no tiene audiencia operativa fija: puede querer ver
gerencia o ver obra según el día. La Tarea 2 ya dejó `defaultModuleForRole('A')` leyendo
`$_SESSION['bi_admin_last_module']`; falta que algo escriba esa clave. El punto correcto es
`BiViewController::renderView()`, que ya recibe `$reportKey` en cada una de las 8 rutas — ahí se
sabe qué pantalla se está viendo, sin duplicar lógica de rol.

**Files:**
- Modify: `src/Controllers/Bi/BiViewController.php`
- Test: `tests/test_bi_lienzo_por_rol.php` (añadir casos)

**Interfaces:**
- Consumes: `BiAccessComponent::REPORT_KEY_TO_MODULE` — **es privado**; esta tarea no lo necesita
  directo, solo escribe `$_SESSION['bi_admin_last_module'] = $reportKey` con el `reportKey` crudo,
  que es la clave que `adminLastModule()` ya sabe traducir.
- Produces: `$_SESSION['bi_admin_last_module']` queda escrito después de cualquier visita del Admin
  a una de las 8 rutas.

- [ ] **Step 1: Escribir la prueba que falla**

Añadir al final de `tests/test_bi_lienzo_por_rol.php`, antes del bloque de resultado:

```php
echo "\nMemoria de la última elección del Admin:\n";
$_SESSION = [];
$_SESSION['bi_admin_last_module'] = 'intermedia';
comprobar(
    'Admin con elección previa de obra aterriza en Intermedia',
    BiAccessComponent::defaultModuleForRole('A'),
    'intermedia'
);
$_SESSION['bi_admin_last_module'] = 'cip';
comprobar(
    'Admin con elección previa de Responsables (cip) aterriza en profesionales',
    BiAccessComponent::defaultModuleForRole('A'),
    'profesionales'
);
$_SESSION = [];
```

- [ ] **Step 2: Correr la prueba y verificar que este bloque ya pasa**

Run: `docker compose exec app php tests/test_bi_lienzo_por_rol.php`
Esperado: PASA — `defaultModuleForRole()` ya sabe leer la sesión desde la Tarea 2; lo que falta es
quién la escribe, que es el resto de esta tarea.

- [ ] **Step 3: Escribir `$_SESSION['bi_admin_last_module']` en cada visita del Admin**

En `src/Controllers/Bi/BiViewController.php`, dentro de `renderView()`, justo después de la línea
que calcula `$role = $this->projectScope->reportRole($projectIds, $_SESSION);` (línea ~72):

```php
// El Admin no tiene audiencia fija: recuerda su última elección para que el
// enlace de entrada del sidebar aterrice ahí la próxima vez (Tarea 3,
// docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md).
if ($role === 'A') {
    $_SESSION['bi_admin_last_module'] = $reportKey;
}
```

- [ ] **Step 4: Verificar en el navegador**

Entrar como `test.A`, abrir `/bi/pdc`, y luego volver a la página anterior (o recargar cualquier
página con el sidebar). El enlace "Control Tower - Informes" del sidebar debe apuntar ahora a
`/bi/pdc...`.

- [ ] **Step 5: Correr la suite y commitear**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=puro
git add src/Controllers/Bi/BiViewController.php tests/test_bi_lienzo_por_rol.php
git commit -m "feat(bi): el Admin recuerda el último lienzo que visitó"
```

---

## Task 4: Responsables filtra al Residente por sus propios compromisos, con interruptor a toda la obra

**Contexto que el ejecutor necesita.** La hoja Responsables (`cip`) ya acepta un filtro `resp` por
querystring (`ControlTowerService::fetchCip()`, `'resp' => 'Responsable_AIA'`), y el frontend
(`bi-spa.js:156`) ya lee `resp` de la URL como valor inicial del filtro. **No hace falta tocar
JavaScript ni la API**: basta con que la página `/bi/responsables` redirija, para el Residente y
sin elección explícita, a sí misma con `resp=<su nombre>` en la URL. El nombre sale de cruzar la
sesión (`general_usuarios.usuario`) con `profesionales` por email — el mismo cruce que ya usa
`ProjectProfessionalsSyncService`. Confirmado con Felipe (2026-08-24): "equipo" para el Residente
significa sus propios compromisos, no un grupo de otras personas — no hace falta jerarquía
jefe→equipo en la base, que no existe hoy en `profesionales`.

**Files:**
- Modify: `src/Controllers/Bi/BiViewController.php`
- Modify: `views/bi/control-tower.php` (botón "Ver toda la obra" en la sección de Responsables)
- Test: `tests/test_bi_alcance_responsables.php` (nuevo)

**Interfaces:**
- Consumes: `$this->db` (ya disponible en `BiViewController` vía `parent::__construct()` — heredado
  de `BaseController`), `TableResolver::resolveByPrefix()` (ya usado en `loadShellWeeks()`).
- Produces: nada para otras tareas — es la última de este plan.

- [ ] **Step 1: Escribir la prueba que falla**

```php
<?php

declare(strict_types=1);
// @requiere: db

/**
 * Prueba: resolver el nombre en `profesionales` de la persona en sesión, cruzando
 * por email contra `general_usuarios`. Es lo que usa el filtro por defecto de
 * Responsables para el Residente.
 * Ver docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\Bi\BiViewController;
use App\Core\Database;

$db = Database::getInstance();
$projectId = (int) ($argv[1] ?? 68);

// Toma cualquier profesional real con email de ese proyecto para armar el caso.
$tProf = \TableResolver::resolveByPrefix((string) $_ENV['DB_NAME'], 'profesionales');
$fila = $db->query(
    "SELECT p.nombre, u.usuario FROM {$tProf} p
     INNER JOIN general_usuarios u ON u.email = p.email
     WHERE p.project_id = ? AND p.email <> '' LIMIT 1",
    [$projectId]
)->fetch();

if ($fila === false) {
    echo "OMITIDA: el proyecto $projectId no tiene ningún profesional con usuario cruzado por email\n";
    exit(0);
}

$controller = new class extends BiViewController {
    public function exponerResolverNombre(string $usuario, int $projectId): ?string
    {
        return $this->resolveOwnProfessionalName($usuario, $projectId);
    }
};

$obtenido = $controller->exponerResolverNombre($fila['usuario'], $projectId);

if ($obtenido !== $fila['nombre']) {
    echo "FALLA: esperaba '{$fila['nombre']}', obtuvo '" . var_export($obtenido, true) . "'\n";
    exit(1);
}
echo "PASA: '{$fila['usuario']}' resuelve a '{$obtenido}'\n";
exit(0);
```

- [ ] **Step 2: Correr la prueba y verificar que falla**

Run: `docker compose exec app php tests/test_bi_alcance_responsables.php 68`
Esperado: FALLA — `resolveOwnProfessionalName()` todavía no existe (error de método no definido, o
`Call to protected method` si el visibility no calza con la subclase de prueba).

- [ ] **Step 3: Añadir el resolutor de nombre propio**

En `src/Controllers/Bi/BiViewController.php`, como método `protected` (para que la subclase de
prueba lo alcance) junto a `loadShellWeeks()`:

```php
/**
 * Nombre en `profesionales` de quien está en sesión, cruzando por email contra
 * `general_usuarios`. Null si no hay cruce (usuario sin profesional, o email
 * distinto entre las dos tablas). Usado por el filtro por defecto de Responsables
 * para el Residente (Tarea 4, 2026-08-24).
 */
protected function resolveOwnProfessionalName(string $usuario, int $projectId): ?string
{
    $usuario = trim($usuario);
    if ($usuario === '' || $projectId <= 0) {
        return null;
    }

    $dbName = (string) ($_SESSION['db'] ?? '');
    if ($dbName === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        return null;
    }

    try {
        $tProf = TableResolver::resolveByPrefix($dbName, 'profesionales');
        $fila = $this->db->query(
            "SELECT p.nombre FROM {$tProf} p
             INNER JOIN general_usuarios u ON u.email = p.email
             WHERE u.usuario = ? AND p.project_id = ? AND p.email <> ''
             LIMIT 1",
            [$usuario, $projectId]
        )->fetch();
    } catch (\Throwable $e) {
        error_log('Error resolviendo nombre propio para Responsables: ' . $e->getMessage());
        return null;
    }

    return $fila !== false ? (string) $fila['nombre'] : null;
}
```

- [ ] **Step 4: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_bi_alcance_responsables.php 68`
Esperado: PASA.

- [ ] **Step 5: Redirigir a Responsables con el filtro propio cuando el Residente entra sin elegir**

En `BiViewController::responsables()`:

```php
public function responsables(): void
{
    $this->maybeRedirectToOwnScope();
    $this->renderView('cip', 'control-tower');
}

/**
 * El Residente aterriza en Responsables viendo solo sus propios compromisos
 * (confirmado con Felipe, 2026-08-24), a menos que ya haya elegido explícitamente
 * un filtro (`resp`) o pedido ver toda la obra (`alcance=obra`).
 */
private function maybeRedirectToOwnScope(): void
{
    if (isset($_GET['resp']) || ($_GET['alcance'] ?? '') === 'obra') {
        return;
    }

    $usuario = (string) ($_SESSION['usuario'] ?? '');
    $rol = strtoupper(trim((string) ($_SESSION['permiso'] ?? '')));
    if ($rol !== 'R' || $usuario === '') {
        return;
    }

    $projectId = (int) ($_SESSION['project_id'] ?? 0);
    $nombre = $this->resolveOwnProfessionalName($usuario, $projectId);
    if ($nombre === null) {
        return;
    }

    $query = $_GET;
    $query['resp'] = $nombre;
    header('Location: /bi/responsables?' . http_build_query($query));
    exit;
}
```

**Nota para el ejecutor:** confirmar en `src/Controllers/BaseController.php` o en el patrón de
sesión ya usado por `BiPreviewAccessPolicy::resolveRole()` cuál es la clave real del rol en
`$_SESSION` (`'permiso'` se ve en `BiPreviewAccessPolicy.php:60`, pero verificar contra la sesión
real de un login por `/dev/entrar` antes de dar el paso por bueno — no asumir el nombre de la
clave sin comprobarlo).

- [ ] **Step 6: Correr la prueba y verificar que pasa**

Run: `docker compose exec app php tests/test_bi_alcance_responsables.php 68`
Esperado: sigue en PASA (esta tarea no cambia la prueba de la Task 3, solo añade comportamiento).

- [ ] **Step 7: Añadir el botón "Ver toda la obra" en la sección de Responsables**

En `views/bi/control-tower.php`, dentro de `<section id="view-cip" ...>` (buscar el encabezado de
la sección Responsables), añadir junto al título:

```php
<?php if (($role ?? '') === 'R'): ?>
<a href="/bi/responsables?alcance=obra" class="aia-btn aia-btn--secondary text-sm">
    Ver toda la obra
</a>
<?php endif; ?>
```

- [ ] **Step 8: Verificar en el navegador — rol permitido y rol denegado**

Entrar como `test.R` en un proyecto donde el usuario cruce con `profesionales` por email, abrir
`/bi/responsables`: la URL debe redirigir a `/bi/responsables?resp=<su nombre>` y la tabla debe
mostrar solo sus propios compromisos. Hacer clic en "Ver toda la obra": la URL pasa a
`?alcance=obra` y la tabla muestra todos los responsables. Entrar como `test.D` y abrir
`/bi/responsables`: no debe haber redirección ni botón "Ver toda la obra" (el Director ya ve la
obra completa por defecto, Tarea del diseño).

- [ ] **Step 9: Correr la suite y commitear**

```bash
docker compose exec app php scripts/run-php-tests.php --nivel=db
git add src/Controllers/Bi/BiViewController.php views/bi/control-tower.php tests/test_bi_alcance_responsables.php
git commit -m "feat(bi): Responsables filtra al Residente por sus propios compromisos, con interruptor a toda la obra"
```

---

## Condición de hecho

- `RbacManager::hasCapability('R', RbacCatalog::PERM_INTERNAL_BI_PREVIEW)` es `true`; sigue siendo
  `false` para todos los roles fuera de A, D, R.
- El enlace de entrada del sidebar aterriza en Programación Intermedia para Director y Residente,
  y en el último módulo visitado (o gerencia, la primera vez) para Admin.
- `/bi/responsables` redirige al Residente a su propio filtro por defecto, con un botón visible
  para ver toda la obra; el Director no ve ese botón ni esa redirección.
- Verificado en el navegador un rol permitido y uno denegado para el acceso a la Torre (Task 1) y
  para el filtro de Responsables (Task 4).
- `docker compose exec app php scripts/run-php-tests.php --nivel=puro` y `--nivel=db` en verde.
