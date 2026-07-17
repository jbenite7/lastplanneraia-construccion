<nav class="py-2 flex flex-col gap-0.5" role="navigation" aria-label="Navegación principal">
    <div class="bi-sheet-selector aia-panel" aria-label="Hojas del reporte">
        <label for="bi-mobile-sheet-select" class="aia-label">Hoja del reporte</label>
        <div class="bi-sheet-select-control">
            <i data-lucide="layers-3" class="w-4 h-4" aria-hidden="true"></i>
            <select id="bi-mobile-sheet-select" class="aia-select" aria-label="Cambiar hoja del reporte" onchange="switchView(this.value)">
                <option value="torre-control" <?= ($reportKey ?? '') === 'overview' ? 'selected' : '' ?>>Resumen Ejecutivo</option>
                <option value="programa-general" <?= ($reportKey ?? '') === 'programa-general' ? 'selected' : '' ?>>Programa General</option>
                <option value="curva-s" <?= ($reportKey ?? '') === 'curva-s' ? 'selected' : '' ?>>Curva S</option>
                <option value="intermedia" <?= ($reportKey ?? '') === 'intermedia' ? 'selected' : '' ?>>Prog. Intermedia (6 Sem)</option>
                <option value="semanal" <?= ($reportKey ?? '') === 'semanal' ? 'selected' : '' ?>>Programación Semanal</option>
                <option value="pdc" <?= ($reportKey ?? '') === 'pdc' ? 'selected' : '' ?>>Plan de Compras</option>
                <option value="cic" <?= ($reportKey ?? '') === 'cic' ? 'selected' : '' ?>>Proveedores (CIC)</option>
                <option value="cip" <?= ($reportKey ?? '') === 'cip' ? 'selected' : '' ?>>Responsables (CIP)</option>
            </select>
        </div>
    </div>
    <div class="bi-sheet-nav-list">
    <a href="#" onclick="switchView('torre-control')" id="nav-torre-control" class="nav-item <?= ($reportKey ?? '') === 'overview' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'overview' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Resumen Ejecutivo
    </a>
    <a href="#" onclick="switchView('programa-general')" id="nav-programa-general" class="nav-item <?= ($reportKey ?? '') === 'programa-general' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'programa-general' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="gantt-chart" class="w-4 h-4"></i> Programa General
    </a>
    <a href="#" onclick="switchView('curva-s')" id="nav-curva-s" class="nav-item <?= ($reportKey ?? '') === 'curva-s' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'curva-s' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="trending-up" class="w-4 h-4"></i> Curva S
    </a>
    <a href="#" onclick="switchView('intermedia')" id="nav-intermedia" class="nav-item <?= ($reportKey ?? '') === 'intermedia' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'intermedia' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="filter" class="w-4 h-4"></i> Prog. Intermedia (6 Sem)
    </a>
    <a href="#" onclick="switchView('semanal')" id="nav-semanal" class="nav-item <?= ($reportKey ?? '') === 'semanal' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'semanal' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="calendar-check" class="w-4 h-4"></i> Programación Semanal
    </a>
    <a href="#" onclick="switchView('pdc')" id="nav-pdc" class="nav-item <?= ($reportKey ?? '') === 'pdc' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'pdc' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="shopping-cart" class="w-4 h-4"></i> Plan de Compras
    </a>
    <a href="#" onclick="switchView('cic')" id="nav-cic" class="nav-item <?= ($reportKey ?? '') === 'cic' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'cic' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="hard-hat" class="w-4 h-4"></i> Proveedores (CIC)
    </a>
    <a href="#" onclick="switchView('cip')" id="nav-cip" class="nav-item <?= ($reportKey ?? '') === 'cip' ? 'active' : '' ?> flex items-center gap-3 px-5 py-2 text-sm font-medium" <?= ($reportKey ?? '') === 'cip' ? 'aria-current="page"' : '' ?>>
        <i data-lucide="users" class="w-4 h-4"></i> Responsables (CIP)
    </a>
    </div>
</nav>
