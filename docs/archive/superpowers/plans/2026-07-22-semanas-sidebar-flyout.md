---
capa: fuente
tipo: plan
estado: cerrado
fecha: 2026-07-22
areas: [proceso]
tags: [archivo]
fuente: docs/archive/superpowers/plans/2026-07-22-semanas-sidebar-flyout.md
resumen: Ítem "Semanas del Proyecto" en el sidebar canónico con flyout de gestión (lista de semanas + crear + eliminar) usando diálogos del design system, contra los…
---

# Flyout "Semanas del Proyecto" con crear/eliminar — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ítem "Semanas del Proyecto" en el sidebar canónico con flyout de gestión (lista de semanas + crear + eliminar) usando diálogos del design system, contra los endpoints legacy existentes.

**Architecture:** El partial `shell_sidebar.php` gana un ítem tipo `action` (extensión aditiva del componente PHP canónico) cuyo flyout reutiliza el mecanismo `.shell-week-flyout`; dos `<dialog class="aia-modal-surface">` server-rendered (solo con permiso RBAC) y un módulo JS nuevo sin jQuery (`shell_week_admin.js`) que replica el flujo legacy (pre-check CIC → POST → 3 desenlaces) con `window.AIA.Notice` y `cambiarSemanaSesion`.

**Tech Stack:** PHP 8.3 (partial + componente DS), CSS en `@layer components`/`legacy-overrides`, JS vanilla, Playwright para el probe, endpoints legacy `nueva_semana.php` / `eliminar_semana.php` / `verificarCICActualizada.php` intactos.

## Global Constraints

- Desktop ≥1180px, solo dark (AGENTS.md); viewport canónico 1180×820.
- Todo comando PHP dentro del contenedor: `docker compose exec -T app php …`.
- La ruta del repo tiene un espacio: citar siempre `cd "/Volumes/Crucial X6/Developer/lps-aia"`.
- Targets de interacción ≥44px (`--ds-target-min`); tokens `--ds-*`/`--aia-*`, sin hex nuevos.
- Paddings/margins de superficies nuevas en `@layer legacy-overrides` (el reset global `* {padding:0}` de styles.css vive en la capa `module` y pisa `components`).
- No tocar la lógica de `src/Legacy/nueva_semana.php` ni `eliminar_semana.php`.
- **Rama**: el worktree puede no estar en `main` (sesión paralela). Antes de cada commit: `git branch --show-current`; si no es `main`, avisar al usuario y no cambiar de rama sin su OK.
- Al declarar "hecho": salida real de comandos de esta sesión (verification-before-completion).

## Datos de referencia (verificados en el código)

- Éxito de `POST /legacy/funciones_generales/php/nueva_semana.php?db={db}` con `{f_inicio_sem, opcion:'nueva_sem'}`: array `[semanaNueva, conteoPDC, ejecucionActualizada, semanalConfirmada]` (src/Legacy/modificar_sem_estado.php:107). Bloqueo por compromisos: mismo shape con `[3] === 0` emitido ANTES de crear (src/Legacy/nueva_semana.php:66) — la condición cliente legacy es `arr[3] == 0 && Number(arr[0]) > 0 && !esAdmin` y en ese caso `arr[0]` es la semana máxima actual sin confirmar. Error: `{respuesta:'ERROR', mensaje}`.
- `POST /legacy/funciones_generales/php/eliminar_semana.php?db={db}` con `{semana, opcion:'eliminar_sem'}` → `{puedeEliminar:'SI'|'NO', maxSemana}`; guard `lps.semana.eliminar`.
- Pre-check: `POST /legacy/funciones_generales/php/verificarCICActualizada.php` con `{db, semana}` → número (≠0 = faltan calificaciones). Ruta limpia de CIC: `/programacion-semanal/cic`.
- Permisos: `lps.semana.crear` / `lps.semana.eliminar` (src/Security/RbacCatalog.php:129-130); `RbacService::can(string $permissionKey, ?string $role)` (src/Security/RbacService.php:94). Instanciar: `new \App\Security\RbacService(\App\Core\Database::getInstance())`.
- Avisos: `window.AIA.Notice.warning|error(message, title)` devuelve Promise (public/js/core/AiaAlertInterceptor.js, cargado siempre por linksComunesHead2.js).
- Redirect de semana: `window.cambiarSemanaSesion(week, path)` (public/js/core/ContextManager.js:93).
- Markup canónico de diálogo: `<div class="aia-dialog" data-aia-component="dialog">…<dialog class="aia-modal-surface" data-aia-dialog aria-labelledby aria-describedby>` (DesignSystemComponent::dialog, L139-155) — estilos en public/css/design-system/components/dialog.css.

---

### Task 1: Ítem tipo `action` en el componente sidebar + iconos `chevron-down`

**Files:**
- Modify: `src/View/Components/DesignSystemComponent.php` (mapa de iconos ~L14-30; `sidebarNavigation()` ~L292-360)
- Test: `tests/test_design_system_components.php`

**Interfaces:**
- Produces: ítem de sidebar `['id','label','icon','action'=>true]` → `<button type="button" class="aia-sidebar__link" data-sidebar-item data-sidebar-action aria-haspopup="menu" aria-expanded="false">` (mismos hijos icon+label que los `<a>`); glifo nuevo `chevron-down` disponible en `DesignSystemComponent::icon(['name'=>'chevron-down'])`.

- [ ] **Step 1: Escribir asserts que fallan** — añadir al final de `tests/test_design_system_components.php` (antes del `echo` final de PASS):

