---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-08-20
areas: [rbac, bi, admin]
fuente: docs/superpowers/plans/2026-08-20-interruptor-control-tower-admin.md
resumen: Un flag global bi.controltower.visible, editable desde /admin, que decide si los roles no-Admin con internal.bi.preview ven el Control Tower; el Admin entra…
---

# Interruptor del Control Tower desde /admin — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Un flag global `bi.control_tower.visible`, editable desde `/admin`, que decide si los roles no-Admin con `internal.bi.preview` ven el Control Tower; el Admin entra siempre.

**Architecture:** Tabla nueva `general_flags` (clave-valor, global, con auditoría de último cambio) + `App\Core\FlagsService` de solo lectura con fail-safe a `false` + `BiPreviewAccessPolicy` que consulta el flag solo para roles no-A + pantalla `GET/POST /admin/modulos` en la mini-app admin siguiendo el patrón de `ConfigController`.

**Tech Stack:** PHP 8.3 plano (sin framework), PDO vía `Database`, tests standalone `tests/test_*.php`, todo dentro de Docker (`docker compose exec app`).

**Spec:** `docs/superpowers/specs/2026-08-20-interruptor-control-tower-admin-design.md`

## Global Constraints

- Todo comando PHP corre dentro del contenedor: `docker compose exec app php ...`.
- Prepared statements siempre; ningún SQL con datos de usuario concatenados.
- Semántica del flag: `true` solo si la fila existe y `valor === '1'`; **cualquier otro caso (fila ausente, tabla ausente, error, valor raro) es apagado**.
- Con flag apagado el rol `A` sigue entrando; los denegados reciben **404, no 403** (contrato vigente, no se toca).
- Los roles con `internal.bi.preview` siguen viviendo en `RbacManager` (`A` y `D` hoy); este plan no los cambia.
- `admin/` no importa clases de `src/` salvo las que ya usa (`App\Security\*`, `Database`): la pantalla admin escribe con SQL propio.
- Cada test standalone declara su nivel con `// @requiere: <nivel>`.
- No commitear `.env` ni evidencia local. Commits atómicos por tarea.

---

### Task 1: Migración `general_flags` con siembra

**Files:**
- Create: `database/migrations/20260820_general_flags.sql`

**Interfaces:**
- Produces: tabla `general_flags(clave VARCHAR(100) PK, valor VARCHAR(255), actualizado_por VARCHAR(100), actualizado_en DATETIME)` con la fila `('bi.control_tower.visible','1','migracion')`. Tasks 2–4 leen/escriben esa tabla.

- [ ] **Step 1: Escribir la migración**

```sql
-- 20260820_general_flags.sql
-- Tabla global de interruptores (spec 2026-08-20-interruptor-control-tower-admin-design.md).
-- Sin project_id a propósito: los flags que viven aquí son globales por diseño.
-- Idempotente: IF NOT EXISTS + INSERT IGNORE, para poder re-correrla sin daño.

CREATE TABLE IF NOT EXISTS general_flags (
  clave VARCHAR(100) NOT NULL,
  valor VARCHAR(255) NOT NULL,
  actualizado_por VARCHAR(100) NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encendido al sembrar: el estado publicado hoy es «A y D lo ven» y la llegada del
-- interruptor no debe cambiar el comportamiento.
INSERT IGNORE INTO general_flags (clave, valor, actualizado_por)
VALUES ('bi.control_tower.visible', '1', 'migracion');
```

- [ ] **Step 2: Aplicarla en la base local**

Run: `docker compose exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < database/migrations/20260820_general_flags.sql`
(Si las variables del contenedor `db` difieren, leer `DB_USER`/`DB_PASS`/`DB_NAME` del `.env` de la raíz y usar esas.)

- [ ] **Step 3: Verificar el resultado, no el código de salida**

