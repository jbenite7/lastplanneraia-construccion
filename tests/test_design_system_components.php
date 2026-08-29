<?php
// @requiere: http


require_once __DIR__ . '/../vendor/autoload.php';

use App\View\Components\DesignSystemComponent;

function dsComponentAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$html = DesignSystemComponent::navigation([
    'id' => 'lab-shell', 'brand' => '<AIA>', 'context' => 'Proyecto & Uno', 'active' => 'pg',
    'destinations' => [['id' => 'pg', 'label' => 'Programa <General>', 'href' => '/programa-general'], ['id' => 'pdc', 'label' => 'PDC', 'href' => '/pdc']],
]);
dsComponentAssert(str_contains($html, '&lt;AIA&gt;') && str_contains($html, 'Proyecto &amp; Uno'), 'content must be escaped');
dsComponentAssert(substr_count($html, 'data-shell-destination') === 2, 'one link per destination');
dsComponentAssert(substr_count($html, 'aria-current="page"') === 1, 'exactly one active destination');
dsComponentAssert(str_contains($html, 'data-aia-component="navigation"'), 'canonical component marker');

$sidebar = DesignSystemComponent::navigation([
    'id' => 'lab-sidebar',
    'presentation' => 'sidebar',
    'brand' => '<AIA>',
    'context' => ['project' => 'Proyecto & Uno', 'week' => 'Semana 7'],
    'active' => 'pg',
    'initialState' => 'expanded',
    'groups' => [
        ['id' => 'planning', 'label' => 'Planificación', 'items' => [
            ['id' => 'pg', 'label' => 'Programa <General>', 'href' => '/programa-general', 'icon' => 'program'],
            ['id' => 'future', 'label' => 'Próximamente', 'state' => 'disabled', 'icon' => 'overview'],
        ]],
        ['id' => 'empty', 'label' => 'Sin módulos', 'items' => []],
    ],
    'utilities' => [
        'notifications' => ['label' => 'Avisos', 'count' => 10, 'state' => 'default'],
        'account' => ['label' => 'Usuario · Admin', 'items' => [['label' => 'Cerrar sesión']]],
    ],
]);
dsComponentAssert(str_contains($sidebar, 'data-shell-pattern="sidebar"'), 'sidebar pattern marker');
dsComponentAssert(str_contains($sidebar, 'Proyecto &amp; Uno'), 'sidebar context must be escaped');
dsComponentAssert(str_contains($sidebar, 'Programa &lt;General&gt;'), 'sidebar labels must be escaped');
dsComponentAssert(str_contains($sidebar, 'aria-controls="lab-sidebar-panel"'), 'sidebar toggle controls navigation panel');
dsComponentAssert(substr_count($sidebar, 'aria-current="page"') === 1, 'sidebar has one active destination');
dsComponentAssert(str_contains($sidebar, 'data-sidebar-empty'), 'empty sidebar groups expose an empty state');
dsComponentAssert(str_contains($sidebar, 'data-sidebar-notification-state="default"'), 'notification state is explicit');
dsComponentAssert(str_contains($sidebar, 'aria-label="Navegación de &lt;AIA&gt;"'), 'sidebar landmark has an accessible name');
dsComponentAssert(str_contains($sidebar, 'data-sidebar-notification-retry'), 'sidebar error state exposes recovery control');
dsComponentAssert(!str_contains($sidebar, '#sidebar-item'), 'sidebar never invents disabled destination URLs');

try {
    DesignSystemComponent::navigation([
        'id' => 'invalid-sidebar', 'presentation' => 'sidebar', 'brand' => 'AIA',
        'context' => ['project' => 'Proyecto', 'week' => 'Semana 1'], 'active' => 'missing',
        'groups' => [['id' => 'planning', 'label' => 'Planificación', 'items' => [
            ['id' => 'pg', 'label' => 'Programa', 'href' => '/pg'],
        ]]],
    ]);
    throw new RuntimeException('unknown sidebar active destination accepted');
} catch (InvalidArgumentException) {}

