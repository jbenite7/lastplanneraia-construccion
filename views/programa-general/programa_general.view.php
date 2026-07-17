<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <script src="/public/vendor/jquery.min.js"></script>
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::render() ?>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
    <?php $pgCssVersion = @filemtime(dirname(__DIR__, 2) . '/public/css/programa-general.css') ?: 'pgSprint04'; ?>
    <?php $pgGeneralJsVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/funcionesGenerales6.js') ?: 'pgGeneralJs04'; ?>
    <link rel="stylesheet" href="/css/programa-general.css?v=<?php echo urlencode((string) $pgCssVersion); ?>" />
    <!-- Toastr (Mensajes Emergentes) -->
    <link rel="stylesheet" href="/public/vendor/toastr.min.css" />
    <script src="/public/vendor/toastr.min.js"></script>
    <script>
        window.getCsrfToken = window.getCsrfToken || function() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta && meta.content ? meta.content : '';
        };

        if (window.jQuery) {
            window.jQuery(document).ajaxSend(function (_event, xhr, settings) {
                var url = settings && settings.url ? String(settings.url) : '';
                if (url.indexOf('/api/general/') === 0) {
                    xhr.setRequestHeader('X-CSRF-Token', window.getCsrfToken());
                }
            });
        }
    </script>
</head>
<body class="aia-shell pg-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programa_general" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="Max_Semana" value="<?php echo (int) ($maxSemana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="Semanal_Confirmada" value="<?php echo (int) ($semanalConfirmada ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="area_PHP" value="<?php echo htmlspecialchars($area ?? 'Construccion', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="<?php echo htmlspecialchars($initialFilterQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
    </div>

    <main class="aia-page hot-full-bleed" id="contenido">
    <div class="row direccionSeccion pg-direction-row">
        <?php if ($area === 'Pre-Construccion'): ?>
        <div class="col-sm-12 text-left mb-1">
            <small class="text-muted"><i class="fas fa-drafting-compass mr-1"></i>Pre-Construcción</small>
        </div>
        <?php endif; ?>
        <div class="col-sm-10 col-md-10 col-lg-10 ml-0 mr-auto text-left" id="textoDireccionSeccion"></div>
    </div>

    <section class="aia-toolbar header-actions action-bar" aria-label="Controles de Programa General">
        <div class="pg-actions-row">
            <div class="aia-action-group pg-toolbar-buttons" role="group" aria-label="Acciones del programa">
                <button type="button" class="aia-btn aia-btn--secondary leyenda_colores" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle ml-1" aria-hidden="true"></i></button>
                <button type="button" id="actualizarEjecucion" class="aia-btn">Actualizar Ejecución <i class="fas fa-sync ml-1" aria-hidden="true"></i></button>
                <button type="button" id="descargarCorteProgramacion" class="aia-btn aia-btn--secondary">Descargar Corte <i class="fas fa-download ml-1" aria-hidden="true"></i></button>
                <button id="btn-export" class="aia-btn aia-btn--secondary">Exportar CSV</button>
                <button id="btn-refresh" class="aia-btn aia-btn--secondary">Recargar</button>
                <?= \App\View\Components\BiAccessComponent::renderLink('programa-general', 'BI Programa') ?>
            </div>
            <div class="pg-status-badges">
                <span id="save-status" class="aia-chip aia-chip--success badge-badge-hidden">Guardado</span>
                <span id="save-error" class="aia-chip aia-chip--critical badge-badge-hidden">Error al guardar</span>
                <span class="aia-chip" aria-live="polite">Filtros <span id="mobileFilterCount" hidden></span></span>
            </div>
        </div>

        <div class="aia-filter-form" id="pdcFiltersMobile" role="group" aria-label="Filtros por estado">
            <?php if ($area === 'Pre-Construccion'): ?>
            <div class="pdc-legend pg-legend pdc-legend-autoscaling" id="pgLegend">
                <span class="pdc-legend-item alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Con Restricción Pendiente <span id="count-con-alerta-restricciones" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Por Iniciar <span id="count-debe-iniciar" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item actividad-futura" data-filter="actividad-futura" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item en-curso" data-filter="en-curso" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> En Ejecución <span id="count-en-curso" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada" data-filter="atrasada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item terminada" data-filter="terminada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Completada <span id="count-terminada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item sin-datos" data-filter="sin-datos" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Sin Datos <span id="count-sin-datos" class="count-badge">(...)</span></span>
            </div>
            <?php else: ?>
            <div class="pdc-legend pg-legend pdc-legend-autoscaling" id="pgLegend">
                <span class="pdc-legend-item alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Con Alerta Restricciones <span id="count-con-alerta-restricciones" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Debe Iniciar <span id="count-debe-iniciar" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item actividad-futura" data-filter="actividad-futura" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item en-curso" data-filter="en-curso" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> En Curso <span id="count-en-curso" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item atrasada" data-filter="atrasada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item terminada" data-filter="terminada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Terminada <span id="count-terminada" class="count-badge">(...)</span></span>
                <span class="pdc-legend-item sin-datos" data-filter="sin-datos" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Sin Datos <span id="count-sin-datos" class="count-badge">(...)</span></span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="hot-container" class="aia-grid-shell" aria-label="Programa General"></div>
    <div id="mobile-card-view" class="aia-data-display__cards"></div>
    </main>

    <div class="modal fade aia-modal" id="modal_leyenda_colores" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content aia-modal-surface">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal_leyenda_colores_Label">Guia Operativa - Programa General<?php if ($area === 'Pre-Construccion'): ?> (Pre-Construcción)<?php endif; ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
                </div>
                <div class="modal-body" id="modal_leyenda_colores_body"></div>
            </div>
        </div>
    </div>

    <div class="row ventanasModalesSemana" id="ventanasModalesSemana" data-skip-legacy-legend="true"></div>

    <?php include __DIR__ . '/../partials/drawer_unificado.php'; ?>

    <script src="/public/vendor/popper.min.js"></script>
    <script src="/public/vendor/bootstrap/bootstrap.min.js"></script>
    <script>window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;</script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('programa-general') ?>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script src="/js/modules/lps_drawer.js?v=20260522d"></script>
    <?php if ($area === 'Pre-Construccion' && $restrictionConfig): ?>
    <script>
        window.__RESTRICTION_CONFIG__ = <?php echo json_encode($restrictionConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php endif; ?>
    <?php $pgHotVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/programa_general/hot.js') ?: 'hot14'; ?>
    <script src="/js/modules/programa_general/hot.js?v=<?php echo urlencode((string) $pgHotVersion); ?>"></script>
    <script>
        if (window.PGHotModule && typeof window.PGHotModule.init === 'function') {
            window.PGHotModule.init();
        }
    </script>
    <script type="text/javascript" src="/js/funcionesGenerales6.js?v=<?php echo urlencode((string) $pgGeneralJsVersion); ?>" charset="utf-8"></script>
</body>
</html>