Run: `docker compose exec app php -r 'require "vendor/autoload.php"; require "src/Core/Database.php"; var_dump(\Database::getInstance()->query("SELECT valor FROM general_flags WHERE clave = ?", ["bi.control_tower.visible"])->fetchColumn());'`
(Verificar primero el namespace real en la cabecera de `src/Core/Database.php`; el admin la usa como `\Database`.)
Expected: `string(1) "1"`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/20260820_general_flags.sql
git commit -m "feat(db): tabla general_flags con el interruptor del Control Tower sembrado en encendido"
```

---

### Task 2: `FlagsService` (lectura con fail-safe)

**Files:**
- Create: `src/Core/FlagsService.php`
- Test: `tests/test_flags_service.php`

**Interfaces:**
- Consumes: tabla `general_flags` (Task 1); `App\Core\Database::getInstance()->query(string $sql, array $params)`.
- Produces: `App\Core\FlagsService::isOn(string $clave): bool` y `FlagsService::overrideForTests(?array $flags): void` (mapa `clave => bool`, `null` limpia). Task 3 consume ambas.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php
// tests/test_flags_service.php
// @requiere: db

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\FlagsService;

$total = 0;
$fallos = 0;

function comprobar(string $caso, bool $obtenido, bool $esperado): void
{
    global $total, $fallos;
    $total++;
    if ($obtenido === $esperado) {
        echo "  OK   {$caso}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba " . var_export($esperado, true)
        . ", obtuvo " . var_export($obtenido, true) . "\n";
}

echo "FlagsService::isOn contra la base real:\n";
// La migración de la Task 1 siembra el flag en '1'.
comprobar('flag sembrado en 1 devuelve true', FlagsService::isOn('bi.control_tower.visible'), true);
comprobar('clave inexistente devuelve false', FlagsService::isOn('no.existe.jamas'), false);

echo "\nCache por request:\n";
// Segunda lectura de la misma clave: debe salir del cache (no medimos la query,
// pero sí que el resultado es estable).
comprobar('segunda lectura estable', FlagsService::isOn('bi.control_tower.visible'), true);

echo "\nOverride de pruebas:\n";
FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
comprobar('override apaga sin tocar la base', FlagsService::isOn('bi.control_tower.visible'), false);
FlagsService::overrideForTests(['otra.clave' => true]);
comprobar('override enciende una clave que no existe en base', FlagsService::isOn('otra.clave'), true);
comprobar('con override activo, clave no listada es false', FlagsService::isOn('bi.control_tower.visible'), false);
FlagsService::overrideForTests(null);
comprobar('limpiar el override vuelve a la base', FlagsService::isOn('bi.control_tower.visible'), true);

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_flags_service.php`
Expected: FALLA con `Class "App\Core\FlagsService" not found`.

- [ ] **Step 3: Implementar `FlagsService`**

```php
<?php

namespace App\Core;

/**
 * Interruptores globales leídos de `general_flags`.
 *
 * Contrato (spec 2026-08-20): `isOn()` devuelve true SOLO si la fila existe y su
 * valor es exactamente '1'. Fila ausente, tabla ausente, error de base o valor
 * raro => false. El fail-safe es deliberado: un flag que no se puede leer se
 * comporta como apagado y nunca tumba la página con un 500.
 *
 * Cache por request (estático): una consulta por clave y por request. Sin TTL ni
 * invalidación — los cambios se hacen desde /admin y aplican al siguiente request.
 */
final class FlagsService
{
    /** @var array<string,bool> */
    private static array $cache = [];

    /** @var array<string,bool>|null Solo para pruebas: evita tocar la base. */
    private static ?array $override = null;

    public static function isOn(string $clave): bool
    {
        if (self::$override !== null) {
            return self::$override[$clave] ?? false;
        }

        if (array_key_exists($clave, self::$cache)) {
            return self::$cache[$clave];
        }

        try {
            $valor = Database::getInstance()
                ->query('SELECT valor FROM general_flags WHERE clave = ? LIMIT 1', [$clave])
                ->fetchColumn();
            self::$cache[$clave] = ($valor === '1');
        } catch (\Throwable) {
            self::$cache[$clave] = false;
        }

        return self::$cache[$clave];
    }

    /**
     * @param array<string,bool>|null $flags Mapa clave => estado; null limpia el
     *                                       override y el cache.
     */
    public static function overrideForTests(?array $flags): void
    {
        self::$override = $flags;
        self::$cache = [];
    }
}
```

Nota: si `Database` vive en el namespace global en vez de `App\Core` (verificar la
cabecera de `src/Core/Database.php` antes de escribir), usar `\Database::getInstance()`.

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `docker compose exec app php tests/test_flags_service.php`
Expected: `Resultado: 7/7`, exit 0.

- [ ] **Step 5: PHPStan del archivo nuevo**

Run: `docker compose exec app vendor/bin/phpstan analyse src/Core/FlagsService.php --memory-limit=1G --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add src/Core/FlagsService.php tests/test_flags_service.php
git commit -m "feat(core): FlagsService de solo lectura con fail-safe a apagado"
```

---

### Task 3: `BiPreviewAccessPolicy` consulta el flag