```php
// Sidebar action item (trigger de menú, no navegación)
$actionNav = DesignSystemComponent::navigation([
    'id' => 'shell-action-test',
    'presentation' => 'sidebar',
    'brand' => 'Last Planner AIA',
    'context' => ['project' => 'P', 'week' => 'Semana 1'],
    'groups' => [[
        'id' => 'information',
        'label' => 'Información',
        'items' => [
            ['id' => 'semanas-proyecto', 'label' => 'Semanas del Proyecto', 'icon' => 'calendar', 'action' => true],
            ['id' => 'pg', 'label' => 'Programa General', 'href' => '/programa-general', 'icon' => 'program'],
        ],
    ]],
]);
dsComponentAssert(str_contains($actionNav, '<button type="button" class="aia-sidebar__link"'), 'action item renders as button');
dsComponentAssert(str_contains($actionNav, 'data-sidebar-action'), 'action item is marked');
dsComponentAssert(str_contains($actionNav, 'aria-haspopup="menu"'), 'action item announces menu');
dsComponentAssert(str_contains($actionNav, 'aria-expanded="false"'), 'action item starts collapsed');
dsComponentAssert(!str_contains($actionNav, 'href=""'), 'action item has no empty href');
$chevron = DesignSystemComponent::icon(['name' => 'chevron-down', 'decorative' => true]);
dsComponentAssert(str_contains($chevron, 'aia-icon--chevron-down'), 'chevron-down glyph exists');
```

- [ ] **Step 2: Verificar que falla**

Run: `cd "/Volumes/Crucial X6/Developer/lps-aia" && docker compose exec -T app php tests/test_design_system_components.php`
Expected: FAIL (unknown icon o assert `action item renders as button`)

- [ ] **Step 3: Implementación mínima** — en `DesignSystemComponent.php`:

(a) Añadir al mapa de iconos, tras `'collapse'`:

```php
'chevron-down' => '<path d="m6 10 6 6 6-6"/>',
```

(b) En `sidebarNavigation()`, dentro del bucle de items, tras resolver `$itemState` y ANTES del bloque `$linkAttributes` actual, tratar el tipo action:

```php
$isAction = ($item['action'] ?? false) === true;
if ($isAction) {
    $itemsMarkup[] = '<li><button type="button" class="aia-sidebar__link"'
        . ' data-sidebar-item data-sidebar-action data-destination-id="' . self::escape($itemId) . '"'
        . ' data-sidebar-icon="' . self::escape($icon) . '" title="' . self::escape($label) . '"'
        . ' aria-haspopup="menu" aria-expanded="false">'
        . self::icon(['name' => $icon, 'decorative' => true])
        . '<span class="aia-sidebar__label">' . self::escape($label) . '</span>' . $badge . '</button></li>';
    continue;
}
```

(Nota: `$seen[$itemId]` ya quedó registrado arriba; `aria-current` no aplica a action items — si `$active` apuntara a uno, el throw existente de "unknown active" no se dispara porque está en `$seen`; aceptable: un action item nunca será `active`.)

- [ ] **Step 4: Verificar que pasa**

Run: `docker compose exec -T app php tests/test_design_system_components.php`
Expected: `Design system components: PASS`

- [ ] **Step 5: Gates rápidos y commit**

Run: `docker compose exec -T app php -l src/View/Components/DesignSystemComponent.php` → sin errores.

```bash
git branch --show-current   # confirmar rama con el usuario si no es main
git add src/View/Components/DesignSystemComponent.php tests/test_design_system_components.php
git commit -m "feat(design-system): ítem sidebar tipo action y glifo chevron-down

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Partial — ítem Semanas, JSON ampliado, chevron del chip y diálogos DS

**Files:**
- Modify: `views/partials/shell_sidebar.php`
- Test: Create `tests/test_shell_sidebar_partial.php` (script autoejecutable, patrón de `tests/test_*.php`)

**Interfaces:**
- Consumes: ítem `action` de Task 1; `RbacService::can('lps.semana.crear'|'lps.semana.eliminar', $rol)`.
- Produces: JSON `#shellWeekMenusData` con campos nuevos `db` (string), `esAdmin` (bool), `maxSemana` (int), `canCreate` (bool), `canDelete` (bool); markup `<dialog id="shellWeekCreateDialog">` y `<dialog id="shellWeekDeleteDialog">` (solo con permiso) con los IDs internos que consume Task 4: `#shellWeekCreateDate`, `#shellWeekCreatePreview`, `#shellWeekCreateSubmit`, `#shellWeekDeleteSubmit`, `#shellWeekDeleteText`; chip con chevron.

- [ ] **Step 1: Escribir el test del partial (falla)** — crear `tests/test_shell_sidebar_partial.php`:

```php
<?php

// Contrato del partial shell_sidebar: ítem Semanas, RBAC de gestión y diálogos DS.
define('PROJECT_ROOT', dirname(__DIR__));
require PROJECT_ROOT . '/vendor/autoload.php';

session_start();
$_SESSION['usuario'] = 'contract';
$_SESSION['proyecto'] = 'Proyecto Contrato';
$_SESSION['semana'] = 3;
$_SESSION['db'] = 'contrato_db';
$_SESSION['nombreUsuario'] = 'Contract User';
$_SESSION['area'] = 'Construccion';
$_SESSION['project_id'] = 0;

function renderShellPartial(string $rol): string
{
    $shellActive = 'programacion-intermedia';
    $shellModuleLabel = 'Programación Intermedia';
    $shellWeeks = [
        ['Semana' => 3, 'Fecha_Inicio_Sem' => '2026-06-01', 'Fecha_Fin_Sem' => '2026-06-07'],
        ['Semana' => 2, 'Fecha_Inicio_Sem' => '2026-05-25', 'Fecha_Fin_Sem' => '2026-05-31'],
        ['Semana' => 1, 'Fecha_Inicio_Sem' => '2026-05-18', 'Fecha_Fin_Sem' => '2026-05-24'],
    ];
    $permiso = $rol;
    ob_start();
    require PROJECT_ROOT . '/views/partials/shell_sidebar.php';
    return (string) ob_get_clean();
}

$fails = 0;
$check = function (bool $ok, string $name) use (&$fails): void {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) {
        $fails++;
    }
};

$admin = renderShellPartial('A');
$check(str_contains($admin, 'data-destination-id="semanas-proyecto"'), 'A: ítem Semanas presente');
$check(str_contains($admin, 'data-sidebar-action'), 'A: ítem Semanas es action (botón)');
$check(str_contains($admin, 'shellWeekCreateDialog'), 'A: diálogo crear presente');
$check(str_contains($admin, 'shellWeekDeleteDialog'), 'A: diálogo eliminar presente');
$check(str_contains($admin, '"canCreate":true'), 'A: JSON canCreate true');
$check(str_contains($admin, '"canDelete":true'), 'A: JSON canDelete true');
$check(str_contains($admin, '"db":"contrato_db"'), 'A: JSON db');
$check(str_contains($admin, '"maxSemana":3'), 'A: JSON maxSemana');
$check(str_contains($admin, '"esAdmin":true'), 'A: JSON esAdmin');
$check(str_contains($admin, 'aia-modal-surface'), 'A: diálogos con clase canónica');
$check(str_contains($admin, 'aia-icon--chevron-down'), 'chip con chevron');
$check(substr_count($admin, 'aria-current="page"') === 1, 'A: un solo aria-current');

$viewer = renderShellPartial('V');
$check(str_contains($viewer, 'data-destination-id="semanas-proyecto"'), 'V: ítem Semanas visible (cambio permitido)');
$check(!str_contains($viewer, 'shellWeekCreateDialog'), 'V: sin diálogo crear');
$check(!str_contains($viewer, 'shellWeekDeleteDialog'), 'V: sin diálogo eliminar');
$check(str_contains($viewer, '"canCreate":false'), 'V: JSON canCreate false');

echo $fails === 0 ? "Shell sidebar partial: PASS\n" : "Shell sidebar partial: FAIL ({$fails})\n";
exit($fails === 0 ? 0 : 1);
```

- [ ] **Step 2: Verificar que falla**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
Expected: FAIL (ítem Semanas ausente, JSON sin campos nuevos)

- [ ] **Step 3: Implementar en `views/partials/shell_sidebar.php`**

(a) Tras calcular `$shellRol`, resolver permisos y máximos:

```php
$shellRbac = new \App\Security\RbacService(\App\Core\Database::getInstance());
$shellCanCreate = $shellRbac->can('lps.semana.crear', $shellRol);
$shellCanDelete = $shellRbac->can('lps.semana.eliminar', $shellRol);
$shellEsAdmin = $shellRol === 'A';
$shellDb = (string) ($_SESSION['db'] ?? '');
$shellMaxSemana = 0;
$shellUltimaSemana = null;
foreach ($shellWeeks as $shellW) {
    $shellN = (int) ($shellW['Semana'] ?? 0);
    if ($shellN > $shellMaxSemana) {
        $shellMaxSemana = $shellN;
        $shellUltimaSemana = $shellW;
    }
}
$shellFechaSugerida = '';
if (!empty($shellUltimaSemana['Fecha_Fin_Sem'])) {
    $shellFechaSugerida = date('Y-m-d', strtotime($shellUltimaSemana['Fecha_Fin_Sem'] . ' +1 day'));
}
```

(b) En `$shellInformacion`, insertar como segundo elemento (tras control-tower, antes de profesionales):

```php
['id' => 'semanas-proyecto', 'label' => 'Semanas del Proyecto', 'icon' => 'calendar', 'action' => true],
```

(El array pasa por `array_filter` sin callback en el paso actual — mantener el patrón existente; este ítem es incondicional.)

(c) En el chip `#ctxSemanaBadge`, sustituir el icono FontAwesome por calendario + chevron del DS:

```php
<i class="far fa-calendar-alt" aria-hidden="true"></i>
<span id="ctxSemanaTexto">Semana <?= $shellSemana ?></span>
<?= \App\View\Components\DesignSystemComponent::icon(['name' => 'chevron-down', 'decorative' => true]) ?>
```

(d) Ampliar el JSON `#shellWeekMenusData` (mismo `json_encode`):

```php
'db' => $shellDb,
'esAdmin' => $shellEsAdmin,
'maxSemana' => $shellMaxSemana,
'canCreate' => $shellCanCreate,
'canDelete' => $shellCanDelete,
'fechaSugerida' => $shellFechaSugerida,
'cicPath' => '/programacion-semanal/cic',
```

(e) Tras la context-bar, los diálogos (server-rendered solo con permiso):

