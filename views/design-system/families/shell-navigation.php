<article class="ds-shell-candidate ds-shell-candidate--sidebar">
    <header class="ds-shell-candidate__head">
        <h3>Sidebar canónico</h3>
        <span class="aia-chip aia-chip--success">Desktop</span>
    </header>
    <?= \App\View\Components\DesignSystemComponent::navigation([
        'id' => 'lab-shell',
        'presentation' => 'sidebar',
        'brand' => 'Last Planner AIA',
        'context' => [
            'project' => 'Optimización Aeropuerto JMC',
            'week' => 'Semana 7',
        ],
        'active' => 'programa-general',
        'initialState' => 'expanded',
        'groups' => [
            [
                'id' => 'information',
                'label' => 'Información',
                'items' => [
                    ['id' => 'control-tower', 'label' => 'Control Tower - Informes', 'href' => '/bi/control-tower', 'icon' => 'chart'],
                    ['id' => 'project-weeks', 'label' => 'Semanas del Proyecto', 'href' => '#project-weeks', 'icon' => 'calendar', 'state' => 'disabled'],
                    ['id' => 'professionals', 'label' => 'Profesionales', 'href' => '/profesionales', 'icon' => 'user'],
                    ['id' => 'subcontractors', 'label' => 'Subcontratistas', 'href' => '/subcontratistas', 'icon' => 'contract'],
                ],
            ],
            [
                'id' => 'obra',
                'label' => 'Obra',
                'items' => [
                    ['id' => 'programa-general', 'label' => 'Programa General', 'href' => '/programa-general', 'icon' => 'program'],
                    ['id' => 'programacion-intermedia', 'label' => 'Programación Intermedia', 'href' => '/programacion-intermedia', 'icon' => 'tasks'],
                    ['id' => 'programacion-semanal', 'label' => 'Programación Semanal', 'href' => '/programacion-semanal', 'icon' => 'calendar'],
                ],
            ],
            [
                'id' => 'compras',
                'label' => 'Compras',
                'items' => [
                    ['id' => 'activity-families', 'label' => 'Familias de Actividades', 'href' => '/listado-actividades', 'icon' => 'list'],
                    ['id' => 'contracts', 'label' => 'Paquetes de Contratación', 'href' => '/contratos', 'icon' => 'contract'],
                    ['id' => 'pdc', 'label' => 'Plan de Compras', 'href' => '/pdc', 'icon' => 'clipboard'],
                ],
            ],
        ],
        'utilities' => [
            'notifications' => ['label' => 'Avisos', 'count' => 10, 'state' => 'default'],
            'account' => [
                'label' => 'Usuario · Administrador',
                'items' => [
                    ['label' => 'Cambiar proyecto'],
                    ['label' => 'Cambiar tema'],
                    ['label' => 'Cerrar sesión'],
                ],
            ],
        ],
    ]) ?>
    <div class="ds-shell-state-controls" role="group" aria-label="Estados del sidebar">
        <button type="button" class="aia-btn aia-btn--secondary" data-sidebar-state-action="default">Default</button>
        <button type="button" class="aia-btn aia-btn--secondary" data-sidebar-state-action="loading">Loading</button>
        <button type="button" class="aia-btn aia-btn--secondary" data-sidebar-state-action="empty">Empty</button>
        <button type="button" class="aia-btn aia-btn--secondary" data-sidebar-state-action="error">Error</button>
    </div>
    <p class="aia-helper">Rail persistente desktop; el drawer inferior a 1200 px permanece como compatibilidad legacy.</p>
</article>
