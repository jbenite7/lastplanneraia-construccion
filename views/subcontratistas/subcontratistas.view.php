<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <!-- jQuery Must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Common Head Resources (Nav, CSS, etc) -->
    <?= \App\View\Components\DesignSystemHeadComponent::render() ?>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>

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
            background: var(--ds-active-surface-raised); /* F1 Task 3c: antes literal claro tipo Bootstrap light */
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
<body class="aia-shell aia-shell--sidebar">
<?php $isPreConstruccion = (($area ?? $_SESSION['area'] ?? 'Construccion') === 'Pre-Construccion'); ?>

    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <!-- Estructura Original de Navegación -->
    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="info_subcontratistas">
    </div>

    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <!-- Hidden inputs for Context -->
    <input type="hidden" id="baseDatos" value="<?php echo $_SESSION['db'] ?? 'Prueba'; ?>">
    <input type="hidden" id="permiso_canonico" value="<?php echo $_SESSION['permiso'] ?? 'V'; ?>">

<div class="header-actions action-bar">
        <h4><?php echo $isPreConstruccion ? 'Interesados Externos (Live Edición)' : 'Subcontratistas (Live Edición)'; ?></h4>
        <?php if ($isPreConstruccion): ?>
            <small class="text-muted d-block mt-1">Gestión de interesados externos del proyecto: Socios, Ventas, Gerencia, Diseñadores, Entidades.</small>
        <?php endif; ?>
        <div>
            <span id="save-status" class="badge badge-success" style="display:none;">Guardado</span>
            <span id="save-error" class="badge badge-danger" style="display:none;">Error al guardar</span>
            <button id="btn-export" class="btn-pdc-modern" onclick="exportCSV()"><i class="fas fa-file-excel"></i> Exportar</button>
            <?= \App\View\Components\BiAccessComponent::renderLink('subcontratistas', $isPreConstruccion ? 'BI Interesados' : 'BI Contratistas') ?>
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
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('subcontratistas') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>

    <!-- Handsontable Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/handsontable.full.min.js"></script>
    <!-- Languages -->
    <script src="https://cdn.jsdelivr.net/npm/handsontable@14.6.1/dist/languages/es-MX.js"></script>

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
                rowHeaders: true,
                rowHeaderWidth: 50,
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

        // ==========================================
        // MOBILE CARD VIEW RENDERER
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
                     <div class="form-group"><input type="text" class="form-control" id="new-mobile-subcontratista" placeholder="<?php echo $isPreConstruccion ? 'Nombre Interesado' : 'Nombre Subcontratista'; ?>"></div>
                     <div class="form-group"><input type="email" class="form-control" id="new-mobile-correo" placeholder="Correo electrónico"></div>
                     <div class="form-group"><input type="text" class="form-control" id="new-mobile-nit" placeholder="<?php echo $isPreConstruccion ? 'Identificación' : 'NIT'; ?>"></div>
                     <div class="form-group"><textarea class="form-control" id="new-mobile-alcance" placeholder="<?php echo $isPreConstruccion ? 'Rol/Interés' : 'Alcance'; ?>"></textarea></div>
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
                        <span class="mobile-label"><?php echo $isPreConstruccion ? 'Interesado' : 'Nombre'; ?></span>
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
                        <span class="mobile-label"><?php echo $isPreConstruccion ? 'Identificación' : 'NIT'; ?></span>
                        <input type="text" class="form-control" style="flex:1; margin-left:20px; text-align:right;"
                               value="${row.NIT || ''}"
                               onchange="updateMobileRow(${id}, 'NIT', this.value)">
                    </div>
                    <div class="mobile-card-row" style="flex-direction:column; align-items:flex-start;">
                        <span class="mobile-label" style="margin-bottom:5px;"><?php echo $isPreConstruccion ? 'Rol/Interés' : 'Alcance'; ?></span>
                        <textarea class="form-control" style="width:100%;"
                                  onchange="updateMobileRow(${id}, 'alcance', this.value)">${row.alcance || ''}</textarea>
                    </div>
                    <div class="mobile-card-row">
                        <span class="mobile-label"><?php echo $isPreConstruccion ? 'Tipo de Interesado' : 'Tipo'; ?></span>
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

            const payload = buildSubcontratistaPayload({
                subcontratista: nombre,
                correo_contacto: correo,
                NIT: nit,
                alcance: alcance,
                tipo_proveedor: tipo,
                activo: 1
            });

            const errors = collectSubcontratistaValidationErrors(payload, {});
            if (errors.length) {
                showValidationMessage(errors, 'warning');
                return;
            }

            const db = document.getElementById('baseDatos').value;

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
                        loadData();
                        if (window.AIA && window.AIA.Notice) window.AIA.Notice.badge('success', "<?php echo $isPreConstruccion ? 'Interesado registrado correctamente' : 'Subcontratista registrado correctamente'; ?>");
                        $('#new-mobile-subcontratista').val('');
                        $('#new-mobile-correo').val('');
                        $('#new-mobile-nit').val('');
                        $('#new-mobile-alcance').val('');
                        $('#new-mobile-tipo').val('');
                    } else {
                        showValidationMessage(res.errors || [res.message || res.respuesta || '<?php echo $isPreConstruccion ? 'No se pudo crear el interesado.' : 'No se pudo crear el subcontratista.'; ?>'], 'warning');
                    }
                },
                error: function() {
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.error('<?php echo $isPreConstruccion ? 'Error de red al crear interesado.' : 'Error de red al crear subcontratista.'; ?>');
                }
            });
        }

        function updateMobileRow(id, prop, value) {
            autosave(id, prop, value);
        }

        function deleteMobileRow(id, nombre) {
            if (window.AIA && window.AIA.Notice) {
                window.AIA.Notice.confirm('¿Seguro que desea eliminar a ' + (nombre || 'este registro') + '?', '<?php echo $isPreConstruccion ? 'Eliminar Interesado' : 'Eliminar Subcontratista'; ?>').then((confirmed) => {
                    if (confirmed) deleteRow(id);
                });
            }
        }
    </script>
</body>
</html>
