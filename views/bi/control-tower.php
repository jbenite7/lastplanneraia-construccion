<!-- Vista: Resumen Ejecutivo -->
<section id="view-torre-control" class="view-section w-full flex flex-col gap-6" role="tabpanel" aria-labelledby="nav-torre-control">
    <section class="card p-5">
        <h3 class="font-semibold text-gray-800 mb-1">Resumen Ejecutivo</h3>
        <p id="executive-brief" class="text-sm text-gray-600 leading-relaxed">Cargando diagnóstico...</p>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="kpi-row">
        <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--construction" aria-label="KPI: Porcentaje Plan Cumplido">
            <i data-lucide="target" class="w-10 h-10" aria-hidden="true"></i>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">PPC</p>
                <p id="kpi-ppc" class="text-2xl font-bold text-gray-900">--%</p>
                <p id="kpi-ppc-delta" class="text-xs text-gray-400">vs semana anterior</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--real-estate" aria-label="KPI: Actividades Programadas">
            <i data-lucide="clipboard-list" class="w-10 h-10" aria-hidden="true"></i>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Programadas</p>
                <p id="kpi-programadas" class="text-2xl font-bold text-gray-900">--</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--corporate" aria-label="KPI: Actividades Ejecutadas">
            <i data-lucide="check-circle" class="w-10 h-10" aria-hidden="true"></i>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Ejecutadas</p>
                <p id="kpi-ejecutadas" class="text-2xl font-bold text-gray-900">--</p>
            </div>
        </div>
        <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--construction" aria-label="KPI: Brecha vs Programado">
            <i data-lucide="alert-triangle" class="w-10 h-10" aria-hidden="true"></i>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Brecha</p>
                <p id="kpi-brecha" class="text-2xl font-bold text-gray-900">--</p>
            </div>
        </div>
        <!-- El resumen contaba compras en riesgo sin ofrecer a donde ir. Esta tarjeta lleva a la
             pestaña que cuenta la historia completa. -->
        <button type="button" id="kpi-pdc-card" onclick="switchView('pdc')" class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--construction text-left" aria-label="KPI: compras vencidas. Abre el reporte Plan de Compras">
            <i data-lucide="shopping-cart" class="w-10 h-10" aria-hidden="true"></i>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Compras vencidas</p>
                <p id="kpi-pdc" class="text-2xl font-bold text-gray-900">--</p>
                <p class="text-xs text-gray-500">Ver Plan de Compras</p>
            </div>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-5" aria-label="Gráfico PPC semanal">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="pie-chart" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">PPC por Semana</h3>
            </div>
            <canvas id="chart-ppc-semanal" height="220" aria-label="Gráfico de barras PPC por semana"></canvas>
        </div>
        <div class="card p-5" aria-label="Gráfico PAC vs Programado">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="bar-chart-3" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">PAC vs Programado</h3>
            </div>
            <canvas id="chart-pac-prog" height="220" aria-label="Gráfico de barras PAC vs Programado"></canvas>
        </div>
    </div>

    <section class="card p-5">
        <div class="flex items-center gap-2 mb-3">
            <i data-lucide="list-checks" class="w-5 h-5" aria-hidden="true"></i>
            <h3 class="font-semibold text-gray-800">Acciones recomendadas</h3>
        </div>
        <div id="recommended-actions" class="text-sm text-gray-600">Sin recomendaciones.</div>
    </section>
</section>

<div id="programa-causal-drilldown" class="bi-drilldown" role="dialog" aria-modal="true"
    aria-labelledby="programa-causal-drilldown-title" hidden>
    <button type="button" class="bi-drilldown__backdrop" data-bi-causal-close tabindex="-1"
        aria-label="Cerrar detalle de causas"></button>
    <section class="bi-drilldown__panel">
        <header class="bi-drilldown__header">
            <div>
                <p class="bi-drilldown__eyebrow">Programa General</p>
                <h2 id="programa-causal-drilldown-title">Detalle de causas</h2>
            </div>
            <button id="programa-causal-drilldown-close" type="button" class="bi-drilldown__close"
                data-bi-causal-close aria-label="Cerrar detalle de causas">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </header>
        <div class="bi-drilldown__body">
            <div id="programa-causal-drilldown-summary" class="bi-drilldown__summary" aria-live="polite"></div>
            <p id="programa-causal-drilldown-explanation" class="bi-drilldown__explanation"></p>
            <div id="programa-causal-drilldown-loading" class="bi-drilldown__state" hidden>
                <div class="loader" aria-hidden="true"></div>
                <span>Consultando registros...</span>
            </div>
            <p id="programa-causal-drilldown-empty" class="bi-drilldown__state" hidden>
                No hay registros para la causa seleccionada en este corte.
            </p>
            <div id="programa-causal-drilldown-table" class="bi-drilldown__table-wrap">
                <table class="bi-drilldown__table bi-causal-drilldown__table">
                    <thead><tr><th>Proyecto / semana</th><th>Actividad / ubicación</th><th>Categoría / causa</th>
                        <th id="programa-causal-drilldown-measure-heading">Inicio / urgencia</th><th>Responsables / crítica</th><th>Impacto / acción</th></tr></thead>
                    <tbody id="programa-causal-drilldown-body"></tbody>
                </table>
            </div>
            <div id="programa-causal-drilldown-cards" class="bi-drilldown__cards"></div>
            <button id="programa-causal-drilldown-load-more" type="button"
                class="aia-btn aia-btn--secondary bi-causal-load-more" hidden>
                <i data-lucide="list-plus" class="w-4 h-4" aria-hidden="true"></i>
                <span>Cargar más actividades</span>
            </button>
        </div>
    </section>
