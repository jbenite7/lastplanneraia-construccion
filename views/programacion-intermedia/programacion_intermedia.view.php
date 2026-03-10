<!DOCTYPE html>
<html lang="es">
<head id="head">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script type="text/javascript" src="/js/linksComunesHead2.js" charset="utf-8"></script>
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <link rel="stylesheet" href="/css/handsontable-module.css" />
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
            height: calc(100vh - 280px);
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

        .pi-actions-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
        }

        .pi-toolbar-actions {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .pi-status-badges {
            display: inline-flex;
            gap: 4px;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
            min-height: 0;
            margin-left: 2px;
        }

        .pi-status-badges .badge {
            min-width: 72px;
            justify-content: center;
            line-height: 1.1;
        }

        .pi-page #piLegend.pdc-legend-autoscaling {
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            overflow-x: hidden;
            overflow-y: visible;
            padding: 4px 0 !important;
        }

        .pi-page #piLegend.pdc-legend-autoscaling .pdc-legend-item {
            flex: 0 1 auto !important;
            width: auto !important;
            white-space: nowrap !important;
            min-height: 32px;
            margin: 0 !important;
        }

        .pi-page #hot-container td.force-wrap,
        .pi-page #hot-container th.force-wrap {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        .pi-page #hot-container .handsontable {
            font-size: 0.76rem;
            line-height: 1.05;
        }

        .pi-page #hot-container .handsontable td,
        .pi-page #hot-container .handsontable th {
            font-size: 0.76rem !important;
            line-height: 1.05;
        }

        .pi-page #hot-container .handsontable td b,
        .pi-page #hot-container .handsontable td strong,
        .pi-page #hot-container .handsontable td small {
            font-size: inherit !important;
            line-height: inherit;
        }

        .pi-page #hot-container .handsontable td small {
            font-size: 0.74em !important;
        }

        .pi-page #hot-container .handsontable td b,
        .pi-page #hot-container .handsontable td strong {
            font-weight: 600;
        }

        .pi-page #hot-container td.pi-cell-editable {
            box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.05);
            cursor: text;
        }

        .pi-page #hot-container td.pi-cell-readonly {
            box-shadow: inset 0 0 0 9999px rgba(148, 163, 184, 0.06);
            cursor: not-allowed;
        }

        .pi-page #hot-container td.pi-cell-editable.current,
        .pi-page #hot-container td.pi-cell-editable.area {
            box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.08), inset 0 0 0 2px rgba(22, 163, 74, 0.32);
        }

        .pi-page #hot-container td.pi-cell-dropdown {
            position: relative;
            padding-right: 16px !important;
        }

        .pi-page #hot-container td.pi-cell-dropdown::after {
            content: "▾";
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: rgba(71, 85, 105, 0.72);
            pointer-events: none;
        }

        .pi-page #hot-container td.pi-cell-dropdown.current::after {
            color: #1e5ea8;
        }

        .pi-page #hot-container td.pi-shared-selector {
            cursor: pointer;
            text-align: center;
        }

        .pi-page #hot-container td.pi-shared-selector .htCheckboxRendererInput {
            transform: scale(1.04);
        }

        .pi-page #hot-container td.pi-row-shared-picked {
            box-shadow: inset 0 0 0 9999px rgba(30, 94, 168, 0.08);
        }

        .pi-page #hot-container .handsontable thead th {
            position: relative !important;
            padding: 0 !important;
            text-align: center !important;
        }

        .pi-page #hot-container .handsontable thead th .relative {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            gap: 2px;
            width: 100%;
            padding: 0 1px;
            box-sizing: border-box;
        }

        .pi-page #hot-container .handsontable thead th .relative > .colHeader {
            order: 1;
            width: 100%;
        }

        .pi-page #hot-container .handsontable thead th .relative > .changeType {
            order: 2;
            align-self: flex-end;
            margin: 0 !important;
            margin-top: 1px !important;
        }

        .pi-page #hot-container .handsontable thead th .colHeader {
            display: block;
            flex: 1 1 auto;
            min-width: 0;
            padding: 0 !important;
            margin: 0;
            line-height: 1;
            font-size: 0.66rem;
            white-space: normal;
            overflow: hidden;
            text-overflow: clip;
            word-break: normal;
            overflow-wrap: break-word;
            text-align: center !important;
        }

        .pi-page #hot-container .handsontable thead th .colHeader.pi-header-single-word {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pi-page #hot-container .handsontable .changeType {
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
            flex: 0 0 auto;
            order: 2;
            margin-left: 0 !important;
        }

        .pi-page #hot-container .handsontable .changeType:before {
            content: "\f0b0";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 6px;
            line-height: 1;
        }

        .pi-page #hot-container .handsontable .changeType:hover,
        .pi-page #hot-container .handsontable .changeType:focus {
            border-color: #7ea7d8;
            background: #eaf3ff;
            color: #1e5ea8;
            cursor: pointer;
        }

        .pi-page .htDropdownMenu:not(.htGhostTable),
        .pi-page .htFiltersConditionsMenu:not(.htGhostTable) {
            z-index: 1085;
        }

        #modal_shared_constraint .modal-content {
            border-radius: 14px;
            overflow: hidden;
        }

        #modal_shared_constraint .form-group label {
            margin-bottom: 0.32rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #2c3a48;
        }

        #modal_shared_constraint .form-control,
        #modal_shared_constraint .form-control-sm {
            min-height: 2.2rem;
            height: 2.2rem;
            padding: 0.34rem 0.68rem;
            font-size: 0.92rem !important;
            line-height: 1.25 !important;
        }

        #modal_shared_constraint select.form-control,
        #modal_shared_constraint select.form-control-sm {
            padding-top: 0.3rem;
            padding-bottom: 0.3rem;
            padding-right: 1.9rem;
            background-position: right 0.55rem center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #modal_shared_constraint textarea.form-control,
        #modal_shared_constraint textarea.form-control-sm {
            min-height: 4.6rem;
            height: auto;
            line-height: 1.35 !important;
        }

        #modal_shared_constraint .form-row > .form-group {
            margin-bottom: 0.62rem;
        }

        .pi-shared-preview {
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            background: linear-gradient(180deg, #f9fbfe 0%, #f4f8fc 100%);
            padding: 12px;
            min-height: 96px;
            max-height: 380px;
            font-size: 0.85rem;
            line-height: 1.28;
            white-space: normal;
            overflow: auto;
        }

        .pi-shared-preview-shell {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pi-shared-kpis {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        }

        .pi-shared-kpi {
            border: 1px solid #dbe6f1;
            border-radius: 8px;
            background: #ffffff;
            padding: 7px 9px;
            min-height: 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
        }

        .pi-shared-kpi-label {
            font-size: 0.72rem;
            letter-spacing: 0.01em;
            color: #607083;
            text-transform: uppercase;
        }

        .pi-shared-kpi-value {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1f2d3a;
            overflow-wrap: anywhere;
        }

        .pi-shared-coverage {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pi-shared-coverage-track {
            flex: 1 1 auto;
            height: 8px;
            border-radius: 999px;
            background: #dde6f0;
            overflow: hidden;
        }

        .pi-shared-coverage-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2f7dd6 0%, #1e62b2 100%);
        }

        .pi-shared-coverage-text {
            min-width: 88px;
            font-size: 0.78rem;
            font-weight: 600;
            text-align: right;
            color: #516273;
        }

        .pi-shared-missing {
            border: 1px solid #f3c4be;
            border-radius: 8px;
            background: #fff6f5;
            padding: 7px 9px;
            color: #8f3a2f;
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .pi-shared-table-wrap {
            border: 1px solid #dbe6f1;
            border-radius: 8px;
            overflow: auto;
            max-height: 228px;
            background: #ffffff;
        }

        .pi-shared-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .pi-shared-table th,
        .pi-shared-table td {
            border-bottom: 1px solid #e6edf5;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }

        .pi-shared-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #4f6173;
            background: #eef4fa;
        }

        .pi-shared-col-id {
            white-space: nowrap;
            font-weight: 700;
            color: #27435f;
        }

        .pi-shared-activity-cell {
            min-width: 260px;
            max-width: 420px;
            overflow-wrap: anywhere;
        }

        .pi-shared-delta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .pi-shared-delta-up {
            color: #0e5c32;
            background: #e9f7ee;
            border: 1px solid #bfe7cc;
        }

        .pi-shared-delta-down {
            color: #9d321f;
            background: #fff1ed;
            border: 1px solid #f4c9c0;
        }

        .pi-shared-delta-neutral {
            color: #4f6173;
            background: #f1f5f9;
            border: 1px solid #d9e2ec;
        }

        .pi-shared-more,
        .pi-shared-empty,
        .pi-shared-loading {
            font-size: 0.8rem;
            color: #506173;
        }

        .pi-shared-empty {
            border: 1px dashed #c7d6e6;
            border-radius: 8px;
            background: #ffffff;
            padding: 9px 10px;
        }

        .pi-shared-more {
            font-weight: 600;
            color: #466382;
        }

        .pi-shared-loading {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 52px;
            font-weight: 600;
        }

        .pi-shared-hint {
            font-size: 0.8rem;
            color: #5b6b7a;
        }

        .pi-shared-tools {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .pi-shared-tools .btn {
            padding: 0.16rem 0.5rem;
            font-size: 0.76rem;
            line-height: 1.15;
        }

        .pi-shared-selection-info {
            display: block;
            margin-top: 4px;
            font-size: 0.78rem;
            color: #5b6b7a;
        }

        #shared-selection-count {
            min-width: 68px;
            text-align: center;
        }

        @media (max-width: 991px) {
            #hot-container {
                height: calc(100vh - 360px);
            }

            .pi-shared-activity-cell {
                min-width: 220px;
            }

            .pi-shared-kpis {
                grid-template-columns: repeat(2, minmax(110px, 1fr));
            }
        }
        /* Help trigger icons in restriction headers */
        .pi-header-controls {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            order: 2;
            width: 100%;
        }
        .pi-help-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #5b7fa6;
            font-size: 8px;
            cursor: pointer;
            line-height: 1;
        }
        .pi-help-trigger:hover {
            color: #1e5ea8;
        }
        .tooltip-inner--wide {
            max-width: 380px;
            text-align: left;
            font-size: 0.82rem;
            line-height: 1.4;
            padding: 10px 14px;
        }

        /* ── Tom Select · Línea Construcción AIA ──────────────────
           Paleta: Naranja #b55211 (main) · #e87722 (mid) · #fbead9 (very light)
           Fondo:  Alabaster #fafafa · Linen #f4f1ea
           Tipografía: Inter (contenido) · sin abuso de uppercase
           ──────────────────────────────────────────────────────── */

        /* Wrapper flotante */
        .htTomSelectWrapper {
            min-width: 300px !important;
            background: #fafafa !important;        /* Alabaster AIA */
            border: 1.5px solid #b55211 !important; /* Naranja Construcción */
            border-radius: 6px !important;
            box-shadow: 0 4px 16px rgba(181,82,17,0.13) !important;
            font-family: 'Inter', 'Segoe UI', sans-serif !important;
        }

        /* Control principal (input + pills) */
        .htTomSelectWrapper .ts-wrapper {
            width: 100% !important;
        }
        .htTomSelectWrapper .ts-control {
            background: #fafafa !important;
            border: none !important;
            border-bottom: 1px solid #f6c79b !important; /* naranja light AIA */
            min-height: 38px !important;
            width: 100% !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            color: #1c1c1e !important;
            padding: 4px 8px !important;
        }
        .htTomSelectWrapper .ts-control input {
            min-width: 120px !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            color: #1c1c1e !important;
        }
        .htTomSelectWrapper .ts-control input::placeholder {
            color: #a1a1aa !important;
        }

        /* Pills (ítems seleccionados) */
        .htTomSelectWrapper .ts-control .item {
            background: #fbead9 !important;   /* very light naranja AIA */
            border: 1px solid #e87722 !important; /* mid naranja AIA */
            color: #8b4011 !important;         /* dark naranja AIA */
            border-radius: 4px !important;
            font-size: 0.78rem !important;
            font-weight: 500 !important;
            padding: 2px 6px !important;
        }
        .htTomSelectWrapper .ts-control .item .remove {
            color: #b55211 !important;
            border-left: 1px solid #e87722 !important;
        }
        .htTomSelectWrapper .ts-control .item .remove:hover {
            background: #f6c79b !important;
        }

        /* Dropdown */
        .htTomSelectWrapper .ts-dropdown {
            background: #fafafa !important;
            border: 1.5px solid #b55211 !important;
            border-top: none !important;
            border-radius: 0 0 6px 6px !important;
            width: 100% !important;
            left: 0 !important;
            max-height: 220px !important;
            overflow-y: auto !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            z-index: 99999 !important;
            box-shadow: 0 6px 16px rgba(181,82,17,0.10) !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-dropdown-content {
            max-height: 210px !important;
            overflow-y: auto !important;
        }

        /* Opción normal */
        .htTomSelectWrapper .ts-dropdown .ts-option {
            padding: 8px 12px !important;
            cursor: pointer !important;
            color: #1c1c1e !important;
            font-family: 'Inter', sans-serif !important;
            border-bottom: 1px solid #f4f1ea !important; /* Linen separator */
            transition: background 0.12s ease !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-option:hover,
        .htTomSelectWrapper .ts-dropdown .ts-option.active {
            background: #fbead9 !important; /* very light naranja */
            color: #8b4011 !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-option:last-child {
            border-bottom: none !important;
        }

        /* Opción especial "+ Crear" */
        .htTomSelectWrapper .ts-dropdown .ts-option[data-value*="Crear"],
        .htTomSelectWrapper .ts-dropdown .ts-option[data-value*="\u2795"] {
            color: #b55211 !important;
            font-weight: 600 !important;
            background: #fff9f5 !important;
            border-top: 1px solid #f6c79b !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-option[data-value*="Crear"]:hover,
        .htTomSelectWrapper .ts-dropdown .ts-option[data-value*="\u2795"]:hover {
            background: #fbead9 !important;
        }

        /* Botón limpiar todo (clear_button plugin) */
        .htTomSelectWrapper .ts-control .clear-button {
            color: #b55211 !important;
            opacity: 0.7 !important;
        }
        .htTomSelectWrapper .ts-control .clear-button:hover {
            opacity: 1 !important;
        }
        /* ─────────────────────────────────────────────────────── */
    </style>
    <link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260223a" />
</head>
<body class="pi-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programacion_intermedia" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int)($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_PHP" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="" aria-hidden="true">
    </div>

    <style>
      /* Brand Manual AIA: Green (#1a5633) para acciones de creación */
      .pi-create-option {
        background-color: #e8f0eb !important;
        color: #1a5633 !important;
        font-weight: 600 !important;
        border-top: 1px solid #c8ddd0 !important;
      }
      .pi-create-option:hover {
        background-color: #1a5633 !important;
        color: #fff !important;
      }
    </style>

    <div class="hot-full-bleed">
    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <div class="header-actions">
        <div class="pi-actions-row">
            <div class="pi-toolbar-actions">
                <button type="button" class="leyenda_colores btn-pdc-modern" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle ml-1"></i></button>
                <button id="btn_informe_compromisos" type="button" class="btn-pdc-modern">Descargar Corte <i class="fas fa-download ml-1"></i></button>
                <button id="btn-export" class="btn-pdc-modern">Exportar CSV</button>
                <button id="btn-refresh" class="btn-pdc-modern">Recargar</button>
                <button id="btn-shared-constraint" class="btn-pdc-modern">Restricción Compartida</button>
                <button id="btn-refresh-listas" class="btn-pdc-modern" title="Recargar listas de Subcontratistas y Profesionales">🔄 Listas</button>
                <button id="btn-shared-select-visible" class="btn-pdc-modern">Seleccionar visibles</button>
                <button id="btn-shared-clear-selection" class="btn-pdc-modern">Limpiar selección</button>
                <span id="shared-selection-count" class="badge badge-secondary">0 selec.</span>
                <div class="pi-status-badges">
                    <span id="save-status" class="badge badge-success" style="display:none;">Guardado</span>
                    <span id="save-error" class="badge badge-danger" style="display:none;">Error al guardar</span>
                </div>
            </div>
            <button class="btn-filter-toggle pdc-mobile-toggle" type="button" data-toggle="collapse" data-target="#pdcFiltersMobile" aria-expanded="false" aria-controls="pdcFiltersMobile">
                <i class="fas fa-filter"></i> Filtros <span class="badge badge-light" id="mobileFilterCount">0</span>
            </button>
        </div>

        <div class="collapse d-md-block" id="pdcFiltersMobile">
            <div class="pdc-legend pi-legend pdc-legend-autoscaling" id="piLegend">
                <span class="pdc-legend-item blocked-overdue-critical" data-filter="blocked-overdue-critical" role="button" tabindex="0"><span class="indicator"></span> Bloqueada Vencida (Crítica) <span id="count-blocked-overdue-critical" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item blocked-overdue" data-filter="blocked-overdue" role="button" tabindex="0"><span class="indicator"></span> Bloqueada Vencida <span id="count-blocked-overdue" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item blocked-due" data-filter="blocked-due" role="button" tabindex="0"><span class="indicator"></span> Debe Iniciar (Con Restric.) <span id="count-blocked-due" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-1-week" data-filter="alert-1-week" role="button" tabindex="0"><span class="indicator"></span> Alerta 1 Semana <span id="count-alert-1-week" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-2-3-weeks" data-filter="alert-2-3-weeks" role="button" tabindex="0"><span class="indicator"></span> Alerta 2-3 Sem <span id="count-alert-2-3-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-4-6-weeks" data-filter="alert-4-6-weeks" role="button" tabindex="0"><span class="indicator"></span> Alerta 4-6 Sem <span id="count-alert-4-6-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item execution-blocked" data-filter="execution-blocked" role="button" tabindex="0"><span class="indicator"></span> En Ejecución (Con Restric.) <span id="count-execution-blocked" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item liberated-control" data-filter="liberated-control" role="button" tabindex="0"><span class="indicator"></span> Liberada / Control <span id="count-liberated-control" class="count-badge">(...)</span></span>
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
                    <h4 class="modal-title" id="modal_leyenda_colores_Label">Guia Operativa - Programación Intermedia</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="modal_leyenda_colores_body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_shared_constraint" role="dialog" data-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><b>Aplicar Restricción Compartida</b></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="piSharedRestrictionType">Tipo de restricción</label>
                            <select id="piSharedRestrictionType" class="form-control form-control-sm">
                                <option value="D_y_E">Diseños y Especif.</option>
                                <option value="Materiales">Materiales</option>
                                <option value="MdeO">Mano de Obra</option>
                                <option value="Equipos">Equipos</option>
                                <option value="Predecesora">Predecesora</option>
                                <option value="Pdto_Cons">Proced. Constructivo</option>
                                <option value="Modelo">Modelación BIM</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="piSharedRestrictionValue">Valor objetivo</label>
                            <select id="piSharedRestrictionValue" class="form-control form-control-sm"></select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="piSharedActivityIds">Consecutivos objetivo</label>
                            <input id="piSharedActivityIds" type="text" class="form-control form-control-sm" placeholder="Ej: 120,121,122">
                            <small class="pi-shared-hint">Se cargan desde selección de filas (editable manualmente).</small>
                            <div class="pi-shared-tools">
                                <button id="btn_pi_shared_use_marked" type="button" class="btn btn-outline-secondary">Cargar marcadas</button>
                                <button id="btn_pi_shared_use_visible" type="button" class="btn btn-outline-secondary">Usar visibles</button>
                                <button id="btn_pi_shared_clear_ids" type="button" class="btn btn-outline-secondary">Limpiar lista</button>
                            </div>
                            <small id="piSharedSelectionInfo" class="pi-shared-selection-info">Marcadas: 0 | Visibles: 0</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="piSharedNote">Observación de lote</label>
                        <textarea id="piSharedNote" class="form-control form-control-sm" rows="2" placeholder="Causa | Acción | Responsable | Fecha | Evidencia"></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="mb-1">Preview de impacto</label>
                        <div id="piSharedPreview" class="pi-shared-preview">Seleccione filas y pulse "Preview".</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn_pi_shared_preview" type="button" class="btn btn-outline-primary">Preview</button>
                    <button id="btn_pi_shared_apply" type="button" class="btn btn-primary">Aplicar en Lote</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row ventanasModalesSemana" id="ventanasModalesSemana"></div>

    <!-- Resolve jQuery UI Tooltip conflict before Bootstrap -->
    <script>
    if ($.fn.tooltip) {
        $.fn.uitooltip = $.fn.tooltip;
        $.fn.tooltip = null;
    }
    if ($.widget && $.widget.bridge) {
        $.widget.bridge('uibutton', $.ui.button);
        $.widget.bridge('uitooltip', $.ui.tooltip);
    }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=gen02" charset="utf-8"></script>
    <script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script type="text/javascript" src="/js/modules/programacion_intermedia/stateMachine.js?v=pi2"></script>
    <script>
        window.PI_HOT_OPTIONS = {
            subcontratistas: <?php
                $piSubcontratistas = ['AIA (MO Directa)'];
                if (!empty($subcontratistas) && is_array($subcontratistas)) {
                    foreach ($subcontratistas as $sub) {
                        if (!empty($sub['subcontratista'])) {
                            $piSubcontratistas[] = $sub['subcontratista'];
                        }
                    }
                }
                $piSubcontratistas = array_values(array_unique(array_filter($piSubcontratistas)));
                echo json_encode($piSubcontratistas, JSON_UNESCAPED_UNICODE);
            ?>,
            profesionales: <?php
                $piProfesionales = [];
                if (!empty($profesionales) && is_array($profesionales)) {
                    foreach ($profesionales as $prof) {
                        if (!empty($prof['nombre'])) {
                            $piProfesionales[] = $prof['nombre'];
                        }
                    }
                }
                $piProfesionales = array_values(array_unique(array_filter($piProfesionales)));
                echo json_encode($piProfesionales, JSON_UNESCAPED_UNICODE);
            ?>
        };
    </script>
    <script type="text/javascript" src="/js/HandsontableTomSelectEditor.js?v=tomselect10"></script>
    <script src="/js/modules/programacion_intermedia/hot.js?v=hot26"></script>

    <script>
        function cargaParametros() {
            if (window.PIHotModule && typeof window.PIHotModule.init === 'function') {
                window.PIHotModule.init();
            }
        }

        $(document).ready(function() {
            cargarDatosGeneralesPagina(document.getElementById('seccion').value);
        });
    </script>
</body>
</html>