```php
<?php if ($shellCanCreate): ?>
<div class="aia-dialog" data-aia-component="dialog">
  <dialog id="shellWeekCreateDialog" class="aia-modal-surface shell-week-dialog" data-aia-dialog
    aria-labelledby="shellWeekCreateTitle" aria-describedby="shellWeekCreateDesc">
    <h3 id="shellWeekCreateTitle">Crear Semana <?= $shellMaxSemana + 1 ?></h3>
    <p id="shellWeekCreateDesc" class="shell-week-dialog__copy">
      La nueva semana copia el programa de la Semana <?= $shellMaxSemana ?> y se convierte en la semana activa del proyecto.
    </p>
    <label class="shell-week-dialog__label" for="shellWeekCreateDate">Fecha de inicio</label>
    <input type="date" id="shellWeekCreateDate" class="shell-week-dialog__date"
      value="<?= htmlspecialchars($shellFechaSugerida, ENT_QUOTES, 'UTF-8') ?>">
    <p class="shell-week-dialog__preview" id="shellWeekCreatePreview" aria-live="polite"></p>
    <div class="shell-week-dialog__actions">
      <button type="button" class="aia-btn" id="shellWeekCreateSubmit">Crear semana</button>
      <button type="button" class="aia-btn aia-btn--secondary" data-aia-dialog-close>Cancelar</button>
    </div>
  </dialog>
</div>
<?php endif; ?>
<?php if ($shellCanDelete && $shellMaxSemana > 0): ?>
<div class="aia-dialog" data-aia-component="dialog">
  <dialog id="shellWeekDeleteDialog" class="aia-modal-surface shell-week-dialog" data-aia-dialog
    aria-labelledby="shellWeekDeleteTitle" aria-describedby="shellWeekDeleteText">
    <h3 id="shellWeekDeleteTitle">Eliminar Semana <?= $shellMaxSemana ?></h3>
    <p id="shellWeekDeleteText" class="shell-week-dialog__copy">
      ¿Eliminar la Semana <?= $shellMaxSemana ?><?= $shellUltimaSemana && !empty($shellUltimaSemana['Fecha_Inicio_Sem']) ? ' (del ' . htmlspecialchars($shellUltimaSemana['Fecha_Inicio_Sem'] . ' al ' . $shellUltimaSemana['Fecha_Fin_Sem'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>?
      Esta acción elimina su programación y no se puede deshacer.
    </p>
    <div class="shell-week-dialog__actions">
      <button type="button" class="aia-btn shell-week-dialog__danger" id="shellWeekDeleteSubmit">Eliminar semana</button>
      <button type="button" class="aia-btn aia-btn--secondary" data-aia-dialog-close>Cancelar</button>
    </div>
  </dialog>
</div>
<?php endif; ?>
```

- [ ] **Step 4: Verificar que pasa**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
Expected: `Shell sidebar partial: PASS`
Run también: `docker compose exec -T app php tests/test_design_system_components.php` (regresión Task 1) y `docker compose exec -T app php -l views/partials/shell_sidebar.php`.

- [ ] **Step 5: Commit**

```bash
git add views/partials/shell_sidebar.php tests/test_shell_sidebar_partial.php
git commit -m "feat(shell): ítem Semanas del Proyecto, JSON de gestión, chevron del chip y diálogos DS

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Flyout de gestión (builder JS del partial) + CSS

**Files:**
- Modify: `views/partials/shell_sidebar.php` (builder del flyout)
- Modify: `public/css/design-system/adapters/shell-sidebar.css`
- Test: `tests/test_shell_sidebar_partial.php` (asserts del builder) + verificación runtime en Task 5

**Interfaces:**
- Consumes: JSON de Task 2; mecanismo `.shell-week-flyout`, `.shell-has-week-menu`, listener delegado `[data-shell-week]` existentes.
- Produces: flyout para `semanas-proyecto` con botón `#shellWeekCreateOpen` (si canCreate), items `[data-shell-week]` SIN `data-shell-path` (recarga página actual), y botón `.shell-week-flyout__delete[data-shell-delete-week]` en la última semana (si canDelete); `aria-expanded` sincronizado en el trigger.

- [ ] **Step 1: Asserts nuevos en el test del partial (fallan)** — añadir a `tests/test_shell_sidebar_partial.php` antes del resumen final:

```php
$check(str_contains($admin, 'shellWeekCreateOpen'), 'A: builder emite botón Nueva semana');
$check(str_contains($admin, 'data-shell-delete-week'), 'A: builder emite trash de última semana');
$check(!str_contains($viewer, 'shellWeekCreateOpen'), 'V: sin botón Nueva semana');
$check(!str_contains($viewer, 'data-shell-delete-week'), 'V: sin trash');
```

(El builder es JS dentro del partial: los strings del template aparecen en el HTML renderizado solo si están dentro de bloques `<?php if ($shellCanCreate): ?>` — por eso el assert funciona server-side.)

- [ ] **Step 2: Verificar que falla**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
Expected: FAIL en los 4 asserts nuevos

- [ ] **Step 3: Implementar el builder** — en el script del partial, dentro del IIFE de flyouts, tras el bucle de `data.modules`, añadir el panel de gestión (el markup de acciones va en bloques PHP condicionales para que el DOM nunca contenga acciones sin permiso):