</div>

<div id="programa-compliance-drilldown" class="bi-drilldown" role="dialog" aria-modal="true"
    aria-labelledby="programa-compliance-drilldown-title" hidden>
    <button type="button" class="bi-drilldown__backdrop" data-bi-drilldown-close tabindex="-1"
        aria-label="Cerrar detalle de incumplimientos"></button>
    <section class="bi-drilldown__panel">
        <header class="bi-drilldown__header">
            <div>
                <p class="bi-drilldown__eyebrow">Programa General</p>
                <h2 id="programa-compliance-drilldown-title">Actividades que explican la brecha</h2>
            </div>
            <button id="programa-compliance-drilldown-close" type="button" class="bi-drilldown__close"
                data-bi-drilldown-close aria-label="Cerrar detalle">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </header>

        <div class="bi-drilldown__body">

            <div id="programa-compliance-drilldown-summary" class="bi-drilldown__summary" aria-live="polite">
                <span>Avance real: --%</span>
                <span>Avance teórico: --%</span>
                <span>Brecha: -- pp</span>
            </div>
            <p id="programa-compliance-drilldown-explanation" class="bi-drilldown__explanation"></p>
            <div id="programa-compliance-drilldown-loading" class="bi-drilldown__state" hidden>
                <div class="loader" aria-hidden="true"></div>
                <span>Consultando actividades...</span>
            </div>
            <p id="programa-compliance-drilldown-empty" class="bi-drilldown__state" hidden>
                No hay actividades con brecha negativa para este corte.
            </p>
            <div id="programa-compliance-drilldown-table" class="bi-drilldown__table-wrap">
                <table class="bi-drilldown__table">
                    <thead>
                        <tr>
                            <th>Actividad</th><th>Fin</th><th>Teórico</th><th>Real</th>
                            <th>Brecha</th><th>Causa / responsables</th><th>Implicación</th>
                        </tr>
                    </thead>
                    <tbody id="programa-compliance-drilldown-body"></tbody>
                </table>
            </div>
            <div id="programa-compliance-drilldown-cards" class="bi-drilldown__cards"></div>
        </div>
    </section>
</div>

<div id="programa-gauge-drilldown" class="bi-drilldown" role="dialog" aria-modal="true"
    aria-labelledby="programa-gauge-drilldown-title" hidden>
    <button type="button" class="bi-drilldown__backdrop" data-bi-progress-close tabindex="-1"
        aria-label="Cerrar composición del avance"></button>
    <section class="bi-drilldown__panel">
        <header class="bi-drilldown__header">
            <div><p class="bi-drilldown__eyebrow">Programa General</p>
                <h2 id="programa-gauge-drilldown-title">Qué compone el % Avance de Obra</h2></div>
            <button id="programa-gauge-drilldown-close" type="button" class="bi-drilldown__close"
                data-bi-progress-close aria-label="Cerrar detalle"><i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i></button>
        </header>
        <div class="bi-drilldown__body">
            <div id="programa-gauge-drilldown-summary" class="bi-drilldown__summary bi-drilldown__summary--four" aria-live="polite"></div>
            <p id="programa-gauge-drilldown-explanation" class="bi-drilldown__explanation"></p>
            <div class="bi-progress-tabs" role="group" aria-label="Lectura del avance">
                <button id="programa-progress-tab-missing" type="button" aria-pressed="true"
                    aria-controls="programa-gauge-drilldown-table programa-gauge-drilldown-cards">Lo que más falta</button>
                <button id="programa-progress-tab-earned" type="button" aria-pressed="false"
                    aria-controls="programa-gauge-drilldown-table programa-gauge-drilldown-cards">Lo que ya suma</button>
            </div>
            <div class="bi-progress-controls">
                <label>Agrupar por<select id="programa-gauge-drilldown-group-by">
                    <option value="project">Proyecto</option><option value="stage">Etapa / frente</option>
                    <option value="responsible">Responsable AIA</option><option value="subcontractor">Subcontratista</option>
                </select></label>
                <label class="bi-progress-critical"><input id="programa-gauge-drilldown-critical-only" type="checkbox"> Solo ruta crítica</label>
            </div>
            <div id="programa-gauge-drilldown-loading" class="bi-drilldown__state" hidden><div class="loader" aria-hidden="true"></div><span>Calculando composición...</span></div>
            <p id="programa-gauge-drilldown-empty" class="bi-drilldown__state" hidden>No hay actividades para esta lectura.</p>
            <div id="programa-gauge-drilldown-table" class="bi-drilldown__table-wrap">
                <table class="bi-drilldown__table"><thead><tr>
                    <th>Actividad</th><th>Fin</th><th>Peso</th><th>Real</th><th>Teórico</th>
                    <th>Aporta / falta</th><th>Responsables y acción</th>
                </tr></thead><tbody id="programa-gauge-drilldown-body"></tbody></table>
            </div>
            <div id="programa-gauge-drilldown-cards" class="bi-drilldown__cards"></div>
            <p id="programa-gauge-drilldown-load-more-error" class="bi-programa-activity-more-error" role="alert" hidden></p>
            <button id="programa-gauge-drilldown-load-more" type="button"
                class="aia-btn aia-btn--secondary bi-causal-load-more" hidden>
                <i data-lucide="list-plus" class="w-4 h-4" aria-hidden="true"></i>
                <span>Cargar más actividades</span>
            </button>
        </div>
    </section>
