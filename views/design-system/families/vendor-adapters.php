<p class="ds-lab__eyebrow">Patrón aprobado · skins centrales tokenizadas</p>
<div class="ds-vendor-grid">
    <article class="ds-vendor-fixture ds-vendor-fixture--grid" data-vendor-fixture="handsontable" data-adapter="canonical">
        <header>
            <p class="ds-lab__eyebrow">Grilla operativa</p>
            <h3>Handsontable</h3>
            <p class="ds-vendor-fixture__lede">Edición tabular para compromisos con responsables, avance y validación antes de guardar.</p>
        </header>
        <div class="ds-vendor-fixture__summary">
            <span class="aia-chip aia-chip--success" data-handsontable-autosave>Autoguardado activo</span>
            <span>4 actividades visibles</span>
        </div>
        <div class="ds-vendor-toolbar" role="group" aria-label="Acciones de la grilla">
            <span>Rango activo: B2:D3 · último guardado hace 2 min</span>
            <div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button" data-vendor-action="handsontable-undo">Deshacer</button><button class="aia-btn" type="button" data-vendor-action="handsontable-add">Añadir actividad</button></div>
        </div>
        <p class="ds-vendor-status" role="status" aria-live="polite" data-handsontable-status>Autoguardado activo. Usa Tab para avanzar entre celdas.</p>
        <div class="handsontable ds-vendor-grid-table" tabindex="0" role="region" aria-label="Grilla editable de compromisos">
            <table class="htCore">
                <thead><tr><th scope="col">Actividad</th><th scope="col">Responsable</th><th scope="col">Avance</th><th scope="col">Estado</th><th scope="col">Acción</th></tr></thead>
                <tbody data-handsontable-rows>
                    <tr><th scope="row">Cimentación</th><td>Ana Torres</td><td><label class="aia-visually-hidden" for="vendor-progress-1">Avance de Cimentación</label><span class="ds-number-input"><input class="aia-input" id="vendor-progress-1" type="number" min="0" max="100" value="50" data-vendor-grid-editor><span aria-hidden="true">%</span></span></td><td><select class="aia-select" aria-label="Estado de Cimentación" data-vendor-grid-editor><option>En ejecución</option><option>En riesgo</option></select></td><td><button class="aia-btn aia-btn--secondary" type="button">Detalle</button></td></tr>
                    <tr><th scope="row">Redes hidrosanitarias</th><td>Carlos Ruiz</td><td><label class="aia-visually-hidden" for="vendor-progress-2">Avance de Redes hidrosanitarias</label><span class="ds-number-input"><input class="aia-input" id="vendor-progress-2" type="number" min="0" max="100" value="20" data-vendor-grid-editor><span aria-hidden="true">%</span></span></td><td><select class="aia-select" aria-label="Estado de Redes hidrosanitarias" data-vendor-grid-editor><option>En riesgo</option><option>Bloqueado</option></select></td><td><button class="aia-btn aia-btn--secondary" type="button">Detalle</button></td></tr>
                    <tr><th scope="row">Acero de refuerzo</th><td>Laura Gómez</td><td>80 %</td><td><span class="aia-chip aia-chip--success">A tiempo</span></td><td><button class="aia-btn aia-btn--secondary" type="button">Detalle</button></td></tr>
                </tbody>
            </table>
        </div>
        <footer class="ds-vendor-grid-footer"><span>3 de 4 actividades · último cambio hace 2 min</span><button class="aia-btn aia-btn--secondary" type="button" data-vendor-action="handsontable-add">Insertar fila</button></footer>
    </article>

    <article class="ds-vendor-fixture ds-vendor-fixture--select" data-vendor-fixture="select2" data-adapter="canonical">
        <header>
            <p class="ds-lab__eyebrow">Selección enriquecida</p>
            <h3>Select2</h3>
            <p class="ds-vendor-fixture__lede">Búsqueda, estado de disponibilidad y limpieza para asignar un responsable sin perder contexto.</p>
        </header>
        <label class="aia-field" for="lab-select2-search"><span>Responsable AIA</span><span class="aia-helper">Requerido · muestra el cargo y la disponibilidad antes de confirmar.</span></label>
        <span class="select2 select2-container select2-container--default">
            <span class="selection"><button type="button" class="select2-selection select2-selection--single" role="combobox" aria-expanded="false" aria-controls="lab-select2-options" aria-label="Responsable AIA" data-select2-preview-toggle><span class="select2-selection__rendered" data-select2-preview-value>Ana Torres · Residente</span></button></span>
            <span class="select2-dropdown" data-select2-preview-dropdown hidden>
                <label class="aia-visually-hidden" for="lab-select2-search">Buscar responsable</label><input class="aia-input" id="lab-select2-search" type="search" placeholder="Buscar por nombre o cargo" data-select2-search>
                <span class="select2-results"><span class="select2-results__meta" data-select2-result-count>3 responsables disponibles</span><span class="select2-results__options" id="lab-select2-options" role="listbox" aria-label="Responsables de prueba">
                    <button class="select2-results__option" type="button" role="option" aria-selected="true" data-select2-value="Ana Torres · Residente" data-select2-search-value="ana torres residente disponible">Ana Torres <small>Residente · Disponible</small></button>
                    <button class="select2-results__option" type="button" role="option" aria-selected="false" data-select2-value="Carlos Ruiz · Director" data-select2-search-value="carlos ruiz director disponible">Carlos Ruiz <small>Director · Disponible</small></button>
                    <button class="select2-results__option" type="button" role="option" aria-selected="false" data-select2-value="Laura Gómez · Oficina Técnica" data-select2-search-value="laura gomez oficina tecnica en revision">Laura Gómez <small>Oficina Técnica · En revisión</small></button>
                </span></span>
                <span class="aia-feedback aia-feedback--info" data-select2-empty hidden>No se encontraron responsables. Prueba con otro nombre o cargo.</span>
            </span>
        </span>
        <div class="ds-vendor-select-footer"><p class="ds-vendor-status" role="status" aria-live="polite" data-select2-status>Seleccionado: Ana Torres · Residente.</p><button class="aia-btn aia-btn--secondary" type="button" data-select2-clear>Limpiar selección</button></div>
    </article>

    <article class="ds-vendor-fixture ds-vendor-fixture--alert" data-vendor-fixture="sweetalert2" data-adapter="canonical">
        <header>
            <p class="ds-lab__eyebrow">Confirmación con impacto</p>
            <h3>SweetAlert2</h3>
            <p class="ds-vendor-fixture__lede">Una confirmación clara para cambios que afectan compromisos, responsables o trazabilidad.</p>
        </header>
        <div class="swal2-popup aia-glass-popup" role="alertdialog" aria-labelledby="vendor-alert-title" aria-describedby="vendor-alert-description" data-sweetalert-popup>
            <div class="ds-vendor-alert__heading"><div class="ds-vendor-alert__signal" aria-hidden="true">!</div><div><span class="aia-chip aia-chip--warning" data-sweetalert-chip>Revisión requerida</span><h4 class="swal2-title" id="vendor-alert-title" data-sweetalert-title>¿Aplicar 2 cambios?</h4><p class="swal2-html-container" id="vendor-alert-description" data-sweetalert-description>Los cambios actualizan el compromiso semanal y quedan registrados en la auditoría.</p></div></div>
            <dl class="ds-vendor-alert__impact"><div><dt>Actividad</dt><dd>Cimentación · avance 50 % → 65 %</dd></div><div><dt>Responsable</dt><dd>Redes hidrosanitarias · Carlos Ruiz</dd></div><div><dt>Control</dt><dd>1 regla requiere validación posterior</dd></div></dl>
            <p class="aia-feedback aia-feedback--warning">Podrás deshacer la aplicación desde el historial de cambios.</p>
            <p class="ds-vendor-status" role="status" aria-live="polite" data-sweetalert-status>Esperando decisión del responsable.</p>
            <div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button" data-sweetalert-action="cancel">Cancelar</button><button class="aia-btn" type="button" data-sweetalert-action="confirm">Aplicar cambios</button></div>
        </div>
    </article>
</div>
