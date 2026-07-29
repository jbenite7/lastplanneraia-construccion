<?php
if (($area ?? 'Construccion') === 'Pre-Construccion') {
    $categoriasCP = [
        "Buen Rendimiento",
        "Oportunidad Detectada",
        "Disenos Listos",
        "Modelacion BIM Disponible",
        "Presupuesto Disponible",
        "Contratacion Disponible",
        "Tramites Resueltos",
        "Condiciones Favorables",
        "Compensacion de Frente"
    ];
} else {
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
}
?>
<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">        <script src="/public/vendor/jquery.min.js"></script>
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('programacion-semanal') ?>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
    <link rel="stylesheet" href="/public/vendor/handsontable/handsontable.full.min.css" />
    <!-- handsontable-module.css llega vía attach-handsontable.css (layer vendor); el link crudo duplicaba la cascada. -->
    <!-- select2.min.css llega vía attach-select2.css (layer vendor); sin capa ganaba al adaptador y dejaba el desplegable claro. -->
    <?php $psCssVersion = @filemtime(dirname(__DIR__, 2) . '/public/css/programacion-semanal.css') ?: 'ps1'; ?>
    <link rel="stylesheet" href="/css/programacion-semanal.css?v=<?= urlencode((string) $psCssVersion) ?>">
    <link rel="stylesheet" href="/css/handsontable-header-global.css?v=20260223a" />
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/change-monitor.css') ?>
</head>
<body class="aia-shell aia-shell--sidebar ps-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programacion_semanal" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="area_PHP" value="<?php echo htmlspecialchars($area ?? 'Construccion', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="" aria-hidden="true">
    </div>

    <div class="hot-full-bleed">
    <div class="row direccionSeccion ps-direction-row">
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <div class="header-actions action-bar ps-hot-toolbar-shell">
            <div class="ps-actions-row">
            <button class="btn-pdc-modern ps-mobile-actions-toggle d-md-none" type="button" data-toggle="collapse" data-target="#psMobileActionsPanel" aria-expanded="false" aria-controls="psMobileActionsPanel">
                <i aria-hidden="true" class="fas fa-sliders-h"></i> <span>Botones de acción</span>
            </button>
            <div class="ps-toolbar-left collapse d-md-flex" id="psMobileActionsPanel">
                <span class="ps-mobile-actions-title d-md-none">Acciones disponibles</span>
                <div class="ps-hot-toolbar-actions">
                    <button type="button" class="leyenda_colores btn-pdc-modern" data-toggle="modal" data-target="#modal_leyenda_colores_ps" aria-label="Ver leyenda de colores"><i aria-hidden="true" class="fas fa-question-circle"></i> <span>Leyenda</span></button>
                    <button id="btn_autoprogramar" class="btn-pdc-modern" aria-label="Autoprogramar Actividades"><i aria-hidden="true" class="fas fa-magic"></i> <span>Autoprogramar Actividades</span></button>
                    <button id="btn_agregar_actividad" type="button" class="btn-pdc-modern" aria-label="Agregar Actividad Manual"><i aria-hidden="true" class="fas fa-plus"></i> <span>Agregar Actividad</span></button>
                    <button id="btn_cerrar_compromisos_semana" type="button" class="btn-pdc-modern" data-toggle="modal" data-target="#modal_cerrar_compromisos" aria-label="Confirmar Compromisos de la Semana"><i aria-hidden="true" class="fas fa-lock"></i> <span>Confirmar Compromisos</span></button>
                    <button type="button" id="btn_reabrir_semana" class="btn-pdc-modern ps-runtime-hidden" aria-label="Reabrir semana para edición"><i aria-hidden="true" class="fas fa-unlock"></i> <span>Reabrir Semana</span></button>
                    <button id="btn_tnp" type="button" class="btn-pdc-modern ps-runtime-hidden" aria-label="Registrar Trabajo No Planificado"><i aria-hidden="true" class="fas fa-bolt"></i> <span>Registrar TNP</span></button>
                    <button id="btn_informe_compromisos" type="button" class="btn-pdc-modern" aria-label="Imprimir Informe de Compromisos"><i aria-hidden="true" class="fas fa-print"></i> <span>Imprimir</span></button>
                    <button id="btn-export" class="btn-pdc-modern" aria-label="Exportar datos a CSV"><i aria-hidden="true" class="fas fa-file-csv"></i> <span>Exportar CSV</span></button>
                    <button id="btn-refresh" class="btn-pdc-modern" aria-label="Recargar tabla de actividades"><i aria-hidden="true" class="fas fa-sync"></i> <span>Recargar</span></button>
                    <?= \App\View\Components\BiAccessComponent::renderLink('semanal', 'BI Semanal') ?>
                </div>
            </div>

            <div class="ps-toolbar-right">
                <div class="ps-status-badges">
                    <span id="save-status" class="badge badge-success badge-badge-hidden">Guardado</span>
                </div>
                <div id="ps-toast-container" aria-live="polite"></div>
                <div class="ps-dropdown-nav" aria-label="Navegacion Programacion Semanal">
                    <button type="button" class="btn-pdc-modern btn-dropdown-trigger" aria-haspopup="true" aria-expanded="false">
                        <i aria-hidden="true" class="fas fa-th-list"></i> <span>Ver Secciones</span> <i aria-hidden="true" class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <div class="ps-dropdown-content" role="menu">
                        <button id="btn_Actividades" type="button" class="ps-dropdown-item is-active" role="menuitem"><i aria-hidden="true" class="fas fa-table"></i> Actividades</button>
                        <button id="btn_CNP" type="button" class="ps-dropdown-item" role="menuitem"><i aria-hidden="true" class="fas fa-calendar-times"></i> Causas No Programacion</button>
                        <button id="btn_CNC" type="button" class="ps-dropdown-item" role="menuitem"><i aria-hidden="true" class="fas fa-exclamation-triangle"></i> Causas No Cumplimiento</button>
                        <button id="btn_Cal_Proveedores" type="button" class="ps-dropdown-item" role="menuitem"><i aria-hidden="true" class="fas fa-clipboard-check"></i> Calificacion Proveedores</button>
                    </div>
                </div>
                <button class="btn-filter-toggle pdc-mobile-toggle" type="button" data-toggle="collapse" data-target="#psAlertsMobile" aria-expanded="false" aria-controls="psAlertsMobile">
                    <i aria-hidden="true" class="fas fa-filter"></i> Alertas <span id="weeklyPhaseMobileLabel" class="ps-weekly-phase-mobile-label">Programacion</span> <span class="badge badge-light" id="mobileAlertCount">0</span>
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
    <div id="mobile-card-view"></div>
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
                    <input id="btn_confirmar_compromisos_semana" type="button" class="btn btn-primary btn-lg" value="Confirmar" aria-label="Confirmar cerrar compromisos">
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

    <div class="modal fade aia-modal ps-module-modal ps-manual-activity-modal" id="formulario_nuevo" tabindex="-1" role="dialog" aria-labelledby="formularioNuevoLabel" aria-hidden="true" aria-modal="true">
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
                                                <tr><td colspan="4" class="text-center ps-muted">Cargando actividades...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7 ps-nueva-actividad-col ps-nueva-actividad-col--formulario">
                            <form class="form_nueva_actividad form form-horizontal ps-form-layout ps-modal-form" autocomplete="off">
                                <div class="form-group ps-form-col">
                                    <label for="idNuevoDisplay" class="control-label">Id *</label>
                                    <input type="hidden" id="idNuevo" name="idNuevo" value="" aria-required="true" required>
                                    <input type="text" id="idNuevoDisplay" class="form-control" value="" placeholder="Selecciona una actividad de la Bandeja" readonly aria-readonly="true" tabindex="-1">
                                    <small class="form-text ps-id-source-hint">Selecciona una actividad de la <strong>Bandeja de No Autoprogramadas</strong> a la izquierda.</small>
                                    <!-- Ancla del mensaje de error por campo. Nace vacia a proposito: un texto
                                         permanente aqui lo leeria el lector de pantalla en modo exploracion
                                         incluso con el campo correcto. Lo rellena y lo vacia submitNewActivity()
                                         a la vez que pone y quita `aria-invalid`. -->
                                    <small class="sr-only ps-field-error-message" id="ps-error-idNuevo"></small>
                                </div>
                                <div class="form-group ps-form-col">
                                    <label for="Actividad" class="control-label">Actividad *</label>
                                    <div><input id="Actividad" name="Actividad" class="form-control" value="" type="text" aria-required="true" required><small class="sr-only ps-field-error-message" id="ps-error-Actividad"></small></div>
                                </div>
                                <div class="form-group ps-form-col">
                                    <label for="Descripcion" class="control-label">Descripción</label>
                                    <div><input id="Descripcion" name="Descripcion" class="form-control" value="" type="text"></div>
                                </div>
                                <input id="Ubicacion" name="Ubicacion" class="form-control" value="" type="hidden">
                                <div class="form-group ps-form-col-6">
                                    <label for="Sub_Contratista" class="control-label">Sub-Contratista *</label>
                                    <div>
                                        <select id="Sub_Contratista" name="Sub_Contratista" class="form-control" aria-required="true" required>
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
                                        <small class="sr-only ps-field-error-message" id="ps-error-Sub_Contratista"></small>
                                    </div>
                                </div>
                                <div class="form-group ps-form-col-6">
                                    <label for="Responsable_AIA" class="control-label">Profesional AIA *</label>
                                    <div>
                                        <select id="Responsable_AIA" name="Responsable_AIA" class="form-control" aria-required="true" required>
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
                                        <small class="sr-only ps-field-error-message" id="ps-error-Responsable_AIA"></small>
                                    </div>
                                </div>
                                <input id="Empresa" name="Empresa" class="form-control" value="" type="hidden">
                                <div class="form-group ps-form-col-4">
                                    <label for="Unidad" class="control-label">Unidad de Medida</label>
                                    <div><input id="Unidad" name="Unidad" class="form-control" value="" type="text" readonly aria-readonly="true" placeholder="Automático" tabindex="-1"></div>
                                </div>
                                <div class="form-group ps-form-col-4">
                                    <label for="CantidadPPTO" class="control-label">Cant. PPTO</label>
                                    <div><input id="CantidadPPTO" name="CantidadPPTO" class="form-control" value="" type="text" readonly aria-readonly="true" placeholder="Sin cantidad" tabindex="-1"></div>
                                </div>
                                <div class="form-group ps-form-col-4">
                                    <label for="Compromiso" class="control-label">Cantidad *</label>
                                    <div><input id="Compromiso" name="Compromiso" class="form-control" value="" type="text" aria-required="true" required><small class="sr-only ps-field-error-message" id="ps-error-Compromiso"></small></div>
                                </div>
                                <input id="Real" name="Real" class="form-control" value="" type="hidden">
                                <input type="hidden" id="opcion" name="opcion" value="nuevo">
                                <div class="ps-form-actions">
                                    <button type="button" id="btn_guardar_nueva_actividad" class="btn btn-primary" aria-label="Guardar nueva actividad">Guardar</button>
                                    <button type="button" id="btn_listar" class="btn btn-outline-secondary" aria-label="Cancelar nueva actividad" data-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                            <p class="mensaje" role="status" aria-live="polite"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade aia-modal ps-module-modal ps-delete-cnp-modal" id="modal_eliminar_actividad" tabindex="-1" role="dialog" aria-labelledby="psDeleteModalTitle" aria-hidden="true" aria-modal="true" data-backdrop="static">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="psDeleteModalTitle"><b>Eliminar Actividad</b></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <p id="psDeleteModalText" class="mb-3"></p>
                    <div class="ps-delete-cnp-grid">
                    <div class="form-group ps-delete-cnp-field">
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
                    <div class="form-group ps-delete-cnp-field">
                        <label for="psDeleteEmpresa">Empresa Encargada de la Ejecucion</label>
                        <input id="psDeleteEmpresa" type="text" class="form-control">
                    </div>
                    <div class="form-group ps-delete-cnp-field">
                        <label for="psDeleteCategoriaCNP">Categoria</label>
                        <select id="psDeleteCategoriaCNP" class="form-control">
                            <option value=""></option>
                            <?php if (($area ?? 'Construccion') === 'Pre-Construccion'): ?>
                                <option value="Diseños">Diseños</option>
                                <option value="Modelación">Modelación</option>
                                <option value="Presupuesto">Presupuesto</option>
                                <option value="Contratación">Contratación</option>
                                <option value="Trámites">Trámites</option>
                            <?php else: ?>
                                <option value="Programación">Programacion</option>
                                <option value="Mano de Obra">Mano de Obra</option>
                                <option value="Materiales">Materiales</option>
                                <option value="Equipos">Equipos</option>
                                <option value="Diseños">Diseños</option>
                                <option value="Administrativas">Administrativas</option>
                                <option value="Causas Exógenas">Causas Exógenas</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group ps-delete-cnp-field">
                        <label for="psDeleteCNP">Causa de No Programacion</label>
                        <select id="psDeleteCNP" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-0 ps-delete-cnp-field ps-delete-cnp-field--full">
                        <label for="psDeleteObservacionesCNP">Observaciones</label>
                        <textarea id="psDeleteObservacionesCNP" class="form-control"></textarea>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn_confirmar_eliminar_actividad" type="button" class="btn btn-danger">Guardar y Eliminar</button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal CNC (Handsontable Handler) -->
    <div class="modal fade aia-modal" id="modal_cnc_hot" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h4 class="modal-title ps-modal-title">Justificación de Incumplimiento (CNC)</h4>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="ps-modal-copy">El avance real digitado es <strong>inferior al compromiso</strong>. Obligatoriamente debes justificar el motivo a continuación. Al guardar, actualizaremos la fila en la tabla y en el servidor.</p>
                    <div class="form-group mt-3 mb-4">
                        <label for="hot_cat_cnc" class="ps-modal-label">Categoría CNC <span class="ps-required">*</span></label>
                        <select id="hot_cat_cnc" class="form-control ps-modal-control">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label for="hot_cnc" class="ps-modal-label">Causa de No Cumplimiento <span class="ps-required">*</span></label>
                        <select id="hot_cnc" class="form-control ps-modal-control" disabled>
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="hot_obs_cnc" class="ps-modal-label">Observaciones <span class="ps-required">*</span></label>
                        <textarea id="hot_obs_cnc" class="form-control ps-modal-control ps-modal-textarea" rows="3" placeholder="Detalle la causa del incumplimiento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer ps-modal-footer-between">
                    <button id="btn_cancelar_cnc_hot" type="button" class="btn btn-outline-secondary ps-modal-secondary" data-dismiss="modal">Cancelar</button>
                    <button id="btn_guardar_cnc_hot" type="button" class="btn aia-btn-primary">Guardar y Confirmar</button>
                </div>
        </div>
    </div>