</div>

<div id="programa-delay-drilldown" class="bi-drilldown" role="dialog" aria-modal="true"
    aria-labelledby="programa-delay-drilldown-title" hidden>
    <button type="button" class="bi-drilldown__backdrop" data-bi-delay-close tabindex="-1"
        aria-label="Cerrar detalle de fecha final y actividades vencidas"></button>
    <section class="bi-drilldown__panel">
        <header class="bi-drilldown__header">
            <div>
                <p class="bi-drilldown__eyebrow">Programa General</p>
                <h2 id="programa-delay-drilldown-title">Fecha final probable y retraso observado</h2>
            </div>
            <button id="programa-delay-drilldown-close" type="button" class="bi-drilldown__close"
                data-bi-delay-close aria-label="Cerrar detalle">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </header>
        <div class="bi-drilldown__body">
            <div id="programa-delay-drilldown-summary" class="bi-drilldown__summary bi-drilldown__summary--four" aria-live="polite"></div>
            <p id="programa-delay-drilldown-explanation" class="bi-drilldown__explanation"></p>
            <div id="programa-delay-drilldown-projects" class="bi-delay-projects" aria-label="Proyección por proyecto"></div>
            <div id="programa-delay-drilldown-observed" class="bi-drilldown__summary" aria-live="polite"></div>
            <div id="programa-delay-drilldown-loading" class="bi-drilldown__state" hidden>
                <div class="loader" aria-hidden="true"></div><span>Calculando fecha final y actividades vencidas...</span>
            </div>
            <p id="programa-delay-drilldown-error" class="bi-drilldown__state" role="alert" hidden>
                No fue posible consultar el detalle. Revise la conexión e intente de nuevo.
            </p>
            <p id="programa-delay-drilldown-empty" class="bi-drilldown__state" hidden>
                No hay actividades vencidas e incompletas para los filtros actuales.
            </p>
            <div id="programa-delay-drilldown-results" class="bi-drilldown__table-wrap">
                <table class="bi-drilldown__table bi-delay-drilldown__table">
                    <thead><tr>
                        <th>Proyecto / actividad</th><th>Fin / corte</th><th>Días vencida</th><th>Plan / real</th>
                        <th>Responsables</th><th>Criticidad / implicación</th>
                    </tr></thead>
                    <tbody id="programa-delay-drilldown-body"></tbody>
                </table>
            </div>
            <div id="programa-delay-drilldown-cards" class="bi-drilldown__cards"></div>
            <button id="programa-delay-drilldown-more" type="button" class="aia-btn aia-btn--secondary bi-delay-detail-trigger" hidden>
                <i data-lucide="list-plus" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver más actividades</span>
            </button>
        </div>
    </section>
</div>

