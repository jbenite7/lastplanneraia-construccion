# Ocultar Control Tower de la navegación — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Quitar Control Tower de toda la navegación de la app, dejando sus rutas abiertas únicamente para el rol Admin (`A`), que las abre por URL directa.

**Architecture:** Un gate nuevo (`BiPreviewAccessPolicy`) apoyado en una capacidad RBAC concedida solo a Admin. Los enlaces desaparecen apagando el punto único por el que ya pasan todos (`BiAccessComponent`); las rutas se protegen en los dos choke points de los controladores BI. No depende del entorno ni de `.env`.

**Tech Stack:** PHP 8.3, sin framework. RBAC propio (`src/Security/`). Tests autónomos estilo `tests/test_*.php` (no hay PHPUnit). Todo se ejecuta dentro del contenedor `app`.

## Global Constraints

- Spec de referencia: `docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md`.
- Todo comando PHP corre dentro de Docker: `docker compose exec app php …`. Nunca un PHP del host.
- No hay PHPUnit ni Pest: los tests son scripts autoejecutables que terminan con `exit(1)` si algo falla.
- Normalizar roles siempre con `App\Security\RbacService::normalizeRole()`; nunca con `RoleManager::cleanCargo()`.
- 404 y nunca 403 al denegar: un 403 confirmaría que la pantalla existe.
- No tocar `BiProjectScope`, `lps.indicadores.ver` ni la lógica interna del BI. El gate se suma, no sustituye.
- Sin cambios de esquema ni migraciones.

---

### Task 1: Capacidad RBAC `internal.bi.preview` para Admin

**Files:**
- Modify: `src/Security/RbacCatalog.php:10` (constante) y `:116` (fila del catálogo)
- Modify: `src/Security/RbacManager.php:29`
- Test: `tests/test_bi_preview_gate.php` (se crea aquí, crece en la Task 2)

**Interfaces:**
- Consumes: nada de tareas anteriores.
- Produces: `RbacCatalog::PERM_INTERNAL_BI_PREVIEW` (string `'internal.bi.preview'`) y la entrada correspondiente en `RbacManager::getCapabilities(string $role): array`, con valor `true` solo para el rol `A`. La Task 2 la consulta vía `RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW)`.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/test_bi_preview_gate.php`:

```php
<?php

declare(strict_types=1);
// @requiere: db

