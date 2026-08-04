<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <!-- jQuery Must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Head Resources (Nav, CSS, etc) -->
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('subcontratistas') ?>
    <link rel="stylesheet" href="/css/subcontratistas.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/subcontratistas.css') ?: 'sub1')) ?>" />
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>

    <!-- Handsontable CSS -->
    <!-- Handsontable CSS llega vía attach-handsontable.css (design system); el link crudo duplicaba la cascada y pisaba dark mode. -->
</head>
<body class="aia-shell aia-shell--sidebar sub-page">
<?php $isPreConstruccion = (($area ?? $_SESSION['area'] ?? 'Construccion') === 'Pre-Construccion'); ?>

    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <!-- Estructura Original de Navegación -->
    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="info_subcontratistas">
    </div>

    <div class="row direccionSeccion">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <!-- Hidden inputs for Context -->
    <input type="hidden" id="baseDatos" value="<?php echo $_SESSION['db'] ?? 'Prueba'; ?>">
    <input type="hidden" id="permiso_canonico" value="<?php echo $_SESSION['permiso'] ?? 'V'; ?>">

    <div class="header-actions action-bar">
        <div>
            <h4><?php echo $isPreConstruccion ? 'Interesados Externos (Live Edición)' : 'Subcontratistas (Live Edición)'; ?></h4>
            <?php if ($isPreConstruccion): ?>
                <small class="text-muted d-block mt-1">Gestión de interesados externos del proyecto: Socios, Ventas, Gerencia, Diseñadores, Entidades.</small>
            <?php endif; ?>
        </div>
        <div class="header-actions-group">
            <span id="save-status" class="aia-chip aia-chip--success sub-save-flag">Guardado</span>
            <span id="save-error" class="aia-chip aia-chip--danger sub-save-flag">Error al guardar</span>
            <button id="btn-export" class="aia-btn aia-btn--secondary" onclick="exportCSV()"><i class="fas fa-file-excel"></i> Exportar</button>
            <?= \App\View\Components\BiAccessComponent::renderLink('subcontratistas', $isPreConstruccion ? 'BI Interesados' : 'BI Contratistas') ?>
        </div>
    </div>

    <!-- Handsontable Container (Desktop) -->
    <div id="hot-container"></div>

    </div>

    <!-- Bootstrap Dependencies (Required for Navbar Dropdowns) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- Common Scripts for Navigation (Depends on jQuery) -->
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('subcontratistas') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/hot_table_width.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>

    <!-- Handsontable Scripts -->
    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <!-- Languages -->
    <script src="/public/vendor/handsontable/es-MX.js"></script>

    <script>
        const container = document.getElementById('hot-container');
        let hot;
        let persistedSubcontratistas = [];
        var dbPrefix = "<?php echo $_SESSION['db'] ?? 'Prueba'; ?>";

        $(document).ready(function() {
            // Load Navigation
            cargarDatosGeneralesPagina(document.getElementById('seccion').value);

            // Pre-Construccion: Rebrand sidebar & breadcrumb labels
            <?php if ($isPreConstruccion): ?>
            setTimeout(function() {
                // Override sidebar active label
                var sidebarLinks = document.querySelectorAll('.nav-link.active, .sidebar .active a, #navbarSupportedContent .active a');
                sidebarLinks.forEach(function(el) {
                    if (el.textContent.trim() === 'Subcontratistas') {
                        el.textContent = 'Interesados Externos';
                    }
                });
                // Override breadcrumb
                var breadcrumbItems = document.querySelectorAll('.breadcrumb-item, .breadcrumb li');
                breadcrumbItems.forEach(function(el) {
                    if (el.textContent.trim() === 'Sub-Contratistas' || el.textContent.trim() === 'Subcontratistas') {
                        el.textContent = 'Interesados Externos';
                    }
                });
                // Override page section title if injected by nav
                var dirSeccion = document.getElementById('textoDireccionSeccion');
                if (dirSeccion && dirSeccion.textContent.trim() === 'Sub-Contratistas') {
                    dirSeccion.textContent = 'Interesados Externos';
                }
            }, 500);
            <?php endif; ?>

            // Recalcular columnas cuando cambia el tamaño de la ventana
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (hot && window.innerWidth > 768) {
                        hot.updateSettings({
                            colWidths: function(colIndex) {
                                const containerWidth = document.getElementById('hot-container').offsetWidth;
                                const percentages = [0, 18, 18, 8, 18, 22, 6, 10];
                                return Math.floor(containerWidth * (percentages[colIndex] / 100));
                            }
                        });
                        hot.render();
                    }
                }, 150);
            });
        });

        // Callback called by cargarDatosGeneralesPagina2.js when nav is ready
        function cargaParametros() {
            loadData();
        }

        function loadData() {
            const db = document.getElementById('baseDatos').value;
            $.ajax({
                url: '/api/subcontratistas/list?db=' + db + '&_t=' + new Date().getTime(),
                type: 'POST',
                data: { opcion: 'listar' },
                dataType: 'json',
                success: function(response) {
                    $('#loading').fadeOut(300, function() {
                        // Response format might be { data: [...] } or just [...] dependong on old endpoint.
                        // Checking subcontratistas list endpoint usually returns {data: [...]}
                        var data = response.data || response;

                        if(Array.isArray(data)) {
                            persistedSubcontratistas = data
                                .filter((row) => row && (row.Id || row.id))
                                .map((row) => ({
                                    Id: row.Id || row.id || null,
                                    subcontratista: row.subcontratista || '',
                                    correo_contacto: row.correo_contacto || '',
                                    NIT: row.NIT || '',
                                    alcance: row.alcance || '',
                                    tipo_proveedor: row.tipo_proveedor || '',
                                    activo: row.activo ? 1 : 0,
                                }));

                            updateOrInitHandsontable(data);

                            // Force render after container is visible and layout settled.
                            // Double render with delay to allow wordWrap + stretchH to settle
                            setTimeout(() => {
                                if (hot) {
                                     hot.render();
                                     // Sincronizar ANTES de recalcular alturas:
                                     // al fijar el ancho cambian los saltos de
                                     // linea. Ver hot_table_width.js.
                                     window.AIA.sincronizarAnchoTabla(document.getElementById('hot-container'));
                                     hot.getPlugin('autoRowSize').recalculateAllRowsHeight();
                                     hot.render();
                                }
                            }, 200);
                        } else {
                            if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error cargando datos: Formato inesperado");
                        }
                    });
                },
                error: function(err) {
                    console.error(err);
                    $('#loading').fadeOut();
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error de red al cargar datos.");
                }
            });
        }

        const providerTypes = <?php echo json_encode($isPreConstruccion
            ? ['Socio', 'Ventas', 'Gerencia', 'Diseñador', 'Consultor', 'Entidad', 'Interventoría', 'Cliente', 'Inversionista', 'Promotor']
            : ['Mano de Obra', 'Suministro e Instalación', 'Suministro de Materiales, Herramientas o Equipos']); ?>;

        function normalizeTextValue(value) {
            return (value || '').toString().trim().replace(/\s+/g, ' ');
        }

        function normalizeEmailValue(value) {
            return (value || '').toString().trim().toLowerCase();
        }

        function normalizeNitValue(value) {
            return (value || '').toString().trim();
        }

        function normalizeNitForCompare(value) {
            return normalizeNitValue(value).replace(/[^a-zA-Z0-9]/g, '');
        }

        function buildSubcontratistaPayload(rowData) {
            return {
                subcontratista: normalizeTextValue(rowData && rowData.subcontratista),
                correo_contacto: normalizeEmailValue(rowData && rowData.correo_contacto),
                NIT: normalizeNitValue(rowData && rowData.NIT),
                alcance: normalizeTextValue(rowData && rowData.alcance),
                tipo_proveedor: normalizeTextValue(rowData && rowData.tipo_proveedor),
                activo: rowData && (rowData.activo === true || rowData.activo === 1 || rowData.activo === '1') ? 1 : 0
            };
        }

        function isSubcontratistaDraftEmpty(payload) {
            return !payload.subcontratista && !payload.correo_contacto && !payload.NIT && !payload.alcance && !payload.tipo_proveedor;
        }

        function isSubcontratistaDraftComplete(payload) {
            return !!(payload.subcontratista && payload.correo_contacto && payload.NIT && payload.alcance && payload.tipo_proveedor);
        }

        function collectSubcontratistaValidationErrors(payload, options) {
            const validationOptions = options || {};
            const currentId = validationOptions.currentId || null;
            const excludeRowData = validationOptions.excludeRowData || null;
            const errors = [];
            if (!payload.subcontratista) errors.push('<?php echo $isPreConstruccion ? 'El nombre del interesado es obligatorio.' : 'El nombre del subcontratista es obligatorio.'; ?>');
            if (!payload.correo_contacto) {
                errors.push('El correo de contacto es obligatorio.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.correo_contacto)) {
                errors.push('El correo de contacto no tiene un formato valido.');
            }
            if (!payload.NIT) errors.push('El NIT es obligatorio.');
            if (!payload.alcance) errors.push('El alcance es obligatorio.');
            if (!payload.tipo_proveedor) {
                errors.push('El tipo de proveedor es obligatorio.');
            } else if (providerTypes.indexOf(payload.tipo_proveedor) === -1) {
                errors.push('El tipo de proveedor seleccionado no es valido.');
            }

            const rows = persistedSubcontratistas;
            rows.forEach((row) => {
                if (!row) return;
                if (excludeRowData && row === excludeRowData) return;
                const rowId = row.Id || row.id || null;
                if (currentId && String(rowId) === String(currentId)) return;
                const candidate = buildSubcontratistaPayload(row);

                if (payload.subcontratista && candidate.subcontratista && payload.subcontratista.toLowerCase() === candidate.subcontratista.toLowerCase()) {
                    errors.push('<?php echo $isPreConstruccion ? 'Ya existe un interesado con ese nombre.' : 'Ya existe un subcontratista con ese nombre.'; ?>');
                }
                if (payload.correo_contacto && candidate.correo_contacto && payload.correo_contacto === candidate.correo_contacto) {
                    errors.push('<?php echo $isPreConstruccion ? 'Ya existe un interesado con ese correo.' : 'Ya existe un subcontratista con ese correo.'; ?>');
                }
                if (payload.NIT && candidate.NIT && normalizeNitForCompare(payload.NIT) === normalizeNitForCompare(candidate.NIT)) {
                    errors.push('<?php echo $isPreConstruccion ? 'Ya existe un interesado con esa identificación.' : 'Ya existe un subcontratista con ese NIT.'; ?>');
                }
            });

            return [...new Set(errors)];
        }

        function showValidationMessage(errors, type) {
            if (!errors || !errors.length || !(window.AIA && window.AIA.Notice)) return;
            const method = type || 'warning';
            const message = errors.join('\n');
            if (window.AIA.Notice[method]) {
                window.AIA.Notice[method](message);
            }
        }

        function revertHandsontableChanges(instance, visualRow, changes) {
            changes.forEach((change) => {
                instance.setDataAtRowProp(visualRow, change.prop, change.oldValue, 'revert');
            });
        }

        function updateOrInitHandsontable(data) {
            if (hot) {
                hot.loadData(data);
                return;
            }

            hot = new Handsontable(container, {
                data: data,
                rowHeaders: false,

                colHeaders: [<?php echo $isPreConstruccion ? "'ID', 'Interesado', 'Correo Contacto', 'Identificación', 'Rol/Interés', 'Tipo de Interesado', 'Activo', 'Acciones'" : "'ID', 'Subcontratista', 'Correo Contacto', 'NIT', 'Alcance', 'Tipo Proveedor', 'Activo', 'Acciones'"; ?>],
                columns: [
                    { data: 'Id', readOnly: true, className: 'htCenter htMiddle' },
                    { data: 'subcontratista', type: 'text', className: 'htCenter htMiddle force-wrap' },
                    { data: 'correo_contacto', type: 'text', className: 'htCenter htMiddle force-wrap' },
                    { data: 'NIT', type: 'text', className: 'htCenter htMiddle' },
                    { data: 'alcance', type: 'text', className: 'htCenter htMiddle force-wrap' },
                     {
                         data: 'tipo_proveedor',
                         type: 'dropdown',
                         className: 'htCenter htMiddle force-wrap',
                         source: providerTypes
                     },
                    {
                        data: 'activo',
                        type: 'checkbox',
                        className: 'htCenter htMiddle'
                    },
                    {
                       data: 'accion',
                       renderer: 'html',
                       readOnly: true,
                       className: 'htCenter htMiddle'
                    }
                ],
                // Anchos en porcentaje: ID(0%), Subcontratista(18%), Correo(18%), NIT(8%), Alcance(18%), TipoProveedor(22%), Activo(6%), Acciones(10%) = 100%
                colWidths: function(colIndex) {
                    const containerWidth = document.getElementById('hot-container').offsetWidth;
                    const percentages = [0, 18, 18, 8, 18, 22, 6, 10]; // ID oculto = 0%
                    return Math.floor(containerWidth * (percentages[colIndex] / 100));
                },
                hiddenColumns: {
                    columns: [0],
                    indicators: false
                },
                contextMenu: true,
                manualRowResize: true,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                language: 'es-MX',
                stretchH: 'none', // Desactivar stretch, usamos colWidths dinámico
                minSpareRows: 1,
                wordWrap: true,
                autoRowSize: true,
                width: '100%',
                height: '100%',

                cells: function(row, col) {
                    const cellProperties = {};
                    if (col === 7) { // Acciones column
                         cellProperties.renderer = function(instance, td, row, col, prop, value, cellProperties) {
                             Handsontable.renderers.HtmlRenderer.apply(this, arguments);
                             const physicalRow = instance.toPhysicalRow(row);
                             const rowData = instance.getSourceDataAtRow(physicalRow);

                             // Matching logic from profesionales for Delete button
                             // In subcontratistas, the ID key is 'Id'
                             if (rowData && rowData.Id && rowData.has_dependencies) {
                                 td.innerHTML = '<button class="aia-btn aia-btn--secondary aia-btn--sm" disabled title="No se puede eliminar: tiene registros asociados en otros módulos del proyecto."><i class="fas fa-lock"></i></button>';
                             } else {
                                 // Perfect circular button via .btn-delete CSS class
                                 td.innerHTML = '<button class="aia-btn aia-btn--critical aia-btn--sm btn-delete" aria-label="Eliminar subcontratista" title="Eliminar subcontratista"><i class="fas fa-trash" aria-hidden="true"></i></button>';
                             }
                         }
                    }
                    return cellProperties;
                },
                afterChange: function(changes, source) {
                    if (source === 'loadData' || source === 'revert' || !changes) return;

                    const instance = this;
                    const groupedChanges = new Map();

                    changes.forEach(([row, prop, oldValue, newValue]) => {
                        if (oldValue === newValue || prop === 'accion') return;
                        const physicalRow = instance.toPhysicalRow(row);
                        if (!groupedChanges.has(physicalRow)) {
                            groupedChanges.set(physicalRow, { visualRow: row, changes: [] });
                        }
                        groupedChanges.get(physicalRow).changes.push({ prop, oldValue, newValue });
                    });

                    groupedChanges.forEach(({ visualRow, changes: rowChanges }, physicalRow) => {
                        const rowData = instance.getSourceDataAtRow(physicalRow);
                        if (!rowData) return;

                        const payload = buildSubcontratistaPayload(rowData);
                        const id = rowData.Id || rowData.id || null;

                        if (!id) {
                            if (isSubcontratistaDraftEmpty(payload)) return;
                            const draftErrors = collectSubcontratistaValidationErrors(payload, { excludeRowData: rowData });
                            if (draftErrors.length) {
                                if (isSubcontratistaDraftComplete(payload)) {
                                    showValidationMessage(draftErrors, 'warning');
                                }
                                return;
                            }
                            createSubcontratista(rowData, { excludeRowData: rowData });
                            return;
                        }

                        const errors = collectSubcontratistaValidationErrors(payload, { currentId: id });
                        if (errors.length) {
                            revertHandsontableChanges(instance, visualRow, rowChanges);
                            showValidationMessage(errors, 'warning');
                            return;
                        }

                        saveRowChanges(id, rowData, rowChanges);
                    });
                }
            });

            // Event listener for delete buttons inside the table
            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-delete');
                if (btn) {
                    const td = btn.closest('td');
                    // Use getCoords to safe find row index
                    const coords = hot.getCoords(td);
                    if (coords && coords.row >= 0) {
                        // Use toPhysicalRow to ensure we get correct source data index even if sorted/filtered
                        const physicalRow = hot.toPhysicalRow(coords.row);
                        const rowData = hot.getSourceDataAtRow(physicalRow);

                        // Note: Profesionales uses 'id' (lowercase), subcontratistas uses 'Id' (uppercase) from DB.
                        // We must check case sensitivity of data source.
                        // Based on previous JSON, it is 'Id'.
                        const recordId = rowData.Id || rowData.id;

                        if(recordId) {
                            // Check if locked
                            if (rowData.has_dependencies) {
                                if (window.AIA && window.AIA.Notice) window.AIA.Notice.warning('No se puede eliminar: tiene registros asociados en otros módulos del proyecto.');
                                return;
                            }
                            if (window.AIA && window.AIA.Notice) {
                                window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + (rowData.subcontratista || 'este registro') + '?', '<?php echo $isPreConstruccion ? 'Eliminar Interesado' : 'Eliminar Subcontratista'; ?>').then((confirmed) => {
                                    if (confirmed) deleteRow(recordId);
                                });
                            }
                        } else {
                            hot.alter('remove_row', coords.row);
                        }
                    }
                }
            });
        }

        function saveRowChanges(id, rowData, changes) {
            const db = document.getElementById('baseDatos').value;
            $('#save-status').hide();
            $('#save-error').hide();

            const payload = buildSubcontratistaPayload(rowData);

            $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'guardar_cambios',
                    cambios: changes.map(function(change) {
                        return {
                            id: id,
                            prop: change.prop,
                            value: payload[change.prop] !== undefined ? payload[change.prop] : rowData[change.prop]
                        };
                    })
                },
                success: function(res) {
                    if (res.status === 'success') {
                        showFeedback('success');
                    } else if (res.status === 'warning') {
                        showValidationMessage(res.errors || [res.message || 'No se pudo guardar el registro.'], 'warning');
                        showFeedback('error');
                        loadData();
                    } else {
                        showValidationMessage(res.errors || [res.message || 'No se pudo guardar el registro.'], 'error');
                        showFeedback('error');
                        loadData();
                    }
                },
                error: function(err) {
                    console.error(err);
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error('<?php echo $isPreConstruccion ? 'Error de red al guardar interesado.' : 'Error de red al guardar subcontratista.'; ?>');
                    showFeedback('error');
                    loadData();
                }
            });
        }

        function autosave(id, column, value, rowData) {
            if (!rowData || !id) return;
            const currentRow = hot ? hot.getSourceData().find((item) => item && String(item.Id || item.id) === String(id)) : null;
            const mergedRow = Object.assign({}, currentRow || {}, rowData || {});
            mergedRow[column] = value;
            const errors = collectSubcontratistaValidationErrors(buildSubcontratistaPayload(mergedRow), { currentId: id });
            if (errors.length) {
                showValidationMessage(errors, 'warning');
                loadData();
                return;
            }
            saveRowChanges(id, mergedRow, [{ prop: column, oldValue: null, newValue: value }]);
        }

        function createSubcontratista(rowData, options) {
            const payload = buildSubcontratistaPayload(rowData);
            const errors = collectSubcontratistaValidationErrors(payload, options || {});
            if (errors.length) {
                showValidationMessage(errors, 'warning');
                return;
            }

            if (rowData.__creating) return;

            const db = document.getElementById('baseDatos').value;
            rowData.__creating = true;
            $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'crear',
                    Subcontratista: payload.subcontratista,
                    Correo: payload.correo_contacto,
                    NIT: payload.NIT,
                    alcance: payload.alcance,
                    tipo_proveedor: payload.tipo_proveedor
                },
                success: function(res) {
                    if (res.status === 'success') {
                        showFeedback('success');
                        loadData(); // Recargar para obtener el nuevo Id
                    } else {
                        rowData.__creating = false;
                        showValidationMessage(res.errors || [res.message || '<?php echo $isPreConstruccion ? 'No se pudo crear el interesado.' : 'No se pudo crear el subcontratista.'; ?>'], 'warning');
                    }
                },
                error: function(err) {
                    console.error(err);
                    rowData.__creating = false;
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("<?php echo $isPreConstruccion ? 'Error de red al crear interesado' : 'Error de red al crear subcontratista'; ?>");
                }
            });
        }

        function deleteRow(id) {
             const db = document.getElementById('baseDatos').value;
             $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'eliminar',
                    id: id
                },
                success: function(res) {
                    if (res.status === 'success') {
                        loadData();
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.badge('success', "Eliminado correctamente");
                    } else {
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error: " + res.message);
                    }
                }
            });
        }

        function showFeedback(type) {
            if (type === 'success') {
                if (window.AIA && window.AIA.Notice && window.AIA.Notice.badge) {
                    window.AIA.Notice.badge('success', 'Guardado');
                } else {
                    $('#save-status').removeClass('badge-badge-hidden').text('Guardado').fadeIn(120).delay(1800).fadeOut(250);
                }
            } else {
                $('#save-error').fadeIn();
            }
        }


        function exportCSV() {
             if(hot) {
                 const exportPlugin = hot.getPlugin('exportFile');
                 exportPlugin.downloadFile('csv', {
                    filename: 'subcontratistas',
                    columnHeaders: true,
                    range: [0, 1, hot.countRows() - 1, hot.countCols() - 2] // Exclude Action col
                  });
             }
        }

    </script>
</body>
</html>