<!-- Vista: Programa General -->
<section id="view-programa-general" class="view-section w-full hidden flex flex-col gap-6" aria-label="Programa General" role="tabpanel" aria-labelledby="nav-programa-general">
    <div class="grid grid-cols-1 gap-6">
        <div class="card p-5">
            <div class="bi-chart-card-header mb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-5 h-5" aria-hidden="true"></i>
                    <h3 class="font-semibold text-gray-800">Curva S Ejecución</h3>
                </div>
                <label class="bi-switch" for="toggle-programa-projections">
                    <input id="toggle-programa-projections" class="bi-switch-input" type="checkbox" checked aria-label="Mostrar u ocultar proyecciones de la Curva S Ejecución">
                    <span class="bi-switch-track" aria-hidden="true"><span class="bi-switch-knob"></span></span>
                    <span>Proyecciones</span>
                </label>
            </div>
            <p id="programa-projection-note" class="bi-chart-note hidden"></p>
            <canvas id="programa-curva-ejecucion" height="220" aria-label="Curva S de ejecución"></canvas>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div id="programa-gauge-card" class="card p-5 bi-gauge-card">
            <div class="bi-section-heading mb-3">
                <i data-lucide="gauge" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="bi-section-heading__title">% Avance de Obra</h3>
            </div>
            <div class="bi-gauge-panel">
                <div class="bi-gauge-visual">
                    <canvas id="programa-gauge" class="h-full w-full" aria-label="Porcentaje de avance de obra"></canvas>
                </div>
                <div class="bi-gauge-readout" aria-live="polite">
                    <span id="programa-gauge-value" class="bi-gauge-readout__value">--%</span>
                    <span class="bi-gauge-readout__label">Avance físico</span>
                    <span id="programa-gauge-range" class="bi-semantic-range">Sin clasificación</span>
                    <span id="programa-gauge-range-reason" class="bi-semantic-range-reason"></span>
                    <span id="programa-gauge-theoretical" class="bi-gauge-readout__meta">Teórico al corte --%</span>
                    <span id="programa-gauge-gap" class="bi-gauge-readout__meta">Brecha -- pp</span>
                </div>
                <div id="programa-gauge-legend" class="bi-gauge-legend" aria-label="Leyenda de avance de obra">
                    <span class="bi-gauge-legend__item">
                        <span id="programa-gauge-legend-swatch-0" class="bi-gauge-legend__swatch" aria-hidden="true"></span>
                        <span id="programa-gauge-legend-label-0" class="bi-gauge-legend__text">Avance real</span>
                    </span>
                    <span class="bi-gauge-legend__item">
                        <span id="programa-gauge-legend-swatch-1" class="bi-gauge-legend__swatch" aria-hidden="true"></span>
                        <span id="programa-gauge-legend-label-1" class="bi-gauge-legend__text">Avance teórico</span>
                    </span>
                </div>
            </div>
            <button id="programa-gauge-drilldown-trigger" type="button"
                class="aia-btn aia-btn--secondary bi-compliance-detail-trigger"
                aria-controls="programa-gauge-drilldown" aria-expanded="false">
                <i data-lucide="layers-3" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver composición del avance</span>
            </button>
        </div>
        <div id="programa-compliance-card" class="card p-5 bi-compliance-card">
            <div class="bi-section-heading mb-3">
                <i data-lucide="check-check" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="bi-section-heading__title">% Cumplimiento Cronograma</h3>
            </div>
            <div class="bi-gauge-panel">
                <div class="bi-gauge-visual">
                    <canvas id="programa-compliance" class="h-full w-full" aria-label="Porcentaje de cumplimiento del cronograma"></canvas>
                </div>
                <div class="bi-gauge-readout" aria-live="polite">
                    <span id="programa-compliance-value" class="bi-gauge-readout__value">--%</span>
                    <span class="bi-gauge-readout__label">Cumplimiento</span>
                    <span id="programa-compliance-range" class="bi-semantic-range">Sin clasificación</span>
                    <span id="programa-compliance-gap" class="bi-gauge-readout__meta">Brecha -- pp</span>
                </div>
                <div id="programa-compliance-legend" class="bi-gauge-legend" aria-label="Leyenda de cumplimiento cronograma">
                    <span class="bi-gauge-legend__item">
                        <span id="programa-compliance-legend-swatch-0" class="bi-gauge-legend__swatch" aria-hidden="true"></span>
                        <span id="programa-compliance-legend-label-0" class="bi-gauge-legend__text">Cumplimiento cronograma</span>
                    </span>
                    <span class="bi-gauge-legend__item">
                        <span id="programa-compliance-legend-swatch-1" class="bi-gauge-legend__swatch" aria-hidden="true"></span>
                        <span id="programa-compliance-legend-label-1" class="bi-gauge-legend__text">Brecha</span>
                    </span>
                </div>
            </div>
            <p id="programa-compliance-explanation" class="bi-compliance-explanation" aria-live="polite">Sin datos del corte.</p>
            <button id="programa-compliance-drilldown-trigger" type="button"
                class="aia-btn aia-btn--secondary bi-compliance-detail-trigger"
                aria-controls="programa-compliance-drilldown" aria-expanded="false">
                <i data-lucide="list-checks" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver actividades incumplidas</span>
            </button>
        </div>
        <div id="programa-delay-card" class="card p-5 bi-delay-card">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="calendar-clock" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Variación probable de fecha final</h3>
            </div>
            <canvas id="programa-dias-retraso" height="170" aria-label="Variación probable de la fecha final en días"></canvas>
            <div id="programa-delay-summary" class="bi-delay-summary" aria-live="polite">
                <p id="programa-delay-status" class="bi-delay-status">Calculando proyección...</p>
                <dl class="bi-delay-dates">
                    <div><dt>Fin contractual</dt><dd id="programa-delay-contractual">--</dd></div>
                    <div><dt>Fin más probable</dt><dd id="programa-delay-p50">--</dd></div>
                    <div><dt>Escenario optimista</dt><dd id="programa-delay-optimistic">--</dd></div>
                    <div><dt>Escenario pesimista</dt><dd id="programa-delay-pessimistic">--</dd></div>
                </dl>
                <p id="programa-delay-method" class="bi-delay-method"></p>
            </div>
            <button id="programa-delay-drilldown-trigger" type="button"
                class="aia-btn aia-btn--secondary bi-delay-detail-trigger"
                aria-controls="programa-delay-drilldown" aria-expanded="false">
                <i data-lucide="calendar-search" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver proyección y actividades vencidas</span>
            </button>
        </div>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div id="programa-cnp-card" class="card p-5 bi-cause-card" aria-label="Causas de No Programación">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="pie-chart" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Causas de No Programación</h3>
            </div>
            <canvas id="programa-cnp" height="220" aria-label="Causas de no programación"></canvas>
            <div id="programa-cnp-category-actions" class="bi-cause-category-actions" role="group" aria-label="Ver CNP por categoría" hidden></div>
            <div id="programa-cnp-insight" class="bi-cause-insight" aria-live="polite" hidden></div>
            <button id="programa-cnp-drilldown-trigger" type="button" class="aia-btn aia-btn--secondary bi-cause-detail-trigger"
                aria-controls="programa-causal-drilldown" aria-expanded="false">
                <i data-lucide="list-filter" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver detalle de CNP</span>
            </button>
        </div>
        <div id="programa-cnc-card" class="card p-5 bi-cause-card" aria-label="Causas de No Cumplimiento">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="pie-chart" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Causas de No Cumplimiento</h3>
            </div>
            <canvas id="programa-cnc" height="220" aria-label="Causas de no cumplimiento"></canvas>
            <div id="programa-cnc-category-actions" class="bi-cause-category-actions" role="group" aria-label="Ver CNC por categoría" hidden></div>
            <div id="programa-cnc-insight" class="bi-cause-insight" aria-live="polite" hidden></div>
            <button id="programa-cnc-drilldown-trigger" type="button" class="aia-btn aia-btn--secondary bi-cause-detail-trigger"
                aria-controls="programa-causal-drilldown" aria-expanded="false">
                <i data-lucide="list-filter" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver detalle de CNC</span>
            </button>
        </div>
        <div id="programa-radar-card" class="card p-5 bi-radar-card xl:col-span-2" aria-label="Radar de Programa General">
            <div class="bi-chart-card-header mb-3">
                <div class="flex items-center gap-2">
                <i data-lucide="radar" class="w-5 h-5" aria-hidden="true"></i>
                    <h3 class="font-semibold text-gray-800">Radar de Programa General</h3>
                </div>
                <span class="bi-radar-card__caption">Resumen secundario</span>
            </div>
            <p id="programa-radar-unavailable" class="bi-radar-unavailable" role="status" hidden>Sin muestra suficiente para comparar los tres ejes.</p>
            <canvas id="programa-radar-productividad" height="220" aria-label="Radar de productividad eficiencia y desempeño"></canvas>
            <div id="programa-radar-axes" class="bi-radar-axis-list" aria-live="polite"></div>
            <button id="programa-radar-detail-trigger" type="button" class="aia-btn aia-btn--secondary bi-radar-detail-trigger"
                aria-controls="programa-radar-drilldown" aria-expanded="false">
                <i data-lucide="list-filter" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver registros que explican el radar</span>
            </button>
        </div>
    </div>
    <div class="card p-5 bi-programa-activities">
        <div class="bi-programa-activities__header">
            <div class="flex items-center gap-2">
                <i data-lucide="list-tree" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Cronograma de actividades que explica el corte</h3>
            </div>
            <span id="programa-activity-cutoff" class="aia-chip bi-programa-activity-cutoff">Corte --</span>
        </div>
        <p id="programa-activity-total" class="bi-programa-activity-total" role="status"
            aria-live="polite" aria-atomic="true">Cargando actividades...</p>
        <div class="bi-programa-activity-panel">
            <div id="programa-activity-loading" class="bi-programa-activity-loading" role="status" aria-live="polite">
                <div class="loader"></div>
                <span>Calculando aportes al corte...</span>
            </div>
            <p id="programa-activity-empty" class="bi-programa-activity-loading" hidden>No hay actividades válidas para los filtros activos.</p>
            <table id="programa-activity-table" class="bi-programa-activity-table" hidden>
                <thead><tr>
                    <th>Actividad / proyecto</th>
                    <th>Cronograma al corte</th>
                    <th>Brecha recuperable</th>
                    <th>Peso / aporte real</th>
                    <th>Estado / criticidad</th>
                    <th>Responsables</th>
                </tr></thead>
                <tbody id="programa-activity-body"></tbody>
            </table>
            <div id="programa-activity-cards" class="bi-programa-activity-cards" hidden></div>
        </div>
        <p id="programa-activity-more-error" class="bi-programa-activity-more-error" role="alert" hidden></p>
        <div class="bi-programa-activity-actions">
            <button id="programa-activity-load-more" type="button" class="aia-btn aia-btn--secondary w-full md:w-auto justify-center" hidden>
                <i data-lucide="list-plus" class="w-4 h-4" aria-hidden="true"></i>
                <span>Ver más actividades</span>
            </button>
            <button id="programa-activity-analysis-trigger" type="button" class="aia-btn aia-btn--secondary w-full md:w-auto justify-center"
                aria-controls="programa-gauge-drilldown" aria-expanded="false">
                <i data-lucide="list-filter" class="w-4 h-4" aria-hidden="true"></i>
                <span>Analizar composición del avance</span>
            </button>
        </div>
    </div>