```js
// Flyout de gestión del ítem "Semanas del Proyecto"
var semTrigger = document.querySelector('[data-shell-pattern="sidebar"] [data-destination-id="semanas-proyecto"]');
if (semTrigger) {
  var semLi = semTrigger.closest('li');
  var semPanel = document.createElement('div');
  semPanel.className = 'shell-week-flyout';
  semPanel.setAttribute('role', 'menu');
  semPanel.setAttribute('aria-label', 'Semanas del Proyecto');

  var semHead = document.createElement('span');
  semHead.className = 'shell-week-flyout__head';
  semHead.textContent = 'Semanas del Proyecto';
  semPanel.appendChild(semHead);

  <?php if ($shellCanCreate): ?>
  var createBtn = document.createElement('button');
  createBtn.type = 'button';
  createBtn.id = 'shellWeekCreateOpen';
  createBtn.className = 'shell-week-flyout__item shell-week-flyout__create';
  createBtn.setAttribute('role', 'menuitem');
  createBtn.innerHTML = '<span>+ Nueva semana</span>';
  semPanel.appendChild(createBtn);
  <?php endif; ?>

  data.weeks.forEach(function (w) {
    var row = document.createElement('div');
    row.className = 'shell-week-flyout__row';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'shell-week-flyout__item';
    btn.setAttribute('role', 'menuitem');
    btn.setAttribute('data-shell-week', String(w.semana));
    if (w.semana === data.currentWeek) btn.setAttribute('aria-current', 'true');
    var label = document.createElement('span');
    label.textContent = 'Semana ' + w.semana;
    btn.appendChild(label);
    if (w.inicio && w.fin) {
      var dates = document.createElement('small');
      dates.textContent = w.inicio + ' – ' + w.fin;
      btn.appendChild(dates);
    }
    row.appendChild(btn);
    <?php if ($shellCanDelete): ?>
    if (w.semana === data.maxSemana) {
      var del = document.createElement('button');
      del.type = 'button';
      del.className = 'shell-week-flyout__delete';
      del.setAttribute('data-shell-delete-week', String(w.semana));
      del.setAttribute('aria-label', 'Eliminar Semana ' + w.semana);
      del.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
      row.appendChild(del);
    }
    <?php endif; ?>
    semPanel.appendChild(row);
  });

  semLi.classList.add('shell-has-week-menu');
  semLi.appendChild(semPanel);
  // Reusar el periodo de gracia: replicar los listeners de los módulos
  semLi.addEventListener('mouseenter', function () {
    clearTimeout(semLi._shellCloseTimer);
    document.querySelectorAll('li.shell-week-open').forEach(function (other) {
      if (other !== semLi) other.classList.remove('shell-week-open');
    });
    semLi.classList.add('shell-week-open');
    semTrigger.setAttribute('aria-expanded', 'true');
  });
  semLi.addEventListener('mouseleave', function () {
    semLi._shellCloseTimer = setTimeout(function () {
      semLi.classList.remove('shell-week-open');
      semTrigger.setAttribute('aria-expanded', 'false');
    }, 350);
  });
  semTrigger.addEventListener('click', function () {
    var open = semLi.classList.toggle('shell-week-open');
    semTrigger.setAttribute('aria-expanded', String(open));
  });
}
```

(Refactor DRY permitido: extraer los listeners de gracia a una función `attachWeekMenuBehavior(li)` compartida con el bucle de módulos, siempre que el probe de Task 5 pase.)

- [ ] **Step 4: CSS** — en `public/css/design-system/adapters/shell-sidebar.css`:

En el bloque `@layer components` de flyouts:

```css
  body.aia-shell--sidebar .shell-week-flyout__row {
    display: flex;
    align-items: stretch;
    gap: var(--ds-space-1);
  }

  body.aia-shell--sidebar .shell-week-flyout__row .shell-week-flyout__item {
    flex: 1 1 auto;
  }

  body.aia-shell--sidebar .shell-week-flyout__create {
    color: var(--ds-active-action-primary);
  }

  body.aia-shell--sidebar .shell-week-flyout__delete {
    display: inline-grid;
    place-items: center;
    min-width: var(--ds-target-min);
    min-height: var(--ds-target-min);
    margin-inline-end: var(--ds-space-2);
    border: var(--ds-border-width) solid transparent;
    border-radius: var(--ds-radius-control-sm);
    background: transparent;
    color: var(--aia-alert-medium);
    cursor: pointer;
  }

  body.aia-shell--sidebar .shell-week-flyout__delete:hover {
    background: color-mix(in srgb, var(--aia-alert-high) 14%, transparent);
    color: var(--aia-alert-high);
  }

  body.aia-shell--sidebar .shell-week-flyout__delete:focus-visible {
    outline: var(--ds-outline-width) solid var(--ds-active-focus-ring);
    outline-offset: var(--ds-outline-offset);
  }
```

Y en `@layer legacy-overrides` (reset global):

```css
  body.aia-shell--sidebar .shell-week-flyout__delete {
    padding: var(--ds-space-1);
    margin-inline-end: var(--ds-space-2);
  }
```

- [ ] **Step 5: Verificar y commit**

Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → PASS.
Run: `docker compose exec -T app php -l views/partials/shell_sidebar.php` y `npx biome check public/css/design-system/adapters/shell-sidebar.css` → limpios.

```bash
git add views/partials/shell_sidebar.php public/css/design-system/adapters/shell-sidebar.css tests/test_shell_sidebar_partial.php
git commit -m "feat(shell): flyout de gestión de Semanas del Proyecto en el rail

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Módulo `shell_week_admin.js` + estilos de diálogo

**Files:**
- Create: `public/js/modules/aia_ui/shell_week_admin.js`
- Modify: `views/partials/shell_sidebar.php` (cargar el script)
- Modify: `public/css/design-system/adapters/shell-sidebar.css` (form del diálogo)
- Test: sintaxis + biome aquí; comportamiento en Task 5

**Interfaces:**
- Consumes: JSON `#shellWeekMenusData` (campos de Task 2), botones de Task 3 (`#shellWeekCreateOpen`, `[data-shell-delete-week]`), diálogos de Task 2, `window.AIA.Notice`, `window.cambiarSemanaSesion`.
- Produces: `window.AiaShellWeekAdmin = { init }` autoarrancado en DOMContentLoaded.