</div>

<!-- Modal TNP - Trabajo No Planificado -->
<div class="modal fade aia-modal" id="modal_tnp" tabindex="-1" role="dialog" aria-labelledby="modal_tnp_label">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header ps-tnp-header">
        <h5 class="modal-title" id="modal_tnp_label">
          Registrar Trabajo No Planificado
          <small class="d-block mt-1 ps-tnp-subtitle">¿Por qué decidiste ejecutarla?</small>
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body ps-tnp-body">
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
        <div id="tnp_actividad_info" class="ps-runtime-hidden">
          <div class="card mb-3 ps-tnp-info-card">
            <div class="card-body py-2 px-3">
              <div class="row">
                <div class="col-6">
                  <small class="ps-muted d-block">Subcontratista</small>
                  <strong id="tnp_info_subcontratista">-</strong>
                </div>
                <div class="col-6">
                  <small class="ps-muted d-block">Responsable AIA</small>
                  <strong id="tnp_info_residente">-</strong>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-4">
                  <small class="ps-muted d-block">Frente</small>
                  <strong id="tnp_info_frente">-</strong>
                </div>
                <div class="col-4">
                  <small class="ps-muted d-block">Unidad</small>
                  <strong id="tnp_info_unidad">-</strong>
                </div>
                <div class="col-4">
                  <small class="ps-muted d-block">Cuantía</small>
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
      <div class="modal-footer ps-tnp-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="btn_guardar_tnp">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/_changeMonitorModal.php'; ?>
<?php include __DIR__ . '/partials/modal_reabrir.php'; ?>
    <?php include __DIR__ . '/../partials/drawer_unificado.php'; ?>

    <script src="/public/vendor/popper.min.js"></script>
    <script src="/public/vendor/bootstrap/bootstrap.min.js"></script>
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('semanal') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<script type="text/javascript" src="/js/cargarDatosGeneralesPagina2.js?v=20260722polish" charset="utf-8"></script>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>
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
    <script src="/js/modules/lps_drawer.js?v=20260722shell1"></script>
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