</section>

<div id="programa-radar-drilldown" class="bi-drilldown" role="dialog" aria-modal="true"
    aria-labelledby="programa-radar-drilldown-title" hidden>
    <button type="button" class="bi-drilldown__backdrop" data-bi-radar-close tabindex="-1"
        aria-label="Cerrar detalle del radar"></button>
    <section class="bi-drilldown__panel">
        <header class="bi-drilldown__header">
            <div>
                <p class="bi-drilldown__eyebrow">Programa General</p>
                <h2 id="programa-radar-drilldown-title">Registros que explican el radar</h2>
            </div>
            <button id="programa-radar-drilldown-close" type="button" class="bi-drilldown__close"
                data-bi-radar-close aria-label="Cerrar detalle del radar">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </header>
        <div class="bi-drilldown__body">
            <div id="programa-radar-drilldown-tabs" class="bi-progress-tabs" role="tablist" aria-label="Eje del radar">
                <button id="programa-radar-tab-productividad" type="button" role="tab" tabindex="0" aria-selected="true" aria-controls="programa-radar-drilldown-panel" data-radar-axis="productividad">Avance promedio</button>
                <button id="programa-radar-tab-eficiencia" type="button" role="tab" tabindex="-1" aria-selected="false" aria-controls="programa-radar-drilldown-panel" data-radar-axis="eficiencia">Cantidades normalizadas</button>
                <button id="programa-radar-tab-desempeno" type="button" role="tab" tabindex="-1" aria-selected="false" aria-controls="programa-radar-drilldown-panel" data-radar-axis="desempeno">PAC</button>
            </div>
            <div id="programa-radar-drilldown-panel" role="tabpanel" aria-labelledby="programa-radar-tab-productividad">
                <div id="programa-radar-drilldown-summary" class="bi-drilldown__summary" aria-live="polite"></div>
                <p id="programa-radar-drilldown-explanation" class="bi-drilldown__explanation"></p>
                <div id="programa-radar-drilldown-loading" class="bi-drilldown__state" hidden>
                    <div class="loader" aria-hidden="true"></div><span>Consultando registros del eje...</span>
                </div>
                <p id="programa-radar-drilldown-error" class="bi-drilldown__state" role="alert" hidden>
                    No fue posible consultar el detalle. Revise la conexión e intente de nuevo.
                </p>
                <p id="programa-radar-drilldown-empty" class="bi-drilldown__state" hidden>
                    No hay compromisos activos para este eje y los filtros actuales.
                </p>
                <div id="programa-radar-drilldown-results" class="bi-drilldown__table-wrap">
                    <table class="bi-drilldown__table bi-radar-drilldown__table">
                        <thead><tr>
                            <th>Proyecto / corte</th><th>Actividad</th><th>Unidad</th><th>Compromiso</th><th>Ejecutado</th>
                            <th>Progreso</th><th>PAC</th><th>Responsable AIA / Sub-Contratista</th><th>Criticidad / TNP</th><th>Elegibilidad / exclusión</th>
                        </tr></thead>
                        <tbody id="programa-radar-drilldown-body"></tbody>
                    </table>
                </div>
                <div id="programa-radar-drilldown-cards" class="bi-drilldown__cards"></div>
                <button id="programa-radar-drilldown-more" type="button" class="aia-btn aia-btn--secondary bi-radar-detail-trigger" hidden>
                    <i data-lucide="list-plus" class="w-4 h-4" aria-hidden="true"></i>
                    <span>Ver más registros</span>
                </button>
            </div>
        </div>
    </section>
