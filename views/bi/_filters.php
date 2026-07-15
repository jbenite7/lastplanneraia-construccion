<form class="bi-filter-form aia-panel text-sm" id="filters-form" onsubmit="event.preventDefault(); applyFilters();" aria-label="Filtros del Dashboard">
    <button type="button" id="bi-mobile-filter-toggle" class="bi-mobile-filter-toggle aia-btn aia-btn--secondary" onclick="toggleMobileFilters()" aria-expanded="false" aria-controls="bi-mobile-filter-panel">
        <span><i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filtros</span>
        <strong id="bi-mobile-filter-count">0</strong>
    </button>

    <div id="bi-mobile-filter-panel" class="bi-mobile-filter-panel">

    <!-- F01: Proyecto (Multi-select) -->
    <div class="relative">
        <label class="aia-label bi-field-label" id="label-proyectos">Proyectos (Global)</label>
        <button type="button" id="btn-project-dropdown" class="aia-select bi-project-select-button" aria-haspopup="listbox" aria-labelledby="label-proyectos" onclick="toggleProjectDropdown()">
            <span id="project-dropdown-text" class="truncate">Seleccionar proyectos...</span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
        </button>
        <div id="project-checkbox-list" class="bi-project-list hidden" role="listbox" aria-multiselectable="true"></div>
    </div>

    <hr class="border-aia-corp-med">

    <!-- Filtros Dinámicos -->
    <div id="dynamic-filters" class="bi-filter-grid">
        <span class="aia-label bi-field-label">Filtros Dinámicos</span>

        <!-- F02: Cascada Semana (1 proyecto) / Rango (multi proyecto) -->
        <div>
            <label for="filter-semana" class="aia-label bi-field-label">Semana</label>
            <select id="filter-semana" class="aia-select" disabled>
                <option value="">Seleccione proyecto(s) primero</option>
            </select>
            <p id="helper-semana" class="text-[10px] mt-1 text-aia-const-light hidden">Bloqueado. Múltiples proyectos seleccionados.</p>
        </div>

        <div class="bi-date-range opacity-50 pointer-events-none transition-opacity" id="container-rangos">
            <div class="flex-1">
                <label for="filter-desde" class="aia-label bi-field-label">Desde</label>
                <input type="date" id="filter-desde" class="aia-input" aria-label="Fecha inicio">
            </div>
            <div class="flex-1">
                <label for="filter-hasta" class="aia-label bi-field-label">Hasta</label>
                <input type="date" id="filter-hasta" class="aia-input" aria-label="Fecha fin">
            </div>
        </div>

        <!-- F03: Filtros Específicos -->
        <div>
            <label for="filter-sub" class="aia-label bi-field-label">Sub-Contratista</label>
            <select id="filter-sub" class="aia-select" aria-label="Filtro Sub Contratista">
                <option value="">Todos</option>
            </select>
        </div>

        <div>
            <label for="filter-resp" class="aia-label bi-field-label">Responsable AIA</label>
            <input type="text" id="filter-resp" list="filter-resp-options" placeholder="Escriba para buscar..." class="aia-input" aria-label="Filtro Responsable AIA">
            <datalist id="filter-resp-options"></datalist>
        </div>

        <div>
            <label for="filter-etapa" class="aia-label bi-field-label">Etapa | Torre | Intervención</label>
            <input type="text" id="filter-etapa" placeholder="Escriba para buscar..." class="aia-input" aria-label="Filtro Etapa">
        </div>

        <!-- Botones -->
        <div class="bi-filter-actions">
            <button type="submit" class="aia-btn aia-btn--construction">
                Aplicar
            </button>
            <button type="button" onclick="resetFilters()" class="aia-btn aia-btn--secondary" aria-label="Limpiar todos los filtros">
                <i data-lucide="filter-x" class="w-4 h-4"></i><span class="bi-clear-filter-label">Limpiar</span>
            </button>
        </div>
    </div>
    </div>
</form>
