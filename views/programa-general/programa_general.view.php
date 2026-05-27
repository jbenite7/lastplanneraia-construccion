<!DOCTYPE html>
<html lang="es">
<head id="head">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260325a" charset="utf-8"></script>
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <link rel="stylesheet" href="/css/handsontable-module.css?v=20260522d" />
    <style>
        .hot-full-bleed {
            --hot-gutter: 8px;
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding-left: var(--hot-gutter);
            padding-right: var(--hot-gutter);
            box-sizing: border-box;
        }

        #hot-container {
            height: calc(100vh - 315px);
            margin-top: 4px;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        /* HOT internal containers: let Handsontable manage its own layout */

        .header-actions {
            display: grid;
            gap: 8px;
            align-items: center;
        }

        .pg-actions-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .pg-status-badges {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            min-width: 220px;
            min-height: 24px;
        }

        .pg-status-badges .badge {
            min-width: 88px;
            justify-content: center;
        }

        .pg-page #hot-container td.force-wrap,
        .pg-page #hot-container th.force-wrap {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        .pg-page #hot-container td.pg-cell-editable {
            box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.06);
            cursor: text;
        }

        .pg-page #hot-container td.pg-cell-readonly {
            box-shadow: inset 0 0 0 9999px rgba(148, 163, 184, 0.08);
            cursor: not-allowed;
        }

        .pg-page #hot-container td.pg-cell-editable.current,
        .pg-page #hot-container td.pg-cell-editable.area {
            box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.08), inset 0 0 0 2px rgba(22, 163, 74, 0.38);
        }

        .pg-page #hot-container .handsontable thead th {
            position: relative !important;
            text-align: center !important;
        }

        .pg-page #hot-container .handsontable thead th .relative {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            gap: 2px;
            width: 100%;
            padding: 0 1px;
            box-sizing: border-box;
        }

        .pg-page #hot-container .handsontable thead th .relative > .colHeader {
            order: 1;
            width: 100%;
        }

        .pg-page #hot-container .handsontable thead th .relative > .changeType {
            order: 2;
            align-self: flex-end;
            margin: 0 !important;
            margin-top: 1px !important;
        }

        .pg-page #hot-container .handsontable thead th .colHeader {
            display: block;
            padding: 0 !important;
            line-height: 1.15;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            word-break: break-word;
            text-align: center !important;
        }

        .pg-page #hot-container .handsontable thead th .changeType {
            float: none !important;
            position: static !important;
            transform: none;
            width: 13px;
            height: 13px;
            margin: 0 !important;
            padding: 0 !important;
            border: 1px solid #cfd8e3;
            border-radius: 4px;
            background: #f4f7fb;
            color: #5c6b7a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            z-index: 2;
        }

        .pg-page #hot-container .handsontable .changeType:before {
            content: "\f0b0";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 9px;
            line-height: 1;
        }

        .pg-page #hot-container .handsontable thead th .changeType:hover,
        .pg-page #hot-container .handsontable thead th .changeType:focus {
            border-color: #7ea7d8;
            background: #eaf3ff;
            color: #1e5ea8;
            cursor: pointer;
        }

        .pg-page .htDropdownMenu:not(.htGhostTable),
        .pg-page .htFiltersConditionsMenu:not(.htGhostTable) {
            z-index: 1085;
        }

        @media (max-width: 991px) {
            #hot-container {
                height: calc(100vh - 390px);
            }
        }
    </style>
    <link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260223a" />
    <!-- Toastr (Mensajes Emergentes) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</head>
<body class="pg-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programa_general" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int)($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="" aria-hidden="true">
    </div>

    <div class="hot-full-bleed">
    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <div class="header-actions">
        <div class="pg-actions-row">
            <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
                <button type="button" class="leyenda_colores btn-pdc-modern" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle ml-1"></i></button>
                <button type="button" id="actualizarEjecucion" class="btn-pdc-modern">Actualizar Ejecución <i class="fas fa-sync ml-1"></i></button>
                <button type="button" id="descargarCorteProgramacion" class="btn-pdc-modern">Descargar Corte <i class="fas fa-download ml-1"></i></button>
                <button id="btn-export" class="btn-pdc-modern">Exportar CSV</button>
                <button id="btn-refresh" class="btn-pdc-modern">Recargar</button>
            </div>
            <div class="pg-status-badges">
                <span id="save-status" class="badge badge-success" style="display:none;">Guardado</span>
                <span id="save-error" class="badge badge-danger" style="display:none;">Error al guardar</span>
                <button class="btn-filter-toggle pdc-mobile-toggle" type="button" data-toggle="collapse" data-target="#pdcFiltersMobile" aria-expanded="false" aria-controls="pdcFiltersMobile">
                    <i class="fas fa-filter"></i> Filtros <span class="badge badge-light" id="mobileFilterCount">0</span>
                </button>
            </div>
        </div>

        <div class="collapse d-md-block" id="pdcFiltersMobile">
            <div class="pdc-legend pg-legend" id="pgLegend">
                <span class="pdc-legend-item alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0"><span class="indicator"></span> Con Alerta Restricciones <span id="count-con-alerta-restricciones" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0"><span class="indicator"></span> Debe Iniciar <span id="count-debe-iniciar" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item actividad-futura" data-filter="actividad-futura" role="button" tabindex="0"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item adelantada" data-filter="adelantada" role="button" tabindex="0"><span class="indicator"></span> Adelantada <span id="count-adelantada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item en-curso" data-filter="en-curso" role="button" tabindex="0"><span class="indicator"></span> En Curso <span id="count-en-curso" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada-critica" data-filter="atrasada-critica" role="button" tabindex="0"><span class="indicator"></span> Atrasada (Crítica) <span id="count-atrasada-critica" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada" data-filter="atrasada" role="button" tabindex="0"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item terminada" data-filter="terminada" role="button" tabindex="0"><span class="indicator"></span> Terminada <span id="count-terminada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item no-requerida" data-filter="no-requerida" role="button" tabindex="0"><span class="indicator"></span> No Requerida <span id="count-no-requerida" class="count-badge">(...)</span></span>
            </div>
        </div>
    </div>

    <div id="hot-container"></div>
    <div id="mobile-card-view" style="display:none;"></div>
    </div>

    <div class="modal fade" id="modal_leyenda_colores" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal_leyenda_colores_Label">Guia Operativa - Programa General</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="modal_leyenda_colores_body"></div>
            </div>
        </div>
    </div>

    <div class="row ventanasModalesSemana" id="ventanasModalesSemana"></div>

    <?php include __DIR__ . '/../partials/drawer_unificado.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script src="/js/modules/lps_drawer.js?v=20260522d"></script>
    <?php $pgHotVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/programa_general/hot.js') ?: 'hot12'; ?>
    <script src="/js/modules/programa_general/hot.js?v=<?php echo urlencode((string)$pgHotVersion); ?>"></script>

    <script>
        function cargaParametros() {
            if (window.PGHotModule && typeof window.PGHotModule.init === 'function') {
                window.PGHotModule.init();
            }
        }

        $(document).ready(function() {
            cargarDatosGeneralesPagina(document.getElementById('seccion').value);
        });
    </script>
</body>
</html>
