<?php /* El carril envuelve al `<nav>` solo para dar un ancla que NO se desplace:
   el degradado de corte tiene que quedarse pegado al borde derecho visible, y
   un `::after` absoluto dentro del propio contenedor con `overflow-x: auto`
   viaja con el contenido. Ver `.bi-tabs-rail` en public/css/bi-control-tower.css. */ ?>
<div class="bi-tabs-rail">
<nav class="bi-tabs-nav flex items-center gap-1 flex-shrink-0 overflow-x-auto whitespace-nowrap" role="tablist" aria-label="Hojas del reporte">
    <button type="button" role="tab" onclick="switchView('torre-control')" id="nav-torre-control" class="nav-item <?= ($reportKey ?? '') === 'overview' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-torre-control" aria-selected="<?= ($reportKey ?? '') === 'overview' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'overview' ? '0' : '-1' ?>">
        <i data-lucide="layout-dashboard" class="w-4 h-4" aria-hidden="true"></i> Resumen Ejecutivo
    </button>
    <button type="button" role="tab" onclick="switchView('programa-general')" id="nav-programa-general" class="nav-item <?= ($reportKey ?? '') === 'programa-general' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-programa-general" aria-selected="<?= ($reportKey ?? '') === 'programa-general' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'programa-general' ? '0' : '-1' ?>">
        <i data-lucide="gantt-chart" class="w-4 h-4" aria-hidden="true"></i> Programa General
    </button>
    <button type="button" role="tab" onclick="switchView('curva-s')" id="nav-curva-s" class="nav-item <?= ($reportKey ?? '') === 'curva-s' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-curva-s" aria-selected="<?= ($reportKey ?? '') === 'curva-s' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'curva-s' ? '0' : '-1' ?>">
        <i data-lucide="trending-up" class="w-4 h-4" aria-hidden="true"></i> Curva S
    </button>
    <button type="button" role="tab" onclick="switchView('intermedia')" id="nav-intermedia" class="nav-item <?= ($reportKey ?? '') === 'intermedia' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-intermedia" aria-selected="<?= ($reportKey ?? '') === 'intermedia' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'intermedia' ? '0' : '-1' ?>">
        <i data-lucide="filter" class="w-4 h-4" aria-hidden="true"></i> Prog. Intermedia (6 Sem)
    </button>
    <button type="button" role="tab" onclick="switchView('semanal')" id="nav-semanal" class="nav-item <?= ($reportKey ?? '') === 'semanal' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-semanal" aria-selected="<?= ($reportKey ?? '') === 'semanal' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'semanal' ? '0' : '-1' ?>">
        <i data-lucide="calendar-check" class="w-4 h-4" aria-hidden="true"></i> Programación Semanal
    </button>
    <button type="button" role="tab" onclick="switchView('pdc')" id="nav-pdc" class="nav-item <?= ($reportKey ?? '') === 'pdc' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-pdc" aria-selected="<?= ($reportKey ?? '') === 'pdc' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'pdc' ? '0' : '-1' ?>">
        <i data-lucide="shopping-cart" class="w-4 h-4" aria-hidden="true"></i> Plan de Compras
    </button>
    <button type="button" role="tab" onclick="switchView('cic')" id="nav-cic" class="nav-item <?= ($reportKey ?? '') === 'cic' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-cic" aria-selected="<?= ($reportKey ?? '') === 'cic' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'cic' ? '0' : '-1' ?>">
        <i data-lucide="hard-hat" class="w-4 h-4" aria-hidden="true"></i> Proveedores (CIC)
    </button>
    <button type="button" role="tab" onclick="switchView('cip')" id="nav-cip" class="nav-item <?= ($reportKey ?? '') === 'cip' ? 'active' : '' ?> flex items-center gap-2 px-4 py-2 text-sm font-medium flex-shrink-0" aria-controls="view-cip" aria-selected="<?= ($reportKey ?? '') === 'cip' ? 'true' : 'false' ?>" tabindex="<?= ($reportKey ?? '') === 'cip' ? '0' : '-1' ?>">
        <i data-lucide="users" class="w-4 h-4" aria-hidden="true"></i> Responsables (CIP)
    </button>
</nav>
</div>
