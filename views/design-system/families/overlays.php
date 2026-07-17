<article data-overlay-candidate="modal-drawer">
    <header><p class="ds-lab__eyebrow">Patrón aprobado</p><h3>Modal y drawer responsive</h3><p>La semántica y el foco permanecen; solo cambia la presentación espacial.</p></header>
    <?= \App\View\Components\DesignSystemComponent::dialog([
        'id' => 'lab-dialog',
        'title' => 'Confirmar cambio',
        'description' => 'La acción conserva el foco y permite cerrar con Escape.',
        'openLabel' => 'Abrir diálogo',
        'closeLabel' => 'Cerrar',
    ]) ?>
    <div class="ds-primitive-row">
        <?= \App\View\Components\DesignSystemComponent::menu([
            'id' => 'lab-menu', 'label' => 'Más acciones',
            'items' => [['label' => 'Editar'], ['label' => 'Duplicar']],
        ]) ?>
        <?= \App\View\Components\DesignSystemComponent::popover([
            'id' => 'lab-help', 'label' => 'Ver ayuda',
            'content' => 'Detalle contextual que no bloquea la página.',
        ]) ?>
    </div>
</article>