</div>

<!-- Vista: Curva S -->
<section id="view-curva-s" class="view-section w-full hidden" aria-label="Curva S" role="tabpanel" aria-labelledby="nav-curva-s">
    <div class="card p-5">
        <div class="flex items-center gap-2 mb-3">
            <i data-lucide="trending-up" class="w-5 h-5" aria-hidden="true"></i>
            <h3 class="font-semibold text-gray-800">Curva S — Programado vs Ejecutado</h3>
        </div>
        <canvas id="chart-curva-s" height="280" aria-label="Gráfico de curva S, programado vs ejecutado"></canvas>
    </div>
</section>

<!-- Vista: Programación Intermedia (6 Semanas) -->
<section id="view-intermedia" class="view-section w-full hidden" aria-label="Programación Intermedia 6 Semanas" role="tabpanel" aria-labelledby="nav-intermedia">
    <div class="card p-5 flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <i data-lucide="filter" class="w-5 h-5" aria-hidden="true"></i>
            <h3 class="font-semibold text-gray-800">Programación Intermedia (6 Semanas)</h3>
        </div>
        <div id="intermedia-content" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div id="intermedia-chart-container">
                <canvas id="chart-intermedia" height="200" aria-label="Gráfico de cumplimiento intermedio"></canvas>
            </div>
            <div id="intermedia-table-container" class="bi-table-container">
                <table class="w-full text-xs">
                    <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Semana</th><th class="p-2 text-left font-semibold text-gray-600">Programado</th><th class="p-2 text-left font-semibold text-gray-600">Ejecutado</th><th class="p-2 text-left font-semibold text-gray-600">%</th></tr></thead>
                    <tbody id="intermedia-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Vista: Programación Semanal -->
<section id="view-semanal" class="view-section w-full hidden" aria-label="Programación Semanal" role="tabpanel" aria-labelledby="nav-semanal">
    <div class="card p-5 flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-5 h-5" aria-hidden="true"></i>
            <h3 class="font-semibold text-gray-800">Programación Semanal</h3>
        </div>
        <div id="semanal-content" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <p class="text-sm text-gray-500 col-span-full">Seleccione una semana para ver el detalle de actividades.</p>
        </div>
        <div class="relative h-56">
            <canvas id="chart-semanal-pac" aria-label="Pac semanal"></canvas>
        </div>
        <!-- Tabla de detalle semanal -->
        <div id="semanal-table-wrapper" class="bi-table-container hidden">
            <table class="w-full text-xs">
                <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Actividad</th><th class="p-2 text-left font-semibold text-gray-600">Responsable</th><th class="p-2 text-left font-semibold text-gray-600">Estado</th><th class="p-2 text-left font-semibold text-gray-600">%</th></tr></thead>
                <tbody id="semanal-body"></tbody>
            </table>
        </div>
    </div>