$contextlessSidebar = DesignSystemComponent::navigation([
    'id' => 'lab-contextless',
    'presentation' => 'sidebar',
    'brand' => 'Last Planner AIA',
    'active' => 'proyectos',
    'groups' => [
        ['id' => 'navigation', 'label' => 'Navegación', 'items' => [
            ['id' => 'proyectos', 'label' => 'Tus proyectos', 'href' => '/proyectos', 'icon' => 'project'],
            ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => '/bi/control-tower', 'icon' => 'chart'],
        ]],
    ],
    'utilities' => [
        'notifications' => ['enabled' => false],
        'account' => [
            'label' => 'Usuario · Administrador',
            'items' => [
                // F0/Task 8 retiro themeToggle (dark es el unico tema). Un item sin
                // href sigue siendo valido en el contrato del componente (ver
                // views/design-system/families/shell-navigation.php, 'Cambiar
                // proyecto' documentado sin href) y mantiene cubierta la rama
                // <button> del renderizador junto a la rama <a> de abajo.
                ['label' => 'Cambiar proyecto', 'icon' => 'project'],
                ['label' => 'Cerrar sesión', 'icon' => 'logout', 'href' => '/logout'],
            ],
        ],
    ],
]);
dsComponentAssert(!str_contains($contextlessSidebar, 'aia-sidebar__context'), 'contextless sidebar omits project/week block');
dsComponentAssert(!str_contains($contextlessSidebar, 'data-sidebar-notifications'), 'disabled notifications omit the utility button');
dsComponentAssert(!str_contains($contextlessSidebar, 'data-sidebar-notification-retry'), 'disabled notifications omit the retry control');
dsComponentAssert(!str_contains($contextlessSidebar, 'aia-theme-switch'), 'themeToggle retired: no account item exposes the theme switch hook');
dsComponentAssert(str_contains($contextlessSidebar, '<button type="button" role="menuitem" class="aia-sidebar__account-item">'), 'account item without href renders a real button');
dsComponentAssert(str_contains($contextlessSidebar, 'href="/logout"'), 'account item with href renders a real link');
dsComponentAssert(str_contains($contextlessSidebar, '<span class="aia-sidebar__account-head" role="presentation">Usuario · Administrador</span>'), 'account panel leads with the identity head');
dsComponentAssert(substr_count($contextlessSidebar, 'aia-sidebar__account-item') === 2, 'every account item carries the alignment class (buttons.css exemption)');
dsComponentAssert(str_contains($contextlessSidebar, 'aria-current="page"'), 'contextless sidebar still marks the active destination');

$header = DesignSystemComponent::pageHeader([
    'id' => 'programa-general', 'title' => 'Programa <General>',
    'context' => 'Semana 6 & proyecto', 'headingLevel' => 2,
    'breadcrumb' => [
        ['label' => 'Planificación', 'href' => '/programa-general'],
        ['label' => 'Programa General'],
    ],
]);
dsComponentAssert(str_contains($header, '<h2 id="programa-general-title">Programa &lt;General&gt;</h2>'), 'configured heading and escaping');
dsComponentAssert(str_contains($header, 'aria-labelledby="programa-general-title"'), 'header accessible name');
dsComponentAssert(substr_count($header, 'aria-current="page"') === 1, 'one current breadcrumb');
dsComponentAssert(str_contains($header, 'data-aia-component="page-header"'), 'page header marker');

$actions = DesignSystemComponent::actionGroup([
    'label' => 'Acciones del formulario',
    'actions' => [
        ['label' => 'Guardar <cambios>', 'variant' => 'primary'],
        ['label' => 'Cancelar', 'variant' => 'secondary'],
        ['label' => 'Guardando…', 'variant' => 'primary', 'state' => 'loading'],
        ['label' => 'No disponible', 'variant' => 'secondary', 'state' => 'disabled'],
    ],
]);
dsComponentAssert(str_contains($actions, 'data-aia-component="action-group"'), 'action group marker');
dsComponentAssert(str_contains($actions, 'role="group" aria-label="Acciones del formulario"'), 'action group accessible name');
dsComponentAssert(str_contains($actions, 'Guardar &lt;cambios&gt;'), 'action labels must be escaped');
dsComponentAssert(substr_count($actions, '<button') === 4, 'one button per action');
dsComponentAssert(substr_count($actions, 'aria-busy="true"') === 1, 'loading state is exposed');
dsComponentAssert(substr_count($actions, ' disabled') === 2, 'loading and disabled actions cannot be activated');