**Files:**
- Modify: `src/Security/BiPreviewAccessPolicy.php` (método `canOpen`, líneas 21-28)
- Modify: `tests/test_bi_preview_gate.php`

**Interfaces:**
- Consumes: `App\Core\FlagsService::isOn()` y `FlagsService::overrideForTests()` (Task 2); `RbacManager::hasCapability()` sin cambios.
- Produces: el mismo `BiPreviewAccessPolicy::canOpen(array $session, ?string $roleOverride = null): bool` — la firma no cambia; solo la semántica interna. Nadie más necesita tocarse: vistas, API y componentes ya llaman a `canOpen()`.

- [ ] **Step 1: Ampliar el test con los casos del flag (que fallarán)**

En `tests/test_bi_preview_gate.php`, después del bloque «Gate de las rutas», añadir:

```php
echo "\nInterruptor global (general_flags via FlagsService):\n";

\App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => false]);
comprobar(
    'flag apagado: Admin sigue entrando',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
comprobar(
    'flag apagado: Director NO entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    false
);

\App\Core\FlagsService::overrideForTests(['bi.control_tower.visible' => true]);
comprobar(
    'flag encendido: Director entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    true
);
comprobar(
    'flag encendido: Residente sigue sin entrar (no tiene la capacidad)',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    false
);

\App\Core\FlagsService::overrideForTests([]);
comprobar(
    'flag ilegible (override vacio = todo false): Director NO entra',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.D'], 'D'),
    false
);
comprobar(
    'flag ilegible: Admin sigue entrando',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
\App\Core\FlagsService::overrideForTests(null);
```

Ojo: el caso existente `'sesion de Director abre (ampliado el 2026-08-20)'` queda cubierto por
la base real (flag sembrado en `1`), no hay que tocarlo.

- [ ] **Step 2: Correr y verificar que fallan los casos nuevos**

Run: `docker compose exec app php tests/test_bi_preview_gate.php`
Expected: FALLA `flag apagado: Director NO entra` (obtiene true) — los demás nuevos pasan de
rebote o fallan según el orden; lo importante es que ese caso falle.

- [ ] **Step 3: Implementar el cambio en la política**

En `src/Security/BiPreviewAccessPolicy.php`, reemplazar el cuerpo de `canOpen()`:

```php
    public static function canOpen(array $session, ?string $roleOverride = null): bool
    {
        $role = $roleOverride === null
            ? self::resolveRole($session)
            : (new RbacService())->normalizeRole($roleOverride);

        if (!RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)) {
            return false;
        }

        // El Admin entra siempre: el interruptor nunca puede dejar por fuera a quien
        // lo administra (lección del 2026-08-13). Para el resto de roles con la
        // capacidad, manda el flag global editable desde /admin.
        if ($role === 'A') {
            return true;
        }

        return \App\Core\FlagsService::isOn('bi.control_tower.visible');
    }
```

- [ ] **Step 4: Correr el test completo y verificar que pasa**

Run: `docker compose exec app php tests/test_bi_preview_gate.php`
Expected: `Resultado: 22/22`, exit 0.

- [ ] **Step 5: PHPStan de los tocados**

Run: `docker compose exec app vendor/bin/phpstan analyse src/Security/BiPreviewAccessPolicy.php src/Core/FlagsService.php --memory-limit=1G --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add src/Security/BiPreviewAccessPolicy.php tests/test_bi_preview_gate.php
git commit -m "feat(bi): el gate del Control Tower obedece el interruptor global para roles no-Admin"
```

---

### Task 4: Pantalla `/admin/modulos`

**Files:**
- Create: `admin/src/Controllers/ModulosController.php`
- Create: `admin/views/pages/modulos/index.php`
- Modify: `admin/public/index.php` (registrar rutas junto a las de `/matching/config`, líneas ~138-140)
- Modify: `admin/views/layouts/main.php` (enlace de navegación junto al de Matching Config, línea ~104)
- Test: `tests/test_admin_modulos.php`

**Interfaces:**
- Consumes: tabla `general_flags` (Task 1) con SQL propio del admin (NO usa `FlagsService`, que es de `src/` y cachea — el admin necesita leer fresco y escribir); `Admin\Core\Security::generateCsrfToken()` / `validateCsrfToken()`; patrón de `ConfigController` (`AdminController`, `render()`, flash en `$_SESSION`).
- Produces: `GET /admin/modulos` (lista y switch), `POST /admin/modulos` (guarda). Nada posterior los consume.

- [ ] **Step 1: Escribir el test que falla**

