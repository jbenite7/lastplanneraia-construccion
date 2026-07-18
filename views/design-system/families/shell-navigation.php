<article class="ds-shell-candidate">
    <header class="ds-shell-candidate__head">
        <div><p class="ds-lab__eyebrow">Patrón aprobado</p><h3>Navegación adaptativa</h3></div>
        <span class="aia-chip">Híbrido</span>
    </header>
    <?= \App\View\Components\DesignSystemComponent::navigation([
        'id' => 'lab-shell',
        'brand' => 'Last Planner AIA',
        'context' => 'Optimización Aeropuerto JMC',
        'active' => 'programa-general',
        'destinations' => [
            ['id' => 'programa-general', 'label' => 'Programa General', 'href' => '/programa-general'],
            ['id' => 'programacion-semanal', 'label' => 'Programación Semanal', 'href' => '/programacion-semanal'],
            ['id' => 'pdc', 'label' => 'PDC', 'href' => '/pdc'],
        ],
    ]) ?>
    <div class="ds-shell-utilities" aria-label="Utilidades globales">
        <?= \App\View\Components\DesignSystemComponent::status(['label' => 'Semana 7', 'tone' => 'info']) ?>
        <?= \App\View\Components\DesignSystemComponent::status(['label' => '10 avisos', 'tone' => 'warning']) ?>
        <?= \App\View\Components\DesignSystemComponent::menu([
            'id' => 'lab-account', 'label' => 'Usuario · Administrador',
            'items' => [
                ['label' => 'Cambiar proyecto'], ['label' => 'Cambiar tema'],
                ['label' => 'Cerrar sesión'],
            ],
        ]) ?>
    </div>
    <p class="aia-helper">Contexto visible desde 1200 px; drawer táctil en anchos menores.</p>
</article>