</section>

<!-- Vista: Plan de Compras (PDC) -->
<section id="view-pdc" class="view-section w-full hidden" aria-label="Plan de Compras" role="tabpanel" aria-labelledby="nav-pdc">
    <div class="flex flex-col gap-6">
        <!-- Titular. El reporte abre diciendo qué pasa, no con una tabla de indicadores: la
             frase la escribe renderPDC() con los datos del corte. -->
        <div class="card p-5 flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-cart" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Plan de Compras (PDC)</h3>
            </div>
            <p id="pdc-titular" class="bi-pdc-lede">Cargando datos de compras...</p>
            <p id="pdc-subtitular" class="text-sm text-gray-600"></p>
            <!-- Este panel no obedece al selector de semana (Decisión 5 del spec B3): responde
                 siempre «hoy», con la fecha del servidor. El rótulo existe para que eso no se lea
                 como un fallo. -->
            <p id="pdc-fecha-corte" class="text-xs text-gray-500"></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="pdc-kpis">
            <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--construction" aria-label="KPI: compras vencidas">
                <i data-lucide="alert-octagon" class="w-10 h-10" aria-hidden="true"></i>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Vencidas</p>
                    <p id="pdc-kpi-vencidos" class="text-2xl font-bold text-gray-900">--</p>
                </div>
            </div>
            <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--corporate" aria-label="KPI: compras en riesgo a tres semanas">
                <i data-lucide="alert-triangle" class="w-10 h-10" aria-hidden="true"></i>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">En riesgo (3 semanas)</p>
                    <p id="pdc-kpi-riesgo" class="text-2xl font-bold text-gray-900">--</p>
                </div>
            </div>
            <div class="card p-5 flex items-center gap-4 border-l-4 bi-kpi--corporate" aria-label="KPI: paquetes sin mirar">
                <i data-lucide="eye-off" class="w-10 h-10" aria-hidden="true"></i>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Sin mirar</p>
                    <p id="pdc-kpi-sin-mirar" class="text-2xl font-bold text-gray-900">--</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="bi-section-heading mb-1">
                <i data-lucide="calendar-clock" class="w-5 h-5" aria-hidden="true"></i>
                <h4 class="font-semibold text-gray-800">Cuánto tiempo queda</h4>
            </div>
            <p class="bi-chart-note">Pasos de contratación abiertos, repartidos por lo que falta para su fecha límite. La primera barra ya se pasó.</p>
            <canvas id="pdc-horizonte" height="200" aria-label="Pasos de contratación pendientes por horizonte de vencimiento"></canvas>
            <p id="pdc-horizonte-nota" class="bi-chart-note mt-3"></p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="card p-5">
                <div class="bi-section-heading mb-1">
                    <i data-lucide="git-commit-horizontal" class="w-5 h-5" aria-hidden="true"></i>
                    <h4 class="font-semibold text-gray-800">Dónde se atasca</h4>
                </div>
                <p class="bi-chart-note">Pasos abiertos por etapa del proceso de contratación; en rojo, los que ya vencieron.</p>
                <canvas id="pdc-paso-chart" height="220" aria-label="Pasos pendientes y vencidos por etapa de contratación"></canvas>
            </div>
            <div class="card p-5">
                <div class="bi-section-heading mb-1">
                    <i data-lucide="users" class="w-5 h-5" aria-hidden="true"></i>
                    <h4 class="font-semibold text-gray-800">Quién lo tiene encima</h4>
                </div>
                <p class="bi-chart-note">Carga pendiente por responsable. «Sin responsable» es trabajo que nadie ha reclamado.</p>
                <canvas id="pdc-resp-chart" height="220" aria-label="Carga pendiente y vencida por responsable"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <div class="bi-section-heading mb-1">
                <i data-lucide="building-2" class="w-5 h-5" aria-hidden="true"></i>
                <h4 class="font-semibold text-gray-800">Cuánto del plan está armado, por obra</h4>
            </div>
            <p class="bi-chart-note">Cobertura por conteo y por valor. Las dos van juntas: por separado cada una cuenta media verdad.</p>
            <canvas id="pdc-cobertura-chart" height="200" aria-label="Cobertura del plan de compras por obra, en conteo y en valor"></canvas>
        </div>

        <details class="card p-5 bi-pdc-detalle">
            <summary class="font-semibold text-gray-800 cursor-pointer">Ver el detalle en tablas</summary>
            <div class="flex flex-col gap-4 mt-4">
                <div id="pdc-table-wrapper" class="bi-table-container">
                    <table class="w-full text-xs" id="pdc-table">
                        <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Indicador</th><th class="p-2 text-left font-semibold text-gray-600">Valor</th><th class="p-2 text-left font-semibold text-gray-600">Acción</th></tr></thead>
                        <tbody id="pdc-body"><tr><td class="p-4 text-center text-gray-400" colspan="3">Cargando datos de compras...</td></tr></tbody>
                    </table>
                </div>

                <h4 class="font-semibold text-gray-800">Cobertura y vencimientos por obra</h4>
                <p class="text-xs text-gray-500">Un paquete partido en lotes cuenta un destino por lote: el total sube cuando una obra parte un paquete.</p>
                <div id="pdc-obra-wrapper" class="bi-table-container">
                    <table class="w-full text-xs" id="pdc-obra-table">
                        <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Obra</th><th class="p-2 text-left font-semibold text-gray-600">Cobertura (conteo)</th><th class="p-2 text-left font-semibold text-gray-600">Cobertura (valor)</th><th class="p-2 text-left font-semibold text-gray-600">Vencidos</th><th class="p-2 text-left font-semibold text-gray-600">En riesgo</th><th class="p-2 text-left font-semibold text-gray-600">Destinos</th><th class="p-2 text-left font-semibold text-gray-600">Sin mirar</th></tr></thead>
                        <tbody id="pdc-obra-body"><tr><td class="p-4 text-center text-gray-400" colspan="7">Cargando...</td></tr></tbody>
                    </table>
                </div>

                <h4 class="font-semibold text-gray-800">Avance de contratación por paso</h4>
                <div id="pdc-paso-wrapper" class="bi-table-container">
                    <table class="w-full text-xs" id="pdc-paso-table">
                        <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Paso</th><th class="p-2 text-left font-semibold text-gray-600">Pendientes</th><th class="p-2 text-left font-semibold text-gray-600">Vencidos</th></tr></thead>
                        <tbody id="pdc-paso-body"><tr><td class="p-4 text-center text-gray-400" colspan="3">Cargando...</td></tr></tbody>
                    </table>
                </div>

                <h4 class="font-semibold text-gray-800">Carga por responsable</h4>
                <div id="pdc-resp-wrapper" class="bi-table-container">
                    <table class="w-full text-xs" id="pdc-resp-table">
                        <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Responsable</th><th class="p-2 text-left font-semibold text-gray-600">Pendientes</th><th class="p-2 text-left font-semibold text-gray-600">Vencidos</th></tr></thead>
                        <tbody id="pdc-resp-body"><tr><td class="p-4 text-center text-gray-400" colspan="3">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </details>
    </div>