```php
<?php
// tests/test_admin_modulos.php
// @requiere: http
// Smoke del interruptor en /admin: la ruta existe, exige sesión, y el POST exige CSRF.
// Sigue el patrón de tests/test_admin_dev_door_guard.php para sesión y peticiones.

$base = getenv('APP_BASE_URL') ?: 'http://localhost';

$total = 0;
$fallos = 0;

function comprobar(string $caso, bool $ok): void
{
    global $total, $fallos;
    $total++;
    if ($ok) { echo "  OK   {$caso}\n"; return; }
    $fallos++;
    echo "  FALLA {$caso}\n";
}

function pedir(string $url, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
    ] + $opts);
    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['status' => $status, 'raw' => $raw];
}

// 1) Sin sesión: redirige a login.
$r = pedir($base . '/admin/modulos');
comprobar('sin sesion redirige (302) a /admin/login', $r['status'] === 302 && str_contains($r['raw'], '/admin/login'));

// 2) Con sesión de admin por la puerta dev del panel.
$cookies = tempnam(sys_get_temp_dir(), 'cook');
pedir($base . '/admin/dev/entrar?u=test.A', [CURLOPT_COOKIEJAR => $cookies]);
$r = pedir($base . '/admin/modulos', [CURLOPT_COOKIEFILE => $cookies]);
comprobar('con sesion A responde 200', $r['status'] === 200);
comprobar('la vista trae el flag', str_contains($r['raw'], 'bi.control_tower.visible'));

// 3) POST sin CSRF: rechazado.
$r = pedir($base . '/admin/modulos', [
    CURLOPT_COOKIEFILE => $cookies,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['clave' => 'bi.control_tower.visible', 'valor' => '0']),
]);
comprobar('POST sin csrf es 403', $r['status'] === 403);

unlink($cookies);
echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
```

Nota para el implementador: antes de dar por bueno el helper de sesión, leer cómo lo hace
`tests/test_admin_dev_door_guard.php` y copiar su forma exacta (URL de la puerta dev del admin
y cookies); si difiere de este esqueleto, manda el patrón existente.

- [ ] **Step 2: Correrlo y verificar que falla**

Run: `docker compose exec app php tests/test_admin_modulos.php`
Expected: FALLA — `/admin/modulos` devuelve 404 (la ruta no existe aún).

- [ ] **Step 3: Implementar el controlador**

```php
<?php

namespace Admin\Controllers;

use Admin\Core\Security;
use Database;

/**
 * Interruptores globales de módulos (tabla general_flags).
 * Spec: docs/superpowers/specs/2026-08-20-interruptor-control-tower-admin-design.md
 */
class ModulosController extends AdminController
{
    /** Los flags que esta pantalla conoce y su texto en humano. */
    private const FLAGS = [
        'bi.control_tower.visible' => 'Control Tower (BI) visible para roles no-Admin',
    ];

    public function index(): void
    {
        $db = Database::getInstance();
        $flags = [];

        try {
            $stmt = $db->prepare('SELECT clave, valor, actualizado_por, actualizado_en FROM general_flags');
            $stmt->execute();
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $flags[$row['clave']] = $row;
            }
        } catch (\Exception $e) {
            error_log('[ModulosController] Error leyendo general_flags: ' . $e->getMessage());
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->render('modulos/index', [
            'title' => 'Módulos',
            'pageTitle' => 'Módulos',
            'breadcrumb' => 'Módulos',
            'conocidos' => self::FLAGS,
            'flags' => $flags,
            'csrf_token' => Security::generateCsrfToken(),
            'flash_success' => $flashSuccess,
            'flash_error' => $flashError,
        ]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['success' => false, 'message' => 'Método no permitido']);
        }

        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Token CSRF inválido']);
        }

        $clave = (string) ($_POST['clave'] ?? '');
        $valor = ($_POST['valor'] ?? '') === '1' ? '1' : '0';

        if (!array_key_exists($clave, self::FLAGS)) {
            http_response_code(422);
            $this->json(['success' => false, 'message' => 'Flag desconocido']);
        }

        $usuario = (string) ($_SESSION['admin_user']['usuario'] ?? 'admin');

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO general_flags (clave, valor, actualizado_por) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor), actualizado_por = VALUES(actualizado_por)'
            );
            $stmt->execute([$clave, $valor, $usuario]);
            $_SESSION['flash_success'] = 'Interruptor guardado.';
        } catch (\Exception $e) {
            error_log('[ModulosController] Error guardando flag: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo guardar el interruptor.';
        }

        header('Location: /admin/modulos');
        exit;
    }
}
```