- [ ] **Step 1: Crear `public/js/modules/aia_ui/shell_week_admin.js`**

```js
((global) => {
  function notice(method, message, title) {
    if (global.AIA && global.AIA.Notice && typeof global.AIA.Notice[method] === "function") {
      return global.AIA.Notice[method](message, title);
    }
    global.alert(message);
    return Promise.resolve();
  }

  function postForm(url, fields) {
    const body = new URLSearchParams(fields);
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
    }).then((res) => {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    });
  }

  function setBusy(button, busy) {
    if (!button) return;
    button.disabled = busy;
    button.setAttribute("aria-busy", String(busy));
  }

  function formatEnd(startIso) {
    const d = new Date(startIso + "T00:00:00");
    if (Number.isNaN(d.getTime())) return "";
    d.setDate(d.getDate() + 6);
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return d.getFullYear() + "-" + mm + "-" + dd;
  }

  function init() {
    const dataEl = document.getElementById("shellWeekMenusData");
    if (!dataEl) return;
    let data;
    try {
      data = JSON.parse(dataEl.textContent);
    } catch (_e) {
      return;
    }

    const createDialog = document.getElementById("shellWeekCreateDialog");
    const createOpen = document.getElementById("shellWeekCreateOpen");
    const dateInput = document.getElementById("shellWeekCreateDate");
    const preview = document.getElementById("shellWeekCreatePreview");
    const createSubmit = document.getElementById("shellWeekCreateSubmit");

    function refreshPreview() {
      if (!dateInput || !preview) return;
      const end = formatEnd(dateInput.value);
      preview.textContent = end ? "Irá del " + dateInput.value + " al " + end + "." : "";
    }

    if (createOpen && createDialog) {
      createOpen.addEventListener("click", () => {
        refreshPreview();
        createDialog.showModal();
      });
    }
    if (dateInput) dateInput.addEventListener("input", refreshPreview);

    if (createSubmit && createDialog) {
      createSubmit.addEventListener("click", () => {
        setBusy(createSubmit, true);
        postForm("/legacy/funciones_generales/php/verificarCICActualizada.php", {
          db: data.db,
          semana: String(data.currentWeek),
        })
          .then((faltan) => {
            if (Number(faltan) !== 0) {
              return notice(
                "warning",
                "No se pueden crear nuevas semanas hasta realizar las Calificaciones Integrales " + faltan + ".",
                "Calificación pendiente",
              ).then(() => {
                global.location.assign(data.cicPath);
              });
            }
            return postForm(
              "/legacy/funciones_generales/php/nueva_semana.php?db=" + encodeURIComponent(data.db),
              { f_inicio_sem: dateInput ? dateInput.value : "", opcion: "nueva_sem" },
            ).then((info) => {
              if (info && info.respuesta === "ERROR") {
                return notice("error", info.mensaje || "No se pudo crear la semana.");
              }
              const semana = Number(info && info[0]);
              const confirmada = Number(info && info[3]);
              if (confirmada === 0 && semana > 0 && !data.esAdmin) {
                return notice(
                  "warning",
                  "No se puede crear la Semana " + (semana + 1) + " hasta confirmar los compromisos de la Semana " + semana + ".",
                  "Semana bloqueada",
                ).then(() => {
                  global.cambiarSemanaSesion(semana, "/programacion-semanal");
                });
              }
              createDialog.close();
              global.cambiarSemanaSesion(semana, "/programa-general");
              return undefined;
            });
          })
          .catch((err) => notice("error", "Error al crear la semana: " + err.message))
          .finally(() => setBusy(createSubmit, false));
      });
    }

    const deleteDialog = document.getElementById("shellWeekDeleteDialog");
    const deleteSubmit = document.getElementById("shellWeekDeleteSubmit");
    let deleteWeek = data.maxSemana;

    document.addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-shell-delete-week]");
      if (!trigger || !deleteDialog) return;
      deleteWeek = parseInt(trigger.getAttribute("data-shell-delete-week"), 10);
      deleteDialog.showModal();
    });

    if (deleteSubmit && deleteDialog) {
      deleteSubmit.addEventListener("click", () => {
        setBusy(deleteSubmit, true);
        postForm(
          "/legacy/funciones_generales/php/eliminar_semana.php?db=" + encodeURIComponent(data.db),
          { semana: String(deleteWeek), opcion: "eliminar_sem" },
        )
          .then((info) => {
            if (info && info.puedeEliminar === "SI") {
              deleteDialog.close();
              global.cambiarSemanaSesion(deleteWeek - 1, global.location.pathname);
              return undefined;
            }
            return notice(
              "warning",
              "Solo se puede eliminar la semana máxima del proyecto (Semana " + (info && info.maxSemana) + ").",
              "Acción no permitida",
            );
          })
          .catch((err) => notice("error", "Error al eliminar la semana: " + err.message))
          .finally(() => setBusy(deleteSubmit, false));
      });
    }

    document.querySelectorAll("[data-aia-dialog-close]").forEach((btn) => {
      const dialog = btn.closest("dialog");
      if (dialog && (dialog.id === "shellWeekCreateDialog" || dialog.id === "shellWeekDeleteDialog")) {
        btn.addEventListener("click", () => dialog.close());
      }
    });
  }

  global.AiaShellWeekAdmin = { init };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init, { once: true });
  } else {
    init();
  }
})(window);
```

