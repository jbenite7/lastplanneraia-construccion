<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BI Control Tower — LPS AIA</title>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme-bootstrap.js') ?>

    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/tokens.css') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/aia-design-system.css') ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="/vendor/chart.js/chart.umd.min.js?v=4.4.1"></script>

    <link rel="stylesheet" href="/css/access.css">
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/design-system/adapters/bi-utilities.css') ?>
    <link rel="stylesheet" href="/css/bi-control-tower.css?v=<?= filemtime(__DIR__ . '/../../public/css/bi-control-tower.css') ?>">
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/bi-filter-drawer.css') ?>
</head>
<body class="aia-shell aia-shell--sidebar bi-control-tower-page antialiased text-sm">

    <?php require __DIR__ . '/../partials/shell_sidebar.php'; ?>

    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="bi-filter-drawer-overlay" data-bi-filter-drawer-overlay hidden></div>
    <div class="bi-filter-drawer" id="bi-filter-drawer" data-bi-filter-drawer hidden role="dialog" aria-modal="true" aria-labelledby="bi-filter-drawer-title" aria-hidden="true">
        <div class="bi-filter-drawer__header">
            <h3 id="bi-filter-drawer-title" class="bi-filter-drawer__title">Filtros</h3>
            <button type="button" class="bi-filter-drawer__close" data-bi-filter-close aria-label="Cerrar filtros">&times;</button>
        </div>
        <div class="bi-filter-drawer__body">
            <?php require __DIR__ . '/_filters.php'; ?>
        </div>
    </div>

    <div class="bi-main-shell flex-1 flex flex-col min-h-0 overflow-hidden relative">
        <header class="bi-main-header border-b border-gray-200 py-3 px-6 flex flex-col justify-center flex-shrink-0 z-10 min-h-[72px]">
            <div class="flex items-center justify-between gap-4 mb-2">
                <h2 id="current-view-title" class="text-xl font-bold text-gray-800 tracking-tight leading-none">Resumen Ejecutivo</h2>
                <button type="button" data-bi-filter-trigger class="aia-btn aia-btn--secondary bi-filter-trigger" aria-haspopup="dialog" aria-controls="bi-filter-drawer" aria-expanded="false">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4" aria-hidden="true"></i>
                    <span>Filtros</span>
                    <strong id="bi-filter-count" class="bi-filter-trigger__count">0</strong>
                </button>
            </div>
            <div id="active-filters" class="flex flex-wrap gap-2 min-h-[24px]" aria-label="Filtros activos aplicados" role="group"></div>
        </header>

        <main class="bi-main-content flex-1 overflow-y-auto p-6 flex flex-col gap-6 relative" role="main">
            <!-- Empty State -->
            <div id="empty-state" class="bi-empty-state hidden" role="alert">
                <i data-lucide="inbox" class="w-16 h-16 text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No hay datos para los filtros seleccionados.</h3>
                <p class="text-gray-500 mb-6 max-w-md">Ajusta el rango de fechas, cambia de proyecto o verifica que existan registros operativos para tu selección actual.</p>
                <button onclick="resetFilters()" class="bi-btn-primary flex items-center gap-2">
                    <i data-lucide="filter-x" class="w-4 h-4"></i> Limpiar filtros
                </button>
            </div>

            <div id="views-container" class="w-full flex flex-col gap-6">
                <?php require_once __DIR__ . '/' . ($viewFile ?? 'control-tower') . '.php'; ?>
            </div>
        </main>

        <footer class="bi-footer border-t border-gray-200 px-6 py-2 text-xs text-gray-400 flex justify-between flex-shrink-0">
            <span>Última actualización de datos: <?= date('d/m/Y H:i:s') ?></span>
            <span>AIA — Construimos por Naturaleza</span>
        </footer>
    </div>

    <script id="bi-data" type="application/json"><?= $initialData ?? '{}' ?></script>
    <script>
        window.__AIA_SHELL_SIDEBAR__ = true;
    </script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
    <script src="/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>
    <script src="/js/modules/bi_chart_theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/bi_chart_theme.js') ?>"></script>
    <script src="/js/modules/bi-spa.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/bi-spa.js') ?>"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/bi_filter_drawer.js') ?>
    <script>lucide.createIcons();</script>
</body>
</html>