Nota: si `AdminController::json()` no hace `exit` por sí mismo (verificar en
`admin/src/Controllers/BaseController.php`), añadir `exit;` tras cada `json()` de rechazo.

- [ ] **Step 4: La vista**

`admin/views/pages/modulos/index.php` — copiar la estructura de
`admin/views/pages/matching/config.php` (mismo layout, mismos bloques de flash) y en el cuerpo:

```php
<div class="card">
  <div class="card-header"><h3 class="card-title">Interruptores de módulos</h3></div>
  <div class="card-body">
    <?php foreach ($conocidos as $clave => $texto):
        $fila = $flags[$clave] ?? null;
        $encendido = $fila !== null && $fila['valor'] === '1';
    ?>
      <form method="POST" action="/admin/modulos" style="margin-bottom: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="clave" value="<?= htmlspecialchars($clave) ?>">
        <input type="hidden" name="valor" value="<?= $encendido ? '0' : '1' ?>">
        <p>
          <strong><?= htmlspecialchars($texto) ?></strong><br>
          <code><?= htmlspecialchars($clave) ?></code> —
          estado: <?= $encendido ? 'ENCENDIDO' : 'APAGADO' ?>
          <?php if ($fila !== null): ?>
            · último cambio: <?= htmlspecialchars($fila['actualizado_por']) ?>
            el <?= htmlspecialchars($fila['actualizado_en']) ?>
          <?php endif; ?>
        </p>
        <button type="submit" class="btn <?= $encendido ? 'btn-warning' : 'btn-success' ?>">
          <?= $encendido ? 'Apagar' : 'Encender' ?>
        </button>
      </form>
    <?php endforeach; ?>
    <p class="text-muted">El Admin siempre puede entrar al módulo, esté como esté el interruptor.</p>
  </div>
</div>
```

- [ ] **Step 5: Rutas y navegación**

En `admin/public/index.php`, junto a las rutas de Matching (líneas ~138-140):

```php
// Interruptores de módulos (general_flags)
$router->add('GET', '/modulos', 'ModulosController@index');
$router->add('POST', '/modulos', 'ModulosController@update');
```

En `admin/views/layouts/main.php`, junto al enlace de Matching Config (línea ~104), con el mismo
markup de `<a class="nav-link">` que sus vecinos:

```php
<a href="/admin/modulos" class="nav-link">
  <p>Módulos</p>
</a>
```

(Copiar el icono/estructura exacta de los ítems vecinos del layout real.)

- [ ] **Step 6: Correr el test y verificar que pasa**

Run: `docker compose exec app php tests/test_admin_modulos.php`
Expected: `Resultado: 4/4`, exit 0.

- [ ] **Step 7: Verificación de punta a punta en navegador (manual, misma sesión)**

1. `http://localhost:8081/admin/dev/entrar?u=test.A` → `/admin/modulos` → Apagar.
2. En otra pestaña, `http://localhost:8081/dev/entrar?u=test.D&p=PDC%20Sandbox%20E2E` →
   `/bi/control-tower` debe dar **404** y la Programación Semanal 0 accesos BI.
3. Volver al admin → Encender → el Director vuelve a ver el módulo (200 y 2 accesos).
4. Como `test.A`, comprobar que con el flag apagado el Admin sigue entrando (200).

- [ ] **Step 8: PHPStan y suite de regresión**

Run: `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G --no-progress`
Expected: `[OK] No errors` (o solo el baseline ya tolerado).
Run: `docker compose exec app php tests/test_bi_preview_gate.php && docker compose exec app php tests/test_flags_service.php`
Expected: ambos en verde.

- [ ] **Step 9: Commit**

```bash
git add admin/src/Controllers/ModulosController.php admin/views/pages/modulos/ admin/public/index.php admin/views/layouts/main.php tests/test_admin_modulos.php
git commit -m "feat(admin): pantalla /admin/modulos para el interruptor del Control Tower"
```

---

## Notas de deploy (para el cierre del frente, no para las tareas)

Trae clase nueva (`FlagsService`, `ModulosController`) → **`composer install` obligatorio** en los
servidores (classmap optimizado), y la migración `.sql` **antes** del smoke, por el cliente
`mysql`. Orden: backup → pull → composer → migración → smoke → verificación con el gate evaluado
por rol, como en el deploy del 2026-08-20.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** database/migrations/20260820_general_flags.sql; src/Core/FlagsService.php; BiPreviewAccessPolicy referencia control_tower; admin ModulosController.php + views/pages/modulos/index.php

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