</section>

<!-- Vista: Proveedores (CIC) -->
<section id="view-cic" class="view-section w-full hidden" aria-label="Proveedores CIC" role="tabpanel" aria-labelledby="nav-cic">
    <div class="card p-5 flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <i data-lucide="hard-hat" class="w-5 h-5" aria-hidden="true"></i>
            <h3 class="font-semibold text-gray-800">Proveedores (CIC)</h3>
        </div>
        <div id="cic-table-wrapper" class="bi-table-container">
            <table class="w-full text-xs" id="cic-table">
                <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Proveedor</th><th class="p-2 text-left font-semibold text-gray-600">Contacto</th><th class="p-2 text-left font-semibold text-gray-600">Servicio</th><th class="p-2 text-left font-semibold text-gray-600">Vigencia</th><th class="p-2 text-left font-semibold text-gray-600">Estado</th></tr></thead>
                <tbody id="cic-body"><tr><td class="p-4 text-center text-gray-400" colspan="5">Cargando datos de proveedores...</td></tr></tbody>
            </table>
        </div>
    </div>
</section>

<!-- Vista: Responsables (CIP) -->
<section id="view-cip" class="view-section w-full hidden" aria-label="Responsables CIP" role="tabpanel" aria-labelledby="nav-cip">
    <div class="card p-5 flex flex-col gap-4">
        <div class="flex items-center gap-2 justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5" aria-hidden="true"></i>
                <h3 class="font-semibold text-gray-800">Responsables (CIP)</h3>
            </div>
            <?php if (($role ?? '') === 'R'): ?>
            <?php
                $cipVerTodaLaObraHref = \App\View\Components\BiAccessComponent::url('profesionales');
                $cipVerTodaLaObraHref .= (str_contains($cipVerTodaLaObraHref, '?') ? '&' : '?') . 'alcance=obra';
            ?>
            <a href="<?= htmlspecialchars($cipVerTodaLaObraHref, ENT_QUOTES, 'UTF-8') ?>" class="aia-btn aia-btn--secondary text-sm">
                Ver toda la obra
            </a>
            <?php endif; ?>
        </div>
        <div id="cip-table-wrapper" class="bi-table-container">
            <table class="w-full text-xs" id="cip-table">
                <thead><tr class="bg-gray-100"><th class="p-2 text-left font-semibold text-gray-600">Nombre</th><th class="p-2 text-left font-semibold text-gray-600">Rol</th><th class="p-2 text-left font-semibold text-gray-600">Actividades</th><th class="p-2 text-left font-semibold text-gray-600">Cumplimiento</th></tr></thead>
                <tbody id="cip-body"><tr><td class="p-4 text-center text-gray-400" colspan="4">Cargando datos de responsables...</td></tr></tbody>
            </table>
        </div>
    </div>
</section>