$filters = DesignSystemComponent::filterForm([
    'id' => 'lab-filters',
    'label' => 'Filtrar actividades',
    'fields' => [
        ['id' => 'search', 'label' => 'Buscar', 'type' => 'search', 'value' => 'Actividad <crítica>'],
        ['id' => 'responsible', 'label' => 'Responsable', 'type' => 'select', 'value' => 'all', 'options' => [
            ['value' => 'all', 'label' => 'Todos'], ['value' => 'resident', 'label' => 'Residente'],
        ]],
        ['id' => 'status', 'label' => 'Estado', 'type' => 'select', 'value' => 'pending', 'options' => [
            ['value' => 'all', 'label' => 'Todos'], ['value' => 'pending', 'label' => 'Pendiente'],
        ]],
    ],
    'actions' => [
        ['label' => 'Aplicar filtros', 'variant' => 'primary'],
        ['label' => 'Limpiar', 'variant' => 'secondary'],
    ],
]);
dsComponentAssert(str_contains($filters, 'data-aia-component="filter-form"'), 'filter form marker');
dsComponentAssert(str_contains($filters, 'aria-label="Filtrar actividades"'), 'filter form accessible name');
dsComponentAssert(substr_count($filters, '<label') === 3, 'one label per filter field');
dsComponentAssert(substr_count($filters, 'data-filter-field=') === 3, 'one field marker per filter field');
dsComponentAssert(str_contains($filters, 'Actividad &lt;crítica&gt;'), 'filter values must be escaped');
dsComponentAssert(substr_count($filters, ' selected') === 2, 'selected filter values are explicit');
dsComponentAssert(str_contains($filters, 'data-aia-component="action-group"'), 'filter actions reuse the canonical group');

$status = DesignSystemComponent::status(['label' => 'Pendiente de aprobación del responsable', 'tone' => 'warning']);
dsComponentAssert(str_contains($status, 'data-aia-component="status"'), 'status marker');
dsComponentAssert(str_contains($status, 'aia-chip--warning'), 'semantic status tone');
dsComponentAssert(str_contains($status, 'Pendiente de aprobación del responsable'), 'complete status words');
$feedback = DesignSystemComponent::feedback(['message' => 'No se pudo <guardar>', 'tone' => 'critical']);
dsComponentAssert(str_contains($feedback, 'data-aia-component="feedback"'), 'feedback marker');
dsComponentAssert(str_contains($feedback, 'role="alert"'), 'critical feedback is assertive');
dsComponentAssert(str_contains($feedback, 'No se pudo &lt;guardar&gt;'), 'feedback content must be escaped');

$dataDisplay = DesignSystemComponent::dataDisplay([
    'label' => 'Actividades',
    'records' => [
        ['id' => 'ACT-001', 'title' => 'Cimentación <norte>', 'status' => 'A tiempo', 'tone' => 'success', 'progress' => '50 %'],
        ['id' => 'ACT-002', 'title' => 'Redes', 'status' => 'Por comprometer', 'tone' => 'warning', 'progress' => '20 %'],
    ],
]);
dsComponentAssert(str_contains($dataDisplay, 'data-aia-component="data-display"'), 'data display marker');
dsComponentAssert(substr_count($dataDisplay, 'data-record-id="ACT-001"') === 2, 'same record exists in table and cards');
dsComponentAssert(str_contains($dataDisplay, 'Cimentación &lt;norte&gt;'), 'record content must be escaped');
dsComponentAssert(str_contains($dataDisplay, 'aria-label="Actividades en tabla"'), 'table view accessible name');
dsComponentAssert(str_contains($dataDisplay, 'aria-label="Actividades en tarjetas"'), 'card view accessible name');

$dialog = DesignSystemComponent::dialog([
    'id' => 'confirm-change', 'title' => 'Confirmar <cambio>',
    'description' => 'La acción conserva el foco.', 'openLabel' => 'Abrir diálogo', 'closeLabel' => 'Cerrar',
]);
dsComponentAssert(str_contains($dialog, 'data-aia-component="dialog"'), 'dialog marker');
dsComponentAssert(str_contains($dialog, 'aria-controls="confirm-change"'), 'trigger controls the dialog');
dsComponentAssert(str_contains($dialog, 'aria-labelledby="confirm-change-title"'), 'dialog title relationship');
dsComponentAssert(str_contains($dialog, 'aria-describedby="confirm-change-description"'), 'dialog description relationship');
dsComponentAssert(str_contains($dialog, 'Confirmar &lt;cambio&gt;'), 'dialog content must be escaped');

$biFigure = DesignSystemComponent::biFigure([
    'id' => 'weekly-progress', 'title' => 'Avance <semanal>',
    'summary' => 'El ejecutado permanece por debajo del plan.',
    'rows' => [
        ['label' => 'Semana 2', 'plan' => 50, 'executed' => 45],
        ['label' => 'Semana 3', 'plan' => 70, 'executed' => 60],
    ],
]);
dsComponentAssert(str_contains($biFigure, 'data-aia-component="bi-figure"'), 'BI figure marker');
dsComponentAssert(str_contains($biFigure, 'aria-labelledby="weekly-progress-title"'), 'BI title relationship');
dsComponentAssert(str_contains($biFigure, 'aria-describedby="weekly-progress-summary"'), 'BI summary relationship');
dsComponentAssert(substr_count($biFigure, 'data-bi-point=') === 4, 'one visual point per series value');
dsComponentAssert(substr_count($biFigure, 'data-bi-row=') === 2, 'one accessible table row per period');
dsComponentAssert(str_contains($biFigure, 'Avance &lt;semanal&gt;'), 'BI copy must be escaped');
dsComponentAssert(!str_contains($biFigure, 'style='), 'BI figure does not use inline styles');

