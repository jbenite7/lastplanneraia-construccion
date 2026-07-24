<?php

/**
 * Shell global con sidebar canónico (DS-027, piloto foundation-shell).
 *
 * Variables esperadas (con fallbacks a sesión):
 * - $shellActive       string  id del ítem activo (p.ej. 'programacion-intermedia')
 * - $shellModuleLabel  string  etiqueta del módulo para la context-bar
 * - $shellWeeks        array   [['Semana' => int, 'Fecha_Inicio_Sem' => ?, 'Fecha_Fin_Sem' => ?], ...]
 * - $proyecto, $semana, $permiso, $nombreUsuario, $area (getSessionVars)
 *
 * La vista consumidora debe además: añadir la clase `aia-shell--sidebar` al body,
 * definir `window.__AIA_SHELL_SIDEBAR__ = true` antes de cargarDatosGeneralesPagina2.js
 * y cargar /js/modules/aia_ui/sidebar_navigation.js.
 */

$shellActive = $shellActive ?? '';
$shellModuleLabel = $shellModuleLabel ?? '';
$shellWeeks = is_array($shellWeeks ?? null) ? $shellWeeks : [];
$shellProyecto = (string) ($proyecto ?? ($_SESSION['proyecto'] ?? ''));
$shellSemana = (int) ($semana ?? ($_SESSION['semana'] ?? 0));
$shellNombre = (string) ($nombreUsuario ?? ($_SESSION['nombreUsuario'] ?? 'Usuario'));
$shellArea = (string) ($area ?? ($_SESSION['area'] ?? 'Construccion'));
$shellRol = \Admin\Core\RoleManager::normalizeRole($permiso ?? ($_SESSION['permiso'] ?? ''));

$shellRbac = new \App\Security\RbacService(\Database::getInstance());
$shellCanCreate = $shellRbac->can('lps.semana.crear', $shellRol);
$shellCanDelete = $shellRbac->can('lps.semana.eliminar', $shellRol);
$shellEsAdmin = $shellRol === 'A';
$shellDb = (string) ($_SESSION['db'] ?? '');
$shellWeekCsrf = \App\Security\CsrfTokenManager::generate('lps_week_admin');
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

