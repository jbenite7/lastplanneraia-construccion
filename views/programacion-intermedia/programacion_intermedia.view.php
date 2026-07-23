<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <title>Programación Intermedia · Last Planner AIA</title>
    <script src="/public/vendor/jquery.min.js"></script>
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <link href="/public/vendor/tom-select/tom-select.bootstrap4.min.css" rel="stylesheet">
    <script src="/public/vendor/tom-select/tom-select.complete.min.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::render() ?>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260722pi1" charset="utf-8"></script>
    <?php $piCssVersion = @filemtime(dirname(__DIR__, 2) . '/public/css/programacion-intermedia.css') ?: 'piDark1'; ?>
    <link rel="stylesheet" href="/css/programacion-intermedia.css?v=<?php echo urlencode((string) $piCssVersion); ?>" />
</head>
<body class="aia-shell aia-shell--sidebar pi-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programacion_intermedia" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="" aria-hidden="true">
        <input type="hidden" id="Semanal_Confirmada" value="<?php echo (int) ($semanalConfirmada ?? 0); ?>" aria-hidden="true">
    </div>


    <div class="hot-full-bleed">
    <div class="header-actions action-bar">
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
                <button id="btn-refresh-listas" class="btn-pdc-modern" title="Recargar listas de Subcontratistas y Profesionales"><i class="fas fa-sync" aria-hidden="true"></i> Listas</button>
                <button id="btn-shared-select-visible" class="btn-pdc-modern">Seleccionar visibles</button>
                <button id="btn-shared-clear-selection" class="btn-pdc-modern">Limpiar selección</button>
                <?= \App\View\Components\BiAccessComponent::renderLink('intermedia', 'BI Intermedia') ?>
                <span id="shared-selection-count" class="badge badge-secondary" aria-live="polite">0 selec.</span>
                <div class="pi-status-badges">
                    <span id="save-status" class="badge badge-success pi-status-badge-hidden" role="status">Guardado</span>
                    <span id="save-error" class="badge badge-danger pi-status-badge-hidden" role="alert">Error al guardar</span>
                </div>
            </div>
        </div>

        <div class="collapse d-md-block" id="pdcFiltersMobile">
            <div class="pdc-legend pi-legend pdc-legend-autoscaling" id="piLegend">
                <span class="pi-legend-window-label <?= $viewAll ? 'is-active' : '' ?>" title="Los conteos del semaforo se calculan sobre la ventana de 6 semanas, no sobre la vista actual.">(Ventana 6 sem.)</span>
                <?php if ($area === 'Pre-Construccion'): ?>
                <span class="pi-legend-window-label pi-legend-window-label--info is-active" title="Pre-Construccion: 4 restricciones activas">Pre-Cons. 4R</span>
                <?php endif; ?>
                <span class="pdc-legend-item blocked-overdue-critical" data-filter="blocked-overdue-critical" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> RC inicio vencido <span id="count-blocked-overdue-critical" class="count-badge">(...)</span></span>

                <span class="pdc-legend-item blocked-overdue" data-filter="blocked-overdue" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Inicio Vencido <span id="count-blocked-overdue" class="count-badge">(...)</span></span>

                <span class="pdc-legend-item blocked-due" data-filter="blocked-due" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Inicio por Habilitar <span id="count-blocked-due" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-1-week" data-filter="alert-1-week" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Alistamiento Urgente <span id="count-alert-1-week" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-2-3-weeks" data-filter="alert-2-3-weeks" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Alistamiento en Riesgo <span id="count-alert-2-3-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item alert-4-6-weeks" data-filter="alert-4-6-weeks" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Alistamiento Pendiente <span id="count-alert-4-6-weeks" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item execution-blocked" data-filter="execution-blocked" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> En Ejecución Pendiente <span id="count-execution-blocked" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item liberated-control" data-filter="liberated-control" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Listo para Comprometer <span id="count-liberated-control" class="count-badge">(...)</span></span>
            </div>
        </div>
    </div>

    <div id="hot-container"></div>
    <div id="mobile-card-view" style="display:none;"></div>
    </div>

    <div class="modal fade aia-modal" id="modal_leyenda_colores" role="dialog" data-backdrop="static" aria-labelledby="modal_leyenda_colores_Label" aria-modal="true">
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

    <div class="modal fade aia-modal" id="modal_shared_constraint" role="dialog" data-backdrop="static" aria-labelledby="modal_shared_constraint_Label" aria-modal="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_shared_constraint_Label"><b>Aplicar Restricción Compartida</b></h5>
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
                                <?php if ($area !== 'Pre-Construccion'): ?>
                                <!-- CONSTRUCCION: 7 restricciones estándar -->
                                <div class="pi-shared-restriction-row" data-restriction-row="D_y_E">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_D_y_E" data-restriction-type="D_y_E" checked>
                                        <label class="custom-control-label" for="piSharedRestriction_D_y_E">Diseños y Especif.</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="D_y_E" aria-label="Valor objetivo para Diseños y Especificaciones"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Materiales">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Materiales" data-restriction-type="Materiales">
                                        <label class="custom-control-label" for="piSharedRestriction_Materiales">Materiales</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Materiales" aria-label="Valor objetivo para Materiales"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="MdeO">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_MdeO" data-restriction-type="MdeO">
                                        <label class="custom-control-label" for="piSharedRestriction_MdeO">Mano de Obra</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="MdeO" aria-label="Valor objetivo para Mano de Obra"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Equipos">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Equipos" data-restriction-type="Equipos">
                                        <label class="custom-control-label" for="piSharedRestriction_Equipos">Equipos</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Equipos" aria-label="Valor objetivo para Equipos"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Predecesora">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Predecesora" data-restriction-type="Predecesora">
                                        <label class="custom-control-label" for="piSharedRestriction_Predecesora">Predecesora</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Predecesora" aria-label="Valor objetivo para Predecesora"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Pdto_Cons">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Pdto_Cons" data-restriction-type="Pdto_Cons">
                                        <label class="custom-control-label" for="piSharedRestriction_Pdto_Cons">Proced. Constructivo</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Pdto_Cons" aria-label="Valor objetivo para Procedimiento Constructivo"></select>
                                </div>
                                <div class="pi-shared-restriction-row" data-restriction-row="Modelo">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_Modelo" data-restriction-type="Modelo">
                                        <label class="custom-control-label" for="piSharedRestriction_Modelo">Modelación BIM</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="Modelo" aria-label="Valor objetivo para Modelación BIM"></select>
                                </div>
                                <?php else: ?>
                                <!-- PRE-CONSTRUCCION: Predecesora + personalizadas nombradas -->
                                <div class="pi-shared-restriction-row" data-restriction-row="restriccion_pc_1">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_restriccion_pc_1" data-restriction-type="restriccion_pc_1" checked>
                                        <label class="custom-control-label" for="piSharedRestriction_restriccion_pc_1">Predecesora</label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="restriccion_pc_1" aria-label="Valor objetivo para Predecesora"></select>
                                </div>
                                <?php if (!empty($pcRestrictionNames[2])): ?>
                                <div class="pi-shared-restriction-row" data-restriction-row="restriccion_pc_2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_restriccion_pc_2" data-restriction-type="restriccion_pc_2">
                                        <label class="custom-control-label" for="piSharedRestriction_restriccion_pc_2"><?= htmlspecialchars($pcRestrictionNames[2], ENT_QUOTES, 'UTF-8') ?></label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="restriccion_pc_2" aria-label="Valor objetivo para <?= htmlspecialchars($pcRestrictionNames[2], ENT_QUOTES, 'UTF-8') ?>"></select>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($pcRestrictionNames[3])): ?>
                                <div class="pi-shared-restriction-row" data-restriction-row="restriccion_pc_3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_restriccion_pc_3" data-restriction-type="restriccion_pc_3">
                                        <label class="custom-control-label" for="piSharedRestriction_restriccion_pc_3"><?= htmlspecialchars($pcRestrictionNames[3], ENT_QUOTES, 'UTF-8') ?></label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="restriccion_pc_3" aria-label="Valor objetivo para <?= htmlspecialchars($pcRestrictionNames[3], ENT_QUOTES, 'UTF-8') ?>"></select>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($pcRestrictionNames[4])): ?>
                                <div class="pi-shared-restriction-row" data-restriction-row="restriccion_pc_4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input pi-shared-restriction-check" id="piSharedRestriction_restriccion_pc_4" data-restriction-type="restriccion_pc_4">
                                        <label class="custom-control-label" for="piSharedRestriction_restriccion_pc_4"><?= htmlspecialchars($pcRestrictionNames[4], ENT_QUOTES, 'UTF-8') ?></label>
                                    </div>
                                    <select class="form-control form-control-sm pi-shared-restriction-value" data-restriction-type="restriccion_pc_4" aria-label="Valor objetivo para <?= htmlspecialchars($pcRestrictionNames[4], ENT_QUOTES, 'UTF-8') ?>"></select>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
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

    <script src="/public/vendor/popper.min.js"></script>
    <script src="/public/vendor/bootstrap/bootstrap.min.js"></script>
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('intermedia') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=20260722falocal" charset="utf-8"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>
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
    <script src="/js/modules/lps_drawer.js?v=20260722shell1"></script>
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