- [ ] **Step 2: Cargar el script desde el partial** — al final de `shell_sidebar.php`, solo si hay algo que gestionar:

```php
<?php if ($shellCanCreate || $shellCanDelete): ?>
<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/shell_week_admin.js') ?>
<?php endif; ?>
```

- [ ] **Step 3: Estilos del formulario del diálogo** — en `shell-sidebar.css`, `@layer components`:

```css
  .shell-week-dialog {
    min-width: 22rem;
    max-width: 28rem;
  }

  .shell-week-dialog__copy {
    color: var(--ds-active-text-secondary);
  }

  .shell-week-dialog__label {
    display: block;
    color: var(--ds-active-text-primary);
    font-size: var(--ds-type-size-sm);
    font-weight: var(--ds-type-weight-semibold);
  }

  .shell-week-dialog__date {
    width: 100%;
    min-height: var(--ds-target-min);
    border: var(--ds-border-width) solid var(--ds-active-border);
    border-radius: var(--ds-radius-control-sm);
    background: var(--ds-active-surface);
    color: var(--ds-active-text-primary);
    color-scheme: dark;
  }

  .shell-week-dialog__preview {
    color: var(--ds-active-text-primary);
    font-weight: var(--ds-type-weight-semibold);
  }

  .shell-week-dialog__actions {
    display: flex;
    gap: var(--ds-space-3);
    justify-content: flex-end;
  }

  .shell-week-dialog__danger {
    background: var(--aia-alert-high);
    color: #ffffff;
  }
```

Y en `@layer legacy-overrides` (reset global; el hex de `__danger` no: usar token — corregir arriba a `color: var(--ds-active-action-text)`):

```css
  .shell-week-dialog {
    padding: var(--ds-space-6);
  }

  .shell-week-dialog__copy,
  .shell-week-dialog__preview {
    margin-block: var(--ds-space-2) var(--ds-space-3);
  }

  .shell-week-dialog__label {
    margin-block-end: var(--ds-space-1);
  }

  .shell-week-dialog__date {
    padding: var(--ds-space-2) var(--ds-space-3);
  }

  .shell-week-dialog__actions {
    margin-block-start: var(--ds-space-4);
  }
```

(Nota corregida: `.shell-week-dialog__danger { background: var(--aia-alert-high); color: var(--ds-active-action-text); }` — sin `#ffffff` literal.)

- [ ] **Step 4: Gates de sintaxis**

Run: `node --check public/js/modules/aia_ui/shell_week_admin.js` → sin salida.
Run: `npx biome check public/js/modules/aia_ui/shell_week_admin.js public/css/design-system/adapters/shell-sidebar.css` → limpio.
Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → PASS (regresión).

- [ ] **Step 5: Commit**

```bash
git add public/js/modules/aia_ui/shell_week_admin.js views/partials/shell_sidebar.php public/css/design-system/adapters/shell-sidebar.css
git commit -m "feat(shell): módulo shell_week_admin — crear/eliminar semana con diálogos DS

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Probe Playwright durable con endpoints interceptados

**Files:**
- Create: `tests/browser/shell-week-admin.mjs` (estilo standalone del repo, sin runner)

**Interfaces:**
- Consumes: fixture `tests/browser/fixtures/projects.mjs` (`BASE_URL`, `CREDENTIALS`), la vista `/programacion-intermedia` con todo lo anterior.

- [ ] **Step 1: Crear `tests/browser/shell-week-admin.mjs`**

```js
// Contrato runtime del flyout de gestión de Semanas y sus diálogos.
// Endpoints interceptados: nunca muta la BD compartida.
import { chromium } from 'playwright';
import { BASE_URL, CREDENTIALS } from './fixtures/projects.mjs';

