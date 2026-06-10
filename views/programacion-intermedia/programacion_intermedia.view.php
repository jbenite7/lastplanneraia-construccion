<!DOCTYPE html>
<html lang="es">
<head id="head">
    <script src="/public/vendor/jquery.min.js"></script>
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=headLoaderV20260530" charset="utf-8"></script>
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <link rel="stylesheet" href="/css/handsontable-module.css?v=20260529a" />
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

        .pi-page #hot-container td.ops-state-td {
            padding: 4px 6px !important;
            overflow: visible !important;
            vertical-align: middle !important;
        }

        .pi-page .ops-state-zoom {
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

        .pi-page .ops-state-zoom:focus-visible {
            outline: 2px solid oklch(62% 0.18 250);
            outline-offset: 2px;
            border-radius: 8px;
        }

        .pi-page .ops-state-topline {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 4px;
            width: 100%;
            min-width: 0;
        }

        .pi-page .ops-state-chip {
            display: block;
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
            overflow: visible;
            overflow-wrap: anywhere;
            white-space: normal;
            word-break: normal;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08), inset 0 0 0 1px rgba(255, 255, 255, 0.48);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-blocked-overdue-critical .ops-state-chip {
            background: var(--pi-critical-bg, #fee2e2);
            background: color-mix(in srgb, var(--pi-critical-bg, #fee2e2) 78%, #fff 22%);
            border-color: var(--pi-critical-border, #dc2626);
            color: var(--pi-critical-text, #991b1b);
            font-weight: 900;
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.24), 0 1px 3px rgba(153, 27, 27, 0.14);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-blocked-overdue .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-blocked-due .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-1-week .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-2-3-weeks .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-4-6-weeks .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-execution-blocked .ops-state-chip,
        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-liberated-control .ops-state-chip {
            font-weight: 800;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-blocked-overdue .ops-state-chip {
            background: var(--pi-overdue-bg, #ffedd5);
            background: color-mix(in srgb, var(--pi-overdue-bg, #ffedd5) 70%, #fff 30%);
            border-color: var(--pi-overdue-border, #f97316);
            color: var(--pi-overdue-text, #9a3412);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-blocked-due .ops-state-chip {
            background: var(--pi-due-bg, #fef3c7);
            background: color-mix(in srgb, var(--pi-due-bg, #fef3c7) 68%, #fff 32%);
            border-color: var(--pi-due-border, #f59e0b);
            color: var(--pi-due-text, #92400e);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-1-week .ops-state-chip {
            background: var(--pi-alert1-bg, #fef9c3);
            background: color-mix(in srgb, var(--pi-alert1-bg, #fef9c3) 66%, #fff 34%);
            border-color: var(--pi-alert1-border, #eab308);
            color: var(--pi-alert1-text, #854d0e);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-2-3-weeks .ops-state-chip {
            background: var(--pi-alert23-bg, #ecfccb);
            background: color-mix(in srgb, var(--pi-alert23-bg, #ecfccb) 66%, #fff 34%);
            border-color: var(--pi-alert23-border, #84cc16);
            color: var(--pi-alert23-text, #3f6212);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-alert-4-6-weeks .ops-state-chip {
            background: var(--pi-alert46-bg, #dcfce7);
            background: color-mix(in srgb, var(--pi-alert46-bg, #dcfce7) 66%, #fff 34%);
            border-color: var(--pi-alert46-border, #22c55e);
            color: var(--pi-alert46-text, #166534);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-execution-blocked .ops-state-chip {
            background: var(--pi-exec-blocked-bg, #fed7aa);
            background: color-mix(in srgb, var(--pi-exec-blocked-bg, #fed7aa) 66%, #fff 34%);
            border-color: var(--pi-exec-blocked-border, #fb923c);
            color: var(--pi-exec-blocked-text, #9a3412);
        }

        .pi-page .handsontable td.ops-state-td.pi-row-state.pi-state-liberated-control .ops-state-chip {
            background: var(--pi-control-bg, #e0f2fe);
            background: color-mix(in srgb, var(--pi-control-bg, #e0f2fe) 66%, #fff 34%);
            border-color: var(--pi-control-border, #38bdf8);
            color: var(--pi-control-text, #0c4a6e);
        }

        .pi-page .handsontable td.pi-soft-restriction-cell:not(.pi-row-state) {
            background: color-mix(in srgb, var(--pi-due-bg, #fde68a) 62%, #fff 38%) !important;
            color: var(--pi-due-text, #78350f) !important;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.24);
        }

        .pi-page .handsontable th.pi-soft-restriction-th,
        .pi-page .handsontable th.pi-soft-restriction-th .colHeader {
            background: color-mix(in srgb, var(--pi-due-bg, #fef3c7) 70%, #fff 30%) !important;
            color: var(--pi-due-text, #92400e) !important;
        }

        .pi-page .pi-soft-restriction-header::after {
            content: " blanda";
            display: inline-block;
            margin-left: 4px;
            padding: 1px 5px;
            border-radius: 999px;
            background: var(--pi-due-bg, #fef3c7);
            color: var(--pi-due-text, #92400e);
            font-size: 0.66rem;
            font-weight: 800;
            line-height: 1.1;
            vertical-align: middle;
        }

        .pi-page .ops-state-count,
        .pi-page .ops-state-more {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            min-height: 20px;
            height: auto;
            padding: 0 6px;
            border-radius: 999px;
            background: oklch(97% 0.02 80);
            color: oklch(38% 0.12 55);
            font-weight: 800;
            font-size: 0.75rem;
            line-height: 1;
            box-shadow: inset 0 0 0 1px rgba(120, 53, 15, 0.12);
        }

        .pi-page .ops-state-pills {
            display: flex;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 3px;
            min-width: 0;
            overflow: visible;
            max-height: none;
        }

        .pi-page .ops-state-pill,
        .pi-page .ops-state-ready {
            display: inline-flex;
            align-items: flex-start;
            max-width: 100%;
            padding: 3px 6px;
            border: 1px solid rgba(120, 53, 15, 0.1);
            border-radius: 7px;
            background: rgba(255, 255, 255, 0.86);
            color: inherit;
            font-weight: 800;
            font-size: 0.7rem;
            line-height: 1.1;
            white-space: normal;
            overflow: visible;
            overflow-wrap: anywhere;
            text-overflow: clip;
        }

        .pi-page .ops-state-ready {
            max-width: 140px;
            color: oklch(36% 0.12 150);
        }

        .pi-page .ops-state-drawer {
            position: fixed;
            inset: 0;
            z-index: 2140;
            pointer-events: none;
        }

        .pi-page .ops-state-drawer.is-open {
            pointer-events: auto;
        }

        .pi-page .ops-state-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.22);
            opacity: 0;
            transition: opacity 160ms ease;
        }

        .pi-page .ops-state-drawer.is-open .ops-state-backdrop {
            opacity: 1;
        }

        .pi-page .ops-state-panel {
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

        .pi-page .ops-state-drawer.is-open .ops-state-panel {
            transform: translateX(0);
        }

        .pi-page .ops-state-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.28);
        }

        .pi-page .ops-state-eyebrow {
            display: block;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pi-page .ops-state-panel-header h5 {
            margin: 4px 0 0;
            color: #111827;
            font-weight: 800;
        }

        .pi-page .ops-state-close {
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

        .pi-page .ops-state-panel-body {
            overflow: auto;
            padding: 18px 20px 24px;
        }

        .pi-page .ops-state-drawer-state,
        .pi-page .ops-state-activity {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .pi-page .ops-state-activity {
            align-items: flex-start;
            color: #334155;
            line-height: 1.3;
        }

        .pi-page .ops-state-activity-id,
        .pi-page .ops-state-action-label,
        .pi-page .ops-state-action-value {
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

        .pi-page .ops-state-panel-body h6 {
            margin: 18px 0 10px;
            color: #111827;
            font-weight: 800;
        }

        .pi-page .ops-state-action-list {
            display: grid;
            gap: 10px;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .pi-page .ops-state-action-list li {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 8px;
            align-items: start;
            padding: 10px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 12px;
            background: #f8fafc;
        }

        .pi-page .ops-state-action-text {
            color: #1f2937;
            line-height: 1.35;
        }

        .pi-page .ops-state-empty-detail {
            padding: 14px;
            border-radius: 12px;
            background: oklch(97% 0.03 150);
            color: oklch(35% 0.11 150);
            font-weight: 700;
        }

        .pi-page #hot-container .handsontable td b,
        .pi-page #hot-container .handsontable td strong {
            font-weight: 600;
        }

        .pi-page #hot-container td.pi-cell-editable:not(.pi-row-state) {
            box-shadow: inset 0 0 0 9999px rgba(34, 197, 94, 0.05);
            cursor: text;
        }

        .pi-page #hot-container td.pi-cell-editable.pi-row-state {
            cursor: text;
        }

        .pi-page #hot-container td.pi-cell-readonly:not(.pi-row-state) {
            box-shadow: inset 0 0 0 9999px rgba(148, 163, 184, 0.06);
            cursor: not-allowed;
        }

        .pi-page #hot-container td.pi-cell-readonly.pi-row-state {
            cursor: not-allowed;
        }

        .pi-page #hot-container td.pi-cell-editable.current,
        .pi-page #hot-container td.pi-cell-editable.area {
            box-shadow: inset 0 0 0 2px rgba(22, 163, 74, 0.32);
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
            white-space: normal;
            word-break: normal;
            overflow-wrap: anywhere;
            overflow: visible;
            text-overflow: clip;
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

        .pi-shared-kpi.pi-shared-kpi-conflict {
            border-color: #f1b5aa;
            background: #fff4f1;
        }
        .pi-shared-kpi.pi-shared-kpi-conflict .pi-shared-kpi-value {
            color: #9d321f;
        }

        .pi-shared-conflicts {
            border: 1px solid #f1b5aa;
            border-radius: 8px;
            background: #fff4f1;
            padding: 9px 11px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.82rem;
            line-height: 1.32;
            color: #6f281b;
        }
        .pi-shared-conflicts-title {
            font-weight: 700;
            color: #8f3a2f;
        }
        .pi-shared-conflict-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fde2dc;
            border: 1px solid #f1b5aa;
            color: #6f281b;
            font-size: 0.76rem;
            font-weight: 600;
            margin-right: 4px;
        }
        .pi-shared-conflict-badge b {
            color: #8f3a2f;
        }
        .pi-shared-conflicts-hint {
            font-size: 0.74rem;
            color: #7a4434;
            font-style: italic;
        }
        .pi-shared-table tr.pi-shared-row-conflict {
            background: #fff8f6;
        }
        .pi-shared-table tr.pi-shared-row-conflict:hover {
            background: #ffece6;
        }
        .pi-shared-table td.pi-shared-cell-conflict {
            color: #8f3a2f;
            font-weight: 600;
            background: #fff0eb;
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

        .pi-shared-restriction-changes {
            display: grid;
            gap: 4px;
            min-width: 220px;
        }

        .pi-shared-restriction-change {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
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

        .pi-shared-restrictions-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .pi-shared-restriction-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .pi-shared-restriction-actions .btn {
            padding: 0.12rem 0.48rem;
            font-size: 0.74rem;
            line-height: 1.15;
        }

        .pi-shared-restrictions-panel {
            display: grid;
            gap: 6px;
            border: 1px solid #d7e0ea;
            border-radius: 8px;
            background: #f8fbff;
            padding: 8px;
        }

        .pi-shared-restriction-row {
            display: grid;
            grid-template-columns: minmax(170px, 1fr) minmax(96px, 124px);
            gap: 8px;
            align-items: center;
            border: 1px solid #e0e8f2;
            border-radius: 7px;
            background: #ffffff;
            padding: 6px 8px;
        }

        .pi-shared-restriction-row.is-disabled {
            opacity: 0.58;
        }

        .pi-shared-restriction-row .custom-control-label {
            font-size: 0.84rem;
            line-height: 1.2;
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

            .pi-shared-restriction-row {
                grid-template-columns: 1fr;
            }
        }

        @layer components {
            @media (max-width: 768px) {
                .pi-page #hot-container {
                    display: block !important;
                }

                .pi-page #mobile-card-view {
                    display: none !important;
                }
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

        /* Wrapper flotante: sin borde propio - lo ponen .ts-control y .ts-dropdown */
        /* El ancho lo fija el JS: Math.max(300, anchoColumna) */
        .htTomSelectWrapper {
            background: transparent !important;
            border: none !important;
            z-index: 10000 !important;
            box-shadow: 0 10px 30px rgba(181,82,17,0.18) !important;
            pointer-events: auto !important;
        }

        /* Control principal (input + pills) */
        .htTomSelectWrapper .ts-wrapper {
            width: 100% !important;
            box-sizing: border-box !important;
        }
        /* Control: borde 3 lados (top + left + right) + radio arriba */
        .htTomSelectWrapper .ts-control {
            background: #fafafa !important;
            border: 1px solid #b55211 !important;
            border-bottom: none !important;
            border-radius: 4px 4px 0 0 !important;
            min-height: 24px !important;
            min-width: 100% !important;
            box-sizing: border-box !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            color: #1c1c1e !important;
            padding: 2px 4px 0 4px !important; /* Removed bottom padding, the button will be the floor */
            box-shadow: none !important;
            overflow: hidden !important; /* Clips the button to the control border radius if needed */
        }
        .htTomSelectWrapper .ts-control input {
            width: 90% !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            color: #1c1c1e !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .htTomSelectWrapper .ts-control input::placeholder {
            color: #a1a1aa !important;
        }

        /* Pills (ítems seleccionados) */
        .htTomSelectWrapper .ts-control .item {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: #fbead9 !important;   /* very light naranja AIA */
            border: 1px solid #e87722 !important; /* mid naranja AIA */
            color: #8b4011 !important;         /* dark naranja AIA */
            border-radius: 4px !important;
            font-size: 0.78rem !important;
            font-weight: 500 !important;
            padding: 2px 6px !important;
            margin: 2px !important;
        }
        .htTomSelectWrapper .ts-control .item .remove {
            margin-left: auto !important;
            color: #b55211 !important;
            border-left: none !important;
            padding-left: 8px !important;
        }
        .htTomSelectWrapper .ts-control .item .remove:hover {
            background: #f6c79b !important;
        }

        /* Dropdown: borde 3 lados (left + right + bottom) + radio abajo */
        .htTomSelectWrapper .ts-dropdown {
            background: #fafafa !important;
            border: 1.5px solid #b55211 !important;
            border-top: none !important;
            border-radius: 0 0 6px 6px !important;
            box-shadow: 0 8px 16px rgba(181,82,17,0.15) !important;
            min-width: 100% !important;
            box-sizing: border-box !important;
            left: 0 !important;
            max-height: 220px !important;
            overflow-y: auto !important;
            font-size: 0.83rem !important;
            font-family: 'Inter', sans-serif !important;
            z-index: 99999 !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-dropdown-content {
            max-height: 210px !important;
            overflow-y: auto !important;
        }

        /* Auto-flip: dropdown hacia arriba cuando la celda esta cerca del bottom */
        .htTomSelectWrapper[data-flip="up"] .ts-dropdown {
            top: auto !important;
            bottom: 100% !important;
            border-top: 1.5px solid #b55211 !important;
            border-bottom: none !important;
            border-radius: 6px 6px 0 0 !important;
            margin-top: 0 !important;
            margin-bottom: -1px !important;
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
        .htTomSelectWrapper .ts-dropdown .ts-option.active,
        .htTomSelectWrapper .ts-dropdown .ts-option.focused {
            background: #f6c79b !important; /* Más intenso que el beige para feedback claro */
            color: #b55211 !important;
            font-weight: 500 !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-option:last-child {
            border-bottom: none !important;
        }

        /* Opción especial "+ Crear" (Estilo Botón de Acción AIA) */
        .htTomSelectWrapper .ts-dropdown .ts-create-option {
            display: block !important;
            color: #ffffff !important; /* Texto blanco obligatorio */
            font-weight: 700 !important;
            background: #b55211 !important; /* Naranja AIA */
            border-radius: 4px !important;
            padding: 8px 12px !important;
            text-align: center !important;
            letter-spacing: 0.5px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15) !important;
            margin: 4px 6px !important;
            border-bottom: none !important;
        }
        /* Garantizar que el texto sea blanco incluso si hay herencia */
        .htTomSelectWrapper .ts-dropdown .ts-create-option * {
            color: #ffffff !important;
        }
        .htTomSelectWrapper .ts-dropdown .ts-option:hover .ts-create-option,
        .htTomSelectWrapper .ts-dropdown .ts-option.active .ts-create-option,
        .htTomSelectWrapper .ts-dropdown .ts-option.focused .ts-create-option {
            background: #e87722 !important;
            cursor: pointer !important;
        }

        .htTomSelectWrapper .ts-control .clear-button.aia-clear-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            margin: 0 !important;
            margin-top: 0 !important; /* Flush with content */
            margin-bottom: 0 !important;
            background: #fff9f5 !important;
            border: none !important;
            border-top: 1px solid #f6c79b !important;
            border-radius: 0 !important;
            color: #b55211 !important;
            padding: 8px 12px !important;
            width: calc(100% + 8px) !important; /* Expands to cover padding */
            margin-left: -4px !important; /* Offsets padding */
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            position: relative !important;
            float: left !important;
            clear: both !important;
            visibility: visible !important;
            box-sizing: border-box !important;
        }
        .htTomSelectWrapper .ts-control .clear-button.aia-clear-btn i {
            font-size: 0.75rem !important;
            color: #b55211 !important;
        }
        .htTomSelectWrapper .ts-control .clear-button.aia-clear-btn:hover {
            background: #b55211 !important;
            color: #ffffff !important;
        }
        .htTomSelectWrapper .ts-control .clear-button.aia-clear-btn:hover i {
            color: #ffffff !important;
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
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
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

      /* Ver Todas las Actividades: toggle slider en el toolbar */
      .pi-view-all-toggle {
        gap: 4px;
      }
      .pi-view-all-toggle-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #4a5568;
        letter-spacing: 0.01em;
        white-space: nowrap;
      }
      /* Switch: estado apagado (look neutro, sin el primary azul por defecto) */
      .pi-view-all-toggle .custom-control-label::before {
        background-color: #cbd5e0;
        border-color: #cbd5e0;
      }
      /* Switch: estado encendido (verde AIA brand #1a5633) */
      .pi-view-all-toggle .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #1a5633;
        border-color: #1a5633;
      }
      .pi-view-all-toggle .custom-control-input:focus ~ .custom-control-label::before {
        box-shadow: 0 0 0 0.2rem rgba(26, 86, 51, 0.25);
        border-color: #1a5633;
      }
      .pi-view-all-toggle .custom-control-input:active ~ .custom-control-label::before {
        background-color: #1a5633;
        border-color: #1a5633;
      }
      .pi-view-all-toggle .custom-control-input:checked ~ .custom-control-label::after {
        background-color: #ffffff;
      }
      /* Refleja el estado en el texto del label (oscurece cuando está activo) */
      .pi-view-all-toggle.is-on .pi-view-all-toggle-label {
        color: #1a5633;
        font-weight: 700;
      }
      /* Badge en el semaforo que recuerda que los conteos son de la ventana de 6 sem. */
      .pi-legend-window-label {
        display: inline-block;
        padding: 2px 8px;
        margin-right: 8px;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        align-self: center;
        border: 1px solid #fde68a;
      }
      .pi-legend-window-label:not(.is-active) { display: none; }
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
                <div class="pi-view-all-toggle d-inline-flex align-items-center mx-2 <?= $viewAll ? 'is-on' : '' ?>" title="<?= $viewAll ? 'Volver a la ventana de 6 semanas de liberacion de restricciones' : 'Mostrar todas las actividades, incluyendo las que aun no entran en la ventana de 6 semanas' ?>">
                    <span class="pi-view-all-toggle-label"><i class="fas fa-layer-group mr-1"></i>Ver Todas las Actividades</span>
                    <div class="custom-control custom-switch mb-0 ml-2">
                        <input type="checkbox" class="custom-control-input" id="piViewAllToggle" <?= $viewAll ? 'checked' : '' ?> aria-label="Ver Todas las Actividades">
                        <label class="custom-control-label" for="piViewAllToggle"></label>
                    </div>
                </div>
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
                <span class="pi-legend-window-label <?= $viewAll ? 'is-active' : '' ?>" title="Los conteos del semaforo se calculan sobre la ventana de 6 semanas, no sobre la vista actual.">(Ventana 6 sem.)</span>
                <span class="pdc-legend-item blocked-overdue-critical" data-filter="blocked-overdue-critical" role="button" tabindex="0"><span class="indicator"></span> RC inicio vencido <span id="count-blocked-overdue-critical" class="count-badge">(...)</span></span>

                <span class="pdc-legend-item blocked-overdue" data-filter="blocked-overdue" role="button" tabindex="0"><span class="indicator"></span> Inicio Vencido <span id="count-blocked-overdue" class="count-badge">(...)</span></span>

                <span class="pdc-legend-item blocked-due" data-filter="blocked-due" role="button" tabindex="0"><span class="indicator"></span> Inicio por Habilitar <span id="count-blocked-due" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-1-week" data-filter="alert-1-week" role="button" tabindex="0"><span class="indicator"></span> Alistamiento Urgente <span id="count-alert-1-week" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-2-3-weeks" data-filter="alert-2-3-weeks" role="button" tabindex="0"><span class="indicator"></span> Alistamiento en Riesgo <span id="count-alert-2-3-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-4-6-weeks" data-filter="alert-4-6-weeks" role="button" tabindex="0"><span class="indicator"></span> Alistamiento Pendiente <span id="count-alert-4-6-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item execution-blocked" data-filter="execution-blocked" role="button" tabindex="0"><span class="indicator"></span> En Ejecución Pendiente <span id="count-execution-blocked" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item liberated-control" data-filter="liberated-control" role="button" tabindex="0"><span class="indicator"></span> Listo para Comprometer <span id="count-liberated-control" class="count-badge">(...)</span></span>
            </div>
        </div>
    </div>

    <div id="hot-container"></div>
    <div id="mobile-card-view" style="display:none;"></div>
    </div>

    <div class="modal fade aia-modal" id="modal_leyenda_colores" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal_leyenda_colores_Label">Guia Operativa - Programación Intermedia</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="modal_leyenda_colores_body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal" id="modal_shared_constraint" role="dialog" data-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><b>Aplicar Restricción Compartida</b></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="piSharedApplyRestriction" checked>
                                <label class="custom-control-label" for="piSharedApplyRestriction">Aplicar restricción al lote</label>
                            </div>
                            <small class="pi-shared-hint">Mantiene el comportamiento actual de restricción compartida.</small>
                        </div>
                        <div class="form-group col-md-7">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="piSharedApplyAssignments">
                                <label class="custom-control-label" for="piSharedApplyAssignments">Aplicar Sub-Contratista y Responsable comunes</label>
                            </div>
                            <small class="pi-shared-hint">Al activar, se unificarán Sub-Contratista y Responsable AIA en todas las actividades marcadas. Use Preview para revisar el impacto antes de aplicar.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <div class="pi-shared-restrictions-header">
                                <label class="mb-0">Restricciones objetivo</label>
                                <div class="pi-shared-restriction-actions">
                                    <button id="btn_pi_shared_select_all_restrictions" type="button" class="btn btn-outline-secondary">Seleccionar todas</button>
                                    <button id="btn_pi_shared_clear_restrictions" type="button" class="btn btn-outline-secondary">Limpiar</button>
                                </div>
                            </div>
                            <div id="piSharedRestrictionsPanel" class="pi-shared-restrictions-panel">
                                <div class="pi-shared-restriction-row" data-restriction-row="D_y_E">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_D_y_E" data-restriction-type="D_y_E" checked>
                                        <label class="custom-control-label" for="piSharedRestriction_D_y_E">Diseños y Especif.</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="D_y_E"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Materiales">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Materiales" data-restriction-type="Materiales">
                                        <label class="custom-control-label" for="piSharedRestriction_Materiales">Materiales</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Materiales"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="MdeO">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_MdeO" data-restriction-type="MdeO">
                                        <label class="custom-control-label" for="piSharedRestriction_MdeO">Mano de Obra</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="MdeO"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Equipos">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Equipos" data-restriction-type="Equipos">
                                        <label class="custom-control-label" for="piSharedRestriction_Equipos">Equipos</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Equipos"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Predecesora">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Predecesora" data-restriction-type="Predecesora">
                                        <label class="custom-control-label" for="piSharedRestriction_Predecesora">Predecesora</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Predecesora"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Pdto_Cons">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Pdto_Cons" data-restriction-type="Pdto_Cons">
                                        <label class="custom-control-label" for="piSharedRestriction_Pdto_Cons">Proced. Constructivo</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Pdto_Cons"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Modelo">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Modelo" data-restriction-type="Modelo">
                                        <label class="custom-control-label" for="piSharedRestriction_Modelo">Modelación BIM</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Modelo"></select>
                                </div>
                            </div>
                            <small class="pi-shared-hint">Marque una, varias o todas las restricciones que desea actualizar en este lote.</small>
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

                    <div id="piSharedAssignmentsFields" class="form-row d-none">
                        <div class="form-group col-md-6">
                            <label for="piSharedSubContratista">Sub-Contratista común</label>
                            <select id="piSharedSubContratista" class="form-control form-control-sm" disabled></select>
                            <small class="pi-shared-hint">Si queda vacío, no se modifica el Sub-Contratista.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="piSharedResponsableAIA">Responsable AIA común</label>
                            <select id="piSharedResponsableAIA" class="form-control form-control-sm" disabled></select>
                            <small class="pi-shared-hint">Si queda vacío, no se modifica el Responsable.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="piSharedNote">Observación de lote</label>
                        <textarea id="piSharedNote" class="form-control form-control-sm" rows="2" placeholder="Causa | Acción | Responsable | Fecha | Evidencia"></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="mb-1">Preview de impacto</label>
                        <div id="piSharedPreview" class="pi-shared-preview">Seleccione filas y pulse "Ver Conflictos" para validar el impacto de la asignación.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn_pi_shared_preview" type="button" class="btn btn-outline-primary">Ver Conflictos</button>
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

    <?php include __DIR__ . '/../partials/drawer_unificado.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=gen02" charset="utf-8"></script>
    <script type="text/javascript" src="/js/funcionesGenerales6.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script type="text/javascript" src="/js/modules/programacion_intermedia/stateMachine.js?v=pi3"></script>
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
    <script type="text/javascript" src="/js/HandsontableTomSelectEditor.js?v=tomselect30"></script>
    <script src="/js/modules/lps_drawer.js?v=20260522d"></script>
    <?php $piHotVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/programacion_intermedia/hot.js') ?: 'hot38'; ?>
    <script src="/js/modules/programacion_intermedia/hot.js?v=<?php echo urlencode((string) $piHotVersion); ?>"></script>

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
