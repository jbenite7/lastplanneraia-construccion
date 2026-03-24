<!DOCTYPE html>
<html lang="es">
<head id="head">
    <!-- jQuery Must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Head Resources (Nav, CSS, etc) -->
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260324a" charset="utf-8"></script>

    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/handsontable.full.min.css" />
    
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
            padding: 0 !important; /* HOT handles padding internally */
            border-right: 1px solid #EDEDED !important;
            border-bottom: 1px solid #EDEDED !important;
        }

        /* Enforce HOT alignment classes */
        #hot-container td.htCenter, 
        #hot-container th.htCenter { text-align: center !important; }
        #hot-container td.htLeft,
        #hot-container th.htLeft { text-align: left !important; }
        #hot-container td.htRight,
        #hot-container th.htRight { text-align: right !important; }
        #hot-container td.htMiddle,
        #hot-container th.htMiddle { vertical-align: middle !important; }

        /* 3. Ensure headers look premium AND centered */
        #hot-container th {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            font-weight: 600 !important;
            vertical-align: middle !important;
            text-align: center !important;
        }

        /* 4. Perfect Circular Delete Button */
        .btn-delete {
            border-radius: 50% !important;
            width: 28px !important;
            height: 28px !important;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            border: none !important;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
            transition: all 0.2s ease;
        }
        
        .btn-delete:hover {
            transform: scale(1.1);
            background-color: #c82333 !important;
        }
        
        #hot-container {
            box-sizing: border-box !important;
        }
        #hot-container * {
            box-sizing: content-box !important;
        }
        
        .btn-delete i {
            font-size: 12px !important;
        }
        
        /* 5. Fix row height and display */
        #hot-container {
            width: 100% !important;
            max-width: 100vw !important;
            overflow: hidden !important; /* Prevent ANY overflow */
        }
        
        /* DYNAMIC FONT SIZE - Scales with viewport */
        #hot-container td,
        #hot-container th {
            font-size: clamp(10px, 1.1vw, 14px) !important; /* Min 10px, scales with viewport, max 14px */
        }
        
        /* Force table to ALWAYS fit container width */
        #hot-container .wtHider,
        #hot-container .wtHolder {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        #hot-container table.htCore {
            width: 100% !important;
            table-layout: fixed !important; /* Forces columns to respect widths */
        }
        
        .force-wrap {
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            overflow: hidden !important;
        }

        /* Force row headers to behave */
        .ht_clone_left tr th, .ht_clone_left tr td {
             box-sizing: content-box !important;
             vertical-align: middle !important;
        }

        /* 5. Fix row height and display */
        #hot-container tr {
            display: table-row !important;
        }

        /* 5. Fix internal Table element */
        #hot-container table {
            width: 100% !important;
            border-collapse: separate !important;
            max-width: 100% !important;
        }

        /* 
           CRITICAL MOBILE OVERRIDES - VERTICAL CARD VIEW 
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
        
        /* NAVBAR FIXES FROM REFERENCE */
         @media (max-width: 1199px) {
             #navbarSupportedContent {
                position: fixed; top: 0; left: 0; height: 100vh; width: 85%; max-width: 320px;
                background-color: #ffffff !important; z-index: 99999 !important;
                padding-top: 0; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,0.2);
                transform: translateX(-100%); transition: transform 0.3s ease-in-out;
                display: block !important;
            }
            #navbarSupportedContent.show { transform: translateX(0) !important; }
            #navbarSupportedContent.show::before {
                content: ''; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                background: rgba(0,0,0,0.5); z-index: -1;
            }
        }

        /* DESKTOP NAV OPTIMIZATION (XL Screens) - Prevents nav from being cut off */
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
        <input type="hidden" name="seccion" id="seccion" value="info_subcontratistas">
    </div>

    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <!-- Hidden inputs for Context -->
    <input type="hidden" id="baseDatos" value="<?php echo $_SESSION['db'] ?? 'Prueba'; ?>">
    <input type="hidden" id="permiso" value="<?php echo $_SESSION['permiso'] ?? 'V'; ?>">

    <div class="header-actions">
        <h4>Subcontratistas (Live Edición)</h4>
        <div>
            <span id="save-status" class="badge badge-success" style="display:none;">Guardado</span>
            <span id="save-error" class="badge badge-danger" style="display:none;">Error al guardar</span>
            <button id="btn-export" class="btn btn-sm btn-outline-secondary" onclick="exportCSV()"><i class="fas fa-file-excel"></i> Exportar</button>
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
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/handsontable.full.min.js"></script>
    <!-- Languages -->
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/languages/es-MX.js"></script>

    <script>
        const container = document.getElementById('hot-container');
        let hot;
        var dbPrefix = "<?php echo $_SESSION['db'] ?? 'Prueba'; ?>";

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
                            // Dual View Init
                            updateOrInitHandsontable(data);
                            renderMobileCards(data);

                            // Force render after container is visible and layout settled (Desktop)
                            // Double render with delay to allow wordWrap + stretchH to settle
                            setTimeout(() => {
                                if(hot && window.innerWidth > 768) {
                                     hot.render();
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

        function updateOrInitHandsontable(data) {
            if (hot) {
                hot.loadData(data);
                return;
            }
            
            hot = new Handsontable(container, {
                data: data,
                rowHeaders: true,
                rowHeaderWidth: 50,
                colHeaders: ['ID', 'Subcontratista', 'Correo Contacto', 'NIT', 'Alcance', 'Tipo Proveedor', 'Activo', 'Acciones'],
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
                        source: ['Mano de Obra', 'Suministro e Instalación', 'Suministro de Materiales, Herramientas o Equipos']
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
                    const containerWidth = document.getElementById('hot-container').offsetWidth - 50; // 50px para rowHeaders
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
                                 td.innerHTML = '<button class="btn btn-secondary btn-xs" disabled title="No se puede eliminar: tiene registros asociados en otros módulos del proyecto."><i class="fas fa-lock"></i></button>';
                             } else {
                                 // Perfect circular button via .btn-delete CSS class
                                 td.innerHTML = '<button class="btn btn-danger btn-xs btn-delete"><i class="fas fa-trash"></i></button>';
                             }
                         }
                    }
                    return cellProperties;
                },
                afterChange: function(changes, source) {
                    if (source === 'loadData' || !changes) return;
                    
                    const instance = this;
                    changes.forEach(([row, prop, oldValue, newValue]) => {
                        if (oldValue === newValue) return;
                        
                        const physicalRow = instance.toPhysicalRow(row);
                        const rowData = instance.getSourceDataAtRow(physicalRow);
                        const id = rowData.Id;
                        
                        // Validar campos vacíos en registros existentes (excepto activo y accion)
                        if (id && prop !== 'accion' && prop !== 'activo') {
                            const trimmedValue = (newValue || '').toString().trim();
                            if (trimmedValue === '') {
                                if (window.AIA && window.AIA.Notice) window.AIA.Notice.warning('No se puede dejar el campo vacío. Por favor ingrese un valor.');
                                // Revertir al valor anterior
                                instance.setDataAtRowProp(row, prop, oldValue, 'revert');
                                return;
                            }
                        }
                        
                        console.log('afterChange triggered:', { id, prop, oldValue, newValue, rowData });
                        
                        // Pasar rowData para permitir creación de nuevos registros
                        autosave(id, prop, newValue, rowData);
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
                                window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + (rowData.subcontratista || 'este registro') + '?', 'Eliminar Subcontratista').then((confirmed) => {
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

        function autosave(id, column, value, rowData) {
             const db = document.getElementById('baseDatos').value;
             $('#save-status').hide();
             $('#save-error').hide();

             // Si no tiene Id, es un registro nuevo
             if (!id) {
                 // Verificar si tiene al menos el nombre para crear
                 if (rowData && rowData.subcontratista) {
                     createSubcontratista(rowData);
                 }
                 return;
             }

            $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'guardar_cambios',
                    cambios: [{
                        id: id,
                        prop: column,
                        value: value
                    }]
                },
                success: function(res) {
                    if (res.status === 'success') {
                         showFeedback('success');
                    } else if (res.status === 'warning') {
                         if (window.AIA && window.AIA.Notice) window.AIA.Notice.warning('Advertencia: ' + (res.message || '') + '\n' + (res.errors ? res.errors.join('\n') : ''));
                         showFeedback('error');
                    } else {
                         if (window.AIA && window.AIA.Notice) window.AIA.Notice.error('Error: ' + (res.message || 'Error desconocido'));
                         showFeedback('error');
                    }
                },
                error: function(err) {
                    console.error(err);
                    showFeedback('error');
                }
            });
        }

        function createSubcontratista(rowData) {
            const db = document.getElementById('baseDatos').value;
            $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'crear',
                    Subcontratista: rowData.subcontratista || '',
                    Correo: rowData.correo_contacto || '',
                    NIT: rowData.NIT || '',
                    alcance: rowData.alcance || '',
                    tipo_proveedor: rowData.tipo_proveedor || ''
                },
                success: function(res) {
                    if (res.status === 'success') {
                        showFeedback('success');
                        loadData(); // Recargar para obtener el nuevo Id
                    } else {
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error creando: " + (res.message || 'Error desconocido'));
                    }
                },
                error: function(err) {
                    console.error(err);
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error de red al crear subcontratista");
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
        
        // ==========================================
        // MOBILE CARD VIEW RENDERER
        // ==========================================
        function renderMobileCards(data) {
            const container = document.getElementById('mobile-card-view');
            container.innerHTML = '';
            
            const providerTypes = ['Mano de Obra', 'Suministro e Instalación', 'Suministro de Materiales, Herramientas o Equipos'];

            // Generate Card Form for New Entry
            let html = `
                <div class="mobile-card" style="border: 2px dashed #007aff; background: #f9faff;">
                    <h5 style="color:#007aff; text-align:center; margin-bottom:15px; font-weight:bold;">
                        <i class="fas fa-plus-circle"></i> Agregar Nuevo
                    </h5>
                     <div class="form-group"><input type="text" class="form-control" id="new-mobile-subcontratista" placeholder="Nombre Subcontratista"></div>
                     <div class="form-group"><input type="email" class="form-control" id="new-mobile-correo" placeholder="Correo electrónico"></div>
                     <div class="form-group"><input type="text" class="form-control" id="new-mobile-nit" placeholder="NIT"></div>
                     <div class="form-group"><textarea class="form-control" id="new-mobile-alcance" placeholder="Alcance"></textarea></div>
                     <div class="form-group">
                        <select class="form-control" id="new-mobile-tipo">
                            <option value="">Seleccione Tipo...</option>
                            ${providerTypes.map(t => `<option value="${t}">${t}</option>`).join('')}
                        </select>
                     </div>
                    <button class="btn btn-primary btn-block shadow-sm" onclick="addMobileSubcontratista()">
                        Guardar Nuevo
                    </button>
                </div>
            `;

            data.forEach(row => {
               // Subcontratistas uses 'Id'
               let id = row.Id || row.id;
               if(!id) return;

               let isChecked = (row.activo == 1 || row.activo == '1') ? 'checked' : '';

                html += `
                <div class="mobile-card">
                    <div class="mobile-card-row">
                        <span class="mobile-label">Nombre</span>
                        <input type="text" class="form-control" style="flex:1; margin-left:20px; text-align:right;"
                               value="${row.subcontratista || ''}"
                               onchange="updateMobileRow(${id}, 'subcontratista', this.value)">
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Correo</span>
                        <input type="email" class="form-control" style="flex:1; margin-left:20px; text-align:right;" 
                               value="${row.correo_contacto || ''}" 
                               onchange="updateMobileRow(${id}, 'correo_contacto', this.value)">
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">NIT</span>
                        <input type="text" class="form-control" style="flex:1; margin-left:20px; text-align:right;" 
                               value="${row.NIT || ''}" 
                               onchange="updateMobileRow(${id}, 'NIT', this.value)">
                    </div>
                    <div class="mobile-card-row" style="flex-direction:column; align-items:flex-start;">
                        <span class="mobile-label" style="margin-bottom:5px;">Alcance</span>
                        <textarea class="form-control" style="width:100%;" 
                                  onchange="updateMobileRow(${id}, 'alcance', this.value)">${row.alcance || ''}</textarea>
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Tipo</span>
                        <select class="form-control" style="flex:1; margin-left:20px;" 
                                onchange="updateMobileRow(${id}, 'tipo_proveedor', this.value)">
                            ${providerTypes.map(t => `<option value="${t}" ${row.tipo_proveedor == t ? 'selected' : ''}>${t}</option>`).join('')}
                        </select>
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label">Activo</span>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="switch-${id}" ${isChecked}
                                   onchange="updateMobileRow(${id}, 'activo', this.checked ? 1 : 0)">
                            <label class="custom-control-label" for="switch-${id}"></label>
                        </div>
                    </div>
                    
                    <div class="mobile-actions">
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteMobileRow(${id}, '${row.subcontratista}')"><i class="fas fa-trash"></i> Eliminar</button>
                    </div>
                </div>
               `; 
            });
            
            container.innerHTML = html;
        }
        
        function addMobileSubcontratista() {
            const nombre = $('#new-mobile-subcontratista').val();
            const correo = $('#new-mobile-correo').val();
            const nit = $('#new-mobile-nit').val();
            const alcance = $('#new-mobile-alcance').val();
            const tipo = $('#new-mobile-tipo').val();

            if (!nombre) {
                if (window.AIA && window.AIA.Notice) window.AIA.Notice.warning("Por favor ingrese al menos el nombre");
                return;
            }

            const db = document.getElementById('baseDatos').value;

            $.ajax({
                url: '/api/subcontratistas/save?db=' + db,
                type: 'POST',
                dataType: 'json',
                data: {
                    opcion: 'crear',
                    Subcontratista: nombre,
                    Correo: correo,
                    NIT: nit,
                    alcance: alcance,
                    tipo_proveedor: tipo
                },
                success: function(res) {
                    if (res.status === 'success') {
                        loadData();
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.badge('success', "Subcontratista registrado correctamente");
                        // Clear form
                        $('#new-mobile-subcontratista').val('');
                        $('#new-mobile-correo').val('');
                        $('#new-mobile-nit').val('');
                        $('#new-mobile-alcance').val('');
                        $('#new-mobile-tipo').val('');
                    } else {
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.error("Error: " + (res.message || res.respuesta));
                    }
                }
            });
        }

        function updateMobileRow(id, prop, value) {
            autosave(id, prop, value);
        }

        function deleteMobileRow(id, nombre) {
            if (window.AIA && window.AIA.Notice) {
                window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + (nombre || 'este registro') + '?', 'Eliminar Subcontratista').then((confirmed) => {
                    if (confirmed) deleteRow(id);
                });
            }
        }
    </script>
</body>
</html>