$decorativeIcon = DesignSystemComponent::icon(['name' => 'filter', 'decorative' => true]);
dsComponentAssert(str_contains($decorativeIcon, 'aria-hidden="true"'), 'decorative icon is hidden');
$labelledIcon = DesignSystemComponent::icon(['name' => 'warning', 'label' => 'Advertencia']);
dsComponentAssert(str_contains($labelledIcon, 'role="img" aria-label="Advertencia"'), 'labelled icon exposes a name');

$search = DesignSystemComponent::search([
    'id' => 'activity-search', 'label' => 'Buscar actividad',
    'value' => 'Redes <norte>', 'actionLabel' => 'Buscar',
]);
dsComponentAssert(str_contains($search, 'role="search"'), 'search landmark');
dsComponentAssert(str_contains($search, 'Redes &lt;norte&gt;'), 'search value escaped');
dsComponentAssert(str_contains($search, 'data-aia-component="search"'), 'search marker');

$pagination = DesignSystemComponent::pagination([
    'label' => 'Páginas de actividades', 'current' => 2, 'total' => 3, 'hrefPattern' => '#page-%d',
]);
dsComponentAssert(str_contains($pagination, 'aria-label="Páginas de actividades"'), 'pagination name');
dsComponentAssert(substr_count($pagination, 'aria-current="page"') === 1, 'one current page');
dsComponentAssert(substr_count($pagination, 'data-page=') === 3, 'one item per page');

$progress = DesignSystemComponent::progress(['label' => 'Importando actividades', 'value' => 60]);
dsComponentAssert(str_contains($progress, '<progress'), 'native progress element');
dsComponentAssert(str_contains($progress, 'value="60" max="100"'), 'progress value exposed');
$liveRegion = DesignSystemComponent::liveRegion(['message' => 'Cambios guardados', 'priority' => 'polite']);
dsComponentAssert(str_contains($liveRegion, 'aria-live="polite"'), 'polite live region');
dsComponentAssert(str_contains($liveRegion, 'aria-atomic="true"'), 'atomic live region');

$menu = DesignSystemComponent::menu([
    'id' => 'row-menu', 'label' => 'Más acciones',
    'items' => [['label' => 'Editar'], ['label' => 'Duplicar']],
]);
dsComponentAssert(str_contains($menu, 'data-aia-component="menu"'), 'menu marker');
dsComponentAssert(str_contains($menu, 'aria-expanded="false"'), 'menu initial state');
dsComponentAssert(substr_count($menu, 'role="menuitem"') === 2, 'menu items');
$popover = DesignSystemComponent::popover([
    'id' => 'activity-help', 'label' => 'Ayuda', 'content' => 'Detalle <persistente>',
]);
dsComponentAssert(str_contains($popover, 'data-aia-component="popover"'), 'popover marker');
dsComponentAssert(str_contains($popover, 'Detalle &lt;persistente&gt;'), 'popover content escaped');

foreach ([['id' => 'x', 'label' => 'X', 'href' => 'javascript:alert(1)'], ['id' => 'pg', 'label' => 'Duplicado', 'href' => '/otro']] as $invalid) {
    try { DesignSystemComponent::navigation(['id' => 'bad', 'brand' => 'AIA', 'context' => 'Proyecto', 'destinations' => [['id' => 'pg', 'label' => 'PG', 'href' => '/pg'], $invalid]]); throw new RuntimeException('invalid destination accepted'); } catch (InvalidArgumentException) {}
}

foreach ([
    ['headingLevel' => 0],
    ['breadcrumb' => []],
    ['breadcrumb' => [['label' => 'Externo', 'href' => 'https://example.com']]],
] as $invalidHeader) {
    $validHeader = [
        'id' => 'header', 'title' => 'Título', 'context' => 'Contexto',
        'headingLevel' => 1, 'breadcrumb' => [['label' => 'Actual']],
    ];
    try { DesignSystemComponent::pageHeader(array_replace($validHeader, $invalidHeader)); throw new RuntimeException('invalid page header accepted'); } catch (InvalidArgumentException) {}
}