/**
 * El módulo BI (Control Tower) está oculto de la navegación mientras se desarrolla, y
 * solo el rol Admin puede abrirlo por URL directa.
 *
 * Declara `db` y no `puro` porque `RbacService::__construct()` llama a
 * `Database::getInstance()` aunque `normalizeRole()` no consulte nada
 * (src/Security/RbacService.php:14). Correrlo exige el servicio `db` levantado.
 *
 * Ver docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Security\RbacCatalog;
use App\Security\RbacManager;

$fallos = 0;
$total = 0;

function comprobar(string $caso, bool $obtenido, bool $esperado): void
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

echo "Capacidad internal.bi.preview:\n";
comprobar(
    'rol A la tiene',
    RbacManager::hasCapability('A', RbacCatalog::PERM_INTERNAL_BI_PREVIEW),
    true
);
foreach (['D', 'R', 'V', 'C', 'DCV', 'OT', 'S', 'G', 'SG'] as $rol) {
    comprobar("rol {$rol} no la tiene", RbacManager::hasCapability($rol, RbacCatalog::PERM_INTERNAL_BI_PREVIEW), false);
}

echo "\nResultado: " . ($total - $fallos) . "/{$total}\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec app php tests/test_bi_preview_gate.php
```

Esperado: error fatal `Undefined constant App\Security\RbacCatalog::PERM_INTERNAL_BI_PREVIEW`.

- [ ] **Step 3: Declarar la constante y su fila de catálogo**

En `src/Security/RbacCatalog.php`, junto a la constante existente (línea 10):

```php
    public const PERM_INTERNAL_DESIGN_SYSTEM_VIEW = 'internal.design-system.view';
    // Vigente mientras el módulo BI se termina de desarrollar: la navegación no lo
    // muestra a nadie y solo Admin puede abrirlo por URL. Ver el spec del 2026-08-13.
    public const PERM_INTERNAL_BI_PREVIEW = 'internal.bi.preview';
```

Y en el arreglo del catálogo, inmediatamente después de la fila del design system (línea ~116):

```php
            ['key' => self::PERM_INTERNAL_BI_PREVIEW, 'module' => 'internal', 'action' => 'ver_bi_preview', 'description' => 'Abrir el modulo BI mientras esta oculto de la navegacion'],
```

- [ ] **Step 4: Conceder la capacidad solo a Admin**

En `src/Security/RbacManager.php`, justo debajo de la línea 29:

```php
            RbacCatalog::PERM_INTERNAL_DESIGN_SYSTEM_VIEW => $isSystemAdmin,
            RbacCatalog::PERM_INTERNAL_BI_PREVIEW => $isSystemAdmin,
```

- [ ] **Step 5: Correr el test y verificar que pasa**

```bash
docker compose exec app php tests/test_bi_preview_gate.php
```

Esperado: `Resultado: 10/10` y código de salida 0.

- [ ] **Step 6: Commit**

```bash
git add src/Security/RbacCatalog.php src/Security/RbacManager.php tests/test_bi_preview_gate.php
git commit -m "feat(rbac): capacidad internal.bi.preview, solo para Admin"
```

---

### Task 2: `BiPreviewAccessPolicy`, el gate de las rutas

**Files:**
- Create: `src/Security/BiPreviewAccessPolicy.php`
- Modify: `tests/test_bi_preview_gate.php` (añadir casos de sesión)

**Interfaces:**
- Consumes: `RbacCatalog::PERM_INTERNAL_BI_PREVIEW` y `RbacManager::hasCapability()` de la Task 1.
- Produces: `App\Security\BiPreviewAccessPolicy::canOpen(array $session, ?string $roleOverride = null): bool`. El segundo parámetro existe para que el test inyecte el rol sin base de datos; en producción se omite siempre. La Task 3 la llama como `BiPreviewAccessPolicy::canOpen($_SESSION)`.

- [ ] **Step 1: Añadir al test los casos de sesión**

Añadir al final de `tests/test_bi_preview_gate.php`, **antes** de la línea `echo "\nResultado: …`:

```php
echo "\nGate de las rutas (BiPreviewAccessPolicy::canOpen):\n";
comprobar(
    'sesion de Admin abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.A'], 'A'),
    true
);
comprobar(
    'sesion de Residente no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.R'], 'R'),
    false
);
comprobar(
    'sesion de Visualizador no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'test.V'], 'V'),
    false
);
comprobar(
    'sesion vacia no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen([], ''),
    false
);
comprobar(
    'alias de rol se normaliza (RESIDENTE DE OBRA -> R) y no abre',
    \App\Security\BiPreviewAccessPolicy::canOpen(['usuario' => 'x'], 'RESIDENTE DE OBRA'),
    false
);
```

- [ ] **Step 2: Correr el test y verificar que falla**

```bash
docker compose exec app php tests/test_bi_preview_gate.php
```

Esperado: error fatal `Class "App\Security\BiPreviewAccessPolicy" not found`.

- [ ] **Step 3: Escribir la política**

Crear `src/Security/BiPreviewAccessPolicy.php`:

```php
<?php

namespace App\Security;

/**
 * El módulo BI (Control Tower) está oculto de la navegación mientras se desarrolla.
 * Sus rutas siguen vivas, pero solo las abre Admin, que entra por URL directa.
 *
 * El rol se resuelve por usuario y no por el proyecto seleccionado, igual que en
 * DesignSystemLabAccessPolicy: la condición es global, no depende de en qué proyecto
 * estuviera el visitante la última vez.
 *
 * Ver docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md
 */
final class BiPreviewAccessPolicy
{
    /**
     * @param array<string,mixed> $session
     * @param string|null $roleOverride Solo para pruebas: evita tocar la base de datos.
     */
    public static function canOpen(array $session, ?string $roleOverride = null): bool
    {
        $role = $roleOverride === null
            ? self::resolveRole($session)
            : (new RbacService())->normalizeRole($roleOverride);

        return RbacManager::hasCapability($role, RbacCatalog::PERM_INTERNAL_BI_PREVIEW);
    }

