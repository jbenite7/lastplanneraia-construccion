<article class="ds-page-candidate" data-page-structure-candidate="inline-header">
    <div class="ds-page-candidate__head">
        <h3>Encabezado integrado</h3>
    </div>
    <div class="ds-page-specimen ds-page-specimen--inline-header">
        <?= \App\View\Components\DesignSystemComponent::pageHeader([
            'id' => 'lab-page',
            'title' => 'Programa General',
            'context' => 'Semana 6 · Optimización Aeropuerto JMC',
            'headingLevel' => 4,
            'breadcrumb' => [
                ['label' => 'Planificación', 'href' => '#contenido'],
                ['label' => 'Programa General'],
            ],
        ]) ?>
        <div class="ds-page-actions"><button class="aia-btn aia-btn--secondary" type="button" data-page-action>Filtrar</button><button class="aia-btn" type="button" data-page-action>Crear actividad</button></div>
        <div class="ds-page-content"><section class="aia-card" data-page-section><strong>Resumen semanal</strong><p>12 actividades · 3 críticas</p></section><section class="aia-card" data-page-section><strong>Actividades críticas</strong><p>Requieren seguimiento esta semana.</p></section></div>
    </div>
</article>