foreach ([
    ['label' => '', 'actions' => [['label' => 'Guardar']]],
    ['label' => 'Acciones', 'actions' => []],
    ['label' => 'Acciones', 'actions' => [['label' => 'Guardar', 'variant' => 'danger']]],
    ['label' => 'Acciones', 'actions' => [['label' => 'Guardar', 'state' => 'unknown']]],
] as $invalidActions) {
    try { DesignSystemComponent::actionGroup($invalidActions); throw new RuntimeException('invalid action group accepted'); } catch (InvalidArgumentException) {}
}

foreach ([
    ['id' => 'filters', 'label' => '', 'fields' => [['id' => 'search', 'label' => 'Buscar', 'type' => 'search']], 'actions' => [['label' => 'Aplicar']]],
    ['id' => 'filters', 'label' => 'Filtros', 'fields' => [], 'actions' => [['label' => 'Aplicar']]],
    ['id' => 'filters', 'label' => 'Filtros', 'fields' => [['id' => 'x', 'label' => 'X', 'type' => 'date']], 'actions' => [['label' => 'Aplicar']]],
    ['id' => 'filters', 'label' => 'Filtros', 'fields' => [['id' => 'x', 'label' => 'X', 'type' => 'select', 'options' => []]], 'actions' => [['label' => 'Aplicar']]],
] as $invalidFilters) {
    try { DesignSystemComponent::filterForm($invalidFilters); throw new RuntimeException('invalid filter form accepted'); } catch (InvalidArgumentException) {}
}

foreach ([
    ['label' => '', 'tone' => 'success'],
    ['label' => 'Estado', 'tone' => 'unknown'],
] as $invalidStatus) {
    try { DesignSystemComponent::status($invalidStatus); throw new RuntimeException('invalid status accepted'); } catch (InvalidArgumentException) {}
}
foreach ([
    ['message' => '', 'tone' => 'success'],
    ['message' => 'Resultado', 'tone' => 'unknown'],
] as $invalidFeedback) {
    try { DesignSystemComponent::feedback($invalidFeedback); throw new RuntimeException('invalid feedback accepted'); } catch (InvalidArgumentException) {}
}
foreach ([
    ['label' => '', 'records' => [['id' => 'A', 'title' => 'A', 'status' => 'A', 'tone' => 'info', 'progress' => '0 %']]],
    ['label' => 'Datos', 'records' => []],
    ['label' => 'Datos', 'records' => [['id' => 'bad id', 'title' => 'A', 'status' => 'A', 'tone' => 'info', 'progress' => '0 %']]],
] as $invalidDataDisplay) {
    try { DesignSystemComponent::dataDisplay($invalidDataDisplay); throw new RuntimeException('invalid data display accepted'); } catch (InvalidArgumentException) {}
}
foreach ([
    ['id' => 'bad id', 'title' => 'Título', 'description' => 'Descripción', 'openLabel' => 'Abrir', 'closeLabel' => 'Cerrar'],
    ['id' => 'dialog', 'title' => '', 'description' => 'Descripción', 'openLabel' => 'Abrir', 'closeLabel' => 'Cerrar'],
] as $invalidDialog) {
    try { DesignSystemComponent::dialog($invalidDialog); throw new RuntimeException('invalid dialog accepted'); } catch (InvalidArgumentException) {}
}
foreach ([
    ['id' => 'bi', 'title' => '', 'summary' => 'Resumen', 'rows' => [['label' => 'S1', 'plan' => 50, 'executed' => 40]]],
    ['id' => 'bi', 'title' => 'Título', 'summary' => 'Resumen', 'rows' => []],
    ['id' => 'bi', 'title' => 'Título', 'summary' => 'Resumen', 'rows' => [['label' => 'S1', 'plan' => 101, 'executed' => 40]]],
] as $invalidBiFigure) {
    try { DesignSystemComponent::biFigure($invalidBiFigure); throw new RuntimeException('invalid BI figure accepted'); } catch (InvalidArgumentException) {}
}
foreach (['icon', 'search', 'pagination', 'progress', 'liveRegion', 'menu', 'popover'] as $primitive) {
    try {
        match ($primitive) {
            'icon' => DesignSystemComponent::icon([]),
            'search' => DesignSystemComponent::search([]),
            'pagination' => DesignSystemComponent::pagination([]),
            'progress' => DesignSystemComponent::progress([]),
            'liveRegion' => DesignSystemComponent::liveRegion([]),
            'menu' => DesignSystemComponent::menu([]),
            'popover' => DesignSystemComponent::popover([]),
        };
        throw new RuntimeException("invalid {$primitive} accepted");
    } catch (InvalidArgumentException) {}
}

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

echo "Design system components: PASS\n";