    /**
     * @param array<string,mixed> $session
     */
    private static function resolveRole(array $session): string
    {
        $username = trim((string) (
            $session['usuario']
            ?? ($session['admin_user']['usuario'] ?? '')
        ));

        if ($username !== '') {
            try {
                return (new RbacService())->resolveRoleForUser($username);
            } catch (\Throwable) {
                return RbacCatalog::DEFAULT_ROLE;
            }
        }

        return strtoupper(trim((string) (
            $session['permiso']
            ?? ($session['admin_user']['permiso'] ?? RbacCatalog::DEFAULT_ROLE)
        )));
    }
}
```

- [ ] **Step 4: Correr el test y verificar que pasa**

```bash
docker compose exec app php tests/test_bi_preview_gate.php
```

Esperado: `Resultado: 15/15` y código de salida 0.

Si `normalizeRole()` resulta no ser estático, el test del alias falla; en ese caso corrígelo instanciando `new RbacService()` como ya hace el código de arriba, sin cambiar el test.

- [ ] **Step 5: Commit**

```bash
git add src/Security/BiPreviewAccessPolicy.php tests/test_bi_preview_gate.php
git commit -m "feat(bi): politica de acceso al modulo BI oculto"
```

---

### Task 3: Cerrar las rutas `/bi/*` y `/api/bi/*` a quien no sea Admin

**Files:**
- Modify: `src/Controllers/Bi/BiViewController.php` (método `renderView()`, alrededor de la línea 50)
- Modify: `src/Controllers/Api/BiControlTowerApiController.php` (constructor, líneas 27-32)

**Interfaces:**
- Consumes: `BiPreviewAccessPolicy::canOpen(array $session): bool` de la Task 2.
- Produces: nada que consuman tareas posteriores.

- [ ] **Step 1: Cerrar las 8 vistas**

En `src/Controllers/Bi/BiViewController.php`, dentro de `renderView()`, **como primera instrucción del método**, antes de `$this->requireAuth()`:

```php
        // El módulo está oculto mientras se desarrolla: solo Admin lo abre por URL.
        // 404 y no 403, para no confirmar que la pantalla existe.
        if (!\App\Security\BiPreviewAccessPolicy::canOpen($_SESSION)) {
            \App\Core\ErrorPage::render(
                404,
                'Esta página no existe',
                'La dirección que abriste no corresponde a ninguna pantalla del producto. Puede que el enlace esté viejo o mal copiado.'
            );
            exit;
        }
```

- [ ] **Step 2: Cerrar las APIs**

En `src/Controllers/Api/BiControlTowerApiController.php`, al final del constructor (después de `$this->projectScope = new BiProjectScope($this->db);`):

```php
        // Mismo gate que las vistas. ErrorPage devuelve JSON para las rutas /api/*.
        if (!\App\Security\BiPreviewAccessPolicy::canOpen($_SESSION)) {
            \App\Core\ErrorPage::render(
                404,
                'Esta página no existe',
                'La dirección que abriste no corresponde a ninguna pantalla del producto.'
            );
            exit;
        }
```

- [ ] **Step 3: Comprobar en el navegador con Admin (permitido)**

Abrir sesión de Admin y pedir la vista:

```bash
docker compose exec app php -r 'echo "usa el navegador integrado";'
```

En el navegador integrado: `http://localhost:8081/dev/entrar?u=test.A&p=PDC%20Sandbox%20E2E`, luego `http://localhost:8081/bi/control-tower`.
Esperado: la pantalla del Control Tower carga (200), no un 404.

- [ ] **Step 4: Comprobar en el navegador con Residente (denegado)**

`http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, luego `http://localhost:8081/bi/control-tower` y `http://localhost:8081/api/bi/control-tower`.
Esperado: la vista responde 404 con la página de error del producto; la API responde 404 con cuerpo JSON.

Este par cubre la exigencia de AGENTS.md de verificar un rol permitido y uno denegado.

- [ ] **Step 5: Commit**

```bash
git add src/Controllers/Bi/BiViewController.php src/Controllers/Api/BiControlTowerApiController.php
git commit -m "feat(bi): cerrar las rutas del modulo BI a quien no sea Admin"
```

---

### Task 4: Quitar los accesos de toda la navegación

**Files:**
- Modify: `src/View/Components/BiAccessComponent.php` (métodos `canAccess()` y `canAccessAny()`)
- Modify: `src/Legacy/datosGeneralesPagina.php:30`

**Interfaces:**
- Consumes: nada de tareas anteriores (es independiente del gate; podría aplicarse sola).
- Produces: `BiAccessComponent::canAccess()` y `canAccessAny()` devuelven `false` de forma incondicional. `renderLink()` y `renderBootConfig()` ya devuelven cadena vacía cuando eso pasa, así que no se tocan.

- [ ] **Step 1: Apagar el punto único de acceso**

En `src/View/Components/BiAccessComponent.php`, sustituir el cuerpo de los dos métodos, conservando sus firmas:

```php
    /**
     * El módulo BI está oculto de la navegación mientras se termina de desarrollar
     * (spec del 2026-08-13). Devolver false apaga de una vez: barra lateral, selector
     * de proyectos, tarjeta del cajón contextual, los cinco botones «BI …» de los
     * módulos y los boot-configs de JS.
     *
     * Admin sigue entrando por URL directa: eso lo gobierna BiPreviewAccessPolicy,
     * no este componente. Para revertir, restaurar el cuerpo original de ambos
     * métodos, que está en el historial de git.
     */
    public static function canAccess(?string $role = null): bool
    {
        return false;
    }

    public static function canAccessAny(): bool
    {
        return false;
    }
