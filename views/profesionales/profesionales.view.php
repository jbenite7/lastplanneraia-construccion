<!DOCTYPE html>
<html lang="es">
<head id="head">
    <!-- jQuery Must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Head Resources (Nav, CSS, etc) -->
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260325a" charset="utf-8"></script>

    <!-- Handsontable CSS -->
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <!-- Additional Local Styles if needed override linksComunes -->
    <style>
        /* Mobile First & Full Height */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Handsontable handles scrolls on Desktop */
        }
        
        /* Allow scroll on Mobile */
        @media (max-width: 768px) {
            html, body {
                overflow: auto !important;
                height: auto !important;
            }
        }
        
        /* Flex layout to handle dynamic header height */
        body {
            display: flex;
            flex-direction: column;
        }

        #encabezado, .direccionSeccion {
            flex: 0 0 auto; /* Don't shrink */
        }

        .header-actions {
            flex: 0 0 auto;
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #hot-container {
            flex: 1 1 auto; /* Take remaining space */
            width: 100%;
            overflow: hidden;
            position: relative;
            background: #fff; /* Ensure it has a background */
        }

        /* Custom Renderers */
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; }
        
        /* Loading overlay */
        #loading {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(255,255,255,0.95); /* Slightly more opaque for better focus */
            z-index: 10000; /* Higher than anything else */
            display: flex; justify-content: center; align-items: center;
        }

        /* RESET & PROTECTION: Prevent global styles.css from breaking Handsontable's premium layout */
        #hot-container {
            width: 100%;
            height: calc(100vh - 180px); /* Fill available vertical space */
            background: #fff;
            box-shadow: var(--shadow-sm);
            border-radius: var(--radius-sm);
            overflow: hidden;
            margin-top: 10px;
        }

        /* Neutralize Mobile-First "Card View" overrides from styles.css */
        
        /* 1. Hide the injected labels from mobile-table-fix.js */
        #hot-container td::before, 
        #hot-container th::before {
            content: none !important;
            display: none !important;
        }

        /* 2. Restore standard table display properties for Handsontable components */
        #hot-container td, 
        #hot-container th {
            display: table-cell !important; /* Force back from 'flex' used in mobile-fix */
            /* text-align: inherit !important;  <-- REMOVED: This was blocking .htCenter */
            padding: 0 !important; /* HOT handles padding internally */
            border-right: 1px solid #EDEDED !important;
            border-bottom: 1px solid #EDEDED !important;
        }

        /* Enforce HOT alignment classes */
        #hot-container td.htCenter { text-align: center !important; }
        #hot-container td.htLeft { text-align: left !important; }
        #hot-container td.htRight { text-align: right !important; }
        #hot-container td.htMiddle { vertical-align: middle !important; }
        #hot-container td.htTop { vertical-align: top !important; }
        #hot-container td.htBottom { vertical-align: bottom !important; }

        /* 3. Ensure headers look premium */
        #hot-container th {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            font-weight: 600 !important;
            vertical-align: middle !important;
            text-align: center !important;
        }

        /* 4. Fix row height and display */
        #hot-container tr {
            display: table-row !important;
            height: auto !important;
        }
        
        #hot-container {
            width: 100% !important;
            max-width: 100vw !important;
            overflow: hidden !important;
        }
        
        /* DYNAMIC FONT SIZE - Scales with viewport */
        #hot-container td,
        #hot-container th {
            font-size: clamp(10px, 1.1vw, 14px) !important;
        }
        
        /* Force table to ALWAYS fit container width */
        #hot-container .wtHider,
        #hot-container .wtHolder {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #hot-container table.htCore {
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        .force-wrap {
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            overflow: hidden !important;
        }

        /* 5. Fix internal Table element */
        #hot-container table {
            width: 100% !important;
            border-collapse: separate !important;
            max-width: 100% !important;
        }

        /* 
           CRITICAL MOBILE OVERRIDES - VERTICAL CARD VIEW 
           Transforms the grid into an aesthetic vertical list for mobile editing 
        */
        /* 
           MOBILE VISIBILITY CONTROL
           We use a dual-view strategy:
           1. Desktop: Handsontable (#hot-container)
           2. Mobile: Custom HTML Cards (#mobile-card-view)
        */
        @media (max-width: 768px) {
            #hot-container {
                display: none !important;
            }
            #mobile-card-view {
                display: block !important;
                padding: 10px;
                padding-bottom: 80px; /* Space for scrolling */
            }
        }

        @media (min-width: 769px) {
            #mobile-card-view {
                display: none !important;
            }
        }

        /* Mobile Card Styling */
        .mobile-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); /* Apple-like shadow */
            border: 1px solid #f0f0f0;
            position: relative;
        }

        .mobile-card-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f9f9f9;
        }

        .mobile-card-row:last-child {
            border-bottom: none;
        }

        .mobile-label {
            font-weight: 600;
            color: #8c8c8c;
            font-size: 0.85rem;
            text-transform: uppercase;
            min-width: 80px; /* Fixed width for alignment */
            display: inline-block;
        }

        .mobile-value {
            font-weight: 500;
            color: #333;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }

        .mobile-actions {
            margin-top: 10px;
            text-align: right;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        /* MOBILE NAV DRAWER FIXES */
        @media (max-width: 1199px) {
             #navbarSupportedContent {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 85%;
                max-width: 320px;
                background-color: #ffffff !important; /* Force White Opaque */
                z-index: 99999 !important;
                padding-top: 0;
                overflow-y: auto; /* Enable Scroll */
                box-shadow: 2px 0 10px rgba(0,0,0,0.2);
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                display: block !important; /* Bootstrap overrides prevention */
            }

            #navbarSupportedContent.show {
                transform: translateX(0) !important;
            }

            /* Backdrop */
            #navbarSupportedContent.show::before {
                content: '';
                position: fixed;
                top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: -1;
            }
            
            /* Text Colors & Contrast */
            .navbar-nav .nav-link {
                color: #000000 !important;
                font-size: 1rem !important;
                padding: 12px 20px !important;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .navbar-nav .dropdown-menu {
                background-color: #f8f9fa !important; /* Light Grey for submenus */
                border: none !important;
                padding-left: 20px !important;
            }

            .navbar-nav .dropdown-item {
                 color: #333 !important;
                 padding: 10px 15px !important;
                 white-space: normal !important; /* Wrap long text */
                 height: auto !important;
            }
            
            /* Remove margins that might hide content */
            .navbar-nav.ml-4 {
                margin-left: 0 !important;
            }
        }

        /* DESKTOP NAV OPTIMIZATION (XL Screens) */
        @media (min-width: 1200px) {
            .navbar-nav .nav-link {
                font-size: 0.85rem !important; /* Scaled down from 1rem */
                padding-left: 8px !important;
                padding-right: 8px !important;
                white-space: nowrap;
            }
            .navbar-brand {
                font-size: 1rem !important;
                margin-right: 5px !important;
            }
            /* Adjust spacing between sections */
            .navbar-nav.ml-4 {
                margin-left: 10px !important;
            }
        }
    </style>
    <link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260223a" />
