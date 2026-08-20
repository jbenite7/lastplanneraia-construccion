<!DOCTYPE html>
<html lang="es">
<head id="head">
    <meta charset="UTF-8">
    <?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <title>Programa General — Last Planner AIA</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <script src="/public/vendor/jquery.min.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::render() ?>
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
    <?php $pgCssVersion = @filemtime(dirname(__DIR__, 2) . '/public/css/programa-general.css') ?: 'pgSprint04'; ?>
    <?php $pgGeneralJsVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/funcionesGenerales6.js') ?: 'pgGeneralJs04'; ?>
    <link rel="stylesheet" href="/css/programa-general.css?v=<?php echo urlencode((string) $pgCssVersion); ?>" />
    <!-- Toastr (Mensajes Emergentes); su JS carga al final del body -->
    <link rel="stylesheet" href="/public/vendor/toastr.min.css" />
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
<body class="aia-shell aia-shell--sidebar pg-page">
    <div id="loading"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></div>

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <div class="encabezado" id="encabezado">
        <input type="hidden" name="seccion" id="seccion" value="programa_general" aria-hidden="true">
        <input type="hidden" id="baseDatos_PHP" value="<?php echo htmlspecialchars($dbName ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="semana_PHP" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="permiso_canonico" value="<?php echo htmlspecialchars($permiso ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <input type="hidden" id="Max_Semana" value="<?php echo (int) ($maxSemana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="Semanal_Confirmada" value="<?php echo (int) ($semanalConfirmada ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="area_PHP" value="<?php echo htmlspecialchars($area ?? 'Construccion', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <!-- C-46: funcionesGenerales6.js lee #semana sin guarda y esta vista no carga el inyector. -->
        <input type="hidden" id="semana" name="semana" value="<?php echo (int) ($semana ?? 0); ?>" aria-hidden="true">
        <input type="hidden" id="scriptBarraFiltros" value="<?php echo htmlspecialchars($initialFilterQuery ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
    </div>

    <main class="aia-page hot-full-bleed" id="contenido">
    <h1 class="aia-visually-hidden">Programa General<?php if ($area === 'Pre-Construccion'): ?> — Pre-Construcción<?php endif; ?></h1>
    <?php if ($area === 'Pre-Construccion'): ?>
    <div class="row direccionSeccion pg-direction-row">
        <div class="col-sm-12 text-left mb-1">
            <small class="text-muted"><i class="fas fa-drafting-compass mr-1"></i>Pre-Construcción</small>
        </div>
    </div>
    <?php endif; ?>

    <section class="aia-toolbar pg-toolbar" aria-label="Controles de Programa General">
        <div class="pg-actions-row">
            <div class="aia-action-group pg-toolbar-buttons" role="group" aria-label="Acciones del programa">
                <button type="button" class="aia-btn aia-btn--secondary leyenda_colores" data-toggle="modal" data-target="#modal_leyenda_colores">Leyenda <i class="fas fa-question-circle ml-1" aria-hidden="true"></i></button>
                <button type="button" id="actualizarEjecucion" class="aia-btn aia-btn--primary">Actualizar Ejecución <i class="fas fa-sync ml-1" aria-hidden="true"></i></button>
                <button type="button" id="descargarCorteProgramacion" class="aia-btn aia-btn--secondary">Descargar Corte <i class="fas fa-download ml-1" aria-hidden="true"></i></button>
                <button id="btn-export" class="aia-btn aia-btn--secondary">Exportar CSV</button>
                <button id="btn-refresh" class="aia-btn aia-btn--secondary">Recargar</button>
                <?= \App\View\Components\BiAccessComponent::renderLink('programa-general', 'BI Programa', 'aia-btn aia-btn--secondary') ?>
            </div>
            <div class="pg-status-badges" aria-live="polite">
                <span id="save-status" class="aia-chip aia-chip--success badge-badge-hidden" role="status">Guardado</span>
            </div>
        </div>

        <div class="aia-filter-form" id="pdcFiltersMobile" role="group" aria-label="Filtros por estado">
            <?php if ($area === 'Pre-Construccion'): ?>
            <div id="pgLegend">
                <span class="aia-chip pdc-legend-item pg-filter-chip alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Con Restricción Pendiente <span id="count-con-alerta-restricciones" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Por Iniciar <span id="count-debe-iniciar" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip actividad-futura" data-filter="actividad-futura" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip en-curso" data-filter="en-curso" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> En Ejecución <span id="count-en-curso" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip atrasada" data-filter="atrasada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip terminada" data-filter="terminada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Completada <span id="count-terminada" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip fuera-de-ventana" data-filter="fuera-de-ventana" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Fuera de Ventana <span id="count-fuera-de-ventana" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip sin-datos" data-filter="sin-datos" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Sin Datos <span id="count-sin-datos" class="count-badge">…</span></span>
            </div>
            <?php else: ?>
            <div id="pgLegend">
                <span class="aia-chip pdc-legend-item pg-filter-chip alerta-restricciones" data-filter="con-alerta-restricciones" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Con Alerta Restricciones <span id="count-con-alerta-restricciones" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip debe-iniciar" data-filter="debe-iniciar" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Debe Iniciar <span id="count-debe-iniciar" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip actividad-futura" data-filter="actividad-futura" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Actividad Futura <span id="count-actividad-futura" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip en-curso" data-filter="en-curso" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> En Curso <span id="count-en-curso" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip atrasada" data-filter="atrasada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Atrasada <span id="count-atrasada" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip terminada" data-filter="terminada" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Terminada <span id="count-terminada" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip fuera-de-ventana" data-filter="fuera-de-ventana" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Fuera de Ventana <span id="count-fuera-de-ventana" class="count-badge">…</span></span>
                <span class="aia-chip pdc-legend-item pg-filter-chip sin-datos" data-filter="sin-datos" role="button" tabindex="0" aria-pressed="false"><span class="indicator"></span> Sin Datos <span id="count-sin-datos" class="count-badge">…</span></span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="hot-container" class="aia-grid-shell" aria-label="Programa General"></div>
    <div id="mobile-card-view" class="aia-data-display__cards"></div>
    </main>

    <div class="modal fade aia-modal" id="modal_leyenda_colores" tabindex="-1" role="dialog" aria-labelledby="modal_leyenda_colores_Label" data-backdrop="static">
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
    <!-- Vendors diferidos del head: jQuery-UI (datepicker del modal de semanas) y Toastr, necesarios solo tras la carga -->
    <script src="/public/vendor/jquery-ui.min.js"></script>
    <script src="/public/vendor/toastr.min.js"></script>
    <script>
        window.__PROJECT_AREA__ = <?php echo json_encode($_SESSION['area'] ?? 'Construccion'); ?>;
        // Shell sidebar (DS-027): el loader conserva datos/permisos pero no monta navbar.
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\BiAccessComponent::renderBootConfig('programa-general') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <script type="text/javascript" src="/js/modules/bi-access.js" charset="utf-8"></script>

    <script src="/public/vendor/handsontable/handsontable.full.min.js"></script>
    <script src="/public/vendor/handsontable/es-MX.js"></script>
    <script src="/js/modules/lps_drawer.js?v=20260722shell1"></script>
    <?php if ($area === 'Pre-Construccion' && $restrictionConfig): ?>
    <script>
        window.__RESTRICTION_CONFIG__ = <?php echo json_encode($restrictionConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php endif; ?>
    <script src="/js/core/SessionExpiredHandler.js?v=20260811a"></script>
    <?php $pgViewSwitchVersion = @filemtime(dirname(__DIR__, 2) . '/public/js/modules/aia_ui/view-switch.js') ?: 'vs1'; ?>
    <script type="module" src="/js/modules/aia_ui/view-switch.js?v=<?php echo urlencode((string) $pgViewSwitchVersion); ?>"></script>
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