```

Los `use` de `RbacService`, `BiProjectScope` y `Database` quedan sin usar en esos métodos; **no los borres** si otros métodos de la clase los usan. Comprueba con:

```bash
grep -n "RbacService\|BiProjectScope\|Database" src/View/Components/BiAccessComponent.php
```

Si alguno queda sin ninguna referencia, quita solo ese `use` para no dejar imports muertos.

- [ ] **Step 2: Alinear el legado**

En `src/Legacy/datosGeneralesPagina.php`, línea 30, sustituir:

```php
$canAccessBi = (new RbacService($dbInstance))->can('lps.indicadores.ver');
```

por:

```php
// Pasa por el mismo interruptor que la vista: si el módulo está oculto, el JSON que
// consume el JS tiene que decir lo mismo que la barra lateral.
$canAccessBi = \App\View\Components\BiAccessComponent::canAccess();
```

- [ ] **Step 3: Comprobar en el navegador que no queda ningún acceso**

Con sesión de Admin (`test.A`, que es el caso exigente: si desaparece para Admin, desaparece para todos), recorrer y confirmar que **no** aparece ninguna entrada ni botón de Control Tower/BI:

- `http://localhost:8081/proyectos` — el selector de proyectos, sin la tarjeta ni el botón.
- `http://localhost:8081/programacion-semanal` — sin el botón «BI Semanal» en la barra de acciones.
- `http://localhost:8081/programa-general` — sin «BI Programa».
- `http://localhost:8081/programacion-intermedia` — sin «BI Intermedia».
- `http://localhost:8081/profesionales` y `/subcontratistas` — sin sus botones BI.
- La barra lateral en cualquiera de ellas — sin «Control Tower - Informes».
- El cajón contextual (botón «CONCURRENCIA LPS») — sin la tarjeta «Control Tower BI».

Revisar además la consola del navegador: no debe aparecer ningún error nuevo por `window.__BI_ACCESS__` ausente (ya era ausente para los roles sin permiso).

- [ ] **Step 4: Confirmar que Admin sigue entrando por URL**

`http://localhost:8081/bi/control-tower` con la misma sesión: sigue cargando 200. Es lo que separa este cambio de un apagado del módulo.

- [ ] **Step 5: Commit**

```bash
git add src/View/Components/BiAccessComponent.php src/Legacy/datosGeneralesPagina.php
git commit -m "feat(bi): quitar Control Tower de la navegacion mientras se desarrolla"
```

---

### Task 5: Actualizar las suites que codifican el contrato viejo

**Files:**
- Modify: `tests/test_bi_project_scope.php:119-127`
- Modify: `tests/browser/bi_control_tower_access.spec.mjs` (cabecera del `test.describe`)

**Interfaces:**
- Consumes: el `canAccess()`/`canAccessAny()` apagado de la Task 4.
- Produces: nada de código.

Tres suites afirman hoy que los accesos BI **se ven**, porque ese era el contrato hasta este
cambio. No se tocan para tapar una regresión: se actualizan porque el comportamiento
esperado cambió a propósito, y se deja dicho en el propio archivo cómo revertirlo.

Las suites de navegador entran con `test.A` (`tests/browser/fixtures/projects.mjs:16`), que
es Admin, así que **siguen navegando a `/bi/*` sin problema**: `bi_control_tower.spec.mjs`,
`bi-kpi-copy.spec.mjs` y `shell-sidebar-rollout.mjs` no requieren cambios — entran por URL,
que es justo lo que el gate permite a Admin. La única que rompe es la que comprueba los
botones de acceso.

- [ ] **Step 1: Correr las dos suites y ver el fallo antes de tocarlas**

```bash
docker compose exec app php tests/test_bi_project_scope.php
```

Esperado: falla con `global BI access was hidden despite an allowed project`.

```bash
npx playwright test tests/browser/bi_control_tower_access.spec.mjs --workers=1
```

Esperado: falla porque los enlaces «BI …» ya no son visibles.

- [ ] **Step 2: Ajustar el test de alcance de proyectos**

En `tests/test_bi_project_scope.php`, sustituir el bloque de las líneas 119-127 (las dos
comprobaciones sobre `BiAccessComponent`, dejando intactas las de `resolve()` y `globalUrl()`):

