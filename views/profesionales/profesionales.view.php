<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <title>Profesionales — Last Planner AIA</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <script src="/js/modules/aia_ui/csrf.js"></script>
    <!-- jQuery Must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Head Resources (Nav, CSS, etc) -->
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('profesionales') ?>
    <link rel="stylesheet" href="/css/profesionales.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/profesionales.css') ?: 'prof1')) ?>" />
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>

    <!-- Handsontable CSS llega vía attach-handsontable.css (design system); el link crudo duplicaba la cascada y pisaba dark mode. -->
</head>
<body class="aia-shell aia-shell--sidebar prof-page">

    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <!-- Estructura Original de Navegación -->
    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="info_profesionales" aria-hidden="true">
    </div>

    <main>
    <h1 class="aia-visually-hidden">Profesionales</h1>
    <div class="row direccionSeccion">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <!-- Hidden inputs for Context -->
    <input type="hidden" id="baseDatos" value="<?php echo $_SESSION['db'] ?? 'Prueba'; ?>">
    <input type="hidden" id="permiso_canonico" value="<?php echo $_SESSION['permiso'] ?? 'V'; ?>">

    <div class="header-actions action-bar">
        <h4>Profesionales (Live Edición)</h4>
        <div class="header-actions-group">
            <span id="save-status" class="aia-chip aia-chip--success prof-save-flag">Guardado</span>
            <span id="save-error" class="aia-chip aia-chip--danger prof-save-flag">Error al guardar</span>
            <button id="btn-export" class="aia-btn aia-btn--secondary"><i class="fas fa-file-excel"></i> Exportar</button>
            <?= \App\View\Components\BiAccessComponent::renderLink('profesionales', 'BI Responsables') ?>
        </div>
    </div>

    <!-- Handsontable Container (Desktop) -->
    <div id="hot-container"></div>

    </main>

    <!-- Bootstrap Dependencies (Required for Navbar Dropdowns) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- Common Scripts for Navigation (Depends on jQuery) -->
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('profesionales') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/hot_table_width.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>

    <!-- Handsontable Scripts -->
    <!-- Handsontable Scripts -->
    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <!-- Languages -->
    <script src="/public/vendor/handsontable/es-MX.js"></script>

    <script>
        const container = document.getElementById('hot-container');
        let hot;

        $(document).ready(function() {
            // Load Navigation
            cargarDatosGeneralesPagina(document.getElementById('seccion').value);

            // Recalcular columnas cuando cambia el tamaño de la ventana
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (hot) {
                        hot.updateSettings({
                            colWidths: function(colIndex) {
                                const containerWidth = document.getElementById('hot-container').offsetWidth;
                                const percentages = [0, 25, 30, 25, 10, 10];
                                return Math.floor(containerWidth * (percentages[colIndex] / 100));
                            }
                        });
                        window.AIA.sincronizarAnchoTabla(container);
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
                url: '/api/profesionales/list?db=' + db,
                type: 'POST',
                data: { opcion: 'listar' },
                dataType: 'json',
                success: function(response) {
                    $('#loading').fadeOut(300, function() {
                        if(response.status === 'success') {
                            updateOrInitHandsontable(response.data);

                            // Force render after container is visible and layout settled.
                            setTimeout(() => {
                                if (!hot) return;
                                // Sincronizar ANTES del render final: al fijar
                                // el ancho cambian los saltos de linea y con
                                // ellos la altura de las filas, y el clon de
                                // encabezados de fila se queda con las
                                // anteriores. Ver hot_table_width.js.
                                window.AIA.sincronizarAnchoTabla(container);
                                hot.render();
                            }, 100);
                        } else {
                            if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error cargando datos: " + response.message);
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

        const professionalCargos = [
            'Administrador',
            'Residente de Obra',
            'Residente SST',
            'Residente Ambiental',
            'Residente Oficina Técnica',
            'Profesional Diseño y Construcción Virtual',
            'Maestro de Obra',
            'Almacenista',
            'Director de Obra',
            'Residente SST + Ambiental',
            'Coordinador de Obras',
            'Gerente de Proyecto'
        ];

        function normalizeTextValue(value) {
            return (value || '').toString().trim().replace(/\s+/g, ' ');
        }

        function normalizeEmailValue(value) {
            return (value || '').toString().trim().toLowerCase();
        }

        function buildProfessionalPayload(rowData) {
            return {
                nombre: normalizeTextValue(rowData && rowData.nombre),
                email: normalizeEmailValue(rowData && rowData.email),
                cargo: normalizeTextValue(rowData && rowData.cargo),
                activo: rowData && (rowData.activo === true || rowData.activo === 1 || rowData.activo === '1') ? 1 : 0
            };
        }

        function isProfessionalDraftEmpty(payload) {
            return !payload.nombre && !payload.email && !payload.cargo;
        }

        function isProfessionalDraftComplete(payload) {
            return !!(payload.nombre && payload.email && payload.cargo);
        }

        function collectProfessionalValidationErrors(payload, options) {
            const validationOptions = options || {};
            const currentId = validationOptions.currentId || null;
            const excludeRowData = validationOptions.excludeRowData || null;
            const errors = [];
            if (!payload.nombre) errors.push('El nombre es obligatorio.');
            if (!payload.email) {
                errors.push('El correo es obligatorio.');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(payload.email)) {
                errors.push('El correo no tiene un formato valido.');
            }
            if (!payload.cargo) {
                errors.push('El cargo es obligatorio.');
            } else if (professionalCargos.indexOf(payload.cargo) === -1) {
                errors.push('El cargo seleccionado no es valido.');
            }

            const rows = hot ? hot.getSourceData() : [];
            rows.forEach((row) => {
                if (!row) return;
                if (excludeRowData && row === excludeRowData) return;
                const rowId = row.id || null;
                if (currentId && String(rowId) === String(currentId)) return;
                const candidate = buildProfessionalPayload(row);
                if (!rowId && isProfessionalDraftEmpty(candidate)) return;
                if (payload.email && candidate.email && payload.email === candidate.email) {
                    errors.push('Ya existe un profesional con ese correo.');
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
                colHeaders: ['ID', 'Nombre', 'Correo', 'Cargo', 'Activo', 'Acciones'],
                columns: [
                    { data: 'id', readOnly: true, className: 'htCenter htMiddle' },
                    { data: 'nombre', type: 'text', className: 'htCenter htMiddle' },
                    { data: 'email', type: 'text', className: 'htCenter htMiddle' },
                     {
                         data: 'cargo',
                         type: 'dropdown',
                         className: 'htCenter htMiddle',
                         source: professionalCargos
                     },
                    { data: 'activo', type: 'checkbox', className: 'htCenter htMiddle' },
                    {
                       data: 'accion',
                       renderer: 'html',
                       readOnly: true,
                       className: 'htCenter htMiddle'
                    }
                ],
                // Anchos en porcentaje: ID(0%), Nombre(25%), Correo(30%), Cargo(25%), Activo(10%), Acciones(10%) = 100%
                colWidths: function(colIndex) {
                    const containerWidth = document.getElementById('hot-container').offsetWidth;
                    const percentages = [0, 25, 30, 25, 10, 10];
                    return Math.floor(containerWidth * (percentages[colIndex] / 100));
                },
                hiddenColumns: {
                    columns: [0],
                    indicators: false
                },
                stretchH: 'none',
                contextMenu: true,
                manualRowResize: true,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                language: 'es-MX',
                minSpareRows: 1,
                wordWrap: true,
                width: '100%',
                height: '100%',

                cells: function(row, col) {
                    const cellProperties = {};
                    const physicalRow = hot ? hot.toPhysicalRow(row) : row;
                    const rowData = hot ? hot.getSourceDataAtRow(physicalRow) : null;

                    if (rowData) {
                        if ([1, 2, 3].includes(col) && !rowData.can_edit_identity) {
                            cellProperties.readOnly = true;
                        }

                        if (col === 4 && !rowData.can_edit_active) {
                            cellProperties.readOnly = true;
                        }
                    }

                    if (col === 3 && rowData && rowData.is_admin_managed && !rowData.cargo) {
                        cellProperties.renderer = function(instance, td) {
                            td.textContent = 'Sin cargo';
                            td.className = 'htCenter htMiddle text-muted';
                        };
                    }

                    if (col === 5) { // Acciones column
                          cellProperties.renderer = function(instance, td, row, col, prop, value, cellProperties) {
                              Handsontable.renderers.HtmlRenderer.apply(this, arguments);
                              const physicalRow = instance.toPhysicalRow(row);
                              const rowData = instance.getSourceDataAtRow(physicalRow);

                              if (rowData && rowData.id && !rowData.can_delete) {
                                  const reason = rowData.delete_reason || 'Registro bloqueado';
                                  td.innerHTML = `<button class="aia-btn aia-btn--secondary aia-btn--sm" disabled title="${reason}"><i class="fas fa-lock"></i></button>`;
                              } else {
                                  td.innerHTML = '<button class="aia-btn aia-btn--critical aia-btn--sm btn-delete" aria-label="Eliminar profesional" title="Eliminar profesional"><i class="fas fa-trash" aria-hidden="true"></i></button>';
                              }
                             td.style.textAlign = 'center';
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

                        const rejectedChanges = [];
                        const acceptedChanges = [];
                        rowChanges.forEach((change) => {
                            if (['nombre', 'email', 'cargo'].includes(change.prop)) {
                                if (!rowData.can_edit_identity) {
                                    rejectedChanges.push(change);
                                    return;
                                }
                            }

                            if (change.prop === 'activo' && !rowData.can_edit_active) {
                                rejectedChanges.push(change);
                                return;
                            }

                            acceptedChanges.push(change);
                        });

                        if (rejectedChanges.length) {
                            revertHandsontableChanges(instance, visualRow, rejectedChanges);
                            const identityRejected = rejectedChanges.some((change) => ['nombre', 'email', 'cargo'].includes(change.prop));
                            const reason = identityRejected
                                ? (rowData.identity_edit_reason || rowData.block_reason || 'Este profesional no permite cambios de identidad desde este modulo.')
                                : (rowData.active_edit_reason || rowData.block_reason || 'Este profesional no permite cambiar Activo en este modulo.');
                            showValidationMessage([reason], 'warning');
                        }

                        if (!acceptedChanges.length) {
                            return;
                        }

                        const payload = buildProfessionalPayload(rowData);
                        const id = rowData.id || null;

                        if (!id) {
                            if (isProfessionalDraftEmpty(payload)) return;
                            const draftErrors = collectProfessionalValidationErrors(payload, { excludeRowData: rowData });
                            if (draftErrors.length) {
                                if (isProfessionalDraftComplete(payload)) {
                                    showValidationMessage(draftErrors, 'warning');
                                }
                                return;
                            }
                            createRow(rowData, { excludeRowData: rowData });
                            return;
                        }

                        const errors = collectProfessionalValidationErrors(payload, { currentId: id });
                        if (errors.length) {
                            revertHandsontableChanges(instance, visualRow, rowChanges);
                            showValidationMessage(errors, 'warning');
                            return;
                        }

                        saveRowChanges(id, rowData, acceptedChanges);
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

                        if(rowData.id) {
                            if (window.AIA && window.AIA.Notice) {
                                window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + rowData.nombre + '?', 'Eliminar Profesional').then((confirmed) => {
                                    if(confirmed) deleteRow(rowData.id);
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

            const payload = buildProfessionalPayload(rowData);

            $.ajax({
                url: '/api/profesionales/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'guardar_cambios',
                    cambios: changes.map(function(change) {
                        return { id: id, prop: change.prop, value: payload[change.prop] !== undefined ? payload[change.prop] : rowData[change.prop] };
                    }),
                    _csrf_token: window.aiaCsrfToken()
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
                error: function() {
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error('Error de red al guardar profesional.');
                    showFeedback('error');
                    loadData();
                }
            });
        }

        function saveData(rowData, prop, newValue) {
            if (!rowData || !rowData.id) return;
            const currentRow = hot ? hot.getSourceData().find((item) => item && String(item.id) === String(rowData.id)) : null;
            const mergedRow = Object.assign({}, currentRow || {}, rowData || {});
            mergedRow[prop] = newValue;

            if (['nombre', 'email', 'cargo'].includes(prop) && !mergedRow.can_edit_identity) {
                showValidationMessage([mergedRow.identity_edit_reason || mergedRow.block_reason || 'Este profesional no permite cambios de identidad desde este modulo.'], 'warning');
                loadData();
                return;
            }

            if (prop === 'activo' && !mergedRow.can_edit_active) {
                showValidationMessage([mergedRow.active_edit_reason || mergedRow.block_reason || 'Este profesional no permite cambiar Activo en este modulo.'], 'warning');
                loadData();
                return;
            }

            const errors = collectProfessionalValidationErrors(buildProfessionalPayload(mergedRow), { currentId: rowData.id });
            if (errors.length) {
                showValidationMessage(errors, 'warning');
                loadData();
                return;
            }
            saveRowChanges(rowData.id, mergedRow, [{ prop: prop, oldValue: null, newValue: newValue }]);
        }

        function createRow(rowData, options) {
             const payload = buildProfessionalPayload(rowData);
             const errors = collectProfessionalValidationErrors(payload, options || {});
             if (errors.length) {
                 showValidationMessage(errors, 'warning');
                 return;
             }

             if (rowData.__creating) return;

             const db = document.getElementById('baseDatos').value;
             rowData.__creating = true;
             $.ajax({
                url: '/api/profesionales/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'crear',
                    nombre: payload.nombre,
                    email: payload.email,
                    cargo: payload.cargo,
                    _csrf_token: window.aiaCsrfToken()
                },
                success: function(res) {
                    if (res.status === 'success') {
                        rowData.id = res.id;
                        showFeedback('success');
                        loadData();
                    } else {
                        rowData.__creating = false;
                        showValidationMessage(res.errors || [res.message || 'No se pudo crear el profesional.'], 'warning');
                    }
                },
                error: function() {
                    rowData.__creating = false;
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error('Error de red al crear profesional.');
                }
             });
        }

        function deleteRow(id) {
             const db = document.getElementById('baseDatos').value;
             $.ajax({
                url: '/api/profesionales/save?db=' + db,
                type: 'POST',
                data: { opcion: 'eliminar', id: id, _csrf_token: window.aiaCsrfToken() },
                success: function(res) {
                    if (res.status === 'success') {
                        // Success -> Reload data to refresh grid without destroying instance
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

    </script>
</body>
</html>
