<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BI Control Tower — LPS AIA</title>
    <script>
        (function () {
            var theme = 'linen';
            var requestedTheme = new URLSearchParams(window.location.search).get('theme');
            try {
                theme = requestedTheme || window.localStorage.getItem('aia-theme') || theme;
            } catch (error) {
                theme = requestedTheme || 'linen';
            }
            theme = theme === 'dark' ? 'dark' : 'linen';
            try {
                if (requestedTheme === 'dark' || requestedTheme === 'linen') {
                    window.localStorage.setItem('aia-theme', theme);
                }
            } catch (error) {
                // The URL-selected theme still applies for the current page.
            }
            document.documentElement.setAttribute('data-aia-theme', theme);
            document.documentElement.classList.toggle('aia-theme-dark', theme === 'dark');
            document.documentElement.classList.toggle('aia-theme-linen', theme !== 'dark');
        })();
    </script>

    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/tokens.css') ?>
    <?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/aia-design-system.css') ?>

    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        aia: {
                            corp: { main: 'var(--ds-color-brand-primary)', med: 'var(--aia-green-medium)', light: 'var(--aia-green-light)', ext: 'var(--aia-green-very-light)' },
                            const: { main: 'var(--ds-color-brand-construction)', med: 'var(--aia-orange-medium)', light: 'var(--aia-orange-light)', ext: 'var(--aia-orange-very-light)' },
                            proj: { main: 'var(--ds-color-brand-aqua)', med: 'var(--aia-aqua-medium)', light: 'var(--aia-aqua-light)', ext: 'var(--aia-aqua-very-light)' },
                            alert: { crit: 'var(--aia-alert-critical)', high: 'var(--aia-alert-high)', med: 'var(--aia-alert-medium)', bg: 'var(--aia-alert-background)' },
                            warn: { crit: 'var(--aia-warning-critical)', high: 'var(--aia-warning-high)', med: 'var(--aia-warning-medium)', bg: 'var(--aia-warning-background)' },
                            neutral: { bg: 'var(--ds-color-bg-canvas)', border: 'var(--ds-color-border-default)', text: 'var(--ds-color-text-primary)' }
                        }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="/vendor/chart.js/chart.umd.min.js?v=4.4.1"></script>

    <link rel="stylesheet" href="/css/access.css">
    <link rel="stylesheet" href="/css/bi-control-tower.css?v=<?= filemtime(__DIR__ . '/../../public/css/bi-control-tower.css') ?>">
</head>
<body class="bi-control-tower-page antialiased h-screen flex bg-aia-neutral-bg text-sm">

    <!-- Sidebar -->
    <aside class="bi-sidebar w-72 bg-aia-corp-main text-white flex flex-col h-full flex-shrink-0 z-20">
        <header class="p-5 border-b border-aia-corp-med flex items-center gap-3">
            <i data-lucide="tower-control" class="w-7 h-7 text-aia-const-light" aria-hidden="true"></i>
            <div>
                <h1 class="text-base font-bold tracking-tight leading-tight">Torre de Control</h1>
                <span class="text-[10px] text-aia-corp-light uppercase tracking-wider">Last Planner AIA</span>
            </div>
        </header>
        <div class="bi-sidebar-scroll flex-1 overflow-y-auto flex flex-col">
            <?php require_once __DIR__ . '/_nav.php'; ?>
            <?php require_once __DIR__ . '/_filters.php'; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="bi-main-shell flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bi-main-header border-b border-gray-200 py-3 px-6 flex flex-col justify-center flex-shrink-0 z-10 min-h-[72px]">
            <h2 id="current-view-title" class="text-xl font-bold text-gray-800 tracking-tight leading-none mb-2">Resumen Ejecutivo</h2>
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
    <script src="/js/modules/aia_ui/theme.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/aia_ui/theme.js') ?>"></script>
    <script src="/js/modules/bi-spa.js?v=<?= filemtime(__DIR__ . '/../../public/js/modules/bi-spa.js') ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