// Transcripción server-side de la visibilidad legacy de maestroPermisos
// (cargarDatosGeneralesPagina2.js): qué ítems de navegación NO ve cada rol.
$shellHiddenByRole = [
    'G' => ['profesionales', 'subcontratistas', 'listado-actividades', 'contratos', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
    'S' => ['profesionales', 'subcontratistas', 'listado-actividades', 'contratos', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
    'SG' => ['profesionales', 'subcontratistas', 'listado-actividades', 'contratos', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
    'C' => ['profesionales', 'subcontratistas', 'listado-actividades', 'contratos', 'plan-compras', 'actualizar-cronograma', 'control-cambios', 'programa-general', 'programacion-intermedia'],
    'V' => ['actualizar-cronograma', 'control-cambios'],
    'OT' => ['actualizar-cronograma'],
    'DCV' => ['actualizar-cronograma'],
];
$shellHidden = $shellHiddenByRole[$shellRol] ?? [];
if ($shellArea === 'Pre-Construccion') {
    $shellHidden = array_merge($shellHidden, ['listado-actividades', 'contratos', 'plan-compras']);
}
// Nunca ocultar el módulo en el que el usuario ya está.
$shellHidden = array_values(array_diff(array_unique($shellHidden), [$shellActive]));

$shellItem = static function (string $id, string $label, string $href, string $icon) use ($shellHidden): ?array {
    if (in_array($id, $shellHidden, true)) {
        return null;
    }
    return ['id' => $id, 'label' => $label, 'href' => $href, 'icon' => $icon];
};

$shellInformacion = array_values(array_filter([
    \App\View\Components\BiAccessComponent::canAccess()
        ? ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => \App\View\Components\BiAccessComponent::url('control-tower'), 'icon' => 'chart']
        : null,
    ['id' => 'semanas-proyecto', 'label' => 'Semanas del Proyecto', 'icon' => 'calendar', 'action' => true],
    $shellItem('profesionales', 'Profesionales', '/profesionales', 'user'),
    $shellItem('subcontratistas', 'Subcontratistas', '/subcontratistas', 'contract'),
    $shellItem('indicadores', 'Indicadores LPS', '/indicadores', 'overview'),
    $shellItem('control-cambios', 'Control de Cambios', '/control-cambios', 'integration'),
]));

$shellObra = array_values(array_filter([
    $shellItem('programa-general', 'Programa General', '/programa-general', 'program'),
    $shellItem('programacion-intermedia', 'Programación Intermedia', '/programacion-intermedia', 'tasks'),
    $shellItem('programacion-semanal', 'Programación Semanal', '/programacion-semanal', 'calendar'),
    $shellItem('actualizar-cronograma', 'Actualizar Cronograma', '/programa-general-actualizar', 'sync'),
]));

$shellCompras = array_values(array_filter([
    $shellItem('listado-actividades', 'Familias de Actividades', '/listado-actividades', 'list'),
    $shellItem('contratos', 'Paquetes de Contratación', '/contratos', 'package'),
    $shellItem('plan-compras', 'Plan de Compras', '/pdc', 'clipboard'),
]));

$shellGroups = array_values(array_filter([
    $shellInformacion !== [] ? ['id' => 'information', 'label' => 'Información', 'items' => $shellInformacion] : null,
    $shellObra !== [] ? ['id' => 'obra', 'label' => 'Obra', 'items' => $shellObra] : null,
    $shellCompras !== [] ? ['id' => 'compras', 'label' => 'Compras', 'items' => $shellCompras] : null,
]));
?>
<div data-sidebar-persist>
<?= \App\View\Components\DesignSystemComponent::navigation([
    'id' => 'app-shell',
    'presentation' => 'sidebar',
    'brand' => 'Last Planner AIA',
    'initialState' => 'collapsed',
    // El contexto proyecto/semana no se duplica en el encabezado del sidebar:
    // ya vive en la context-bar (#ctxProyecto / #ctxModulo / chip de semana).
    'active' => $shellActive,
    'groups' => $shellGroups,
    'utilities' => [
        // Avisos: la campana runtime (notifications.js) se integra en el rollout;
        // el piloto no muestra un contador estático que mentiría.
        'notifications' => ['enabled' => false],
        'account' => [
            'label' => 'Usuario · ' . $shellNombre,
            'items' => [
                ['label' => 'Cambiar proyecto', 'href' => '/proyectos', 'icon' => 'project'],
                ['label' => 'Cambiar tema', 'themeToggle' => true, 'icon' => 'theme'],
                ['label' => 'Cerrar sesión', 'href' => '/logout', 'icon' => 'logout'],
            ],
        ],
    ],
]) ?>
</div>

<div class="context-bar" id="shellContextBar">
  <span id="ctxProyecto"><?= htmlspecialchars($shellProyecto, ENT_QUOTES, 'UTF-8') ?></span>
  <span aria-hidden="true">/</span>
  <span id="ctxModulo"><?= htmlspecialchars($shellModuleLabel, ENT_QUOTES, 'UTF-8') ?></span>
  <div class="aia-menu context-week-menu" data-aia-component="menu">
    <button type="button" class="context-week-chip" id="ctxSemanaBadge" data-aia-menu-trigger
      aria-haspopup="menu" aria-controls="ctxWeekMenu" aria-expanded="false"
      <?= $shellSemana > 0 ? '' : 'style="display: none;"' ?>>
      <i class="far fa-calendar-alt" aria-hidden="true"></i>
      <span id="ctxSemanaTexto">Semana <?= $shellSemana ?></span>
      <?= \App\View\Components\DesignSystemComponent::icon(['name' => 'chevron-down', 'decorative' => true]) ?>
    </button>
    <div id="ctxWeekMenu" data-aia-menu-panel role="menu" hidden>
      <?php foreach ($shellWeeks as $shellWeek): ?>
        <?php $shellWeekNum = (int) ($shellWeek['Semana'] ?? 0); ?>
        <?php if ($shellWeekNum < 1) { continue; } ?>
        <button type="button" role="menuitem" data-shell-week="<?= $shellWeekNum ?>"
          <?= $shellWeekNum === $shellSemana ? 'aria-current="true"' : '' ?>>
          <span>Semana <?= $shellWeekNum ?></span>
          <?php if (!empty($shellWeek['Fecha_Inicio_Sem']) && !empty($shellWeek['Fecha_Fin_Sem'])): ?>
            <small><?= htmlspecialchars('Del ' . $shellWeek['Fecha_Inicio_Sem'] . ' al ' . $shellWeek['Fecha_Fin_Sem'], ENT_QUOTES, 'UTF-8') ?></small>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php if ($shellCanCreate): ?>
<div class="aia-dialog" data-aia-component="dialog">
  <dialog id="shellWeekCreateDialog" class="aia-modal-surface shell-week-dialog" data-aia-dialog
    aria-labelledby="shellWeekCreateTitle" aria-describedby="shellWeekCreateDesc">
    <h3 id="shellWeekCreateTitle">Crear Semana <?= $shellMaxSemana + 1 ?></h3>
    <p id="shellWeekCreateDesc" class="shell-week-dialog__copy">
      <?php if ($shellMaxSemana > 0): ?>
        La nueva semana copia el programa de la Semana <?= $shellMaxSemana ?> y se convierte en la semana activa del proyecto.
      <?php else: ?>
        Será la primera semana del proyecto: se construye desde el Programa Maestro.
      <?php endif; ?>
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
<script type="application/json" id="shellWeekMenusData"><?= json_encode([
    'currentWeek' => $shellSemana,
    'weeks' => array_values(array_filter(array_map(static fn ($w) => [
        'semana' => (int) ($w['Semana'] ?? 0),
        'inicio' => (string) ($w['Fecha_Inicio_Sem'] ?? ''),
        'fin' => (string) ($w['Fecha_Fin_Sem'] ?? ''),
    ], $shellWeeks), static fn ($w) => $w['semana'] > 0)),
    'modules' => [
        'programa-general' => ['label' => 'Programa General', 'path' => '/programa-general'],
        'programacion-intermedia' => ['label' => 'Programación Intermedia', 'path' => '/programacion-intermedia'],
        'programacion-semanal' => ['label' => 'Programación Semanal', 'path' => '/programacion-semanal'],
    ],
    'db' => $shellDb,
    'esAdmin' => $shellEsAdmin,
    'maxSemana' => $shellMaxSemana,
    'canCreate' => $shellCanCreate,
    'canDelete' => $shellCanDelete,
    'fechaSugerida' => $shellFechaSugerida,
    'cicPath' => '/programacion-semanal/cic',
    'csrfToken' => $shellWeekCsrf,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script>
  // Cambio de semana (chip de contexto y flyouts del rail): endpoint legacy de contexto.
  document.addEventListener('click', function (event) {
    var item = event.target.closest('[data-shell-week]');
    if (!item) return;
    var week = parseInt(item.getAttribute('data-shell-week'), 10);
    if (!week || typeof window.cambiarSemanaSesion !== 'function') return;
    window.cambiarSemanaSesion(week, item.getAttribute('data-shell-path') || window.location.pathname);
  });

  // Flyout de semanas por módulo (PG/PI/PS): panel alineado con la píldora del rail.
  (function () {
    var dataEl = document.getElementById('shellWeekMenusData');
    if (!dataEl) return;
    var data;
    try { data = JSON.parse(dataEl.textContent); } catch (_e) { return; }
    if (!data || !Array.isArray(data.weeks) || data.weeks.length === 0) return;

    Object.keys(data.modules).forEach(function (moduleId) {
      var link = document.querySelector('[data-shell-pattern="sidebar"] [data-destination-id="' + moduleId + '"]');
      if (!link) return;
      var li = link.closest('li');
      if (!li) return;
      var module = data.modules[moduleId];
      var isActiveModule = link.getAttribute('aria-current') === 'page';

      var panel = document.createElement('div');
      panel.className = 'shell-week-flyout';
      panel.setAttribute('role', 'menu');
      panel.setAttribute('aria-label', 'Semanas de ' + module.label);

      var head = document.createElement('span');
      head.className = 'shell-week-flyout__head';
      head.textContent = module.label;
      panel.appendChild(head);

      data.weeks.forEach(function (w) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'shell-week-flyout__item';
        btn.setAttribute('role', 'menuitem');
        btn.setAttribute('data-shell-week', String(w.semana));
        btn.setAttribute('data-shell-path', module.path);
        if (isActiveModule && w.semana === data.currentWeek) btn.setAttribute('aria-current', 'true');
        var label = document.createElement('span');
        label.textContent = 'Semana ' + w.semana;
        btn.appendChild(label);
        if (w.inicio && w.fin) {
          var dates = document.createElement('small');
          dates.textContent = 'Del ' + w.inicio + ' al ' + w.fin;
          btn.appendChild(dates);
        }
        panel.appendChild(btn);
      });

      li.classList.add('shell-has-week-menu');
      li.appendChild(panel);

      // Periodo de gracia al salir: un trayecto diagonal hacia el panel cruza
      // brevemente fuera del li y el cierre por :hover puro sería instantáneo.
      // El estado .shell-week-open aguanta 350ms y re-entrar lo cancela.
      li.addEventListener('mouseenter', function () {
        clearTimeout(li._shellCloseTimer);
        document.querySelectorAll('li.shell-week-open').forEach(function (other) {
          if (other !== li) other.classList.remove('shell-week-open');
        });
        li.classList.add('shell-week-open');
      });
      li.addEventListener('mouseleave', function () {
        li._shellCloseTimer = setTimeout(function () {
          li.classList.remove('shell-week-open');
        }, 350);
      });
    });

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
          dates.textContent = 'Del ' + w.inicio + ' al ' + w.fin;
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

    // Menú de cuenta: abre por hover con el mismo periodo de gracia que los
    // flyouts de semana. No basta CSS: Bootstrap declara
    // [hidden]{display:none!important} y el panel del componente usa [hidden],
    // así que el estado se gobierna por atributo (igual que el click de
    // components.js, que sigue funcionando para teclado y lectores).
    var account = document.querySelector('[data-shell-pattern="sidebar"] .aia-sidebar__account');
    if (account) {
      var accTrigger = account.querySelector('[data-aia-menu-trigger]');
      var accPanel = account.querySelector('[data-aia-menu-panel]');
      if (accTrigger && accPanel) {
        var setAccOpen = function (open) {
          accTrigger.setAttribute('aria-expanded', String(open));
          accPanel.hidden = !open;
        };
        account.addEventListener('mouseenter', function () {
          clearTimeout(account._shellCloseTimer);
          setAccOpen(true);
        });
        account.addEventListener('mouseleave', function () {
          account._shellCloseTimer = setTimeout(function () {
            setAccOpen(false);
          }, 350);
        });
      }
    }
  })();
</script>
<?php if ($shellCanCreate || $shellCanDelete): ?>
<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/shell_week_admin.js') ?>
<?php endif; ?>