</head>
<body>

    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <!-- Estructura Original de Navegación -->
    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="info_profesionales" aria-hidden="true">
    </div>

    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <!-- Hidden inputs for Context -->
    <input type="hidden" id="baseDatos" value="<?php echo $_SESSION['db'] ?? 'Prueba'; ?>">
    <input type="hidden" id="permiso_canonico" value="<?php echo $_SESSION['permiso'] ?? 'V'; ?>">

    <div class="header-actions">
        <h4>Profesionales (Live Edición)</h4>
        <div>
            <span id="save-status" class="badge badge-success" style="display:none;">Guardado</span>
            <span id="save-error" class="badge badge-danger" style="display:none;">Error al guardar</span>
            <button id="btn-export" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-excel"></i> Exportar</button>
        </div>
    </div>

    <!-- Handsontable Container (Desktop) -->
    <div id="hot-container"></div>
    
    <!-- Custom Mobile Card View (Hidden on Desktop) -->
    <div id="mobile-card-view" style="display:none;">
        <!-- JS will populate cards here -->
    </div>

    <!-- Bootstrap Dependencies (Required for Navbar Dropdowns) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- Common Scripts for Navigation (Depends on jQuery) -->
    <script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>

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
                    if (hot && window.innerWidth > 768) {
                        hot.updateSettings({
                            colWidths: function(colIndex) {
                                const containerWidth = document.getElementById('hot-container').offsetWidth - 50;
                                const percentages = [0, 25, 30, 25, 10, 10];
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
                url: '/api/profesionales/list?db=' + db,
                type: 'POST',
                data: { opcion: 'listar' },
                dataType: 'json',
                success: function(response) {
                    $('#loading').fadeOut(300, function() {
                        if(response.status === 'success') {
                            // Dual View Init
                            updateOrInitHandsontable(response.data);
                            renderMobileCards(response.data);

                            // Force render after container is visible and layout settled (Desktop)
                            setTimeout(() => {
                                if(hot && window.innerWidth > 768) hot.render();
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

        function buildCargoOptionsHtml(selectedValue, includePlaceholder = true, placeholderLabel = 'Seleccionar...') {
            let options = includePlaceholder ? `<option value="">${placeholderLabel}</option>` : '';
            professionalCargos.forEach((cargo) => {
                const selected = selectedValue === cargo ? 'selected' : '';
                options += `<option value="${cargo}" ${selected}>${cargo}</option>`;
            });
            return options;
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
                rowHeaders: true,
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
                    const containerWidth = document.getElementById('hot-container').offsetWidth - 50;
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
                                  td.innerHTML = `<button class="btn btn-secondary btn-xs" disabled title="${reason}"><i class="fas fa-lock"></i></button>`;
                              } else {
                                  td.innerHTML = '<button class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></button>';
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
                    cargo: payload.cargo
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
                data: { opcion: 'eliminar', id: id },
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

        // ==========================================
        // MOBILE CARD VIEW RENDERER (EDITABLE)
        // ==========================================
        function renderMobileCards(data) {
            const container = document.getElementById('mobile-card-view');
            container.innerHTML = '';
            
            // Generate Card Form for New Entry
            let html = `
                <div class="mobile-card" style="border: 2px dashed #007aff; background: #f9faff;">
                    <h5 style="color:#007aff; text-align:center; margin-bottom:15px; font-weight:bold;">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo
                    </h5>
                     <div class="form-group">
                        <input type="text" class="form-control" id="new-mobile-nombre" placeholder="Nombre completo">
                    </div>
                     <div class="form-group">
                        <input type="email" class="form-control" id="new-mobile-email" placeholder="Correo electrónico">
                    </div>
                     <div class="form-group">
                        <select class="form-control" id="new-mobile-cargo">
                            ${buildCargoOptionsHtml('', true)}
                        </select>
                    </div>
                    <button class="btn btn-primary btn-block shadow-sm" onclick="createMobileRow()">
                        Guardar Nuevo
                    </button>
                </div>
            `;

            data.forEach(row => {
               if(!row.id) return; // Skip empty rows
               const isChecked = row.activo ? 'checked' : '';
                const cargoOptions = buildCargoOptionsHtml(row.cargo || '', true, (!row.cargo && row.is_admin_managed) ? 'Sin cargo' : 'Seleccionar...');
                const disableIdentityEdition = !row.can_edit_identity ? 'disabled' : '';
                const disableActiveEdition = !row.can_edit_active ? 'disabled' : '';
                const deleteActionHtml = !row.can_delete
                    ? `<button class="btn btn-outline-secondary btn-sm" disabled title="${row.delete_reason || 'Registro bloqueado'}"><i class="fas fa-lock"></i> Bloqueado</button>`
                    : `<button class="btn btn-outline-danger btn-sm" onclick="deleteMobileRow(${row.id}, '${row.nombre}')"><i class="fas fa-trash"></i> Eliminar</button>`;
                const guidance = row.identity_edit_reason || row.active_edit_reason || row.block_reason;
                const blockReasonHtml = guidance
                    ? `<div class="text-muted" style="font-size:12px; margin-top:10px;">${guidance}</div>`
                    : '';

                html += `
                <div class="mobile-card">
                    <div class="mobile-card-row">
                        <span class="mobile-label">Nombre</span>
                        <input type="text" class="form-control" style="flex:1; margin-left:20px; text-align:right;" 
                               value="${row.nombre || ''}" 
                               ${disableIdentityEdition}
                               onchange="updateMobileRow(${row.id}, 'nombre', this.value)">
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Correo</span>
                        <input type="email" class="form-control" style="flex:1; margin-left:20px; text-align:right;" 
                               value="${row.email || ''}" 
                               ${disableIdentityEdition}
                               onchange="updateMobileRow(${row.id}, 'email', this.value)">
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Cargo</span>
                        <select class="form-control" style="flex:1; margin-left:20px; text-align-last:right;" 
                                ${disableIdentityEdition}
                                onchange="updateMobileRow(${row.id}, 'cargo', this.value)">
                            ${cargoOptions}
                        </select>
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Activo</span>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="switch-${row.id}" ${isChecked}
                                   ${disableActiveEdition}
                                   onchange="updateMobileRow(${row.id}, 'activo', this.checked)">
                            <label class="custom-control-label" for="switch-${row.id}"></label>
                        </div>
                    </div>
                    
                    <div class="mobile-actions">
                         ${deleteActionHtml}
                    </div>
                    ${blockReasonHtml}
                </div>
               `; 
            });
            
            container.innerHTML = html;
        }
        
        function updateMobileRow(id, prop, value) {
            // Map 'activo' boolean to what backend expects if needed, or just send boolean
            // Handsontable sends boolean for checkbox, backend auto-save handles it.
            saveData({ id: id }, prop, value);
        }

        function createMobileRow() {
            const nombre = document.getElementById('new-mobile-nombre').value;
            const email = document.getElementById('new-mobile-email').value;
            const cargo = document.getElementById('new-mobile-cargo').value;

            createRow({ nombre: nombre, email: email, cargo: cargo, activo: 1 });
        }

        function deleteMobileRow(id, nombre) {
             if (window.AIA && window.AIA.Notice) {
                 window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + nombre + '?', 'Eliminar Profesional').then((confirmed) => {
                     if(confirmed) deleteRow(id);
                 });
             }
        }
    </script>
</body>
</html>
