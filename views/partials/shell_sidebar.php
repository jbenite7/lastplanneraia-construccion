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
    'context' => [
        'project' => $shellProyecto !== '' ? $shellProyecto : 'Proyecto',
        'week' => $shellSemana > 0 ? 'Semana ' . $shellSemana : 'Sin semana activa',
    ],
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
    </button>
    <div id="ctxWeekMenu" data-aia-menu-panel role="menu" hidden>
      <?php foreach ($shellWeeks as $shellWeek): ?>
        <?php $shellWeekNum = (int) ($shellWeek['Semana'] ?? 0); ?>
        <?php if ($shellWeekNum < 1) { continue; } ?>
        <button type="button" role="menuitem" data-shell-week="<?= $shellWeekNum ?>"
          <?= $shellWeekNum === $shellSemana ? 'aria-current="true"' : '' ?>>
          <span>Semana <?= $shellWeekNum ?></span>
          <?php if (!empty($shellWeek['Fecha_Inicio_Sem']) && !empty($shellWeek['Fecha_Fin_Sem'])): ?>
            <small><?= htmlspecialchars($shellWeek['Fecha_Inicio_Sem'] . ' – ' . $shellWeek['Fecha_Fin_Sem'], ENT_QUOTES, 'UTF-8') ?></small>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
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
          dates.textContent = w.inicio + ' – ' + w.fin;
          btn.appendChild(dates);
        }
        panel.appendChild(btn);
      });

      li.classList.add('shell-has-week-menu');
      li.appendChild(panel);
    });
  })();
</script>
