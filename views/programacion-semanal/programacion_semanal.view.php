<?php
$categoriasCP = [
    "Buen Rendimiento",
    "Oportunidad Detectada",
    "Mano de Obra Disponible",
    "Materiales Disponibles",
    "Equipos Disponibles",
    "Disenos Listos",
    "Gestion Resuelta",
    "Condiciones Favorables",
    "Compensacion de Frente"
];
?>
<!DOCTYPE html>
<html lang="es">
<head id="head">
    <script src="/public/vendor/jquery.min.js"></script>
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=headLoaderV20260530" charset="utf-8"></script>
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <link rel="stylesheet" href="/css/handsontable-module.css?v=20260529a" />
    <link rel="stylesheet" href="/public/vendor/select2/select2.min.css" />
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
            height: calc(100vh - 380px);
            margin-top: 4px;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        #mensajeActualizacion {
            margin: 0 !important;
            min-height: 0;
            line-height: 1.1;
        }

        #mensajeActualizacion:empty {
            display: none;
        }

        .header-actions {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: 1fr; /* Forzar 100% del ancho del padre */
            gap: 8px;
            align-items: center;
            width: 100%;
            max-width: 100%;
            --ps-hot-scale: 1;
            --ps-hot-gap: 6px;
            --ps-hot-btn-font: 0.72rem;
            --ps-hot-btn-icon: 0.76rem;
            --ps-hot-btn-py: 0.2rem;
            --ps-hot-btn-px: 0.5rem;
            --ps-hot-btn-h: 1.75rem;
            --ps-hot-tab-font: 0.78rem;
            --ps-hot-tab-py: 0.45rem;
            --ps-hot-tab-px: 0.75rem;
        }

        .ps-actions-row {
            display: flex;
            gap: calc(var(--ps-hot-gap) * var(--ps-hot-scale));
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between; /* Alineación Dual */
            min-width: 0;
            width: 100%;
            overflow: visible !important;
            padding: 2px 0;
        }

        .ps-toolbar-left, .ps-toolbar-right {
            display: flex;
            align-items: center;
            gap: calc(var(--ps-hot-gap) * var(--ps-hot-scale));
        }

        .ps-toolbar-right {
           margin-left: auto;
        }

        .ps-status-badges {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 88px;
            min-height: 24px;
        }

        .ps-status-badges .badge {
            min-width: 88px;
            justify-content: center;
        }

        .ps-actions-row.ps-actions-stacked {
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .ps-actions-row.ps-actions-stacked .ps-hot-toolbar-actions {
            flex: 1 1 100%;
        }

        .ps-actions-row.ps-actions-stacked .ps-module-switcher {
            margin-left: 0;
        }

        .ps-actions-row.ps-actions-stacked .pdc-mobile-toggle {
            width: 100%;
        }

        .ps-hot-toolbar-actions {
            display: inline-flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: calc(var(--ps-hot-gap) * var(--ps-hot-scale));
            max-inline-size: none;
            inline-size: auto;
            min-width: 0;
            flex: 1 1 auto;
        }

        .ps-hot-status-badges {
            display: inline-flex;
            gap: calc(4px * var(--ps-hot-scale));
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
            min-height: 0;
            margin-left: 2px;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .ps-hot-status-badges .badge {
            min-width: calc(72px * var(--ps-hot-scale));
            justify-content: center;
            line-height: 1.1;
            font-size: calc(0.72rem * var(--ps-hot-scale));
            padding: calc(0.22rem * var(--ps-hot-scale)) calc(0.42rem * var(--ps-hot-scale));
        }

        .ps-page .ps-hot-toolbar-shell .btn-pdc-modern {
            min-block-size: calc(var(--ps-hot-btn-h) * var(--ps-hot-scale));
            padding: calc(var(--ps-hot-btn-py) * var(--ps-hot-scale)) calc(var(--ps-hot-btn-px) * var(--ps-hot-scale));
            font-size: calc(var(--ps-hot-btn-font) * var(--ps-hot-scale));
            line-height: 1;
            border-radius: 4px; /* Rectangular: Estilo PG/PI */
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .ps-page .ps-hot-toolbar-shell .btn-pdc-modern i {
            font-size: calc(var(--ps-hot-btn-icon) * var(--ps-hot-scale));
        }

        /* Etiquetas Adaptativas: Ocultar texto en pantallas medianas/pequeñas */
        @media (max-width: 1200px) {
            .ps-page .ps-hot-toolbar-shell .btn-pdc-modern span {
                display: none !important;
            }
            .ps-page .ps-hot-toolbar-shell .btn-pdc-modern i {
                margin: 0 !important;
            }
        }

        .ps-page .btn-group-pdc {
            display: inline-flex;
            gap: 1px;
            background: #e2e8f0;
            padding: 1px;
            border-radius: 4px;
        }

        .ps-page .btn-group-pdc .btn-pdc-modern {
            border-radius: 0 !important;
            box-shadow: none !important;
        }

        .ps-page .btn-group-pdc .btn-pdc-modern:first-child {
            border-radius: 4px 0 0 4px !important;
        }

        .ps-page .btn-group-pdc .btn-pdc-modern:last-child {
            border-radius: 0 4px 4px 0 !important;
        }


        /* Dropdown de Navegación por Hover */
        /* Dropdown de Navegación por Hover - Visibility Fix */
        .ps-dropdown-nav {
            position: relative;
            display: inline-block;
            z-index: 1000;
        }

        /* Puente invisible para evitar que el dropdown se cierre en el espacio en blanco */
        .ps-dropdown-nav::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -15px;
            height: 15px;
        }

        .ps-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: #ffffff;
            min-width: 240px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.2);
            z-index: 1001;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            overflow: visible !important;
            margin-top: 4px;
        }

        .ps-dropdown-nav:hover .ps-dropdown-content,
        .ps-dropdown-nav.is-open .ps-dropdown-content {
            display: block !important;
        }

        .ps-dropdown-item {
            color: #334155;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .ps-dropdown-item:last-child {
            border-bottom: none;
        }

        .ps-dropdown-item:hover {
            background-color: #f1f5f9;
            color: #1e5ea8;
        }

        .ps-dropdown-item.is-active {
            background-color: #eff6ff !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            border-left: 3px solid #1e5ea8 !important;
        }

        .ps-dropdown-item i {
            width: 18px;
            text-align: center;
            color: #64748b;
        }

        .btn-dropdown-trigger {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e5ea8 !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        }

        .btn-dropdown-trigger:hover {
            background: #f8fafc !important;
            border-color: #1e5ea8 !important;
        }

        #textoFechaCierreCompromisos {
            font-size: 0.86rem;
            color: #475569;
            margin-left: 8px;
        }

        .ps-page #hot-container td.force-wrap,
        .ps-page #hot-container th.force-wrap {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        .ps-page #hot-container td.ops-state-td {
            padding: 4px 6px !important;
            vertical-align: middle !important;
        }

        .ps-page .ops-state-zoom {
            display: grid;
            gap: 4px;
            width: 100%;
            min-width: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            text-align: left;
            cursor: zoom-in;
        }

        .ps-page .ops-state-zoom:focus-visible {
            outline: 2px solid oklch(62% 0.18 250);
            outline-offset: 2px;
            border-radius: 8px;
        }

        .ps-page .ops-state-topline {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 4px;
            width: 100%;
            min-width: 0;
        }

        .ps-page .ops-state-chip {
            display: -webkit-box;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            padding: 4px 8px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            color: inherit;
            font-weight: 900;
            font-size: 0.78rem;
            line-height: 1.18;
            overflow: hidden;
            overflow-wrap: anywhere;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08), inset 0 0 0 1px rgba(255, 255, 255, 0.48);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-critical-route .ops-state-chip {
            background: var(--aia-alert-background, #fdecec);
            background: color-mix(in srgb, var(--aia-alert-background, #fdecec) 78%, #fff 22%);
            border-color: var(--aia-alert-high, #e53935);
            color: var(--aia-alert-critical, #9a1f1f);
            font-weight: 900;
            box-shadow: inset 0 0 0 1px rgba(229, 57, 53, 0.2), 0 1px 3px rgba(154, 31, 31, 0.12);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-critical .ops-state-chip {
            background: var(--aia-alert-background, #fdecec);
            background: color-mix(in srgb, var(--aia-alert-background, #fdecec) 76%, #fff 24%);
            border-color: var(--aia-alert-high, #e53935);
            color: var(--aia-alert-critical, #9a1f1f);
            font-weight: 900;
            box-shadow: inset 0 0 0 1px rgba(229, 57, 53, 0.2), 0 1px 3px rgba(154, 31, 31, 0.12);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-high .ops-state-chip,
        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-medium .ops-state-chip,
        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-control .ops-state-chip {
            font-weight: 800;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-high .ops-state-chip {
            background: var(--aia-orange-very-light, #fbead9);
            background: color-mix(in srgb, var(--aia-orange-very-light, #fbead9) 68%, #fff 32%);
            border-color: var(--aia-orange-primary, #b55211);
            color: var(--aia-orange-dark, #8b4011);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-medium .ops-state-chip {
            background: var(--aia-warning-background, #fff8e1);
            background: color-mix(in srgb, var(--aia-warning-background, #fff8e1) 68%, #fff 32%);
            border-color: var(--aia-warning-high, #ffca28);
            color: var(--aia-warning-critical, #a0731a);
        }

        .ps-page .handsontable td.ops-state-td.ps-row-state.ps-alert-control .ops-state-chip {
            background: var(--aia-green-very-light, #d5e5db);
            background: color-mix(in srgb, var(--aia-green-very-light, #d5e5db) 66%, #fff 34%);
            border-color: var(--aia-green-primary, #1a5633);
            color: var(--aia-green-dark, #1a3c2a);
        }

        .ps-alert-tnp {
            border-left: 3px solid #4a81bd;
            background-color: #e6f0fa;
        }

        .ps-page .ops-state-count,
        .ps-page .ops-state-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: oklch(97% 0.02 80);
            color: oklch(38% 0.12 55);
            font-weight: 800;
            font-size: 0.75rem;
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(120, 53, 15, 0.12);
        }

        .ps-page .ops-state-pills {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 3px;
            min-width: 0;
            overflow: hidden;
            max-height: 36px;
        }

        .ps-page .ops-state-pill,
        .ps-page .ops-state-ready {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            max-width: calc(100% - 24px);
            padding: 3px 6px;
            border: 1px solid rgba(120, 53, 15, 0.1);
            border-radius: 7px;
            background: rgba(255, 255, 255, 0.86);
            color: inherit;
            font-weight: 800;
            font-size: 0.7rem;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ps-page .ops-state-pill.is-done,
        .ps-page .ops-state-action-list li.is-done .ops-state-action-label {
            background: oklch(94% 0.07 145);
            color: oklch(33% 0.12 145);
        }

        .ps-page .ops-state-pill.is-pending,
        .ps-page .ops-state-action-list li.is-pending .ops-state-action-label {
            background: oklch(95% 0.08 78);
            color: oklch(38% 0.12 55);
        }

        .ps-page .ops-state-pill.is-partial,
        .ps-page .ops-state-action-list li.is-partial .ops-state-action-label {
            background: oklch(94% 0.09 88);
            color: oklch(40% 0.13 65);
        }

        .ps-page .ops-state-pill.is-critical,
        .ps-page .ops-state-action-list li.is-critical .ops-state-action-label {
            background: oklch(93% 0.09 32);
            color: oklch(36% 0.16 28);
        }

        .ps-page .ops-state-pill.is-conflict,
        .ps-page .ops-state-action-list li.is-conflict .ops-state-action-label {
            background: oklch(93% 0.08 305);
            color: oklch(36% 0.15 305);
        }

        .ps-page .ops-state-action-list li.is-na .ops-state-action-label,
        .ps-page .ops-state-action-list li.is-info .ops-state-action-label {
            background: #eef2f7;
            color: #64748b;
        }

        .ps-page .ops-state-pill-icon,
        .ps-page .ops-state-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            font-size: 0.62rem;
            line-height: 1;
            flex: 0 0 auto;
        }

        .ps-page .ops-state-ready {
            max-width: 140px;
            color: oklch(36% 0.12 150);
        }

        .ps-page .ops-state-drawer {
            position: fixed;
            inset: 0;
            z-index: 2140;
            pointer-events: none;
        }

        .ps-page .ops-state-drawer.is-open {
            pointer-events: auto;
        }

        .ps-page .ops-state-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.22);
            opacity: 0;
            transition: opacity 160ms ease;
        }

        .ps-page .ops-state-drawer.is-open .ops-state-backdrop {
            opacity: 1;
        }

        .ps-page .ops-state-panel {
            position: absolute;
            top: 0;
            right: 0;
            display: grid;
            grid-template-rows: auto 1fr;
            width: min(420px, 92vw);
            height: 100dvh;
            background: #ffffff;
            color: #1f2937;
            box-shadow: -20px 0 40px rgba(15, 23, 42, 0.18);
            transform: translateX(102%);
            transition: transform 180ms ease;
        }

        .ps-page .ops-state-drawer.is-open .ops-state-panel {
            transform: translateX(0);
        }

        .ps-page .ops-state-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.28);
        }

        .ps-page .ops-state-eyebrow {
            display: block;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .ps-page .ops-state-panel-header h5 {
            margin: 4px 0 0;
            color: #111827;
            font-weight: 800;
        }

        .ps-page .ops-state-close {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
        }

        .ps-page .ops-state-panel-body {
            overflow: auto;
            padding: 18px 20px 24px;
        }

        .ps-page .ops-state-drawer-state,
        .ps-page .ops-state-activity {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .ps-page .ops-state-activity {
            align-items: flex-start;
            color: #334155;
            line-height: 1.3;
        }

        .ps-page .ops-state-activity-id,
        .ps-page .ops-state-action-label,
        .ps-page .ops-state-action-value {
            display: inline-flex;
            align-items: center;
            padding: 3px 7px;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ps-page .ops-state-panel-body h6 {
            margin: 18px 0 10px;
            color: #111827;
            font-weight: 800;
        }

        .ps-page .ops-state-action-list {
            display: grid;
            gap: 10px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .ps-page .ops-state-action-list li {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 8px;
            align-items: start;
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 12px;
            background: #f8fafc;
        }

        .ps-page .ops-state-action-list li.is-done {
            border-color: oklch(84% 0.08 145);
            background: oklch(98% 0.025 145);
        }

        .ps-page .ops-state-action-list li.is-pending,
        .ps-page .ops-state-action-list li.is-partial {
            border-color: oklch(86% 0.09 78);
            background: oklch(98% 0.025 80);
        }

        .ps-page .ops-state-action-list li.is-critical,
        .ps-page .ops-state-action-list li.is-conflict {
            border-color: oklch(82% 0.1 32);
            background: oklch(98% 0.025 35);
        }

        .ps-page .ops-state-action-text {
            color: #1f2937;
            line-height: 1.35;
        }

        .ps-page .ops-state-empty-detail {
            padding: 14px;
            border-radius: 12px;
            background: oklch(97% 0.03 150);
            color: oklch(35% 0.11 150);
            font-weight: 700;
        }

        .ps-page #hot-container td .ps-commit-value {
            display: inline-block;
            vertical-align: middle;
        }

        .ps-page #hot-container td .ps-commit-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 6px;
            min-width: 12px;
            font-size: 11px;
            line-height: 1;
            vertical-align: middle;
            user-select: none;
        }

        .ps-page #hot-container td .ps-commit-indicator.is-low {
            color: #b45309;
        }

        .ps-page #hot-container td .ps-commit-indicator.is-ok {
            color: #166534;
        }

        .ps-page #hot-container .handsontable thead th {
            position: relative !important;
            padding: 0 !important;
            text-align: center !important;
        }

        .ps-page #hot-container .handsontable thead th .relative {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            justify-content: flex-start;
            gap: 2px;
            width: 100%;
            padding: 0 1px;
            box-sizing: border-box;
        }

        .ps-page #hot-container .handsontable thead th .relative > .colHeader {
            order: 1;
            width: 100%;
        }

        .ps-page #hot-container .handsontable thead th .relative > .changeType {
            order: 2;
            align-self: flex-end;
            margin: 0 !important;
            margin-top: 1px !important;
        }

        .ps-page #hot-container .handsontable thead th .colHeader {
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

        .ps-page #hot-container .handsontable thead th .colHeader.ps-header-single-word {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ps-page #hot-container .handsontable thead th .changeType {
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
        }

        .ps-page #hot-container .handsontable .changeType:before {
            content: "\f0b0";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 6px;
            line-height: 1;
        }

        .ps-page #hot-container .handsontable .changeType:hover,
        .ps-page #hot-container .handsontable .changeType:focus {
            border-color: #7ea7d8;
            background: #eaf3ff;
            color: #1e5ea8;
            cursor: pointer;
        }

        .ps-page .htDropdownMenu:not(.htGhostTable),
        .ps-page .htFiltersConditionsMenu:not(.htGhostTable) {
            z-index: 1085;
        }

        @media (max-width: 991px) {
            #hot-container {
                height: calc(100vh - 440px);
            }

            .ps-module-switcher {
                width: auto;
                margin-left: auto;
                justify-content: flex-start;
            }
        }

        /* Responsive scaling based on viewport width to prevent ResizeObserver jump loops */
        /* Scale stops at 0.70 minimum to guarantee readability and UX */
        @media (max-width: 1650px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.95; }
        }
        @media (max-width: 1550px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.90; }
        }
        @media (max-width: 1450px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.85; }
        }
        @media (max-width: 1350px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.80; }
        }
        @media (max-width: 1250px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.75; }
        }
        @media (max-width: 1100px) {
            .ps-hot-header-actions { --ps-hot-scale: 0.70; }
        }
    </style>
    <link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260223a" />
    <link rel="stylesheet" href="/css/change-monitor.css?v=20260602a" />
</head>
<body class="ps-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programacion_semanal" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="area_PHP" value="<?php echo htmlspecialchars($area ?? 'Construccion', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="" aria-hidden="true">
    </div>

    <div class="hot-full-bleed">
    <div class="row direccionSeccion" style="margin:0;">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <div class="header-actions ps-hot-toolbar-shell">
        <div class="ps-actions-row">
            <div class="ps-toolbar-left">
                <div class="ps-hot-toolbar-actions">
                    <button type="button" class="leyenda_colores btn-pdc-modern" data-toggle="modal" data-target="#modal_leyenda_colores_ps" aria-label="Ver leyenda de colores"><i class="fas fa-question-circle"></i> <span>Leyenda</span></button>
                    <button id="btn_autoprogramar" class="btn-auto btn-auto-orange" aria-label="Autoprogramar Actividades"><i class="fas fa-magic"></i> <span>Autoprogramar Actividades</span></button>
                    <button id="btn_agregar_actividad" type="button" class="btn-pdc-modern" aria-label="Agregar Actividad Manual"><i class="fas fa-plus"></i> <span>Agregar Actividad</span></button>
                    <button id="btn_cerrar_compromisos_semana" type="button" class="btn-pdc-modern" data-toggle="modal" data-target="#modal_cerrar_compromisos" aria-label="Confirmar Compromisos de la Semana"><i class="fas fa-lock"></i> <span>Confirmar Compromisos</span></button>
                    <button id="btn_tnp" type="button" class="btn-pdc-modern" style="display: none; background-color: #4a81bd; border-color: #3a6a9d;" aria-label="Registrar Trabajo No Planificado"><i class="fas fa-bolt"></i> <span>Registrar TNP</span></button>
                    <button id="btn_informe_compromisos" type="button" class="btn-pdc-modern" aria-label="Imprimir Informe de Compromisos"><i class="fas fa-print"></i> <span>Imprimir</span></button>
                    <div class="btn-group-pdc">
                        <button id="btn-export" class="btn-pdc-modern" aria-label="Exportar datos a CSV"><i class="fas fa-file-csv"></i> <span>Exportar CSV</span></button>
                        <button id="btn-refresh" class="btn-pdc-modern" aria-label="Recargar tabla de actividades"><i class="fas fa-sync"></i> <span>Recargar</span></button>
                    </div>
                </div>
            </div>

            <div class="ps-toolbar-right">
                <div class="ps-status-badges">
                    <span id="save-status" class="badge badge-success badge-badge-hidden">Guardado</span>
                </div>
                <div id="ps-toast-container" aria-live="polite"></div>
                <div class="ps-dropdown-nav" aria-label="Navegacion Programacion Semanal">
                    <button type="button" class="btn-pdc-modern btn-dropdown-trigger">
                        <i class="fas fa-th-list"></i> <span>Ver Secciones</span> <i class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <div class="ps-dropdown-content" role="menu">
                        <button id="btn_Actividades" type="button" class="ps-dropdown-item is-active" role="menuitem"><i class="fas fa-table"></i> Actividades</button>
                        <button id="btn_CNP" type="button" class="ps-dropdown-item" role="menuitem"><i class="fas fa-calendar-times"></i> Causas No Programacion</button>
                        <button id="btn_CNC" type="button" class="ps-dropdown-item" role="menuitem"><i class="fas fa-exclamation-triangle"></i> Causas No Cumplimiento</button>
                        <button id="btn_Cal_Proveedores" type="button" class="ps-dropdown-item" role="menuitem"><i class="fas fa-clipboard-check"></i> Calificacion Proveedores</button>
                    </div>
                </div>
                <button class="btn-filter-toggle pdc-mobile-toggle" type="button" data-toggle="collapse" data-target="#psAlertsMobile" aria-expanded="false" aria-controls="psAlertsMobile">
                    <i class="fas fa-filter"></i> Alertas <span id="weeklyPhaseMobileLabel" class="ps-weekly-phase-mobile-label">Programacion</span> <span class="badge badge-light" id="mobileAlertCount">0</span>
                </button>
            </div>
        </div>

        <div class="collapse d-md-block" id="psAlertsMobile">
            <div class="pdc-legend ps-legend pdc-legend-autoscaling" id="psAlertsLegend"></div>
        </div>

        <span id="textoFechaCierreCompromisos" class="d-none" aria-live="polite"></span>
        <p id="mensajeActualizacion" class="mb-0 mt-1"></p>
    </div>

    <div id="hot-container"></div>
    <div id="mobile-card-view" style="display:none;"></div>
    </div>

    <div class="row ventanasModalesSemana" id="ventanasModalesSemana"></div>

    <div class="modal fade aia-modal" id="modal_leyenda_colores_ps" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal_leyenda_colores_ps_Label">Guia Operativa - Programación Semanal</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="modal_leyenda_colores_ps_body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal" id="modal_cerrar_compromisos" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><b>Cierre de Compromisos</b></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="cerrar_compromisos_semana"></div>
                <div class="modal-footer">
                    <input id="btn_confirmar_compromisos_semana" type="button" class="btn btn-primary btn-lg" value="Confirmar" aria-label="Confirmar cerrar compromisos" aria-pressed="false">
                    <input id="btn_cancelar_compromisos_semana" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cancelar" aria-label="Cancelar cerrar compromisos">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal" id="modal_aceptar_cerrar_compromisos" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><b>Resultado de Cierre</b></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="aceptar_cerrar_compromisos_semana"></div>
                <div class="modal-footer">
                    <input id="btn_cerrar_aceptar_compromisos_semana" type="button" data-dismiss="modal" class="btn btn-danger btn-lg" value="Cerrar" aria-label="Cerrar alerta">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal" id="formulario_nuevo" tabindex="-1" role="dialog" aria-labelledby="formularioNuevoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable ps-modal-nueva-actividad" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formularioNuevoLabel">Agregar Actividad Manual</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row ps-nueva-actividad-grid">
                        <div class="col-lg-5 mb-3 ps-nueva-actividad-col ps-nueva-actividad-col--bandeja">
                            <div class="card h-100 ps-card-excepciones">
                                <div class="card-header d-flex justify-content-between align-items-center ps-card-excepciones__header">
                                    <strong>Bandeja de No Autoprogramadas</strong>
                                    <button type="button" id="btn_recargar_bandeja_no_autoprogramadas" class="btn btn-outline-secondary btn-sm">Actualizar</button>
                                </div>
                                <div class="card-body p-2 ps-card-excepciones__body">
                                    <input type="text" id="filtro_excepciones_no_autoprogramadas" class="form-control form-control-sm mb-2" placeholder="Filtrar por Id o Actividad" aria-label="Filtrar bandeja de actividades no autoprogramadas">
                                    <div class="table-responsive ps-excepciones-scroll">
                                        <table class="table table-sm table-hover mb-0" id="tabla_excepciones_no_autoprogramadas">
                                            <thead>
                                                <tr>
                                                    <th>Id</th>
                                                    <th>Actividad</th>
                                                    <th>Motivo</th>
                                                    <th class="text-right">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_excepciones_no_autoprogramadas">
                                                <tr><td colspan="4" class="text-center text-muted">Cargando actividades...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 ps-nueva-actividad-col ps-nueva-actividad-col--formulario">
                            <form class="form_nueva_actividad form form-horizontal ps-form-layout ps-modal-form" autocomplete="off">
                                <div class="form-group ps-form-col">
                                    <label for="idNuevo" class="col-sm-8 control-label">Id *</label>
                                    <div class="col-sm-8">
                                        <select id="idNuevo" name="idNuevo" class="form-control w-100" data-placeholder="Seleccione una actividad">
                                            <option value=""></option>
                                            <?php
                                            $dbInstance = Database::getInstance();
        $db = $_SESSION['db'];
        $semana = $_SESSION['semana'];
        $query = "SELECT * FROM {$db}_programa_consolidado WHERE Semana=? AND Titulo=0 AND Semanas_Inicio<=12 AND Semanas_Inicio>=1 AND Ejecutado=0";
        try {
            $stmt = $dbInstance->prepare($query);
            $stmt->execute([$semana]);
            while ($valores = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $Actividad = strip_tags((string) ($valores["Actividad"] ?? ""));
                $Actividad = str_replace('"', '\"', $Actividad);
                $Actividad = str_replace("'", "\'", $Actividad);
                echo '<option value="' . $valores["Id"] . '">(' . $valores["Id"] . ') - ' . $Actividad . '</option>';
            }
        } catch (PDOException $e) {
        }
        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group ps-form-col">
                                    <label for="Actividad" class="col-sm-8 control-label">Actividad *</label>
                                    <div class="col-sm-8"><input id="Actividad" name="Actividad" class="form-control" value="" type="text"></div>
                                </div>
                                <div class="form-group ps-form-col">
                                    <label for="Descripcion" class="col-sm-8 control-label">Descripción</label>
                                    <div class="col-sm-8"><input id="Descripcion" name="Descripcion" class="form-control" value="" type="text"></div>
                                </div>
                                <input id="Ubicacion" name="Ubicacion" class="form-control" value="" type="hidden">
                                <div class="form-group ps-form-col-6">
                                    <label for="Sub_Contratista" class="col-sm-8 control-label">Sub-Contratista *</label>
                                    <div class="col-sm-8">
                                        <select id="Sub_Contratista" name="Sub_Contratista" class="form-control">
                                            <option value=""></option>
                                            <?php
        if (!empty($subcontratistas) && is_array($subcontratistas)) {
            foreach ($subcontratistas as $sub) {
                if (!empty($sub['subcontratista'])) {
                    $value = htmlspecialchars($sub['subcontratista'], ENT_QUOTES, 'UTF-8');
                    echo "<option value=\"{$value}\">{$value}</option>";
                }
            }
        }
        ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group ps-form-col-6">
                                    <label for="Responsable_AIA" class="col-sm-8 control-label">Profesional AIA *</label>
                                    <div class="col-sm-8">
                                        <select id="Responsable_AIA" name="Responsable_AIA" class="form-control">
                                            <option value=""></option>
                                            <?php
        if (!empty($profesionales) && is_array($profesionales)) {
            foreach ($profesionales as $prof) {
                if (!empty($prof['nombre'])) {
                    $value = htmlspecialchars($prof['nombre'], ENT_QUOTES, 'UTF-8');
                    echo "<option value=\"{$value}\">{$value}</option>";
                }
            }
        }
        ?>
                                        </select>
                                    </div>
                                </div>
                                <input id="Empresa" name="Empresa" class="form-control" value="" type="hidden">
                                <div class="form-group ps-form-col-4">
                                    <label for="Unidad" class="col-sm-8 control-label">Unidad de Medida</label>
                                    <div class="col-sm-4"><input id="Unidad" name="Unidad" class="form-control" value="" type="text" readonly aria-readonly="true" placeholder="Automático"></div>
                                </div>
                                <div class="form-group ps-form-col-4">
                                    <label for="CantidadPPTO" class="col-sm-8 control-label">Cant. PPTO</label>
                                    <div class="col-sm-4"><input id="CantidadPPTO" name="CantidadPPTO" class="form-control" value="" type="text" readonly aria-readonly="true" placeholder="Sin cantidad"></div>
                                </div>
                                <div class="form-group ps-form-col-4">
                                    <label for="Compromiso" class="col-sm-8 control-label">Cantidad *</label>
                                    <div class="col-sm-4"><input id="Compromiso" name="Compromiso" class="form-control" value="" type="text"></div>
                                </div>
                                <input id="Real" name="Real" class="form-control" value="" type="hidden">
                                <input type="hidden" id="opcion" name="opcion" value="nuevo">
                                <div class="form-group mt-3">
                                    <div class="col-sm-offset-2 col-sm-8">
                                        <input id="btn_guardar_nueva_actividad" type="button" class="btn btn-primary" value="Guardar" aria-label="Guardar nueva actividad">
                                        <input id="btn_listar" type="button" class="btn btn-danger" value="Cancelar" aria-label="Cancelar nueva actividad" data-dismiss="modal">
                                    </div>
                                </div>
                            </form>
                            <div class="col-sm-offset-2 col-sm-8">
                                <p class="mensaje" role="status" aria-live="polite"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal" id="modal_eliminar_actividad" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><b>Eliminar Actividad</b></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="psDeleteModalText" class="mb-3"></p>
                    <div class="form-group">
                        <label for="psDeleteResponsableAIA">Profesional de AIA Encargado de la Actividad</label>
                        <select id="psDeleteResponsableAIA" class="form-control">
                            <option value=""></option>
                            <?php
                                if (!empty($profesionales) && is_array($profesionales)) {
                                    foreach ($profesionales as $prof) {
                                        if (!empty($prof['nombre'])) {
                                            $value = htmlspecialchars($prof['nombre'], ENT_QUOTES, 'UTF-8');
                                            echo "<option value=\"{$value}\">{$value}</option>";
                                        }
                                    }
                                }
        ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="psDeleteEmpresa">Empresa Encargada de la Ejecucion</label>
                        <input id="psDeleteEmpresa" type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="psDeleteCategoriaCNP">Categoria</label>
                        <select id="psDeleteCategoriaCNP" class="form-control">
                            <option value=""></option>
                            <option value="Programación">Programacion</option>
                            <option value="Mano de Obra">Mano de Obra</option>
                            <option value="Materiales">Materiales</option>
                            <option value="Equipos">Equipos</option>
                            <option value="Diseños">Diseños</option>
                            <option value="Administrativas">Administrativas</option>
                            <option value="Causas Exógenas">Causas Exógenas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="psDeleteCNP">Causa de No Programacion</label>
                        <select id="psDeleteCNP" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="psDeleteObservacionesCNP">Observaciones</label>
                        <textarea id="psDeleteObservacionesCNP" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn_confirmar_eliminar_actividad" type="button" class="btn btn-primary">Guardar y Eliminar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal CNC (Handsontable Handler) -->
    <div class="modal fade aia-modal" id="modal_cnc_hot" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h4 class="modal-title" style="font-size: 1.15rem; letter-spacing: -0.02em;">Justificación de Incumplimiento (CNC)</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="color: #4a5568; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem;">El avance real digitado es <strong style="color: #1a5633; font-family: 'Montserrat', sans-serif;">inferior al compromiso</strong>. Obligatoriamente debes justificar el motivo a continuación. Al guardar, actualizaremos la fila en la tabla y en el servidor.</p>
                    <div class="form-group mt-3 mb-4">
                        <label for="hot_cat_cnc" style="font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 0.9rem; color: #2d3748;">Categoría CNC <span style="color: #1a5633;">*</span></label>
                        <select id="hot_cat_cnc" class="form-control" style="border-radius: 6px; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; height: auto;">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label for="hot_cnc" style="font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 0.9rem; color: #2d3748;">Causa de No Cumplimiento <span style="color: #1a5633;">*</span></label>
                        <select id="hot_cnc" class="form-control" disabled style="border-radius: 6px; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; height: auto;">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="hot_obs_cnc" style="font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 0.9rem; color: #2d3748;">Observaciones <span style="color: #1a5633;">*</span></label>
                        <textarea id="hot_obs_cnc" class="form-control" rows="3" placeholder="Detalle la causa del incumplimiento..." style="border-radius: 6px; border: 1px solid #e2e8f0; padding: 0.75rem; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                    <button id="btn_cancelar_cnc_hot" type="button" class="btn btn-outline-secondary" data-dismiss="modal" style="font-family: 'Montserrat', sans-serif; font-weight: 600; padding: 0.5rem 1.25rem;">Cancelar</button>
                    <button id="btn_guardar_cnc_hot" type="button" class="btn aia-btn-primary">Guardar y Confirmar</button>
                </div>
        </div>
    </div>
</div>

<!-- Modal TNP - Trabajo No Planificado -->
<div class="modal fade aia-modal" id="modal_tnp" tabindex="-1" role="dialog" aria-labelledby="modal_tnp_label">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #2E7D32; color: white;">
        <h5 class="modal-title" id="modal_tnp_label">
          Registrar Trabajo No Planificado
          <small class="d-block mt-1" style="font-weight: normal; opacity: 0.9;">¿Por qué decidiste ejecutarla?</small>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="background-color: #faf8f5;">
        <input type="hidden" id="tnp_consecutivo" value="">
        <input type="hidden" id="tnp_id_actividad" value="">

        <!-- Activity Selector -->
        <div class="form-group">
          <label for="tnp_actividad_select"><strong>Actividad *</strong></label>
          <select id="tnp_actividad_select" class="form-control" data-placeholder="Buscar actividad..." required>
            <option value="">Seleccione una actividad...</option>
          </select>
        </div>

        <!-- Activity Info Panel -->
        <div id="tnp_actividad_info" style="display: none;">
          <div class="card mb-3" style="border-left: 4px solid #2E7D32;">
            <div class="card-body py-2 px-3">
              <div class="row">
                <div class="col-6">
                  <small class="text-muted d-block">Subcontratista</small>
                  <strong id="tnp_info_subcontratista">-</strong>
                </div>
                <div class="col-6">
                  <small class="text-muted d-block">Responsable AIA</small>
                  <strong id="tnp_info_residente">-</strong>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-4">
                  <small class="text-muted d-block">Frente</small>
                  <strong id="tnp_info_frente">-</strong>
                </div>
                <div class="col-4">
                  <small class="text-muted d-block">Unidad</small>
                  <strong id="tnp_info_unidad">-</strong>
                </div>
                <div class="col-4">
                  <small class="text-muted d-block">Cuantía</small>
                  <strong id="tnp_info_cuantia">-</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="tnp_categoria_cp"><strong>Causa de Programación (CP) *</strong></label>
          <select id="tnp_categoria_cp" class="form-control" required>
            <option value="">Seleccione una causa...</option>
            <?php foreach ($categoriasCP as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label for="tnp_cp"><strong>CP (Detalle adicional)</strong></label>
          <input type="text" id="tnp_cp" class="form-control" maxlength="255" placeholder="Detalle opcional de la causa">
        </div>
        
        <div class="form-group">
          <label for="tnp_ejecutado_real"><strong>Ejecutado Real *</strong></label>
          <input type="number" id="tnp_ejecutado_real" class="form-control" step="0.1" min="0.1" required placeholder="Cantidad ejecutada">
        </div>
        
        <div class="form-group">
          <label for="tnp_observaciones_cp"><strong>Observaciones</strong></label>
          <textarea id="tnp_observaciones_cp" class="form-control" maxlength="500" rows="3" placeholder="Observaciones opcionales (máx. 500 caracteres)"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="background-color: #f5f3f0;">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="btn_guardar_tnp">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/_changeMonitorModal.php'; ?>
    <?php include __DIR__ . '/../partials/drawer_unificado.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;</script>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js" charset="utf-8"></script>
    <script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script type="text/javascript" src="/js/modules/programacion_semanal/stateMachine.js?v=ps3"></script>
    <script>
        window.PS_HOT_OPTIONS = {
            subcontratistas: <?php
                $psSubcontratistas = ['AIA (MO Directa)'];
        if (!empty($subcontratistas) && is_array($subcontratistas)) {
            foreach ($subcontratistas as $sub) {
                if (!empty($sub['subcontratista'])) {
                    $psSubcontratistas[] = $sub['subcontratista'];
                }
            }
        }
        $psSubcontratistas = array_values(array_unique(array_filter($psSubcontratistas)));
        echo json_encode($psSubcontratistas, JSON_UNESCAPED_UNICODE);
        ?>,
            profesionales: <?php
            $psProfesionales = [];
        if (!empty($profesionales) && is_array($profesionales)) {
            foreach ($profesionales as $prof) {
                if (!empty($prof['nombre'])) {
                    $psProfesionales[] = $prof['nombre'];
                }
            }
        }
        $psProfesionales = array_values(array_unique(array_filter($psProfesionales)));
        echo json_encode($psProfesionales, JSON_UNESCAPED_UNICODE);
        ?>,
            categoriasCnc: <?php
            $psCategoriasCnc = [];
        if (!empty($categoriasCnc) && is_array($categoriasCnc)) {
            foreach ($categoriasCnc as $cnc) {
                if (!empty($cnc['Categoria_CNC'])) {
                    $psCategoriasCnc[] = $cnc['Categoria_CNC'];
                }
            }
        }
        $psCategoriasCnc = array_values(array_unique(array_filter($psCategoriasCnc)));
        echo json_encode($psCategoriasCnc, JSON_UNESCAPED_UNICODE);
        ?>
        };
    </script>
    <?php if (($area ?? 'Construccion') === 'Pre-Construccion'): ?>
    <script>
        /* Pre-Construccion: inline restriction config (4 restrictions) so hot.js skips the AJAX fetch.
           Keys match consolidado DB columns so getRestrictionSourceValue() resolves correctly. */
        (function () {
            if (window.__RESTRICTION_CONFIG__) { return; }
            window.__RESTRICTION_CONFIG__ = {
                area: 'Pre-Construccion',
                restrictions: [
                    { key: 'D_y_E',     label: 'Disenos y Especificaciones', type: 'hard', threshold: 100, options: ['0%','33%','66%','100%','N/A'] },
                    { key: 'Materiales', label: 'Materiales',               type: 'hard', threshold: 100, options: ['0%','33%','66%','100%','N/A'] },
                    { key: 'MdeO',      label: 'Mano de Obra',              type: 'hard', threshold: 100, options: ['0%','33%','66%','100%','N/A'] },
                    { key: 'Equipos',   label: 'Equipos y Herramienta',     type: 'hard', threshold: 100, options: ['0%','33%','66%','100%','N/A'] }
                ],
                hardRestrictions: ['D_y_E','Materiales','MdeO','Equipos'],
                softRestrictions: []
            };
        })();
    </script>
    <?php endif; ?>
    <script src="/js/modules/lps_drawer.js?v=20260522c"></script>
    <?php $psHotVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/programacion_semanal/hot.js') ?: 'hot50'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
    <script src="/js/modules/programacion_semanal/hot.js?v=<?php echo urlencode((string) $psHotVersion); ?>"></script>
    <script src="/js/modules/programacion_semanal/changeMonitor.js?v=ap1"></script>

    <script>
        function cargaParametros() {
            if (window.PSHotModule && typeof window.PSHotModule.init === 'function') {
                window.PSHotModule.init();
            }
            if (window.ChangeMonitor && typeof window.ChangeMonitor.init === 'function') {
                window.ChangeMonitor.init();
            }
        }

        $(document).ready(function() {
            cargarDatosGeneralesPagina(document.getElementById('seccion').value);
        });
    </script>
</body>
</html>