```php
        // Las dos comprobaciones de BiAccessComponent que había aquí afirmaban que el
        // acceso global se veía y el contextual no. Desde el 2026-08-13 el componente
        // devuelve false siempre —el módulo está oculto de la navegación mientras se
        // desarrolla— así que ya no distinguen nada. Lo que este test debe seguir
        // protegiendo es el ALCANCE, que no ha cambiado: se comprueba contra
        // BiProjectScope directamente, que es su dueño.
        // Al revertir el ocultamiento, restaurar las dos líneas desde el historial.
        if (!$candidateScope->hasAnyAccess($_SESSION)) {
            $failures[] = 'global BI scope was hidden despite an allowed project';
        }
        if ($candidateScope->canAccessProject($deniedProjectId, $_SESSION)) {
            $failures[] = 'denied active project was reported as accessible';
        }
```

- [ ] **Step 3: Correr el test y verificar que pasa**

```bash
docker compose exec app php tests/test_bi_project_scope.php
```

Esperado: sin fallos.

- [ ] **Step 4: Suspender la suite de puntos de acceso**

En `tests/browser/bi_control_tower_access.spec.mjs`, cambiar `test.describe(` por
`test.describe.skip(` y poner encima este comentario:

```js
// SUSPENDIDA el 2026-08-13, no borrada: esta suite comprueba los seis botones «BI …» de
// los módulos, y esos botones están ocultos a propósito mientras el Control Tower se
// termina de desarrollar (docs/superpowers/specs/2026-08-13-ocultar-control-tower-design.md).
// Su contenido sigue siendo el contrato correcto para cuando el módulo vuelva a la
// navegación: quitar el `.skip` es toda la reversión que necesita.
```

- [ ] **Step 5: Confirmar que queda suspendida y que las otras pasan**

```bash
npx playwright test tests/browser/bi_control_tower_access.spec.mjs --workers=1
```

Esperado: los casos aparecen como `skipped`, ninguno fallado.

```bash
npx playwright test tests/browser/bi_control_tower.spec.mjs --workers=1
```

Esperado: pasa — entra como `test.A`, que sigue teniendo acceso por URL. Si falla con 404,
el gate de la Task 3 está resolviendo mal el rol: revísalo antes de seguir.

- [ ] **Step 6: Commit**

```bash
git add tests/test_bi_project_scope.php tests/browser/bi_control_tower_access.spec.mjs
git commit -m "test(bi): actualizar las suites al contrato de Control Tower oculto"
```

---

### Task 6: Verificación completa y cierre del frente

**Files:**
- Modify: `memoria/log.md` (una línea de bitácora)
- Create: `memoria/decisiones/control-tower-oculto-mientras-se-desarrolla.md`

**Interfaces:**
- Consumes: el resultado de las cuatro tareas anteriores.
- Produces: nada de código.

- [ ] **Step 1: Correr la verificación que exige el área tocada**

```bash
docker compose exec app php tests/test_bi_preview_gate.php
```

```bash
docker compose exec app php tests/test_dev_door_guard.php
```

```bash
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: los dos tests en verde y PHPStan sin errores nuevos respecto al baseline. Si PHPStan marca los parámetros ahora sin usar de `canAccess(?string $role = null)`, deja la firma como está (los consumidores la llaman con argumento) y añade la entrada al baseline solo si el error es realmente nuevo y no evitable.

- [ ] **Step 2: Escribir la nota de decisión en la wiki**

Crear `memoria/decisiones/control-tower-oculto-mientras-se-desarrolla.md` con el frontmatter obligatorio (`tipo: decision`, `estado: vigente`, `fecha: 2026-08-13`, `areas: [bi, rbac]`, `fuente:` el spec, `resumen:` una línea) y un cuerpo que recoja: que los enlaces se ocultan para todos y solo Admin abre por URL, que la primera versión del diseño bloqueaba las rutas y el usuario lo corrigió a mitad, y cómo se revierte. Enlazar con `[[rbac-y-rutas]]`.

- [ ] **Step 3: Anotar la bitácora y pasar el lint de la wiki**

Añadir una línea a `memoria/log.md` con el formato `- 2026-08-13 · ingest · … · [[páginas]]`, y después:

```bash
npm run test:wiki
```

Esperado: «Sin hallazgos».

- [ ] **Step 4: Commit**

```bash
git add memoria/
git commit -m "docs(memoria): Control Tower oculto de la navegacion mientras se desarrolla"
```

- [ ] **Step 5: Publicar, siguiendo el gate de cierre de frente**

```bash
git fetch origin
```

```bash
git status -sb
```

Si hay divergencia, integrar con `git merge origin/main`, resolver a la vista y **volver a correr la verificación del Step 1** antes de publicar. Después, en un comando aparte:

```bash
git push origin main
```

Confirmar con `git status -sb` que no queda `ahead` ni `behind`.
