<div class="ds-candidate-grid ds-filter-comparison">
    <article class="ds-filter-candidate" data-filter-candidate="inline-fields">
        <header><p class="ds-lab__eyebrow">Patrón aprobado</p><h3>Filtros siempre visibles</h3><p>Los criterios permanecen disponibles sin introducir pasos adicionales.</p></header>
        <?= \App\View\Components\DesignSystemComponent::filterForm([
            'id' => 'lab-filters', 'label' => 'Filtrar actividades',
            'fields' => [
                ['id' => 'search', 'label' => 'Buscar', 'type' => 'search', 'value' => 'Actividad crítica'],
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
        ]) ?>
        <?= \App\View\Components\DesignSystemComponent::search([
            'id' => 'lab-search', 'label' => 'Buscar actividad',
            'value' => 'Redes norte', 'actionLabel' => 'Buscar',
        ]) ?>
        <?= \App\View\Components\DesignSystemComponent::pagination([
            'label' => 'Páginas de actividades', 'current' => 2,
            'total' => 3, 'hrefPattern' => '#page-%d',
        ]) ?>
    </article>
</div>
<section class="ds-control-gallery" aria-labelledby="control-gallery-title">
    <h3 id="control-gallery-title">Controles de formulario</h3>
    <label class="aia-field"><span>Nombre de actividad</span><input class="aia-input" value="Cimentación"></label>
    <label class="aia-field"><span>Observaciones</span><textarea class="aia-textarea">Revisión semanal</textarea></label>
    <label class="aia-field"><span>Responsable</span><select class="aia-select"><option>Ana Torres</option><option>Carlos Ruiz</option></select></label>
    <div class="aia-field"><span id="reviewers-combobox-label">Responsables de revisión</span>
        <span class="select2 select2-container select2-container--default select2-container--multiple aia-select2-reference" data-select2-multi>
            <span class="selection"><span class="select2-selection select2-selection--multiple" role="combobox" aria-labelledby="reviewers-combobox-label" aria-expanded="false" aria-haspopup="listbox">
                <span class="select2-selection__rendered">
                    <span class="select2-selection__choice">Ana Torres <button type="button" class="select2-selection__choice__remove" aria-label="Quitar Ana Torres">×</button></span>
                    <span class="select2-selection__choice">Carlos Ruiz <button type="button" class="select2-selection__choice__remove" aria-label="Quitar Carlos Ruiz">×</button></span>
                </span>
            </span></span>
        </span>
    </div>
    <label class="aia-field"><span>Fecha de inicio</span><input class="aia-input" type="date" value="2026-07-13"></label>
    <label class="aia-choice"><input type="checkbox" checked><span>Actividad crítica</span></label>
    <label class="aia-choice"><input type="radio" name="priority" checked><span>Prioridad normal</span></label>
    <label class="aia-switch"><input type="checkbox" role="switch" checked><span>Activar proyección</span></label>
    <label class="aia-field"><span>Importar archivo</span><input class="aia-input" type="file"></label>
    <label class="aia-field"><span>Código</span><input class="aia-input" aria-describedby="code-help"><small class="aia-helper" id="code-help">Usa el identificador visible del programa.</small></label>
    <label class="aia-field"><span>Campo con error</span><input class="aia-input" aria-invalid="true" aria-describedby="field-error"><small class="aia-feedback aia-feedback--critical" id="field-error">Revisa este valor.</small></label>
</section>