const results = [];
const check = (name, ok, detail) => {
  results.push({ name, ok });
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name} — ${detail}`);
};

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

const calls = { cic: [], crear: [], eliminar: [], contexto: [] };
await page.route('**/verificarCICActualizada.php', async (route) => {
  calls.cic.push(route.request().postData());
  await route.fulfill({ contentType: 'application/json', body: '0' });
});
await page.route('**/nueva_semana.php*', async (route) => {
  calls.crear.push({ url: route.request().url(), body: route.request().postData() });
  await route.fulfill({ contentType: 'application/json', body: '[5, 0, 1, 1]' });
});
await page.route('**/eliminar_semana.php*', async (route) => {
  calls.eliminar.push({ url: route.request().url(), body: route.request().postData() });
  await route.fulfill({ contentType: 'application/json', body: '{"puedeEliminar":"SI","maxSemana":4}' });
});

await page.goto(`${BASE_URL}/login`);
await page.locator('#usuario').fill(CREDENTIALS.username);
await page.locator('#password').fill(CREDENTIALS.password);
await Promise.all([
  page.waitForURL((u) => u.pathname === '/proyectos', { timeout: 45000 }),
  page.locator('button[type="submit"]').click(),
]);
await page.locator('.project-item').first().waitFor({ timeout: 45000 });
await page.locator('.project-item button[type="submit"], .project-item .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });
await page.goto(`${BASE_URL}/programacion-intermedia`);
await page.waitForSelector('[data-shell-pattern="sidebar"]', { timeout: 20000 });

// Stub del redirect de contexto para capturar sin navegar
await page.evaluate(() => {
  window.__weekCalls = [];
  window.cambiarSemanaSesion = (week, path) => { window.__weekCalls.push({ week, path }); };
});

const maxSemana = await page.evaluate(() => JSON.parse(document.getElementById('shellWeekMenusData').textContent).maxSemana);

// 1) Estructura del flyout de gestión
const s = await page.evaluate(() => {
  const li = document.querySelector('[data-destination-id="semanas-proyecto"]').closest('li');
  const panel = li.querySelector('.shell-week-flyout');
  return {
    isButton: document.querySelector('[data-destination-id="semanas-proyecto"]').tagName === 'BUTTON',
    hasPanel: !!panel,
    weeks: panel ? panel.querySelectorAll('[data-shell-week]').length : 0,
    createBtn: !!document.getElementById('shellWeekCreateOpen'),
    trash: panel ? panel.querySelectorAll('[data-shell-delete-week]').length : 0,
  };
});
check('ítem Semanas es botón con flyout', s.isButton && s.hasPanel, JSON.stringify(s));
check('flyout lista semanas y acciones', s.weeks > 0 && s.createBtn && s.trash === 1, `weeks=${s.weeks} trash=${s.trash}`);

// 2) Diálogo crear: fecha sugerida + preview viva + flujo interceptado
await page.evaluate(() => document.getElementById('shellWeekCreateOpen').click());
await page.waitForTimeout(200);
const create = await page.evaluate(() => ({
  open: document.getElementById('shellWeekCreateDialog').open,
  fecha: document.getElementById('shellWeekCreateDate').value,
  preview: document.getElementById('shellWeekCreatePreview').textContent,
}));
check('diálogo crear abre con fecha sugerida', create.open && /^\d{4}-\d{2}-\d{2}$/.test(create.fecha), JSON.stringify(create));
check('preview viva calcula el fin (+6 días)', create.preview.includes(create.fecha), create.preview);

await page.evaluate(() => document.getElementById('shellWeekCreateSubmit').click());
await page.waitForTimeout(600);
const afterCreate = await page.evaluate(() => window.__weekCalls);
check('crear: pre-check CIC + POST + redirect a PG semana nueva',
  calls.cic.length === 1 && calls.crear.length === 1
    && calls.crear[0].body.includes('opcion=nueva_sem')
    && afterCreate.some((c) => c.week === 5 && c.path === '/programa-general'),
  JSON.stringify({ cic: calls.cic.length, crear: calls.crear.length, redirects: afterCreate }));

// 3) Diálogo eliminar: flujo interceptado
await page.evaluate(() => document.querySelector('[data-shell-delete-week]').click());
await page.waitForTimeout(200);
const delOpen = await page.evaluate(() => document.getElementById('shellWeekDeleteDialog').open);
check('diálogo eliminar abre desde el trash', delOpen, String(delOpen));
await page.evaluate(() => document.getElementById('shellWeekDeleteSubmit').click());
await page.waitForTimeout(600);
const afterDelete = await page.evaluate(() => window.__weekCalls);
check('eliminar: POST correcto + redirect a semana-1',
  calls.eliminar.length === 1
    && calls.eliminar[0].body.includes('opcion=eliminar_sem')
    && afterDelete.some((c) => c.week === maxSemana - 1),
  JSON.stringify({ eliminar: calls.eliminar.length, redirects: afterDelete }));

await browser.close();
const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} checks OK`);
process.exit(failed.length ? 1 : 0);
```

- [ ] **Step 2: Ejecutar (primer run real)**

Run: `cd "/Volumes/Crucial X6/Developer/lps-aia" && node tests/browser/shell-week-admin.mjs`
Expected: `N/N checks OK` (si algo falla, arreglar implementación — no el test — salvo error del propio test).

- [ ] **Step 3: Gates de regresión del shell**

Run: `node tests/test_foundation_shell_contract.mjs` → `CONTRACT OK` (nota: es probable que NO requiera cambios; si falla por el partial, revisar qué assert y decidir con criterio — no adaptar tests para ocultar regresiones).
Run: `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → PASS.

- [ ] **Step 4: Commit**

```bash
git add tests/browser/shell-week-admin.mjs
git commit -m "test(shell): contrato runtime del flyout de Semanas con endpoints interceptados

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 6: Validación visual y cierre

**Files:** ninguno nuevo (evidencia y ajustes menores si el visual lo exige)

- [ ] **Step 1: Validación visual en el navegador integrado** (regla de cierre de sprint del usuario): `preview_start` → login fixture → `/programacion-intermedia` a 1180×820 dark → hover en "Semanas del Proyecto" (flyout con Nueva semana + trash) → abrir diálogo crear (fecha + preview) → screenshot para el usuario. Consola sin errores nuevos.
- [ ] **Step 2: Actualizar el manifiesto** — añadir a `docs/design-system/manifests/foundation-shell.json`: `"public/js/modules/aia_ui/shell_week_admin.js"` en `sources` y `"tests/test_shell_sidebar_partial.php"`, `"tests/browser/shell-week-admin.mjs"` en `tests`. Verificar `node scripts/design-system-contracts.mjs` (recordar: exige worktree limpio — correr tras el commit final).
- [ ] **Step 3: Commit final**

```bash
git add docs/design-system/manifests/foundation-shell.json
git commit -m "docs(shell): manifiesto foundation-shell cubre la gestión de semanas

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 4: Reporte al usuario** — qué se verificó (comandos + salidas), pendientes (push si lo pide, rama actual del worktree), y demo en el panel.
