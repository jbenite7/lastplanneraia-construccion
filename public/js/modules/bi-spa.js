/**
 * BI Control Tower SPA
 * Vanilla JS + Tailwind + Lucide + Chart.js
 */

const BI = {
  currentView: 'torre-control',
  viewRequestId: 0,
  charts: {},
  filters: {
    projects: [],
    semana: '',
    desde: '',
    hasta: '',
    sub: '',
    resp: '',
    etapa: '',
  },
  dataByView: {},
  projects: [],
  chartSettings: {
    programaProjections: true,
  },
  chartThemeCache: null,
  drilldown: {
    requestId: 0,
    isOpen: false,
    previousFocus: null,
  },
  progressDrilldown: {
    requestId: 0, isOpen: false, previousFocus: null,
    mode: 'missing', groupBy: 'project', criticalOnly: false, payload: null,
    endpoint: '', pagination: null, expandedTrigger: null,
  },
  activityTimeline: {
    requestId: 0, records: [], pagination: null, summary: null, endpoint: '', mediaQuery: null,
  },
  causalDrilldown: {
    requestId: 0, isOpen: false, previousFocus: null, type: '', category: '',
    endpoint: '', interaction: null, records: [], pagination: null, summary: null,
  },
  radarDrilldown: {
    requestId: 0, isOpen: false, previousFocus: null, axis: 'productividad', source: 'api',
    records: [], pagination: null,
  },
  delayDrilldown: {
    requestId: 0, isOpen: false, previousFocus: null, activities: [], pagination: null, payload: null,
  },
};

const VIEW_META = {
  'torre-control': { endpoint: '/api/bi/control-tower', label: 'Resumen Ejecutivo' },
  'programa-general': { endpoint: '/api/bi/report/programa-general', label: 'Programa General' },
  intermedia: { endpoint: '/api/bi/report/intermedia', label: 'Programación Intermedia' },
  semanal: { endpoint: '/api/bi/report/semanal', label: 'Programación Semanal' },
  pdc: { endpoint: '/api/bi/report/pdc', label: 'Plan de Compras' },
  cic: { endpoint: '/api/bi/report/cic', label: 'Proveedores' },
  cip: { endpoint: '/api/bi/report/cip', label: 'Responsables' },
  'curva-s': { endpoint: '/api/bi/report/curva-s', label: 'Curva S' },
};

const VIEW_FROM_REPORT_KEY = {
  overview: 'torre-control',
  'programa-general': 'programa-general',
  intermedia: 'intermedia',
  semanal: 'semanal',
  pdc: 'pdc',
  cic: 'cic',
  cip: 'cip',
  'curva-s': 'curva-s',
};

const VIEW_FROM_PATH = {
  'control-tower': 'torre-control',
  'programa-general': 'programa-general',
  intermedia: 'intermedia',
  semanal: 'semanal',
  pdc: 'pdc',
  contratistas: 'cic',
  responsables: 'cip',
  'curva-s': 'curva-s',
};

const CHART_COLOR_TOKENS = {
  'brand-primary': '--bi-chart-brand-primary',
  'brand-primary-medium': '--bi-chart-brand-primary-medium',
  'brand-aqua': '--bi-chart-brand-aqua',
  'brand-aqua-medium': '--bi-chart-brand-aqua-medium',
  'brand-construction': '--bi-chart-brand-construction',
  'brand-construction-medium': '--bi-chart-brand-construction-medium',
  critical: '--bi-chart-critical',
  'status-critical': '--bi-status-critical',
  'status-warning': '--bi-status-warning',
  'status-success': '--bi-status-success',
  'neutral-muted': '--bi-chart-neutral-muted',
  'surface-muted': '--bi-chart-surface-muted',
};

const CHART_PALETTE = [
  'brand-aqua',
  'brand-primary',
  'brand-construction',
  'brand-construction-medium',
  'brand-aqua-medium',
  'critical',
  'neutral-muted',
  'brand-primary-medium',
];

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  if (closeDelayDrilldown()) {
    event.preventDefault();
    return;
  }
  if (closeRadarDrilldown()) {
    event.preventDefault();
    return;
  }
  if (closeCausalDrilldown()) {
    event.preventDefault();
    return;
  }
  if (closeProgressDrilldown()) {
    event.preventDefault();
    return;
  }
  if (closeComplianceDrilldown()) {
    event.preventDefault();
    return;
  }
  const dropdown = document.getElementById('project-checkbox-list');
  if (dropdown && !dropdown.classList.contains('hidden')) {
    dropdown.classList.add('hidden');
  }
  closeMobileFilters();
});

function init() {
  const bootData = parseBootData();
  BI.currentView = viewFromPathAndReportKey(bootData);
  BI.dataByView[BI.currentView] = bootData;
  const searchProjects = getSearchProjects();
  const bootProjects = Array.isArray(bootData.project_ids)
    ? bootData.project_ids
    : (bootData.project_id ? [bootData.project_id] : []);

  BI.filters = {
    ...BI.filters,
    projects: sanitizeProjectIds(searchProjects.length ? searchProjects : bootProjects),
    semana: (new URLSearchParams(window.location.search)).get('semana') || '',
    desde: (new URLSearchParams(window.location.search)).get('desde') || (new URLSearchParams(window.location.search)).get('fecha_desde') || '',
    hasta: (new URLSearchParams(window.location.search)).get('hasta') || (new URLSearchParams(window.location.search)).get('fecha_hasta') || '',
    sub: (new URLSearchParams(window.location.search)).get('sub') || (new URLSearchParams(window.location.search)).get('subcontratista') || '',
    resp: (new URLSearchParams(window.location.search)).get('resp') || (new URLSearchParams(window.location.search)).get('responsable') || '',
    etapa: (new URLSearchParams(window.location.search)).get('etapa') || '',
  };

  if (Array.isArray(bootData.projects) && bootData.projects.length) {
    renderProjectList(bootData.projects);
  } else {
    fetchProjects();
  }

  syncFilterControls();
  syncChartControls();
  wireLiveFilterEvents();
  wireChartControlEvents();
  wireComplianceDrilldownEvents();
  wireProgressDrilldownEvents();
  wireProgramActivityEvents();
  wireCausalDrilldownEvents();
  wireRadarDrilldownEvents();
  wireDelayDrilldownEvents();
  wireSheetTabsEvents();
  wireThemeEvents();
  renderActiveChips();
  renderMobileFilterState();
  updateFilterUI();
  switchView(BI.currentView);
}

function parseBootData() {
  const script = document.getElementById('bi-data');
  if (!script) return {};
  try {
    return JSON.parse(script.textContent || '{}');
  } catch (_err) {
    return {};
  }
}

function sanitizeProjectIds(values) {
  if (!Array.isArray(values)) return [];
  return values.map((id) => String(id).trim()).filter(Boolean);
}

function getSearchProjects() {
  const params = new URLSearchParams(window.location.search);
  const projects = [];
  params.forEach((value, key) => {
    if (key === 'project_ids[]' || key === 'project_id') {
      if (value) projects.push(value);
    }
  });
  return projects;
}

function syncFilterControls() {
  setValue('filter-semana', BI.filters.semana);
  setValue('filter-desde', BI.filters.desde);
  setValue('filter-hasta', BI.filters.hasta);
  setValue('filter-sub', BI.filters.sub);
  setValue('filter-resp', BI.filters.resp);
  setValue('filter-etapa', BI.filters.etapa);
}

function wireLiveFilterEvents() {
  const bindings = {
    'filter-semana': 'semana',
    'filter-desde': 'desde',
    'filter-hasta': 'hasta',
    'filter-sub': 'sub',
    'filter-resp': 'resp',
    'filter-etapa': 'etapa',
  };

  Object.entries(bindings).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (!el || el.dataset.biBound === '1') return;
    const sync = () => {
      BI.filters[key] = el.value || '';
      renderActiveChips();
      if (['semana', 'desde', 'hasta'].includes(key)) {
        fetchFilterOptions();
      }
    };
    el.addEventListener('input', sync);
    el.addEventListener('change', sync);
    el.dataset.biBound = '1';
  });
}

function viewFromPathAndReportKey(bootData) {
  const path = window.location.pathname.replace(/\/+$/, '') || '/';
  const direct = path.split('/').pop();
  if (VIEW_FROM_PATH[direct]) return VIEW_FROM_PATH[direct];

  const reportKey = typeof bootData?.report_key === 'string' ? bootData.report_key : '';
  if (VIEW_FROM_REPORT_KEY[reportKey]) return VIEW_FROM_REPORT_KEY[reportKey];

  return 'torre-control';
}

function setValue(id, value) {
  const el = document.getElementById(id);
  if (!el) return;
  el.value = value || '';
}

function getFilterPayload() {
  return {
    project_id: BI.filters.projects[0] || '',
    project_ids: BI.filters.projects,
    semana: BI.filters.semana || '',
    desde: BI.filters.desde || '',
    hasta: BI.filters.hasta || '',
    sub: BI.filters.sub || '',
    resp: BI.filters.resp || '',
    etapa: BI.filters.etapa || '',
  };
}

function routeForView(viewId) {
  const meta = VIEW_META[viewId];
  return meta ? meta.endpoint : '/api/bi/control-tower';
}

async function fetchCurrentView(viewId, payload = getFilterPayload()) {
  const url = new URL(routeForView(viewId), window.location.origin);
  appendFilterParams(url.searchParams, payload);
  const response = await fetch(url, { credentials: 'same-origin' });
  if (!response.ok) {
    throw new Error('HTTP ' + response.status);
  }
  return response.json();
}

function appendFilterParams(params, payload) {
  if (payload.project_id) params.set('project_id', payload.project_id);
  if (payload.project_ids?.length) {
    payload.project_ids.forEach((projectId) => {
      params.append('project_ids[]', projectId);
    });
  }
  if (payload.semana) params.set('semana', payload.semana);
  if (payload.sub) params.set('sub', payload.sub);
  if (payload.resp) params.set('resp', payload.resp);
  if (payload.etapa) params.set('etapa', payload.etapa);
  if (payload.desde) params.set('desde', payload.desde);
  if (payload.hasta) params.set('hasta', payload.hasta);
}

function ensureCurrentViewLoaded() {
  if (!BI.currentView) {
    BI.currentView = 'torre-control';
  }
  if (BI.dataByView[BI.currentView]) {
    renderCurrentView();
    return Promise.resolve();
  }
  return loadCurrentView();
}

function loadCurrentView() {
  const viewId = BI.currentView;
  const payload = getFilterPayload();
  const requestId = ++BI.viewRequestId;
  showLoading(true);
  return fetchCurrentView(viewId, payload)
    .then((data) => {
      if (requestId !== BI.viewRequestId || viewId !== BI.currentView) return;
      BI.dataByView[viewId] = data || {};
      renderCurrentView();
      showLoading(false);
    })
    .catch((error) => {
      if (requestId !== BI.viewRequestId || viewId !== BI.currentView) return;
      console.error('[BI] Failed to fetch view', viewId, error);
      showLoading(false);
      showEmptyState(true, 'No fue posible consultar el endpoint real de BI para los filtros seleccionados.');
    });
}

function renderCurrentView() {
  const data = BI.dataByView[BI.currentView] || {};
  if (!data || data.respuesta !== 'BIEN' || hasNoData(data)) {
    showEmptyState(true, 'No hay datos para la vista activa');
    return;
  }
  hideEmptyState();
  renderView(BI.currentView, data);
}

function hasNoData(data) {
  const scorecard = Array.isArray(data.scorecard) ? data.scorecard : [];
  const hasBrief = Boolean(executiveBriefText(data.executive_brief));
  const hasActions = Array.isArray(data.recommended_actions) && data.recommended_actions.length > 0;
  const hasDrivers = Array.isArray(data.drivers) && data.drivers.length > 0;
  const hasRisks = Array.isArray(data.risks) && data.risks.length > 0;
  return scorecard.length === 0 && !hasBrief && !hasActions && !hasDrivers && !hasRisks;
}

function switchView(viewId) {
  BI.currentView = viewId;
  if (viewId !== 'programa-general') {
    closeRadarDrilldown(false);
    closeCausalDrilldown(false);
    closeComplianceDrilldown(false);
  }

  document.querySelectorAll('.view-section').forEach((section) => section.classList.add('hidden'));
  const target = document.getElementById('view-' + viewId);
  if (target) target.classList.remove('hidden');

  document.querySelectorAll('.nav-item').forEach((item) => {
    item.classList.remove('active');
    item.setAttribute('aria-selected', 'false');
    item.setAttribute('tabindex', '-1');
  });
  const nav = document.getElementById('nav-' + viewId);
  if (nav) {
    nav.classList.add('active');
    nav.setAttribute('aria-selected', 'true');
    nav.setAttribute('tabindex', '0');
  }

  const title = document.getElementById('current-view-title');
  if (title) title.textContent = VIEW_META[viewId]?.label || viewId;

  ensureCurrentViewLoaded();
}

function wireSheetTabsEvents() {
  const nav = document.querySelector('.bi-tabs-nav');
  if (!nav || nav.dataset.sheetTabsBound === '1') return;
  nav.addEventListener('keydown', handleSheetTabsKeydown);
  nav.dataset.sheetTabsBound = '1';
}

function handleSheetTabsKeydown(event) {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
  const currentTab = event.target instanceof Element ? event.target.closest('[role="tab"].nav-item') : null;
  if (!currentTab) return;
  const tabs = Array.from(event.currentTarget.querySelectorAll('[role="tab"].nav-item'));
  const currentIndex = tabs.indexOf(currentTab);
  if (currentIndex < 0 || !tabs.length) return;
  event.preventDefault();
  let nextIndex = currentIndex;
  if (event.key === 'Home') nextIndex = 0;
  if (event.key === 'End') nextIndex = tabs.length - 1;
  if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
  if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabs.length;
  const nextTab = tabs[nextIndex];
  const nextViewId = (nextTab.id || '').replace(/^nav-/, '');
  if (nextViewId) switchView(nextViewId);
  nextTab.focus();
}

function renderView(viewId, data) {
  if (viewId === 'torre-control') return renderOverview(data);
  if (viewId === 'programa-general') return renderProgramaGeneral(data);
  if (viewId === 'curva-s') return renderCurvaS(data);
  if (viewId === 'intermedia') return renderIntermedia(data);
  if (viewId === 'semanal') return renderSemanal(data);
  if (viewId === 'pdc') return renderPDC(data);
  if (viewId === 'cic') return renderCIC(data);
  if (viewId === 'cip') return renderCIP(data);
}

function renderOverview(data) {
  const scorecard = Array.isArray(data.scorecard) ? data.scorecard : [];
  setText('executive-brief', executiveBriefText(data.executive_brief));
  setText('kpi-ppc', valueWithUnit(scorecard[0]));
  setText('kpi-programadas', valueWithUnit(scorecard[1]));
  setText('kpi-ejecutadas', valueWithUnit(scorecard[2]));
  setText('kpi-brecha', valueWithUnit(scorecard[3]));
  setText('kpi-ppc-delta', 'Resumen KPI');
  // El conteo de compras vencidas viaja dentro del scorecard, no como campo suelto del payload:
  // se busca por nombre para que reordenar el scorecard no cambie en silencio de KPI.
  const pdcKpi = scorecard.find((item) => (item.kpi || '') === 'PDC en riesgo');
  setText('kpi-pdc', formatInteger(toNumber(pdcKpi ? pdcKpi.value : 0)));

  const labels = scorecard.slice(0, 6).map((item) => item.kpi || 'Métrica');
  const values = scorecard.slice(0, 6).map((item) => toNumber(item.value));
  const ppcChart = chartPayload(data, 'chart-ppc-semanal');
  if (ppcChart) {
    renderBarChart('chart-ppc-semanal', chartLabels(ppcChart, labels), chartDatasetData(ppcChart, 0, values), chartDatasetLabel(ppcChart, 0, 'Indicador'), chartDatasetColor(ppcChart, 0, 'brand-primary'));
  }
  const flowChart = chartPayload(data, 'chart-pac-prog');
  if (flowChart) {
    renderLineLikeBars('chart-pac-prog', chartLabels(flowChart, labels), chartDatasets(flowChart));
  }

  renderRecommendedActions(data.recommended_actions);
}

function renderRecommendedActions(items) {
  const rows = Array.isArray(items) ? items : [];
  const container = document.getElementById('recommended-actions');
  if (!container) return;
  if (!rows.length) {
    container.innerHTML = '<p class="text-sm text-gray-500">Sin recomendaciones en este corte.</p>';
    return;
  }
  const lis = rows.slice(0, 8).map((item) => {
    const text = escapeHtml(item.action || item.descripcion || item.text || 'Acción');
    return `<li class=\"text-sm text-gray-600\">• ${text}</li>`;
  });
  container.innerHTML = `<ul class=\"space-y-1\">${lis.join('')}</ul>`;
}

function renderProgramaGeneral(data) {
  const rows = data.scorecard || [];
  const gaugeChart = chartPayload(data, 'programa-gauge');
  const complianceChart = chartPayload(data, 'programa-compliance');
  syncProgramaComplianceUI(gaugeChart, complianceChart);

  renderProgramaActivities(data.activity_snapshot || { total: 0, activities: [] });
  const curveExecutionChart = chartPayload(data, 'programa-curva-ejecucion');
  if (curveExecutionChart) {
    syncProgramaProjectionAvailability(curveExecutionChart);
    renderLineChart('programa-curva-ejecucion', chartLabels(curveExecutionChart), programaExecutionDatasets(curveExecutionChart), {
      tooltipContext: programaExecutionTooltipContext(curveExecutionChart),
    });
  }
  if (gaugeChart) {
    const gaugeValues = chartDatasetData(gaugeChart);
    renderComparativeProgressGauge('programa-gauge', gaugeChart);
    updateGaugeReadout('programa-gauge', gaugeValues);
  }
  if (complianceChart) {
    const complianceValues = chartDatasetData(complianceChart);
    renderProgressDonut('programa-compliance', chartLabels(complianceChart), complianceValues, true, chartDatasetColor(complianceChart, 0, 'brand-aqua'));
    updateGaugeReadout('programa-compliance', complianceValues, 'Brecha');
  }
  const delayChart = chartPayload(data, 'programa-dias-retraso');
  if (delayChart) {
    renderProgramaDelay(delayChart);
  }
  const cnpChart = chartPayload(data, 'programa-cnp');
  if (cnpChart) {
    syncCausalDrilldownUI('cnp', cnpChart);
    renderCnpInsight(cnpChart.metrics || {});
    renderDoughnutChart('programa-cnp', chartLabels(cnpChart), chartDatasetData(cnpChart), chartDatasetLabel(cnpChart, 0, 'Causas'), {
      onSegmentClick: (category) => openCausalDrilldown('cnp', category),
      showShare: true,
    });
  }
  const cncChart = chartPayload(data, 'programa-cnc');
  if (cncChart) {
    syncCausalDrilldownUI('cnc', cncChart);
    renderCncInsight(cncChart.metrics || {});
    renderDoughnutChart('programa-cnc', chartLabels(cncChart), chartDatasetData(cncChart), chartDatasetLabel(cncChart, 0, 'Causas'), {
      onSegmentClick: (category) => openCausalDrilldown('cnc', category),
      showShare: true,
    });
  }
  const radarChart = chartPayload(data, 'programa-radar-productividad');
  if (radarChart) {
    renderProgramaRadar(radarChart);
  }
  setText('programa-critical-count', String(scoreValue(rows, 'Críticas atrasadas')));
}

function renderProgramaDelay(chart) {
  const canvas = document.getElementById('programa-dias-retraso');
  const metrics = chart?.metrics || {};
  const available = chart?.availability === true && Number.isFinite(finiteNumber(metrics?.variation_days?.p50));
  if (canvas) canvas.hidden = !available;
  if (available) {
    const days = finiteNumber(metrics.variation_days.p50);
    const label = days > 0 ? 'Retraso probable' : (days < 0 ? 'Adelanto probable' : 'Terminación en fecha');
    renderBarChart('programa-dias-retraso', [label], [days], label, chartDatasetColor(chart, 0, days > 0 ? 'critical' : 'brand-primary'));
  } else {
    BI.charts['programa-dias-retraso']?.destroy();
    delete BI.charts['programa-dias-retraso'];
    clearChartDataTable('programa-dias-retraso');
  }

  const days = finiteNumber(metrics?.variation_days?.p50);
  const statusText = available
    ? `${days > 0 ? 'Terminación posterior' : (days < 0 ? 'Terminación anticipada' : 'Terminación en fecha')}: ${formatInteger(Math.abs(days))} días frente al fin contractual en el escenario P50.`
    : (metrics?.reason || 'No hay historia suficiente para proyectar la fecha final.');
  setText('programa-delay-status', statusText);
  setText('programa-delay-contractual', formatShortDate(metrics?.contractual_finish));
  setText('programa-delay-p50', formatShortDate(metrics?.forecast?.p50_finish));
  setText('programa-delay-optimistic', formatShortDate(metrics?.forecast?.p10_finish));
  setText('programa-delay-pessimistic', formatShortDate(metrics?.forecast?.p90_finish));
  setText('programa-delay-method', available
    ? `Monte Carlo con ${formatInteger(metrics?.simulation_count)} simulaciones. Rango probable 80%: ${formatShortDate(metrics?.probable_range_80?.earliest_finish)} a ${formatShortDate(metrics?.probable_range_80?.latest_finish)}.`
    : 'La proyección se activará cuando existan al menos 3 incrementos positivos de avance real.');
  syncProgramaDelayUI(chart);
}

function syncProgramaDelayUI(chart) {
  const trigger = document.getElementById('programa-delay-drilldown-trigger');
  if (!trigger) return;
  const endpoint = chart?.interaction?.detail_endpoint || trigger.dataset.detailEndpoint || '';
  trigger.disabled = !endpoint;
  trigger.dataset.detailEndpoint = endpoint;
  trigger.setAttribute('aria-expanded', BI.delayDrilldown.isOpen ? 'true' : 'false');
}

function wireDelayDrilldownEvents() {
  const card = document.getElementById('programa-delay-card');
  if (card && card.dataset.biDelayBound !== '1') {
    card.addEventListener('dblclick', (event) => {
      if (event.target instanceof Element && event.target.closest('#programa-delay-drilldown-trigger')) return;
      openDelayDrilldown();
    });
    card.dataset.biDelayBound = '1';
  }

  const trigger = document.getElementById('programa-delay-drilldown-trigger');
  if (trigger && trigger.dataset.biDelayBound !== '1') {
    trigger.addEventListener('click', () => openDelayDrilldown());
    trigger.dataset.biDelayBound = '1';
  }

  const more = document.getElementById('programa-delay-drilldown-more');
  if (more && more.dataset.biDelayBound !== '1') {
    more.addEventListener('click', () => fetchDelayDrilldownPage(false));
    more.dataset.biDelayBound = '1';
  }

  const modal = document.getElementById('programa-delay-drilldown');
  if (modal && modal.dataset.biDelayBound !== '1') {
    modal.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.closest('[data-bi-delay-close]')) closeDelayDrilldown();
    });
    modal.addEventListener('keydown', trapDelayDrilldownFocus);
    modal.dataset.biDelayBound = '1';
  }
}

function openDelayDrilldown() {
  const trigger = document.getElementById('programa-delay-drilldown-trigger');
  const modal = document.getElementById('programa-delay-drilldown');
  if (!modal || !trigger?.dataset.detailEndpoint) return;
  BI.delayDrilldown.requestId += 1;
  BI.delayDrilldown.isOpen = true;
  BI.delayDrilldown.previousFocus = trigger;
  BI.delayDrilldown.activities = [];
  BI.delayDrilldown.pagination = null;
  BI.delayDrilldown.payload = null;
  modal.hidden = false;
  trigger.setAttribute('aria-expanded', 'true');
  setDelayDrilldownLoading(true);
  setDelayDrilldownState('');
  clearDelayDrilldownResults();
  modal.querySelector('.bi-drilldown__close')?.focus({ preventScroll: true });
  fetchDelayDrilldownPage(true);
}

function closeDelayDrilldown() {
  const modal = document.getElementById('programa-delay-drilldown');
  if (!modal || modal.hidden) return false;
  BI.delayDrilldown.requestId += 1;
  BI.delayDrilldown.isOpen = false;
  modal.hidden = true;
  setDelayDrilldownLoading(false);
  document.getElementById('programa-delay-drilldown-trigger')?.setAttribute('aria-expanded', 'false');
  const target = BI.delayDrilldown.previousFocus;
  if (target && document.contains(target)) target.focus({ preventScroll: true });
  BI.delayDrilldown.previousFocus = null;
  return true;
}

function fetchDelayDrilldownPage(reset) {
  const endpoint = document.getElementById('programa-delay-drilldown-trigger')?.dataset.detailEndpoint;
  if (!endpoint) return;
  const offset = reset ? 0 : BI.delayDrilldown.activities.length;
  const requestId = ++BI.delayDrilldown.requestId;
  const url = new URL(endpoint, window.location.origin);
  appendFilterParams(url.searchParams, getFilterPayload());
  url.searchParams.set('limit', '50');
  url.searchParams.set('offset', String(offset));
  setDelayDrilldownLoading(true);
  setDelayDrilldownState('');

  fetch(url, { credentials: 'same-origin' })
    .then((response) => {
      if (!response.ok) throw new Error('HTTP ' + response.status);
      return response.json();
    })
    .then((payload) => {
      if (!BI.delayDrilldown.isOpen || requestId !== BI.delayDrilldown.requestId) return;
      BI.delayDrilldown.payload = payload;
      const rows = Array.isArray(payload?.activities) ? payload.activities : [];
      BI.delayDrilldown.activities = reset ? rows : BI.delayDrilldown.activities.concat(rows);
      BI.delayDrilldown.pagination = payload?.pagination || null;
      renderDelayDrilldown(payload);
    })
    .catch((error) => {
      if (!BI.delayDrilldown.isOpen || requestId !== BI.delayDrilldown.requestId) return;
      console.error('[BI] Failed to fetch delay drilldown', error);
      setDelayDrilldownState('error');
    })
    .finally(() => {
      if (BI.delayDrilldown.isOpen && requestId === BI.delayDrilldown.requestId) setDelayDrilldownLoading(false);
    });
}

function renderDelayDrilldown(payload) {
  renderDelayForecastSummary(payload?.forecast || {});
  renderDelayObservedSummary(payload?.observed || {});
  renderDelayProjectBreakdown(payload?.forecast?.project_breakdown || []);
  renderDelayActivityTable(BI.delayDrilldown.activities);
  renderDelayActivityCards(BI.delayDrilldown.activities);
  const hasRows = BI.delayDrilldown.activities.length > 0;
  setDelayDrilldownState(hasRows ? '' : 'empty');
  const more = document.getElementById('programa-delay-drilldown-more');
  if (more) more.hidden = payload?.pagination?.has_more !== true;
}

function renderDelayForecastSummary(forecast) {
  const summary = document.getElementById('programa-delay-drilldown-summary');
  const explanation = document.getElementById('programa-delay-drilldown-explanation');
  const p50 = forecast?.variation_days?.p50;
  if (summary) {
    summary.innerHTML = [
      `Variación P50: ${formatSignedDays(p50)}`,
      `Fin contractual: ${formatShortDate(forecast?.contractual_finish)}`,
      `Fin P50: ${formatShortDate(forecast?.forecast?.p50_finish)}`,
      `Rango P10–P90: ${formatShortDate(forecast?.forecast?.p10_finish)} – ${formatShortDate(forecast?.forecast?.p90_finish)}`,
    ].map((text) => `<span>${escapeHtml(text)}</span>`).join('');
  }
  if (explanation) {
    explanation.textContent = forecast?.availability === true
      ? `La fecha P50 es el resultado central de ${formatInteger(forecast.simulation_count)} simulaciones Monte Carlo. P10–P90 contiene el rango probable 80%; revise las actividades vencidas para priorizar recuperación.`
      : (forecast?.reason || 'No hay historia suficiente para estimar una fecha final probable.');
  }
}

function renderDelayObservedSummary(observed) {
  const summary = document.getElementById('programa-delay-drilldown-observed');
  if (!summary) return;
  summary.innerHTML = [
    `Actividades vencidas: ${formatInteger(observed?.delayed_activity_count)}`,
    `En ruta crítica: ${formatInteger(observed?.critical_delayed_count)}`,
    `Mayor atraso observado: ${formatInteger(observed?.max_observed_delay_days)} días`,
  ].map((text) => `<span>${escapeHtml(text)}</span>`).join('');
}

function renderDelayProjectBreakdown(projects) {
  const container = document.getElementById('programa-delay-drilldown-projects');
  if (!container) return;
  container.innerHTML = (Array.isArray(projects) ? projects : []).map((project) => `<article class="bi-delay-project" data-status="${escapeHtml(project.status || 'unavailable')}">
    <h3>${escapeHtml(project.project || `Proyecto ${project.project_id || ''}`)}</h3>
    <dl><div><dt>Contractual</dt><dd>${escapeHtml(formatShortDate(project.contractual_finish))}</dd></div>
      <div><dt>P50</dt><dd>${escapeHtml(formatShortDate(project.p50_finish))}</dd></div>
      <div><dt>Variación</dt><dd>${escapeHtml(formatSignedDays(project?.variation_days?.p50))}</dd></div></dl>
    ${project.availability === true ? '' : `<p>${escapeHtml(project.reason || 'Proyección no disponible')}</p>`}
  </article>`).join('');
}

function renderDelayActivityTable(rows) {
  const body = document.getElementById('programa-delay-drilldown-body');
  if (!body) return;
  body.innerHTML = rows.map((row) => `<tr>
    <td><strong>${escapeHtml(row.project || `Proyecto ${row.project_id || ''}`)}</strong><small>${escapeHtml(row.activity || 'Actividad sin nombre')}</small></td>
    <td>${escapeHtml(formatShortDate(row.planned_finish))}<small>Corte: ${escapeHtml(formatShortDate(row.cutoff))}</small></td>
    <td><strong>${escapeHtml(formatInteger(row.observed_delay_days))} días</strong></td>
    <td>${escapeHtml(formatPercent(row.planned_pct))}<small>Real: ${escapeHtml(formatPercent(row.progress_pct))}</small></td>
    <td>${renderComplianceResponsibles(row)}</td>
    <td><strong>${row.critical ? 'Ruta crítica' : 'No crítica'}</strong><small>${escapeHtml(row.implication || '--')}</small></td>
  </tr>`).join('');
}

function renderDelayActivityCards(rows) {
  const container = document.getElementById('programa-delay-drilldown-cards');
  if (!container) return;
  container.innerHTML = rows.map((row) => `<article class="programa-compliance-drilldown-card">
    <div><h3>${escapeHtml(row.activity || 'Actividad sin nombre')}</h3><p>${escapeHtml(row.project || `Proyecto ${row.project_id || ''}`)}</p></div>
    <div class="programa-compliance-drilldown-card__metrics"><span>${escapeHtml(formatInteger(row.observed_delay_days))} días vencida</span>
      <span>Plan: ${escapeHtml(formatPercent(row.planned_pct))}</span><span>Real: ${escapeHtml(formatPercent(row.progress_pct))}</span></div>
    <p><strong>${row.critical ? 'Ruta crítica' : 'Actividad vencida'}</strong> · Fin ${escapeHtml(formatShortDate(row.planned_finish))} · Corte ${escapeHtml(formatShortDate(row.cutoff))}</p>
    <p>${escapeHtml(row.implication || '--')}</p>${renderComplianceResponsibles(row)}
  </article>`).join('');
}

function clearDelayDrilldownResults() {
  [
    'programa-delay-drilldown-summary', 'programa-delay-drilldown-observed',
    'programa-delay-drilldown-projects', 'programa-delay-drilldown-body',
    'programa-delay-drilldown-cards',
  ].forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.innerHTML = '';
  });
  const more = document.getElementById('programa-delay-drilldown-more');
  if (more) more.hidden = true;
}

function setDelayDrilldownLoading(loading) {
  const element = document.getElementById('programa-delay-drilldown-loading');
  if (element) element.hidden = !loading;
}

function setDelayDrilldownState(state) {
  const error = document.getElementById('programa-delay-drilldown-error');
  const empty = document.getElementById('programa-delay-drilldown-empty');
  const results = document.getElementById('programa-delay-drilldown-results');
  const cards = document.getElementById('programa-delay-drilldown-cards');
  if (error) error.hidden = state !== 'error';
  if (empty) empty.hidden = state !== 'empty';
  if (results) results.hidden = state === 'error' || state === 'empty';
  if (cards) cards.hidden = state === 'error' || state === 'empty';
}

function trapDelayDrilldownFocus(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeDelayDrilldown();
    return;
  }
  if (event.key !== 'Tab') return;
  const modal = document.getElementById('programa-delay-drilldown');
  const focusable = Array.from(modal?.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])') || [])
    .filter((element) => !element.hidden);
  if (!focusable.length) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault(); last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault(); first.focus();
  }
}

function formatSignedDays(value) {
  if (value === null || value === undefined || value === '') return '--';
  const days = Number(value);
  if (!Number.isFinite(days)) return '--';
  if (days === 0) return '0 días';
  return `${days > 0 ? '+' : '−'}${formatInteger(Math.abs(days))} días`;
}

function renderProgramaRadar(chart) {
  const canvas = document.getElementById('programa-radar-productividad');
  const unavailable = document.getElementById('programa-radar-unavailable');
  const axes = radarAxes(chart);
  const isAvailable = chart.status !== 'unavailable' && axes.every((axis) => radarAxisIsAvailable(axis));
  if (canvas) canvas.hidden = !isAvailable;
  if (unavailable) unavailable.hidden = isAvailable;
  renderRadarAxes(axes);
  syncRadarDetailUI(chart);
  if (!isAvailable) {
    BI.charts['programa-radar-productividad']?.destroy();
    delete BI.charts['programa-radar-productividad'];
    clearChartDataTable('programa-radar-productividad');
    return;
  }
  renderRadarChart('programa-radar-productividad', chartLabels(chart), axes.map((axis) => axis.value), chartDatasetLabel(chart, 0, 'Radar'));
}

const RADAR_AXIS_COPY = {
  productividad: {
    title: 'Avance promedio',
    measure: 'el avance promedio de las actividades con dato válido',
    formula: 'se promedian los progresos reportados; es un proxy de productividad, no una medición de horas o rendimientos',
    goal: 'La meta es acercarse al avance comprometido del corte.',
    action: 'Revise primero las actividades con progreso menor al compromiso.',
  },
  eficiencia: {
    title: 'Cumplimiento normalizado de cantidades',
    measure: 'qué tanto de la cantidad comprometida se ejecutó',
    formula: 'cantidad ejecutada dividida entre cantidad comprometida, normalizada a porcentaje',
    goal: 'La referencia es 100%; por debajo hay cantidad pendiente frente al compromiso.',
    action: 'Priorice los frentes con mayor cantidad pendiente y valide su restricción.',
  },
  desempeno: {
    title: 'PAC',
    measure: 'la proporción de compromisos que se cumplieron',
    formula: 'compromisos PAC cumplidos divididos entre compromisos evaluables',
    goal: 'La referencia es 100%; la brecha muestra compromisos sin cumplir.',
    action: 'Aclare la causa del incumplimiento y acuerde una recuperación con el responsable.',
  },
};

function radarAxes(chart) {
  const values = Array.isArray(chart?.datasets?.[0]?.data) ? chart.datasets[0].data : [];
  const source = chart?.axes || chart?.metrics?.axes || chart?.metrics?.radar || {};
  return Object.keys(RADAR_AXIS_COPY).map((key, index) => {
    const raw = source?.[key] && typeof source[key] === 'object' ? source[key] : {};
    const value = firstRadarValue(raw, values[index]);
    const sampleSize = firstFinite(raw.sample_size, raw.n, chart?.sample_size);
    const minimumSample = Math.max(1, firstFinite(raw.min_sample, raw.minimum_sample, raw.min_sample_size, chart?.minimum_sample_size, 3));
    const status = radarStatus(raw.status, chart?.status);
    return {
      key,
      ...RADAR_AXIS_COPY[key],
      value,
      rawValue: firstFinite(raw.raw_value, value),
      sampleSize,
      minimumSample,
      populationSize: firstFinite(raw.population_size, raw.eligible_population, chart?.population_size),
      available: typeof raw.available === 'boolean' ? raw.available : null,
      status: status.key,
      statusLabel: status.label,
      trend: radarTrendText(raw.trend ?? raw.trend_label ?? raw.trend_direction),
      explanation: radarExplanationText(raw.explanation || raw.description || raw.warning),
      action: String(raw.action || RADAR_AXIS_COPY[key].action).trim(),
      method: String(raw.method || raw.formula || chart?.method || '').trim(),
      source: String(raw.source || chart?.source_relations?.join(', ') || '').trim(),
      overTarget: raw.over_target === true,
    };
  });
}

function firstRadarValue(raw, fallback) {
  for (const candidate of [raw?.display_value, raw?.value, raw?.score, raw?.percentage, fallback]) {
    if (candidate === null || candidate === undefined || candidate === '') continue;
    const number = finiteNumber(candidate);
    if (Number.isFinite(number)) return number;
  }
  return null;
}

function radarStatus(value, fallback = '') {
  const selected = value && typeof value === 'object' ? value : fallback;
  if (selected && typeof selected === 'object') {
    return {
      key: String(selected.key || '').trim(),
      label: String(selected.label || selected.key || '').trim(),
    };
  }
  const key = String(selected || '').trim();
  return { key, label: key };
}

function firstFinite(...values) {
  for (const value of values) {
    const number = finiteNumber(value);
    if (Number.isFinite(number)) return number;
  }
  return null;
}

function radarTrendText(value) {
  if (value === null || value === undefined || value === '') return '';
  if (typeof value === 'object') return String(value.label || value.text || value.direction || '').trim();
  return String(value).trim();
}

function radarExplanationText(value) {
  if (value === null || value === undefined) return '';
  if (typeof value === 'object') {
    return [value.headline, value.implication, value.text, value.method]
      .filter((part) => typeof part === 'string' && part.trim())
      .join(' ')
      .trim();
  }
  return String(value).trim();
}

function radarAxisIsAvailable(axis) {
  return axis.available !== false
    && axis.status !== 'unavailable'
    && Number.isFinite(finiteNumber(axis.value))
    && Number.isFinite(finiteNumber(axis.sampleSize))
    && axis.sampleSize >= axis.minimumSample;
}

function radarAxisStatus(axis) {
  if (!radarAxisIsAvailable(axis)) return 'Sin muestra suficiente';
  if (axis.statusLabel) return axis.statusLabel;
  if (axis.status && !['available', 'ok'].includes(axis.status.toLowerCase())) return axis.status;
  if (axis.value >= 90) return 'En rango';
  if (axis.value >= 70) return 'Requiere seguimiento';
  return 'Requiere intervención';
}

function radarAxisSample(axis) {
  const sample = Number.isFinite(finiteNumber(axis.sampleSize)) ? formatInteger(axis.sampleSize) : '--';
  const population = Number.isFinite(finiteNumber(axis.populationSize)) ? ` de ${formatInteger(axis.populationSize)} elegibles` : '';
  return `Muestra: ${sample}/${formatInteger(axis.minimumSample)} mínimo${population}`;
}

function radarSentence(value) {
  const text = String(value || '').trim();
  if (!text) return '';
  return /[.!?]$/.test(text) ? text : `${text}.`;
}

function radarAxisTooltip(axis) {
  const value = radarAxisIsAvailable(axis) ? formatPercent(axis.value) : 'Sin muestra suficiente';
  const explanation = axis.explanation || `Qué mide: ${axis.measure}.`;
  const parts = [
    axis.title,
    explanation,
    `Fórmula simple: ${axis.formula}`,
    axis.source ? `Fuente: ${axis.source}` : '',
    radarAxisSample(axis),
    `Meta o brecha: ${axis.goal}`,
    `Lectura actual: ${value}`,
    axis.overTarget ? `El valor bruto es ${formatPercent(axis.rawValue)}; el radar se limita visualmente a 100%` : '',
    `Acción: ${axis.action}`,
    axis.method ? `Método reportado: ${axis.method}` : 'Método: se usan únicamente registros con los datos necesarios',
  ];
  return parts.filter(Boolean).map(radarSentence).join(' ');
}

function renderRadarAxes(axes) {
  const container = document.getElementById('programa-radar-axes');
  if (!container) return;
  container.innerHTML = axes.map((axis) => {
    const tooltipId = `programa-radar-tooltip-${axis.key}`;
    const trend = axis.trend ? `<span class="bi-radar-axis__trend">Tendencia: ${escapeHtml(axis.trend)}</span>` : '';
    const value = radarAxisIsAvailable(axis) ? formatPercent(axis.value) : 'Sin muestra suficiente';
    return `<article class="bi-radar-axis" data-radar-axis="${escapeHtml(axis.key)}" data-state="${escapeHtml(axis.status || 'unavailable')}">
      <div class="bi-radar-axis__heading"><h4>${escapeHtml(axis.title)}</h4><button type="button" class="bi-radar-axis__info" aria-describedby="${tooltipId}">Cómo se calcula</button><span id="${tooltipId}" class="bi-radar-axis__tooltip" role="tooltip">${escapeHtml(radarAxisTooltip(axis))}</span></div>
      <p class="bi-radar-axis__value">${escapeHtml(value)}</p>
      <p class="bi-radar-axis__meta">${escapeHtml(radarAxisSample(axis))} · Estado: ${escapeHtml(radarAxisStatus(axis))}</p>
      ${trend}
      <p class="bi-radar-axis__explanation">${escapeHtml(axis.explanation || axis.measure)}</p>
      <p class="bi-radar-axis__action"><strong>Acción:</strong> ${escapeHtml(axis.action)}</p>
    </article>`;
  }).join('');
}

function syncRadarDetailUI(chart) {
  const trigger = document.getElementById('programa-radar-detail-trigger');
  if (!trigger) return;
  trigger.dataset.detailEndpoint = chart?.interaction?.detail_endpoint || '/api/bi/report/programa-general/radar-detail';
  trigger.setAttribute('aria-expanded', BI.radarDrilldown.isOpen ? 'true' : 'false');
}

function wireRadarDrilldownEvents() {
  const card = document.getElementById('programa-radar-card');
  const trigger = document.getElementById('programa-radar-detail-trigger');
  const modal = document.getElementById('programa-radar-drilldown');
  if (card && card.dataset.radarBound !== '1') {
    card.addEventListener('dblclick', (event) => {
      if (event.target instanceof Element && event.target.closest('button, canvas')) return;
      openRadarDrilldown();
    });
    card.dataset.radarBound = '1';
  }
  const canvas = document.getElementById('programa-radar-productividad');
  if (canvas && canvas.dataset.radarBound !== '1') {
    canvas.addEventListener('dblclick', () => openRadarDrilldown());
    canvas.dataset.radarBound = '1';
  }
  if (trigger && trigger.dataset.radarBound !== '1') {
    trigger.addEventListener('click', () => openRadarDrilldown());
    trigger.dataset.radarBound = '1';
  }
  if (modal && modal.dataset.radarBound !== '1') {
    modal.addEventListener('click', (event) => {
      const closeTarget = event.target instanceof Element ? event.target.closest('[data-bi-radar-close]') : null;
      if (closeTarget) closeRadarDrilldown();
    });
    modal.querySelectorAll('[data-radar-axis]').forEach((tab) => {
      tab.addEventListener('click', () => selectRadarAxis(String(tab.dataset.radarAxis || 'productividad')));
    });
    modal.querySelector('#programa-radar-drilldown-more')?.addEventListener('click', () => {
      loadRadarDrilldownAxis({ append: true });
    });
    modal.addEventListener('keydown', handleRadarDrilldownKeydown);
    modal.dataset.radarBound = '1';
  }
}

function openRadarDrilldown(axis = BI.radarDrilldown.axis) {
  const modal = document.getElementById('programa-radar-drilldown');
  const trigger = document.getElementById('programa-radar-detail-trigger');
  if (!modal) return;
  const activeElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
  const previousFocus = activeElement && activeElement !== document.body && activeElement !== document.documentElement
    ? activeElement : trigger;
  BI.radarDrilldown = {
    ...BI.radarDrilldown,
    isOpen: true,
    previousFocus,
    axis: RADAR_AXIS_COPY[axis] ? axis : 'productividad',
    records: [],
    pagination: null,
  };
  modal.hidden = false;
  trigger?.setAttribute('aria-expanded', 'true');
  syncRadarTabs();
  setRadarDrilldownLoading(true);
  setRadarDrilldownError(false);
  setRadarDrilldownEmpty(false);
  setRadarDrilldownMoreVisible(false);
  setRadarDrilldownResultsVisible(false);
  clearRadarDrilldownResults();
  requestAnimationFrame(() => document.getElementById('programa-radar-drilldown-close')?.focus({ preventScroll: true }));
  loadRadarDrilldownAxis();
}

function closeRadarDrilldown(restoreFocus = true) {
  const modal = document.getElementById('programa-radar-drilldown');
  if (!modal || modal.hidden) return false;
  BI.radarDrilldown.requestId += 1;
  BI.radarDrilldown.isOpen = false;
  modal.hidden = true;
  const trigger = document.getElementById('programa-radar-detail-trigger');
  trigger?.setAttribute('aria-expanded', 'false');
  if (restoreFocus) {
    const target = BI.radarDrilldown.previousFocus instanceof HTMLElement && document.contains(BI.radarDrilldown.previousFocus)
      ? BI.radarDrilldown.previousFocus : trigger;
    target?.focus({ preventScroll: true });
  }
  BI.radarDrilldown.previousFocus = null;
  return true;
}

function selectRadarAxis(axis) {
  if (!RADAR_AXIS_COPY[axis] || !BI.radarDrilldown.isOpen) return;
  BI.radarDrilldown.axis = axis;
  BI.radarDrilldown.records = [];
  BI.radarDrilldown.pagination = null;
  syncRadarTabs();
  setRadarDrilldownLoading(true);
  setRadarDrilldownError(false);
  setRadarDrilldownEmpty(false);
  setRadarDrilldownMoreVisible(false);
  setRadarDrilldownResultsVisible(false);
  clearRadarDrilldownResults();
  loadRadarDrilldownAxis();
}

function syncRadarTabs() {
  document.querySelectorAll('#programa-radar-drilldown [data-radar-axis]').forEach((tab) => {
    const selected = tab.dataset.radarAxis === BI.radarDrilldown.axis;
    tab.setAttribute('aria-selected', selected ? 'true' : 'false');
    tab.setAttribute('tabindex', selected ? '0' : '-1');
    if (selected) {
      document.getElementById('programa-radar-drilldown-panel')?.setAttribute('aria-labelledby', tab.id);
    }
  });
}

function loadRadarDrilldownAxis({ append = false } = {}) {
  const data = BI.dataByView['programa-general'] || {};
  const chart = chartPayload(data, 'programa-radar-productividad');
  const endpoint = document.getElementById('programa-radar-detail-trigger')?.dataset.detailEndpoint
    || chart?.interaction?.detail_endpoint
    || '/api/bi/report/programa-general/radar-detail';
  const axis = BI.radarDrilldown.axis;
  const offset = append ? BI.radarDrilldown.records.length : 0;
  const requestId = ++BI.radarDrilldown.requestId;
  setRadarDrilldownLoading(true, { preserveResults: append });
  setRadarDrilldownError(false);
  fetchRadarDrilldownPayload(endpoint, axis, { limit: 50, offset })
    .then((payload) => {
      if (!isActiveRadarRequest(requestId)) return;
      renderRadarDrilldownPayload(payload, chart, { append });
    })
    .finally(() => {
      if (isActiveRadarRequest(requestId)) setRadarDrilldownLoading(false);
    });
}

function fetchRadarDrilldownPayload(endpoint, axis, pagination = {}) {
  if (!endpoint) return Promise.resolve({ __radarSource: 'error', __radarError: 'No se configuró el endpoint del detalle.', records: [] });
  const url = new URL(endpoint, window.location.origin);
  appendFilterParams(url.searchParams, getFilterPayload());
  url.searchParams.set('axis', axis);
  url.searchParams.set('limit', String(pagination.limit || 50));
  url.searchParams.set('offset', String(pagination.offset || 0));
  return fetch(url, { credentials: 'same-origin' })
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    })
    .then((payload) => ({ ...payload, __radarSource: 'api' }))
    .catch((error) => ({ __radarSource: 'error', __radarError: error instanceof Error ? error.message : 'Error de red', records: [] }));
}

function isActiveRadarRequest(requestId) {
  return BI.radarDrilldown.isOpen && BI.radarDrilldown.requestId === requestId;
}

function renderRadarDrilldownPayload(payload, chart, { append = false } = {}) {
  const axis = radarAxes(chart).find((candidate) => candidate.key === BI.radarDrilldown.axis) || radarAxes(chart)[0];
  if (payload?.__radarSource === 'error') {
    if (!append) {
      BI.radarDrilldown.records = [];
      BI.radarDrilldown.pagination = null;
      clearRadarDrilldownResults();
      setRadarDrilldownResultsVisible(false);
    }
    setRadarDrilldownEmpty(false);
    setRadarDrilldownError(true, `No fue posible consultar el detalle (${payload.__radarError || 'error desconocido'}). Revise la conexión e intente de nuevo.`);
    setRadarDrilldownMoreVisible(false);
    return;
  }
  const detailRows = payload?.data?.records || payload?.data?.activities || payload?.records || payload?.activities || [];
  const rows = (Array.isArray(detailRows) ? detailRows : [])
    .map(normalizeRadarRecord);
  BI.radarDrilldown.records = append ? [...BI.radarDrilldown.records, ...rows] : rows;
  BI.radarDrilldown.pagination = payload?.pagination || null;
  const totalPopulation = Number.isFinite(finiteNumber(payload?.summary?.total_population)) ? Number(payload.summary.total_population) : rows.length;
  const eligible = Number.isFinite(finiteNumber(payload?.summary?.eligible_count)) ? Number(payload.summary.eligible_count) : null;
  const excluded = Number.isFinite(finiteNumber(payload?.summary?.excluded_count)) ? Number(payload.summary.excluded_count) : null;
  renderRadarDrilldownSummary(axis, totalPopulation, eligible, excluded);
  setText('programa-radar-drilldown-explanation', `${radarExplanationText(payload?.explanation) || axis.explanation || axis.measure} Compromisos activos entregados por el detalle analítico para los filtros actuales.`);
  setRadarDrilldownError(false);
  if (!BI.radarDrilldown.records.length) {
    setRadarDrilldownResultsVisible(false);
    setRadarDrilldownEmpty(true);
    clearRadarDrilldownResults();
    setRadarDrilldownMoreVisible(false);
    return;
  }
  setRadarDrilldownEmpty(false);
  setRadarDrilldownResultsVisible(true);
  renderRadarDrilldownRecords(BI.radarDrilldown.records);
  setRadarDrilldownMoreVisible(Boolean(BI.radarDrilldown.pagination?.has_more));
}

function normalizeRadarRecord(row) {
  const read = (...keys) => {
    for (const key of keys) {
      const value = row?.[key];
      if (value !== null && value !== undefined && value !== '') return value;
    }
    return null;
  };
  const axis = BI.radarDrilldown.axis;
  const eligibilityMap = read('eligibility', 'elegibilidad');
  const exclusionMap = read('exclusion_reasons');
  const eligibility = eligibilityMap && typeof eligibilityMap === 'object' ? eligibilityMap[axis] : eligibilityMap;
  const exclusion = exclusionMap && typeof exclusionMap === 'object' ? exclusionMap[axis] : read('exclusion', 'excluded_reason', 'exclusion_reason');
  return {
    project: read('project', 'project_name', 'Proyecto'), cutoff: read('cutoff', 'cutoff_date', 'corte', 'week', 'semana', 'Semana'),
    activity: read('activity', 'actividad', 'Actividad', 'Titulo'), unit: read('unit', 'unidad', 'Unidad'),
    commitment: read('commitment', 'compromiso', 'Compromiso'), executed: read('executed', 'ejecutado', 'Ejecutado', 'Ejecutado_Real'),
    progress: read('p_completed', 'progress_pct', 'progress', 'progreso', 'P_Completado'), pac: read('pac', 'PAC'),
    responsible: read('responsible', 'responsable', 'responsable_aia', 'Responsable AIA'), subcontractor: read('subcontractor', 'subcontratista', 'sub_contratista', 'Sub-Contratista'),
    critical: read('critical', 'critica', 'Crítica', 'Ruta_Critica'), tnp: read('tnp', 'Es_TNP'),
    eligibility: eligibility && typeof eligibility === 'object' ? (eligibility.eligible ? 'Elegible' : 'Excluido') : eligibility,
    exclusion: eligibility && typeof eligibility === 'object' ? eligibility.reason : exclusion,
  };
}

function renderRadarDrilldownSummary(axis, total, eligible = null, excluded = null) {
  const summary = document.getElementById('programa-radar-drilldown-summary');
  if (!summary) return;
  const items = [
    `Eje: ${axis.title}`,
    `Lectura: ${radarAxisIsAvailable(axis) ? formatPercent(axis.value) : 'Sin muestra suficiente'}`,
    `Población: ${formatInteger(total)}`,
  ];
  if (eligible !== null) items.push(`Elegibles: ${formatInteger(eligible)}`);
  if (excluded !== null) items.push(`Excluidos: ${formatInteger(excluded)}`);
  summary.innerHTML = items.map((text) => `<span>${escapeHtml(text)}</span>`).join('');
}

function renderRadarDrilldownTable(rows) {
  const body = document.getElementById('programa-radar-drilldown-body');
  if (!body) return;
  body.innerHTML = rows.map((row) => `<tr>
    <td>${escapeHtml(radarDetailText(row.project))}<br><small>${escapeHtml(radarCutoffText(row.cutoff))}</small></td>
    <td><strong>${escapeHtml(radarDetailText(row.activity, 'Actividad sin nombre'))}</strong></td>
    <td>${escapeHtml(radarDetailText(row.unit))}</td><td>${escapeHtml(radarDetailText(row.commitment))}</td><td>${escapeHtml(radarDetailText(row.executed))}</td>
    <td>${escapeHtml(radarPercentText(row.progress))}</td><td>${escapeHtml(radarPacText(row.pac))}</td>
    <td>${renderComplianceResponsibles(row)}</td><td>${escapeHtml(causalCriticalLabel(row.critical))}<br><small>${escapeHtml(radarTnpText(row.tnp))}</small></td>
    <td>${escapeHtml(radarDetailText(row.eligibility))}<br><small>${escapeHtml(radarDetailText(row.exclusion))}</small></td>
  </tr>`).join('');
}

function renderRadarDrilldownRecords(rows) {
  const mobile = window.matchMedia('(max-width: 767px)').matches;
  if (mobile) {
    renderRadarDrilldownCards(rows);
    const body = document.getElementById('programa-radar-drilldown-body');
    if (body) body.innerHTML = '';
  } else {
    renderRadarDrilldownTable(rows);
    const cards = document.getElementById('programa-radar-drilldown-cards');
    if (cards) cards.innerHTML = '';
  }
}

function renderRadarDrilldownCards(rows) {
  const container = document.getElementById('programa-radar-drilldown-cards');
  if (!container) return;
  container.innerHTML = rows.map((row) => `<article class="bi-radar-drilldown-card">
    <h3>${escapeHtml(radarDetailText(row.activity, 'Actividad sin nombre'))}</h3>
    <dl class="bi-radar-drilldown-card__fields">
      ${renderRadarField('Proyecto', row.project)}${renderRadarField('Corte / semana', radarCutoffText(row.cutoff))}
      ${renderRadarField('Unidad', row.unit)}${renderRadarField('Compromiso', row.commitment)}${renderRadarField('Ejecutado', row.executed)}
      ${renderRadarField('Progreso', radarPercentText(row.progress))}${renderRadarField('PAC', radarPacText(row.pac))}
      ${renderRadarField('Responsable AIA', row.responsible)}${renderRadarField('Sub-Contratista', row.subcontractor)}
      ${renderRadarField('Criticidad', causalCriticalLabel(row.critical))}${renderRadarField('TNP', radarTnpText(row.tnp))}${renderRadarField('Elegibilidad', row.eligibility)}${renderRadarField('Exclusión', row.exclusion)}
    </dl>
  </article>`).join('');
}

function renderRadarField(label, value) {
  return `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(radarDetailText(value))}</dd></div>`;
}

function radarDetailText(value, fallback = 'Sin dato') {
  if (value === null || value === undefined || value === '') return fallback;
  return String(value);
}

function radarCutoffText(value) {
  return value === null || value === undefined || value === '' ? 'Corte / semana sin dato' : `Corte / semana: ${value}`;
}

function radarPercentText(value) {
  const numeric = finiteNumber(value);
  if (!Number.isFinite(numeric)) return 'Sin dato';
  return formatPercent(numeric <= 1 ? numeric * 100 : numeric);
}

function radarPacText(value) {
  if (value === null || value === undefined || value === '') return 'Sin dato';
  if (value === true || ['1', 'si', 'sí', 'true'].includes(String(value).toLowerCase())) return 'Cumple';
  if (value === false || ['0', 'no', 'false'].includes(String(value).toLowerCase())) return 'No cumple';
  return String(value);
}

function radarTnpText(value) {
  return value === true || ['1', 'si', 'sí', 'true'].includes(String(value).toLowerCase()) ? 'Sí, excluido' : 'No';
}

function setRadarDrilldownLoading(visible, { preserveResults = false } = {}) {
  const loading = document.getElementById('programa-radar-drilldown-loading');
  if (loading) loading.hidden = !visible;
  if (visible && !preserveResults) setRadarDrilldownResultsVisible(false);
  const more = document.getElementById('programa-radar-drilldown-more');
  if (more) more.disabled = visible;
}

function setRadarDrilldownEmpty(visible) {
  const empty = document.getElementById('programa-radar-drilldown-empty');
  if (empty) empty.hidden = !visible;
}

function setRadarDrilldownError(visible, message = '') {
  const error = document.getElementById('programa-radar-drilldown-error');
  if (!error) return;
  if (message) error.textContent = message;
  error.hidden = !visible;
}

function setRadarDrilldownMoreVisible(visible) {
  const button = document.getElementById('programa-radar-drilldown-more');
  if (button) button.hidden = !visible;
}

function clearRadarDrilldownResults() {
  const body = document.getElementById('programa-radar-drilldown-body');
  const cards = document.getElementById('programa-radar-drilldown-cards');
  if (body) body.innerHTML = '';
  if (cards) cards.innerHTML = '';
}

function setRadarDrilldownResultsVisible(visible) {
  const table = document.getElementById('programa-radar-drilldown-results');
  const cards = document.getElementById('programa-radar-drilldown-cards');
  const mobile = window.matchMedia('(max-width: 767px)').matches;
  if (table) table.hidden = !visible || mobile;
  if (cards) cards.hidden = !visible || !mobile;
}

function handleRadarDrilldownKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeRadarDrilldown();
    return;
  }
  const currentTab = event.target instanceof Element ? event.target.closest('[role="tab"][data-radar-axis]') : null;
  if (currentTab && ['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
    const tabs = Array.from(event.currentTarget.querySelectorAll('[role="tab"][data-radar-axis]'));
    const currentIndex = tabs.indexOf(currentTab);
    if (currentIndex >= 0 && tabs.length) {
      event.preventDefault();
      let nextIndex = currentIndex;
      if (event.key === 'Home') nextIndex = 0;
      if (event.key === 'End') nextIndex = tabs.length - 1;
      if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
      if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabs.length;
      const nextTab = tabs[nextIndex];
      selectRadarAxis(String(nextTab.dataset.radarAxis || 'productividad'));
      nextTab.focus();
    }
    return;
  }
  if (event.key !== 'Tab') return;
  const focusable = Array.from(event.currentTarget.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
    .filter((element) => element instanceof HTMLElement && !element.hidden && !element.hasAttribute('disabled') && element.getClientRects().length > 0);
  if (!focusable.length) return event.preventDefault();
  const first = focusable[0];
  const last = focusable.at(-1);
  if (event.shiftKey && (document.activeElement === first || !event.currentTarget.contains(document.activeElement))) return event.preventDefault(), last.focus();
  if (!event.shiftKey && document.activeElement === last) return event.preventDefault(), first.focus();
}

function wireProgramActivityEvents() {
  const loadMore = document.getElementById('programa-activity-load-more');
  if (loadMore && loadMore.dataset.biBound !== '1') {
    loadMore.addEventListener('click', loadMoreProgramActivities);
    loadMore.dataset.biBound = '1';
  }
  const analysis = document.getElementById('programa-activity-analysis-trigger');
  if (analysis && analysis.dataset.biBound !== '1') {
    analysis.addEventListener('click', openProgressDrilldown);
    analysis.dataset.biBound = '1';
  }
  if (!BI.activityTimeline.mediaQuery) {
    BI.activityTimeline.mediaQuery = window.matchMedia('(max-width: 767px)');
    BI.activityTimeline.mediaQuery.addEventListener?.('change', syncProgramActivityResponsiveVisibility);
  }
}

function renderProgramaActivities(snapshot, append = false) {
  const loading = document.getElementById('programa-activity-loading');
  const table = document.getElementById('programa-activity-table');
  const body = document.getElementById('programa-activity-body');
  const cards = document.getElementById('programa-activity-cards');
  if (!append) BI.activityTimeline.requestId += 1;
  const incoming = (Array.isArray(snapshot?.activities) ? snapshot.activities : []).map(normalizeProgramActivity);
  const records = append ? [...BI.activityTimeline.records, ...incoming] : incoming;
  BI.activityTimeline.records = [...new Map(records.map((activity) => [activity.activity_key, activity])).values()];
  BI.activityTimeline.pagination = snapshot?.pagination || {
    total: BI.activityTimeline.records.length,
    returned_count: BI.activityTimeline.records.length,
    has_more: false,
    next_offset: BI.activityTimeline.records.length,
  };
  BI.activityTimeline.summary = append
    ? { ...(BI.activityTimeline.summary || {}), ...(snapshot?.summary || {}) }
    : (snapshot?.summary || {});
  BI.activityTimeline.endpoint = snapshot?.detail_endpoint || BI.activityTimeline.endpoint || '/api/bi/report/programa-general/progress-detail';
  const activities = BI.activityTimeline.records;
  const total = Number.isFinite(Number(BI.activityTimeline.pagination?.total))
    ? Number(BI.activityTimeline.pagination.total)
    : activities.length;
  if (loading) loading.hidden = true;
  renderProgramActivitySummary(total);
  if (!body || !cards || !table) return;
  const empty = document.getElementById('programa-activity-empty');
  if (empty) empty.hidden = activities.length > 0;
  body.innerHTML = activities.map(renderProgramActivityRow).join('');
  cards.innerHTML = activities.map(renderProgramActivityCard).join('');
  syncProgramActivityResponsiveVisibility();
  syncProgramActivityActions();
}

function syncProgramActivityResponsiveVisibility() {
  const hasRecords = BI.activityTimeline.records.length > 0;
  const mobile = BI.activityTimeline.mediaQuery?.matches ?? window.matchMedia('(max-width: 767px)').matches;
  const table = document.getElementById('programa-activity-table');
  const cards = document.getElementById('programa-activity-cards');
  if (table) table.hidden = !hasRecords || mobile;
  if (cards) cards.hidden = !hasRecords || !mobile;
}

function normalizeProgramActivity(row) {
  return {
    ...row,
    activity_key: String(row?.activity_key || `${toNumber(row?.project_id)}:${toNumber(row?.unique_id)}`),
    real_pct: toNumber(row?.real_pct ?? row?.progress_pct),
    planned_pct: toNumber(row?.planned_pct),
    gap_pp: toNumber(row?.gap_pp),
    weight_pct: toNumber(row?.weight_pct),
    real_contribution_pp: toNumber(row?.real_contribution_pp),
    recoverable_pp: toNumber(row?.recoverable_pp),
    observed_delay_days: Math.max(0, toNumber(row?.observed_delay_days)),
  };
}

function renderProgramActivitySummary(total) {
  const shown = BI.activityTimeline.records.length;
  const summary = BI.activityTimeline.summary || {};
  const cutoff = summary.cutoff_label || summary.cutoff || '--';
  setText('programa-activity-cutoff', `Corte ${cutoff}`);
  setText(
    'programa-activity-total',
    total
      ? `Mostrando ${formatInteger(shown)} de ${formatInteger(total)} actividades · avance real ${formatPercent(summary.real_pct)} · teórico ${formatPercent(summary.theoretical_pct)} · brecha ${formatSignedPp(summary.gap_pp)}.`
      : 'Sin actividades válidas para los filtros activos.',
  );
}

function renderProgramActivityRow(activity) {
  return `<tr data-activity-key="${escapeHtml(activity.activity_key)}">
    <td><strong>${escapeHtml(activity.activity || 'Actividad sin nombre')}</strong><small>${escapeHtml(activity.project || '--')} · ${escapeHtml(activity.stage || 'Sin estado')}</small></td>
    <td>${renderProgramActivityTimeline(activity)}</td>
    <td><strong>${escapeHtml(formatSignedPp(activity.gap_pp))}</strong><small>Recuperable: ${escapeHtml(formatSignedPp(activity.recoverable_pp))}</small></td>
    <td><strong>${escapeHtml(formatPercent(activity.weight_pct))}</strong><small>Aporte real: ${escapeHtml(formatSignedPp(activity.real_contribution_pp))}</small></td>
    <td>${renderProgramActivityState(activity)}</td>
    <td>${renderProgramActivityOwners(activity)}</td>
  </tr>`;
}

function renderProgramActivityCard(activity) {
  return `<article class="bi-programa-activity-card" data-activity-key="${escapeHtml(activity.activity_key)}">
    <header><div><h4>${escapeHtml(activity.activity || 'Actividad sin nombre')}</h4><p>${escapeHtml(activity.project || '--')}</p></div>${renderProgramActivityState(activity)}</header>
    ${renderProgramActivityTimeline(activity)}
    <dl>
      <div><dt>Brecha</dt><dd>${escapeHtml(formatSignedPp(activity.gap_pp))}</dd></div>
      <div><dt>Aporte recuperable</dt><dd>${escapeHtml(formatSignedPp(activity.recoverable_pp))}</dd></div>
      <div><dt>Peso</dt><dd>${escapeHtml(formatPercent(activity.weight_pct))}</dd></div>
      <div><dt>Aporte real</dt><dd>${escapeHtml(formatSignedPp(activity.real_contribution_pp))}</dd></div>
      <div><dt>Responsable AIA</dt><dd>${escapeHtml(activity.responsible || 'Sin asignar')}</dd></div>
      <div><dt>Subcontratista</dt><dd>${escapeHtml(activity.subcontractor || 'Sin asignar')}</dd></div>
    </dl>
  </article>`;
}

function renderProgramActivityTimeline(activity) {
  return `<div class="bi-programa-activity-timeline" aria-label="Cronograma de ${escapeHtml(activity.activity || 'actividad')}">
    <div class="bi-programa-activity-dates">
      <span><small>Inicio</small>${escapeHtml(formatShortDate(activity.planned_start))}</span>
      <span><small>Corte</small>${escapeHtml(formatShortDate(activity.cutoff))}</span>
      <span><small>Fin</small>${escapeHtml(formatShortDate(activity.planned_finish))}</span>
    </div>
    <label><span>Real ${escapeHtml(formatPercent(activity.real_pct))}</span><progress class="bi-programa-activity-progress--real" max="100" value="${clampProgramActivityProgress(activity.real_pct)}"></progress></label>
    <label><span>Teórico ${escapeHtml(formatPercent(activity.planned_pct))}</span><progress class="bi-programa-activity-progress--planned" max="100" value="${clampProgramActivityProgress(activity.planned_pct)}"></progress></label>
  </div>`;
}

function renderProgramActivityState(activity) {
  const status = activity.late ? 'Atrasada' : (activity.state || 'Sin estado');
  const delay = activity.late ? `<small>${escapeHtml(formatInteger(activity.observed_delay_days))} días vencida</small>` : '';
  const critical = activity.critical ? '<small>Ruta crítica</small>' : '<small>No crítica</small>';
  return `<span class="bi-programa-activity-state" data-status="${activity.late ? 'late' : 'current'}"><strong>${escapeHtml(status)}</strong>${delay}${critical}</span>`;
}

function renderProgramActivityOwners(activity) {
  return `<span class="bi-programa-activity-owners"><strong>Responsable AIA</strong>${escapeHtml(activity.responsible || 'Sin asignar')}<strong>Subcontratista</strong>${escapeHtml(activity.subcontractor || 'Sin asignar')}</span>`;
}

function clampProgramActivityProgress(value) {
  return Math.min(100, Math.max(0, toNumber(value)));
}

function syncProgramActivityActions() {
  const loadMore = document.getElementById('programa-activity-load-more');
  if (loadMore) {
    loadMore.hidden = !BI.activityTimeline.pagination?.has_more;
    loadMore.disabled = false;
  }
  const analysis = document.getElementById('programa-activity-analysis-trigger');
  if (analysis) analysis.disabled = !BI.activityTimeline.endpoint;
}

function loadMoreProgramActivities() {
  const endpoint = BI.activityTimeline.endpoint;
  const pagination = BI.activityTimeline.pagination;
  if (!endpoint || !pagination?.has_more) return;
  const button = document.getElementById('programa-activity-load-more');
  const error = document.getElementById('programa-activity-more-error');
  if (button) button.disabled = true;
  if (error) error.hidden = true;
  const requestId = ++BI.activityTimeline.requestId;
  const url = new URL(endpoint, window.location.origin);
  appendFilterParams(url.searchParams, getFilterPayload());
  url.searchParams.set('limit', '100');
  url.searchParams.set('offset', String(pagination.next_offset || BI.activityTimeline.records.length));
  fetch(url, { credentials: 'same-origin' })
    .then((response) => {
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      return response.json();
    })
    .then((payload) => {
      if (BI.activityTimeline.requestId !== requestId) return;
      renderProgramaActivities({ ...payload, detail_endpoint: endpoint }, true);
    })
    .catch(() => {
      if (BI.activityTimeline.requestId !== requestId || !error) return;
      error.textContent = 'No fue posible cargar más actividades. Los registros visibles se conservaron.';
      error.hidden = false;
      if (button) button.disabled = false;
    });
}

function renderCnpInsight(metrics = {}) {
  const container = document.getElementById('programa-cnp-insight');
  if (!container) return;
  const total = Math.max(0, toNumber(metrics.total));
  container.hidden = false;
  if (!total) {
    container.innerHTML = '<p class="bi-cause-insight__empty">No hay actividades con CNP para los filtros activos.</p>';
    return;
  }

  const categories = Array.isArray(metrics.categories) ? metrics.categories : [];
  const leading = categories[0] || {};
  const topCause = leading.category
    ? `${leading.category}: ${formatInteger(leading.count)} (${formatPercent(leading.share_pct)})`
    : 'Sin categoría dominante';
  const metricItems = [
    ['No programadas', total],
    ['Críticas', metrics.critical_count],
    ['Inicio vencido', metrics.overdue_start_count],
    ['Sin Responsable AIA', metrics.unassigned_responsible_count],
  ];
  container.innerHTML = `
    <div class="bi-cause-insight__metrics">
      ${metricItems.map(([label, value]) => `<div><strong>${escapeHtml(formatInteger(toNumber(value)))}</strong><span>${escapeHtml(label)}</span></div>`).join('')}
    </div>
    <p class="bi-cause-insight__lead"><strong>Principal causa:</strong> ${escapeHtml(topCause)}.</p>
    <p class="bi-cause-insight__action">Prioriza las actividades críticas o con inicio vencido y asigna los responsables faltantes.</p>`;
}

function renderCncInsight(metrics = {}) {
  const container = document.getElementById('programa-cnc-insight');
  if (!container) return;
  const total = Math.max(0, toNumber(metrics.total));
  container.hidden = false;
  if (!total) {
    container.innerHTML = '<p class="bi-cause-insight__empty">No hay CNC registradas para los filtros activos. El gráfico no inventa causas para este corte.</p>';
    return;
  }

  const categories = Array.isArray(metrics.categories) ? metrics.categories : [];
  const leading = categories[0] || {};
  const topCause = leading.category
    ? `${leading.category}: ${formatInteger(leading.count)} (${formatPercent(leading.share_pct)})`
    : 'Sin categoría dominante';
  const average = Number.isFinite(finiteNumber(metrics.average_completion_pct))
    ? formatPercent(metrics.average_completion_pct)
    : 'Sin muestra';
  const metricItems = [
    ['Incumplimientos', total],
    ['Sin ejecución', metrics.zero_execution_count],
    ['Brecha ≥ 50%', metrics.severe_gap_count],
    ['Cumplimiento medio', average],
  ];
  container.innerHTML = `
    <div class="bi-cause-insight__metrics">
      ${metricItems.map(([label, value]) => `<div><strong>${escapeHtml(typeof value === 'string' ? value : formatInteger(toNumber(value)))}</strong><span>${escapeHtml(label)}</span></div>`).join('')}
    </div>
    <p class="bi-cause-insight__lead"><strong>Principal causa:</strong> ${escapeHtml(topCause)}.</p>
    <p class="bi-cause-insight__action">Atiende primero los compromisos críticos, sin ejecución o con brecha igual o mayor al 50%.</p>`;
}

const CAUSAL_DRILLDOWN_CONFIG = {
  cnp: { canvasId: 'programa-cnp', cardId: 'programa-cnp-card', modulePath: '/programacion-semanal/cnp', shortLabel: 'CNP', title: 'Causas de No Programación' },
  cnc: { canvasId: 'programa-cnc', cardId: 'programa-cnc-card', modulePath: '/programacion-semanal/cnc', shortLabel: 'CNC', title: 'Causas de No Cumplimiento' },
};

function causalDrilldownConfig(type) {
  return CAUSAL_DRILLDOWN_CONFIG[type] || null;
}

function wireCausalDrilldownEvents() {
  Object.keys(CAUSAL_DRILLDOWN_CONFIG).forEach((type) => {
    const config = causalDrilldownConfig(type);
    const card = document.getElementById(config.cardId);
    const trigger = document.getElementById(`programa-${type}-drilldown-trigger`);
    if (card && card.dataset.causalBound !== '1') {
      card.addEventListener('dblclick', (event) => {
        if (!(event.target instanceof Element && event.target.closest(`#programa-${type}-drilldown-trigger, #${config.canvasId}`))) openCausalDrilldown(type);
      });
      card.dataset.causalBound = '1';
    }
    if (trigger && trigger.dataset.causalBound !== '1') {
      trigger.addEventListener('click', () => openCausalDrilldown(type));
      trigger.dataset.causalBound = '1';
    }
  });

  const modal = document.getElementById('programa-causal-drilldown');
  if (modal && modal.dataset.causalBound !== '1') {
    modal.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.closest('[data-bi-causal-close]')) closeCausalDrilldown();
    });
    modal.addEventListener('keydown', handleCausalDrilldownKeydown);
    modal.dataset.causalBound = '1';
  }
  const loadMore = document.getElementById('programa-causal-drilldown-load-more');
  if (loadMore && loadMore.dataset.causalBound !== '1') {
    loadMore.addEventListener('click', loadMoreCausalRecords);
    loadMore.dataset.causalBound = '1';
  }
}

function openCausalDrilldown(type, category = '') {
  const config = causalDrilldownConfig(type);
  const chart = chartPayload(BI.dataByView['programa-general'] || {}, config?.canvasId);
  const trigger = document.getElementById(`programa-${type}-drilldown-trigger`);
  const endpoint = trigger?.dataset.detailEndpoint || chart?.interaction?.detail_endpoint;
  const modal = document.getElementById('programa-causal-drilldown');
  if (!config || !modal || !endpoint) return;

  BI.causalDrilldown = {
    ...BI.causalDrilldown,
    isOpen: true,
    previousFocus: document.activeElement,
    type,
    category: String(category || '').trim(),
    endpoint,
    interaction: chart?.interaction || null,
    records: [],
    pagination: null,
    summary: null,
  };
  modal.hidden = false;
  trigger?.setAttribute('aria-expanded', 'true');
  setText('programa-causal-drilldown-title', config.title);
  setText('programa-causal-drilldown-measure-heading', type === 'cnc' ? 'Compromiso / ejecución' : 'Inicio / urgencia');
  setText('programa-causal-drilldown-explanation', BI.causalDrilldown.category ? `Registros asociados a la categoría ${BI.causalDrilldown.category}.` : 'Selecciona un segmento para acotar por categoría o revisa todos los registros del corte.');
  setCausalDrilldownLoading(true);
  setCausalDrilldownEmpty(false);
  setCausalDrilldownResultsVisible(false);
  clearCausalDrilldownResults();
  requestAnimationFrame(() => document.getElementById('programa-causal-drilldown-close')?.focus({ preventScroll: true }));

  const requestId = ++BI.causalDrilldown.requestId;
  fetchCausalDrilldownPayload(endpoint, chart?.interaction, BI.causalDrilldown.category, 0)
    .then((payload) => {
      if (!isActiveCausalRequest(requestId)) return;
      renderCausalDrilldownPayload(payload, type, false);
    })
    .catch(() => {
      if (isActiveCausalRequest(requestId)) renderCausalDrilldownError();
    })
    .finally(() => {
      if (isActiveCausalRequest(requestId)) setCausalDrilldownLoading(false);
    });
}

function syncCausalDrilldownUI(type, chart) {
  const trigger = document.getElementById(`programa-${type}-drilldown-trigger`);
  const endpoint = chart?.interaction?.detail_endpoint || '';
  if (!trigger) return;
  trigger.disabled = !endpoint;
  trigger.dataset.detailEndpoint = endpoint;
  trigger.setAttribute('aria-expanded', BI.causalDrilldown.isOpen && BI.causalDrilldown.type === type ? 'true' : 'false');
  renderCausalCategoryActions(type, chart, Boolean(endpoint));
}

function renderCausalCategoryActions(type, chart, enabled) {
  const container = document.getElementById(`programa-${type}-category-actions`);
  if (!container) return;
  const labels = chartLabels(chart);
  const values = chartDatasetData(chart);
  const categories = labels.filter((label, index) => label && label !== 'Sin registros' && toNumber(values[index]) > 0);
  container.hidden = !enabled || categories.length === 0;
  container.innerHTML = categories.map((category) => `<button type="button" class="aia-chip bi-cause-category-trigger"
    aria-label="Ver ${escapeHtml(type.toUpperCase())} de la categoría ${escapeHtml(category)}"
    data-causal-category="${escapeHtml(category)}">${escapeHtml(category)}</button>`).join('');
  container.querySelectorAll('[data-causal-category]').forEach((button) => {
    button.addEventListener('click', () => openCausalDrilldown(type, button.dataset.causalCategory || ''));
  });
}

function closeCausalDrilldown(restoreFocus = true) {
  const modal = document.getElementById('programa-causal-drilldown');
  if (!modal || modal.hidden) return false;
  const type = BI.causalDrilldown.type;
  const trigger = document.getElementById(`programa-${type}-drilldown-trigger`);
  BI.causalDrilldown.requestId += 1;
  BI.causalDrilldown.isOpen = false;
  BI.causalDrilldown.records = [];
  BI.causalDrilldown.pagination = null;
  clearCausalLoadMoreError();
  syncCausalLoadMore(false);
  modal.hidden = true;
  trigger?.setAttribute('aria-expanded', 'false');
  if (restoreFocus) (BI.causalDrilldown.previousFocus instanceof HTMLElement ? BI.causalDrilldown.previousFocus : trigger)?.focus({ preventScroll: true });
  BI.causalDrilldown.previousFocus = null;
  return true;
}

function handleCausalDrilldownKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    event.stopPropagation();
    closeCausalDrilldown();
    return;
  }
  if (event.key !== 'Tab') return;
  const focusable = Array.from(event.currentTarget.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
    .filter((element) => element instanceof HTMLElement && !element.hidden && !element.hasAttribute('disabled') && element.getAttribute('tabindex') !== '-1' && element.getClientRects().length > 0);
  if (!focusable.length) return event.preventDefault();
  const first = focusable[0];
  const last = focusable.at(-1);
  if (event.shiftKey && (document.activeElement === first || !event.currentTarget.contains(document.activeElement))) return event.preventDefault(), last.focus();
  if (!event.shiftKey && document.activeElement === last) return event.preventDefault(), first.focus();
}

function isActiveCausalRequest(requestId) {
  return BI.causalDrilldown.isOpen && BI.causalDrilldown.requestId === requestId;
}

function fetchCausalDrilldownPayload(endpoint, interaction, category, offset = 0, includeSummary = true) {
  const url = new URL(endpoint, window.location.origin);
  appendFilterParams(url.searchParams, getFilterPayload());
  if (category) url.searchParams.set(interaction?.category_param || 'category', category);
  url.searchParams.set('limit', '25');
  url.searchParams.set('offset', String(Math.max(0, toNumber(offset))));
  url.searchParams.set('include_summary', includeSummary ? '1' : '0');
  return fetch(url, { credentials: 'same-origin' }).then((response) => {
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
  });
}

function renderCausalDrilldownPayload(payload, type, append = false) {
  const rows = (Array.isArray(payload?.activities) ? payload.activities : (Array.isArray(payload?.records) ? payload.records : []))
    .map(normalizeCausalRecord);
  BI.causalDrilldown.records = append ? [...BI.causalDrilldown.records, ...rows] : rows;
  BI.causalDrilldown.pagination = payload?.pagination || null;
  clearCausalLoadMoreError();
  BI.causalDrilldown.summary = append
    ? { ...(BI.causalDrilldown.summary || {}), ...(payload?.summary || {}) }
    : (payload?.summary || {});
  const total = Number.isFinite(Number(payload?.pagination?.total))
    ? Number(payload.pagination.total)
    : (Number.isFinite(Number(BI.causalDrilldown.summary?.total)) ? Number(BI.causalDrilldown.summary.total) : BI.causalDrilldown.records.length);
  renderCausalDrilldownSummary(BI.causalDrilldown.summary || {}, total, BI.causalDrilldown.records.length);
  syncCausalLoadMore(Boolean(payload?.pagination?.has_more));
  if (!BI.causalDrilldown.records.length) {
    setCausalDrilldownResultsVisible(false);
    setCausalDrilldownEmpty(true);
    clearCausalDrilldownResults();
    return;
  }
  setCausalDrilldownEmpty(false);
  setCausalDrilldownResultsVisible(true);
  renderCausalDrilldownTable(BI.causalDrilldown.records, type);
  renderCausalDrilldownCards(BI.causalDrilldown.records, type);
}

function normalizeCausalRecord(row) {
  const read = (...keys) => {
    const value = keys.map((key) => row?.[key]).find((candidate) => candidate !== null && candidate !== undefined && candidate !== '');
    return value === undefined ? '' : value;
  };
  return {
    project: read('project', 'project_name', 'Proyecto'), week: read('week', 'semana', 'Semana'),
    activity: read('activity', 'actividad', 'Actividad', 'Titulo'), category: read('category_canonical', 'category', 'categoria', 'Categoría'),
    cause: read('cause', 'causa', 'Causa'), observation: read('observations', 'observation', 'observacion', 'Observación'),
    responsible: read('responsible', 'responsable', 'Responsable'), subcontractor: read('subcontractor', 'subcontratista', 'Subcontratista'), location: read('location', 'ubicacion', 'Ubicacion'),
    critical: read('critical', 'critica', 'Crítica'), projectId: read('project_id', 'Proyecto_ID'), activityId: read('activity_id', 'id', 'Id', 'Consecutivo', 'consecutivo'),
    startDate: read('start_date', 'fecha_inicio', 'Fecha_Inicio'), cutoff: read('cutoff', 'fecha_corte'),
    daysToStart: read('days_to_start'), startStatus: read('start_status'), priority: read('priority'),
    committedQuantity: read('committed_quantity'), executedQuantity: read('executed_quantity'),
    completionPct: read('completion_pct'), shortfallQuantity: read('shortfall_quantity'), shortfallPct: read('shortfall_pct'),
    unit: read('unit', 'Unidad'), executionStatus: read('execution_status'),
    impact: read('impact', 'implicacion', 'Implicación'), recommendedAction: read('recommended_action', 'accion', 'Acción'),
    operationalLink: read('operational_link', 'operational_url', 'activity_url'),
  };
}

function renderCausalDrilldownSummary(summaryData, total, shown) {
  const category = BI.causalDrilldown.category || 'Todas las categorías';
  const summary = document.getElementById('programa-causal-drilldown-summary');
  const typeSpecific = BI.causalDrilldown.type === 'cnc'
    ? [
      `Sin ejecución: ${formatInteger(summaryData.zero_execution_count)}`,
      `Brecha ≥ 50%: ${formatInteger(summaryData.severe_gap_count)}`,
      `Cumplimiento medio: ${formatPercent(summaryData.average_completion_pct)}`,
    ]
    : [
      `Inicio vencido: ${formatInteger(summaryData.overdue_start_count)}`,
      `Sin Responsable AIA: ${formatInteger(summaryData.unassigned_responsible_count)}`,
    ];
  if (summary) summary.innerHTML = [
    `Tipo: ${BI.causalDrilldown.type.toUpperCase()}`,
    `Categoría: ${category}`,
    `Mostradas: ${formatInteger(shown)} de ${formatInteger(total)}`,
    `Críticas: ${formatInteger(summaryData.critical_count)}`,
    ...typeSpecific,
  ]
    .map((text) => `<span>${escapeHtml(text)}</span>`).join('');
}

function renderCausalDrilldownTable(rows, type) {
  const body = document.getElementById('programa-causal-drilldown-body');
  if (!body) return;
  body.innerHTML = rows.map((row) => `<tr>
    <td>${escapeHtml(row.project || '--')}<br><small>Semana ${escapeHtml(row.week || '--')}</small></td>
    <td><strong>${escapeHtml(row.activity || 'Actividad sin nombre')}</strong><br><small>${escapeHtml(row.location || 'Sin ubicación')}</small></td>
    <td><strong>${escapeHtml(row.category || '--')}</strong><br><small>${escapeHtml(row.cause || '--')}</small><br><small>${escapeHtml(row.observation || 'Sin observación')}</small></td>
    <td>${type === 'cnc' ? renderCncExecution(row) : `${escapeHtml(formatShortDate(row.startDate))}<br><small>${escapeHtml(causalStartStatusLabel(row.startStatus, row.daysToStart))}</small>`}</td>
    <td>${renderComplianceResponsibles(row)}<small>Crítica: ${escapeHtml(causalCriticalLabel(row.critical))}</small></td>
    <td><strong>${escapeHtml(row.impact || '--')}</strong><br><small>${escapeHtml(row.recommendedAction || '--')}</small></td>
  </tr>`).join('');
}

function renderCausalDrilldownCards(rows, type) {
  const container = document.getElementById('programa-causal-drilldown-cards');
  if (!container) return;
  container.innerHTML = rows.map((row) => `<article class="bi-causal-drilldown-card">
    <h3>${escapeHtml(row.activity || 'Actividad sin nombre')}</h3>
    <dl class="bi-causal-drilldown-card__fields">
      ${renderCausalField('Proyecto', row.project)}${renderCausalField('Semana', row.week)}
      ${renderCausalField('Categoría', row.category)}${renderCausalField('Causa', row.cause)}
      ${renderCausalField('Observación', row.observation)}${renderCausalField('Crítica', causalCriticalLabel(row.critical))}
      ${renderCausalField('Responsable AIA', row.responsible)}${renderCausalField('Subcontratista', row.subcontractor)}
      ${renderCausalField('Ubicación', row.location)}
      ${type === 'cnc'
        ? `${renderCausalField('Compromiso', causalQuantityLabel(row.committedQuantity, row.unit))}${renderCausalField('Ejecución real', causalQuantityLabel(row.executedQuantity, row.unit))}${renderCausalField('Cumplimiento', formatPercent(row.completionPct))}${renderCausalField('Brecha', causalGapLabel(row))}`
        : `${renderCausalField('Inicio planificado', formatShortDate(row.startDate))}${renderCausalField('Urgencia', causalStartStatusLabel(row.startStatus, row.daysToStart))}`}
    </dl>
    <div class="bi-causal-drilldown-card__decision" data-priority="${escapeHtml(row.priority || 'monitor')}">
      <p><strong>Impacto</strong>${escapeHtml(row.impact || '--')}</p>
      <p><strong>Acción recomendada</strong>${escapeHtml(row.recommendedAction || '--')}</p>
    </div>
  </article>`).join('');
}

function renderCncExecution(row) {
  return `<strong>Compromiso: ${escapeHtml(causalQuantityLabel(row.committedQuantity, row.unit))}</strong><br>
    <small>Real: ${escapeHtml(causalQuantityLabel(row.executedQuantity, row.unit))}</small><br>
    <small>Cumplimiento: ${escapeHtml(formatPercent(row.completionPct))}</small><br>
    <small>Brecha: ${escapeHtml(causalGapLabel(row))}</small>`;
}

function causalQuantityLabel(value, unit) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '--';
  const suffix = String(unit || '').trim();
  return `${formatChartTooltipValue(number)}${suffix ? ` ${suffix}` : ''}`;
}

function causalGapLabel(row) {
  const quantity = causalQuantityLabel(row.shortfallQuantity, row.unit);
  const percent = formatPercent(row.shortfallPct);
  if (quantity === '--' && percent === '--') return '--';
  return quantity === '--' ? percent : `${quantity} (${percent})`;
}

function causalStartStatusLabel(status, days) {
  const amount = finiteNumber(days);
  if (status === 'overdue') return `Inicio vencido hace ${formatInteger(Math.abs(amount))} días`;
  if (status === 'due_today') return 'Inicio planificado para el corte';
  if (status === 'next_7_days') return `Inicia en ${formatInteger(amount)} días`;
  if (status === 'future') return `Inicia en ${formatInteger(amount)} días`;
  return 'Sin fecha comparable';
}

function syncCausalLoadMore(visible) {
  const button = document.getElementById('programa-causal-drilldown-load-more');
  if (button) button.hidden = !visible;
}

function loadMoreCausalRecords() {
  const state = BI.causalDrilldown;
  if (!state.isOpen || !state.endpoint || !state.pagination?.has_more) return;
  const button = document.getElementById('programa-causal-drilldown-load-more');
  clearCausalLoadMoreError();
  if (button) button.disabled = true;
  const requestId = ++BI.causalDrilldown.requestId;
  fetchCausalDrilldownPayload(state.endpoint, state.interaction, state.category, state.pagination.next_offset, false)
    .then((payload) => {
      if (isActiveCausalRequest(requestId)) renderCausalDrilldownPayload(payload, state.type, true);
    })
    .catch(() => {
      if (isActiveCausalRequest(requestId)) renderCausalLoadMoreError();
    })
    .finally(() => {
      if (button) button.disabled = false;
    });
}

function renderCausalField(label, value) {
  return `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(String(value || '').trim() || 'Sin asignar')}</dd></div>`;
}

function causalCriticalLabel(value) {
  if (value === null || value === undefined || value === '') return 'Sin clasificar';
  return value === true || ['1', 'si', 'sí', 'true'].includes(String(value).toLowerCase()) ? 'Sí' : 'No';
}

function causalOperationalUrl(type, row = {}) {
  const directLink = safeOperationalUrl(row.operationalLink);
  if (directLink) return directLink;
  const config = causalDrilldownConfig(type);
  const url = new URL(config?.modulePath || '/programacion-semanal', window.location.origin);
  const projectId = row.projectId || BI.filters.projects[0];
  const week = row.week || BI.filters.semana;
  if (projectId) url.searchParams.set('project_id', projectId);
  if (week) url.searchParams.set('semana', week);
  if (row.category) url.searchParams.set('categoria', row.category);
  if (row.activityId) url.searchParams.set('actividad_id', row.activityId);
  return `${url.pathname}${url.search}`;
}

function safeOperationalUrl(value) {
  try {
    const url = new URL(String(value || ''), window.location.origin);
    return url.origin === window.location.origin ? `${url.pathname}${url.search}${url.hash}` : '';
  } catch (_error) {
    return '';
  }
}

function setCausalDrilldownLoading(visible) {
  const loading = document.getElementById('programa-causal-drilldown-loading');
  if (loading) loading.hidden = !visible;
}

function setCausalDrilldownEmpty(visible, message = '') {
  const empty = document.getElementById('programa-causal-drilldown-empty');
  if (!empty) return;
  if (!empty.dataset.defaultText) empty.dataset.defaultText = empty.textContent.trim();
  empty.textContent = message || empty.dataset.defaultText;
  empty.hidden = !visible;
}

function setCausalDrilldownResultsVisible(visible) {
  ['programa-causal-drilldown-table', 'programa-causal-drilldown-cards'].forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.hidden = !visible;
  });
}

function clearCausalDrilldownResults() {
  const body = document.getElementById('programa-causal-drilldown-body');
  const cards = document.getElementById('programa-causal-drilldown-cards');
  if (body) body.innerHTML = '';
  if (cards) cards.innerHTML = '';
}

function renderCausalDrilldownError() {
  setCausalDrilldownResultsVisible(false);
  syncCausalLoadMore(false);
  clearCausalDrilldownResults();
  setCausalDrilldownEmpty(true, 'No fue posible consultar los registros para los filtros activos.');
}

function renderCausalLoadMoreError() {
  const button = document.getElementById('programa-causal-drilldown-load-more');
  if (!button) return;
  button.hidden = false;
  button.setAttribute('aria-describedby', 'programa-causal-drilldown-load-more-error');
  let error = document.getElementById('programa-causal-drilldown-load-more-error');
  if (!error) {
    error = document.createElement('p');
    error.id = 'programa-causal-drilldown-load-more-error';
    error.className = 'bi-causal-drilldown__load-error';
    error.setAttribute('role', 'alert');
    button.insertAdjacentElement('beforebegin', error);
  }
  error.textContent = 'No fue posible cargar la siguiente página. Los registros visibles se conservaron; vuelve a intentar.';
}

function clearCausalLoadMoreError() {
  const button = document.getElementById('programa-causal-drilldown-load-more');
  button?.removeAttribute('aria-describedby');
  document.getElementById('programa-causal-drilldown-load-more-error')?.remove();
}

function wireProgressDrilldownEvents() {
  const card = document.getElementById('programa-gauge-card');
  const trigger = document.getElementById('programa-gauge-drilldown-trigger');
  const modal = document.getElementById('programa-gauge-drilldown');
  if (card && card.dataset.progressBound !== '1') {
    card.addEventListener('dblclick', (event) => {
      if (!(event.target instanceof Element && event.target.closest('#programa-gauge-drilldown-trigger'))) openProgressDrilldown();
    });
    card.dataset.progressBound = '1';
  }
  if (trigger && trigger.dataset.biBound !== '1') {
    trigger.addEventListener('click', openProgressDrilldown); trigger.dataset.biBound = '1';
  }
  if (modal && modal.dataset.biBound !== '1') {
    modal.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.closest('[data-bi-progress-close]')) closeProgressDrilldown();
    });
    modal.addEventListener('keydown', handleProgressDrilldownKeydown);
    modal.dataset.biBound = '1';
  }
  document.getElementById('programa-progress-tab-missing')?.addEventListener('click', () => setProgressMode('missing'));
  document.getElementById('programa-progress-tab-earned')?.addEventListener('click', () => setProgressMode('earned'));
  document.getElementById('programa-gauge-drilldown-group-by')?.addEventListener('change', (event) => {
    BI.progressDrilldown.groupBy = event.target.value; renderProgressDrilldown();
  });
  document.getElementById('programa-gauge-drilldown-critical-only')?.addEventListener('change', (event) => {
    BI.progressDrilldown.criticalOnly = event.target.checked; reloadProgressDrilldown();
  });
  document.getElementById('programa-gauge-drilldown-load-more')?.addEventListener('click', loadMoreProgressDrilldown);
}

function openProgressDrilldown(event = null) {
  const chart = chartPayload(BI.dataByView['programa-general'] || {}, 'programa-gauge');
  const modal = document.getElementById('programa-gauge-drilldown');
  const trigger = document.getElementById('programa-gauge-drilldown-trigger');
  const endpoint = trigger?.dataset.detailEndpoint || chart?.interaction?.detail_endpoint;
  if (!modal || !endpoint) return;
  BI.progressDrilldown.previousFocus = document.activeElement;
  BI.progressDrilldown.expandedTrigger = event?.currentTarget instanceof HTMLElement ? event.currentTarget : trigger;
  BI.progressDrilldown.endpoint = endpoint;
  BI.progressDrilldown.pagination = null;
  BI.progressDrilldown.payload = null;
  BI.progressDrilldown.isOpen = true;
  modal.hidden = false;
  BI.progressDrilldown.expandedTrigger?.setAttribute('aria-expanded', 'true');
  syncProgressLoadMore(false);
  clearProgressLoadMoreError();
  requestAnimationFrame(() => document.getElementById('programa-gauge-drilldown-close')?.focus({ preventScroll: true }));
  reloadProgressDrilldown();
}

function reloadProgressDrilldown() {
  const endpoint = BI.progressDrilldown.endpoint;
  if (!endpoint || !BI.progressDrilldown.isOpen) return;
  BI.progressDrilldown.pagination = null;
  BI.progressDrilldown.payload = null;
  clearProgressLoadMoreError();
  syncProgressLoadMore(false);
  setProgressLoading(true);
  const requestId = ++BI.progressDrilldown.requestId;
  fetchComplianceDrilldownPayload(endpoint, {
    limit: 50,
    offset: 0,
    sort: BI.progressDrilldown.mode,
    critical_only: BI.progressDrilldown.criticalOnly ? 1 : 0,
  }).then((payload) => {
    if (BI.progressDrilldown.requestId !== requestId) return;
    mergeProgressDrilldownPage(payload, false);
  }).catch(() => {
    if (BI.progressDrilldown.requestId !== requestId || !BI.progressDrilldown.isOpen) return;
    renderProgressError();
  }).finally(() => {
    if (BI.progressDrilldown.requestId !== requestId || !BI.progressDrilldown.isOpen) return;
    setProgressLoading(false);
  });
}

function closeProgressDrilldown(restoreFocus = true) {
  const modal = document.getElementById('programa-gauge-drilldown');
  if (!modal || modal.hidden) return false;
  modal.hidden = true; BI.progressDrilldown.isOpen = false; BI.progressDrilldown.requestId++;
  const trigger = document.getElementById('programa-gauge-drilldown-trigger');
  BI.progressDrilldown.expandedTrigger?.setAttribute('aria-expanded', 'false');
  const target = BI.progressDrilldown.previousFocus instanceof HTMLElement ? BI.progressDrilldown.previousFocus : trigger;
  if (restoreFocus) target?.focus({ preventScroll: true });
  BI.progressDrilldown.previousFocus = null;
  BI.progressDrilldown.expandedTrigger = null;
  return true;
}

function setProgressMode(mode) {
  if (!['missing', 'earned'].includes(mode)) return;
  BI.progressDrilldown.mode = mode;
  document.getElementById('programa-progress-tab-missing')?.setAttribute('aria-pressed', mode === 'missing' ? 'true' : 'false');
  document.getElementById('programa-progress-tab-earned')?.setAttribute('aria-pressed', mode === 'earned' ? 'true' : 'false');
  reloadProgressDrilldown();
}

function handleProgressDrilldownKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeProgressDrilldown();
    return;
  }
  if (event.key !== 'Tab') return;

  const modal = document.getElementById('programa-gauge-drilldown');
  const focusable = Array.from(modal?.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])') || [])
    .filter((element) => element instanceof HTMLElement)
    .filter((element) => !element.hidden && !element.hasAttribute('disabled') && element.tabIndex >= 0);
  if (!focusable.length) {
    event.preventDefault();
    return;
  }

  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  const active = document.activeElement;
  if (event.shiftKey && (active === first || !modal.contains(active))) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && active === last) {
    event.preventDefault();
    first.focus();
  }
}

function setProgressLoading(visible) {
  const loading = document.getElementById('programa-gauge-drilldown-loading');
  if (loading) loading.hidden = !visible;
}

function progressVisibleActivities() {
  const rows = Array.isArray(BI.progressDrilldown.payload?.activities) ? BI.progressDrilldown.payload.activities : [];
  return rows.filter((row) => !BI.progressDrilldown.criticalOnly || row.critical)
    .filter((row) => BI.progressDrilldown.mode === 'earned' ? Number(row.real_contribution_pp) > 0 : Number(row.recoverable_pp) > 0)
    .sort((a, b) => BI.progressDrilldown.mode === 'earned'
      ? Number(b.real_contribution_pp) - Number(a.real_contribution_pp)
      : Number(b.recoverable_pp) - Number(a.recoverable_pp));
}

function renderProgressDrilldown() {
  const payload = BI.progressDrilldown.payload;
  if (!payload) return;
  renderProgressSummary(payload.summary || {});
  const rows = progressVisibleActivities();
  document.getElementById('programa-gauge-drilldown-empty').hidden = rows.length > 0;
  renderProgressTable(rows); renderProgressCards(rows);
  syncProgressLoadMore(Boolean(BI.progressDrilldown.pagination?.has_more));
}

function renderProgressSummary(summary) {
  const container = document.getElementById('programa-gauge-drilldown-summary');
  if (container) container.innerHTML = [
    `Avance real: ${formatPercent(summary.real_pct)}`,
    `Teórico al corte: ${formatPercent(summary.theoretical_pct)}`,
    `Brecha: ${formatSignedPp(summary.gap_pp)}`,
    `Pendiente: ${formatPercent(Math.max(0, 100 - finiteNumber(summary.real_pct)))}`,
  ].map((text) => `<span>${escapeHtml(text)}</span>`).join('');
  const shown = Array.isArray(BI.progressDrilldown.payload?.activities) ? BI.progressDrilldown.payload.activities.length : 0;
  const total = Number(BI.progressDrilldown.pagination?.total || shown);
  setText('programa-gauge-drilldown-explanation', `Identifica qué actividades aportan al avance y cuáles pueden recuperar más puntos frente al plan. Mostrando ${formatInteger(shown)} de ${formatInteger(total)} actividades.`);
}

function mergeProgressDrilldownPage(payload, append) {
  const incoming = Array.isArray(payload?.activities) ? payload.activities : [];
  const current = append && Array.isArray(BI.progressDrilldown.payload?.activities)
    ? BI.progressDrilldown.payload.activities
    : [];
  const activities = [...new Map([...current, ...incoming].map((row) => [String(row.activity_key || `${row.project_id}:${row.unique_id}`), row])).values()];
  BI.progressDrilldown.pagination = payload?.pagination || null;
  BI.progressDrilldown.payload = { ...payload, activities };
  clearProgressLoadMoreError();
  renderProgressDrilldown();
}

function loadMoreProgressDrilldown() {
  const endpoint = BI.progressDrilldown.endpoint;
  const pagination = BI.progressDrilldown.pagination;
  if (!endpoint || !pagination?.has_more) return;
  syncProgressLoadMore(true, true);
  clearProgressLoadMoreError();
  const requestId = ++BI.progressDrilldown.requestId;
  fetchComplianceDrilldownPayload(endpoint, {
    limit: pagination.limit || 50,
    offset: pagination.next_offset || 0,
    sort: BI.progressDrilldown.mode,
    critical_only: BI.progressDrilldown.criticalOnly ? 1 : 0,
  }).then((payload) => {
    if (BI.progressDrilldown.requestId !== requestId) return;
    mergeProgressDrilldownPage(payload, true);
  }).catch(() => {
    if (BI.progressDrilldown.requestId !== requestId) return;
    renderProgressLoadMoreError();
    syncProgressLoadMore(true, false);
  });
}

function syncProgressLoadMore(visible, disabled = false) {
  const button = document.getElementById('programa-gauge-drilldown-load-more');
  if (!button) return;
  button.hidden = !visible;
  button.disabled = disabled;
}

function renderProgressLoadMoreError() {
  const error = document.getElementById('programa-gauge-drilldown-load-more-error');
  if (!error) return;
  error.textContent = 'No fue posible cargar la siguiente página. Los registros visibles se conservaron.';
  error.hidden = false;
}

function clearProgressLoadMoreError() {
  const error = document.getElementById('programa-gauge-drilldown-load-more-error');
  if (!error) return;
  error.textContent = '';
  error.hidden = true;
}

function progressGroupLabel(row) {
  const field = BI.progressDrilldown.groupBy;
  return String(row?.[field] || 'Sin asignar');
}

function renderProgressTable(rows) {
  const body = document.getElementById('programa-gauge-drilldown-body');
  if (!body) return;
  body.innerHTML = rows.map((row) => `<tr data-activity-key="${escapeHtml(row.activity_key || `${row.project_id}:${row.unique_id}`)}">
    <td><strong>${escapeHtml(row.activity)}</strong><br><small>${escapeHtml(progressGroupLabel(row))}${row.critical ? ' · Ruta crítica' : ''}</small></td>
    <td>${escapeHtml(formatShortDate(row.planned_finish))}</td>
    <td>${escapeHtml(formatPercent(row.weight_pct))}</td>
    <td>${escapeHtml(formatPercent(row.real_pct))}</td>
    <td>${escapeHtml(formatPercent(row.planned_pct))}</td>
    <td>${escapeHtml(formatSignedPp(BI.progressDrilldown.mode === 'earned' ? row.real_contribution_pp : -row.recoverable_pp))}</td>
    <td>${renderProgressOwnership(row)}<small>Acción: ${escapeHtml(progressAction(row))}</small></td>
  </tr>`).join('');
}

function renderProgressCards(rows) {
  const container = document.getElementById('programa-gauge-drilldown-cards');
  if (!container) return;
  container.innerHTML = rows.map((row) => `<article class="programa-gauge-drilldown-card" data-activity-key="${escapeHtml(row.activity_key || `${row.project_id}:${row.unique_id}`)}">
    <div><h3>${escapeHtml(row.activity)}</h3><p>${escapeHtml(progressGroupLabel(row))}${row.critical ? ' · Ruta crítica' : ''}</p></div>
    <div class="programa-gauge-drilldown-card__metrics">
      <span>Peso: ${escapeHtml(formatPercent(row.weight_pct))}</span><span>Real: ${escapeHtml(formatPercent(row.real_pct))}</span>
      <span>Teórico: ${escapeHtml(formatPercent(row.planned_pct))}</span><span>${BI.progressDrilldown.mode === 'earned' ? 'Aporta' : 'Falta'}: ${escapeHtml(formatPercent(BI.progressDrilldown.mode === 'earned' ? row.real_contribution_pp : row.recoverable_pp))}</span>
    </div>${renderProgressOwnership(row)}<p><strong>Bloqueo:</strong> ${escapeHtml(row.blocker || 'Sin bloqueo registrado')}</p>
    <p><strong>Acción:</strong> ${escapeHtml(progressAction(row))}</p>
  </article>`).join('');
}

function renderProgressOwnership(row) {
  return `<div class="bi-drilldown__responsibles">
    ${renderComplianceResponsibleLine('Responsable AIA', row.responsible)}
    ${renderComplianceResponsibleLine('Subcontratista', row.subcontractor)}
  </div>`;
}

function progressAction(row) {
  if (BI.progressDrilldown.mode === 'earned') return 'Mantener el frente activo y proteger su ritmo de producción.';
  if (row.blocker && row.blocker !== 'Sin bloqueo registrado') return `Resolver ${String(row.blocker).toLowerCase()} y recuperar producción.`;
  return row.critical ? 'Priorizar un plan de recuperación de ruta crítica.' : 'Acordar producción semanal para cerrar la brecha.';
}

function renderProgressError() {
  BI.progressDrilldown.payload = { summary: {}, activities: [] }; renderProgressDrilldown();
  setText('programa-gauge-drilldown-empty', 'No fue posible calcular la composición para los filtros activos.');
}

function wireComplianceDrilldownEvents() {
  const card = document.getElementById('programa-compliance-card');
  if (card && card.dataset.biBound !== '1') {
    card.addEventListener('dblclick', (event) => {
      if (event.target instanceof Element && event.target.closest('#programa-compliance-drilldown-trigger')) {
        return;
      }
      openComplianceDrilldown('card');
    });
    card.dataset.biBound = '1';
  }

  const trigger = document.getElementById('programa-compliance-drilldown-trigger');
  if (trigger && trigger.dataset.biBound !== '1') {
    trigger.addEventListener('click', () => openComplianceDrilldown('button'));
    trigger.dataset.biBound = '1';
  }

  const modal = document.getElementById('programa-compliance-drilldown');
  if (modal && modal.dataset.biBound !== '1') {
    modal.addEventListener('click', (event) => {
      const closeTarget = event.target instanceof Element ? event.target.closest('[data-bi-drilldown-close]') : null;
      if (closeTarget) {
        closeComplianceDrilldown();
      }
    });
    modal.addEventListener('keydown', handleComplianceDrilldownKeydown);
    modal.dataset.biBound = '1';
  }
}

function openComplianceDrilldown(source) {
  const data = BI.dataByView['programa-general'] || {};
  const gaugeChart = chartPayload(data, 'programa-gauge');
  const complianceChart = chartPayload(data, 'programa-compliance');
  const trigger = document.getElementById('programa-compliance-drilldown-trigger');
  const detailEndpoint = trigger?.dataset.detailEndpoint || complianceChart?.interaction?.detail_endpoint;
  const modal = document.getElementById('programa-compliance-drilldown');

  if (!modal || !detailEndpoint) return;

  BI.drilldown.previousFocus = document.getElementById('programa-compliance-drilldown-trigger');
  BI.drilldown.isOpen = true;
  BI.drilldown.source = source;
  modal.hidden = false;

  syncProgramaComplianceUI(gaugeChart, complianceChart);
  setComplianceDrilldownLoading(true);
  setComplianceDrilldownEmpty(false);
  clearComplianceDrilldownResults();
  setComplianceDrilldownExpanded(true);
  focusComplianceDrilldown();

  const requestId = ++BI.drilldown.requestId;
  fetchComplianceDrilldownPayload(detailEndpoint)
    .then((payload) => {
      if (!isActiveComplianceRequest(requestId)) return;
      renderComplianceDrilldownPayload(payload, gaugeChart, complianceChart);
    })
    .catch((error) => {
      if (!isActiveComplianceRequest(requestId)) return;
      console.error('[BI] Failed to fetch compliance drilldown', error);
      renderComplianceDrilldownError();
    })
    .finally(() => {
      if (!isActiveComplianceRequest(requestId)) return;
      setComplianceDrilldownLoading(false);
    });
}

function closeComplianceDrilldown(restoreFocus = true) {
  const modal = document.getElementById('programa-compliance-drilldown');
  if (!modal || modal.hidden) return false;

  BI.drilldown.requestId += 1;
  BI.drilldown.isOpen = false;
  modal.hidden = true;
  setComplianceDrilldownExpanded(false);
  setComplianceDrilldownLoading(false);

  if (restoreFocus) {
    const fallback = document.getElementById('programa-compliance-drilldown-trigger');
    const target = BI.drilldown.previousFocus && document.contains(BI.drilldown.previousFocus)
      ? BI.drilldown.previousFocus
      : fallback;
    if (target && typeof target.focus === 'function') {
      target.focus({ preventScroll: true });
    }
  }

  BI.drilldown.previousFocus = null;
  return true;
}

function handleComplianceDrilldownKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    closeComplianceDrilldown();
    return;
  }
  if (event.key !== 'Tab') return;

  const focusable = complianceDrilldownFocusableElements();
  if (!focusable.length) {
    event.preventDefault();
    return;
  }

  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  const active = document.activeElement;

  if (event.shiftKey) {
    if (active === first || !event.currentTarget.contains(active)) {
      event.preventDefault();
      last.focus();
    }
    return;
  }

  if (active === last) {
    event.preventDefault();
    first.focus();
  }
}

function fetchComplianceDrilldownPayload(endpoint, params = {}) {
  const url = new URL(endpoint, window.location.origin);
  appendFilterParams(url.searchParams, getFilterPayload());
  Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, String(value)));
  return fetch(url, { credentials: 'same-origin' })
    .then((response) => {
      if (!response.ok) {
        throw new Error('HTTP ' + response.status);
      }
      return response.json();
    });
}

function renderComplianceDrilldownPayload(payload, gaugeChart, complianceChart) {
  const metrics = complianceMetricsFromPayload(payload, gaugeChart, complianceChart);
  renderComplianceDrilldownSummary(metrics);

  const activities = Array.isArray(payload?.activities) ? payload.activities : [];
  if (!activities.length) {
    setComplianceDrilldownResultsVisible(false);
    setComplianceDrilldownEmpty(true);
    clearComplianceDrilldownResults();
    return;
  }

  setComplianceDrilldownEmpty(false);
  setComplianceDrilldownResultsVisible(true);
  renderComplianceDrilldownTable(activities);
  renderComplianceDrilldownCards(activities);
}

function renderComplianceDrilldownError() {
  setComplianceDrilldownResultsVisible(false);
  clearComplianceDrilldownResults();
  setComplianceDrilldownEmpty(true, 'No fue posible consultar las actividades para los filtros activos.');
}

function renderComplianceDrilldownTable(rows) {
  const body = document.getElementById('programa-compliance-drilldown-body');
  if (!body) return;

  body.innerHTML = rows.map((row) => (
    `<tr>
      <td><strong>${escapeHtml(row.activity || 'Actividad sin nombre')}</strong></td>
      <td>${escapeHtml(formatShortDate(row.planned_finish))}</td>
      <td>${escapeHtml(formatPercent(row.planned_pct))}</td>
      <td>${escapeHtml(formatPercent(row.real_pct))}</td>
      <td>${escapeHtml(formatSignedPp(row.gap_pp))}</td>
      <td>
        <strong>${escapeHtml(row.cause || '--')}</strong>
        ${renderComplianceResponsibles(row)}
      </td>
      <td>${escapeHtml(row.implication || row.cause || '--')}</td>
    </tr>`
  )).join('');
}

function renderComplianceDrilldownCards(rows) {
  const container = document.getElementById('programa-compliance-drilldown-cards');
  if (!container) return;

  container.innerHTML = rows.map((row) => (
    `<article class="programa-compliance-drilldown-card">
      <div>
        <h3>${escapeHtml(row.activity || 'Actividad sin nombre')}</h3>
        <p>${escapeHtml(formatShortDate(row.planned_finish))}</p>
      </div>
      <div class="programa-compliance-drilldown-card__metrics">
        <span>Teórico: ${escapeHtml(formatPercent(row.planned_pct))}</span>
        <span>Real: ${escapeHtml(formatPercent(row.real_pct))}</span>
        <span>Brecha: ${escapeHtml(formatSignedPp(row.gap_pp))}</span>
      </div>
      <p><strong>${escapeHtml(row.cause || 'Avance menor al plan')}</strong>${row.critical ? ' · Ruta crítica' : ''}${row.delay_days > 0 ? ` · ${escapeHtml(formatInteger(row.delay_days))} días` : ''}</p>
      <p>${escapeHtml(row.implication || row.cause || '--')}</p>
      ${renderComplianceResponsibles(row)}
    </article>`
  )).join('');
}

function renderComplianceResponsibles(row) {
  return `<div class="bi-drilldown__responsibles">
    ${renderComplianceResponsibleLine('Responsable AIA', row?.responsible)}
    ${renderComplianceResponsibleLine('Subcontratista', row?.subcontractor)}
  </div>`;
}

function renderComplianceResponsibleLine(label, value) {
  const normalizedValue = String(value || '').trim() || 'Sin asignar';
  return `<p class="bi-drilldown__responsible-line"><span class="bi-drilldown__responsible-label">${escapeHtml(label)}:</span> ${escapeHtml(normalizedValue)}</p>`;
}

function syncProgramaComplianceUI(gaugeChart, complianceChart) {
  const metrics = complianceMetricsFromPayload({}, gaugeChart, complianceChart);
  const trigger = document.getElementById('programa-compliance-drilldown-trigger');
  const progressTrigger = document.getElementById('programa-gauge-drilldown-trigger');
  const complianceEndpoint = complianceChart?.interaction?.detail_endpoint || trigger?.dataset.detailEndpoint || '';
  const progressEndpoint = gaugeChart?.interaction?.detail_endpoint || progressTrigger?.dataset.detailEndpoint || '';

  renderProgramaComplianceTexts(metrics);

  if (trigger) {
    trigger.disabled = !complianceEndpoint;
    trigger.dataset.detailEndpoint = complianceEndpoint;
    trigger.setAttribute('aria-expanded', BI.drilldown.isOpen ? 'true' : 'false');
  }
  if (progressTrigger) {
    progressTrigger.disabled = !progressEndpoint;
    progressTrigger.dataset.detailEndpoint = progressEndpoint;
    progressTrigger.setAttribute('aria-expanded', BI.progressDrilldown.isOpen ? 'true' : 'false');
  }

}

function renderProgramaComplianceTexts(metrics) {
  setText('programa-gauge-value', formatPercent(metrics.real_pct));
  setText('programa-gauge-theoretical', `Teórico al corte ${formatPercent(metrics.theoretical_pct)}`);
  setText('programa-gauge-gap', `Brecha ${formatSignedPp(metrics.gap_pp)}`);
  setText('programa-compliance-value', formatPercent(metrics.compliance_pct));
  setText('programa-compliance-gap', `Brecha ${formatSignedPp(metrics.gap_pp)}`);
  setText('programa-compliance-explanation', complianceExplanationText(metrics.explanation, false) || 'Sin datos del corte.');
  renderSemanticRange('programa-gauge', metrics.range);
  renderSemanticRange('programa-compliance', metrics.compliance_range || metrics.range);
  renderComplianceDrilldownSummary(metrics);
}

function renderSemanticRange(prefix, range) {
  const key = ['critical', 'warning', 'success'].includes(range?.key) ? range.key : '';
  setText(`${prefix}-range`, range?.label || 'Sin clasificación');
  const card = document.getElementById(`${prefix}-card`);
  if (card) card.dataset.range = key;
  if (prefix === 'programa-gauge') {
    const basis = finiteNumber(range?.basis_value);
    const tolerance = finiteNumber(range?.tolerance_pct) || 5;
    const lowerLimit = 100 - tolerance;
    const upperLimit = 100 + tolerance;
    const reason = basis < lowerLimit
      ? `Rendimiento ${formatPercent(basis)} del avance esperado · por debajo de ${formatPercent(lowerLimit)}`
      : (basis > upperLimit
        ? `Rendimiento ${formatPercent(basis)} del avance esperado · supera ${formatPercent(upperLimit)}`
        : `Rendimiento ${formatPercent(basis)} del avance esperado · tolerancia ${formatPercent(lowerLimit)}–${formatPercent(upperLimit)}`);
    setText('programa-gauge-range-reason', reason);
  }
}

function renderComplianceDrilldownSummary(metrics) {
  const summary = document.getElementById('programa-compliance-drilldown-summary');
  const explanation = document.getElementById('programa-compliance-drilldown-explanation');
  if (summary) {
    summary.innerHTML = [
      `Avance real: ${escapeHtml(formatPercent(metrics.real_pct))}`,
      `Avance teórico: ${escapeHtml(formatPercent(metrics.theoretical_pct))}`,
      `Brecha: ${escapeHtml(formatSignedPp(metrics.gap_pp))}`,
    ].map((text) => `<span>${text}</span>`).join('');
  }
  if (explanation) {
    explanation.textContent = complianceExplanationText(metrics.explanation, true) || 'Sin explicación disponible para este corte.';
  }
}

function complianceMetricsFromPayload(payload, gaugeChart, complianceChart) {
  const chartMetrics = complianceChart?.metrics || gaugeChart?.metrics || {};
  const summary = payload?.summary && typeof payload.summary === 'object' ? payload.summary : {};
  const explanation = payload?.explanation ?? summary.explanation ?? chartMetrics.explanation ?? null;

  return {
    real_pct: finiteNumber(summary.real_pct ?? chartMetrics.real_pct),
    theoretical_pct: finiteNumber(summary.theoretical_pct ?? chartMetrics.theoretical_pct),
    compliance_pct: finiteNumber(summary.compliance_pct ?? chartMetrics.compliance_pct),
    gap_pp: finiteNumber(summary.gap_pp ?? chartMetrics.gap_pp),
    range: gaugeChart?.metrics?.range || null,
    compliance_range: complianceChart?.metrics?.range || null,
    explanation,
  };
}

function complianceExplanationText(explanation, includeMethod) {
  if (typeof explanation === 'string') return explanation.trim();
  if (!explanation || typeof explanation !== 'object') return '';

  const parts = [explanation.headline, explanation.implication];
  if (includeMethod && explanation.method) {
    parts.push(explanation.method);
  }
  return parts.filter(Boolean).join(' ').trim();
}

function setComplianceDrilldownExpanded(expanded) {
  const trigger = document.getElementById('programa-compliance-drilldown-trigger');
  if (trigger) {
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  }
}

function setComplianceDrilldownLoading(visible) {
  const loading = document.getElementById('programa-compliance-drilldown-loading');
  if (loading) {
    loading.hidden = !visible;
  }
  if (visible) {
    setComplianceDrilldownResultsVisible(false);
  }
}

function setComplianceDrilldownEmpty(visible, message = '') {
  const empty = document.getElementById('programa-compliance-drilldown-empty');
  if (!empty) return;
  if (!empty.dataset.defaultText) {
    empty.dataset.defaultText = empty.textContent.trim() || 'No hay actividades con brecha negativa para este corte.';
  }
  if (message) {
    empty.textContent = message;
  } else if (visible) {
    empty.textContent = empty.dataset.defaultText;
  }
  empty.hidden = !visible;
}

function clearComplianceDrilldownResults() {
  const body = document.getElementById('programa-compliance-drilldown-body');
  const cards = document.getElementById('programa-compliance-drilldown-cards');
  if (body) body.innerHTML = '';
  if (cards) cards.innerHTML = '';
}

function setComplianceDrilldownResultsVisible(visible) {
  const table = document.getElementById('programa-compliance-drilldown-table');
  const cards = document.getElementById('programa-compliance-drilldown-cards');
  if (table) {
    table.hidden = !visible;
  }
  if (cards) {
    cards.hidden = !visible;
  }
}

function focusComplianceDrilldown() {
  const focusTarget = document.getElementById('programa-compliance-drilldown-close');
  if (!focusTarget) return;
  window.requestAnimationFrame(() => {
    focusTarget.focus({ preventScroll: true });
  });
}

function complianceDrilldownFocusableElements() {
  const modal = document.getElementById('programa-compliance-drilldown');
  if (!modal) return [];

  return Array.from(modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
    .filter((element) => element instanceof HTMLElement)
    .filter((element) => !element.hidden && !element.hasAttribute('disabled'));
}

function isActiveComplianceRequest(requestId) {
  return BI.drilldown.isOpen && BI.drilldown.requestId === requestId;
}

function renderCurvaS(data) {
  const curvaChart = chartPayload(data, 'chart-curva-s');
  if (!curvaChart) return;
  renderLineChart('chart-curva-s', chartLabels(curvaChart), chartDatasets(curvaChart));
}

function renderIntermedia(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const body = document.getElementById('intermedia-body');
  if (!body) return;
  body.innerHTML = '';
  if (!rows.length) {
    body.innerHTML = '<tr><td class=\"p-4 text-center text-gray-400\" colspan=\"4\">Sin datos de programación intermedia.</td></tr>';
    return;
  }
  rows.forEach((row) => {
    const value = toNumber(row.value);
    const pct = Math.min(100, Math.max(0, value));
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class=\"p-2\">${escapeHtml(row.kpi || '--')}</td><td class=\"p-2\">${value}</td><td class=\"p-2\">${value}</td><td class=\"p-2\">${renderProgressBadge(pct)}</td>`;
    body.appendChild(tr);
  });
  const intermediaChart = chartPayload(data, 'chart-intermedia');
  if (intermediaChart) {
    renderBarChart('chart-intermedia', chartLabels(intermediaChart), chartDatasetData(intermediaChart), chartDatasetLabel(intermediaChart, 0, 'Cumplimiento'), chartDatasetColor(intermediaChart, 0, 'brand-primary-medium'));
  }
}

function renderSemanal(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const body = document.getElementById('semanal-body');
  const wrapper = document.getElementById('semanal-table-wrapper');
  if (!body || !wrapper) return;

  if (!rows.length) {
    wrapper.classList.add('hidden');
    return;
  }
  wrapper.classList.remove('hidden');
  body.innerHTML = '';
  rows.forEach((row) => {
    const pct = toNumber(row.value);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class=\"p-2\">${escapeHtml(row.kpi || '--')}</td><td class=\"p-2\">${escapeHtml(row.action || row.status || '--')}</td><td class=\"p-2\">${statusPill(pct > 75 ? 'completa' : 'pendiente')}</td><td class=\"p-2\">${renderProgressBadge(pct)}</td>`;
    body.appendChild(tr);
  });
  const pacChart = chartPayload(data, 'chart-semanal-pac');
  if (pacChart) {
    renderProgressDonut('chart-semanal-pac', chartLabels(pacChart), chartDatasetData(pacChart), false);
  }
}

// Pinta un tbody a partir de una lista, o el mensaje de vacío si no hay nada. Evita repetir
// cuatro veces el mismo bucle en el panel de compras.
function fillRows(id, items, columnas, celdas, vacio) {
  const body = document.getElementById(id);
  if (!body) return;
  if (!items.length) {
    body.innerHTML = `<tr><td class="p-4 text-center text-gray-400" colspan="${columnas}">${escapeHtml(vacio)}</td></tr>`;
    return;
  }
  body.innerHTML = '';
  items.forEach((item) => {
    const tr = document.createElement('tr');
    tr.innerHTML = celdas(item).map((c) => `<td class="p-2">${c}</td>`).join('');
    body.appendChild(tr);
  });
}

// Cubetas del horizonte, en el orden en que el tiempo corre. El nombre de cada una se escribe
// en semanas porque «sem1» es la clave del servicio, no algo que un director deba descifrar.
// El color no es decorativo: es una rampa de urgencia, de rojo a verde, en el mismo orden en que
// corre el tiempo. `status-critical` NO sirve aquí — es el rosa pálido del texto de estado, no un
// color de serie; el rojo de datos es `critical`.
const PDC_HORIZONTE = [
  { clave: 'vencido', label: 'Ya vencido', color: 'critical' },
  { clave: 'sem1', label: 'Esta semana', color: 'brand-construction-medium' },
  { clave: 'sem2', label: 'En 2 semanas', color: 'brand-construction' },
  { clave: 'sem3', label: 'En 3 semanas', color: 'brand-aqua' },
  { clave: 'sem6', label: 'En 6 semanas', color: 'brand-primary' },
  { clave: 'sin_fecha', label: 'Sin fecha', color: 'neutral-muted' },
];

/**
 * Barras con varias series, en horizontal o apiladas. Los helpers que ya existían pintan una
 * sola serie vertical (renderBarChart) o series-línea (renderLineLikeBars); el panel de compras
 * necesita ambas cosas a la vez: pendiente vs vencido, apilado, y con las etiquetas de paso o
 * de responsable en el eje largo para que se lean sin girar la cabeza.
 */
function renderGroupedBarChart(canvasId, labels, datasets, options = {}) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas);
  const theme = chartTheme();
  const horizontal = options.horizontal === true;
  const stacked = options.stacked === true;
  const norm = datasets.map((dataset) => ({
    label: dataset.label || '',
    data: sanitizeChartData(dataset.data),
    backgroundColor: Array.isArray(dataset.color)
      ? dataset.color.map(resolveChartColor)
      : resolveChartColor(dataset.color),
    borderRadius: 4,
  }));
  syncChartDataTable(canvasId, labels, norm);

  // Con `max` el eje no se autoescala: un porcentaje siempre se lee contra el 100, no contra el
  // valor más alto del corte, que haría ver «lleno» un 40 %.
  const valueAxis = { beginAtZero: true, stacked, ticks: { precision: 0 } };
  if (typeof options.max === 'number') valueAxis.max = options.max;
  const categoryAxis = { stacked };
  const scales = chartCartesianScales(theme, horizontal
    ? { x: valueAxis, y: categoryAxis }
    : { x: categoryAxis, y: valueAxis });

  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets = norm;
    BI.charts[canvasId].options.scales = scales;
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: { labels, datasets: norm },
    options: {
      indexAxis: horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: options.aspectRatio || 2.1,
      animation: false,
      resizeDelay: 200,
      plugins: {
        legend: chartLegendOptions(theme, { display: norm.length > 1 }),
        tooltip: chartTooltipOptions(theme),
      },
      scales,
    },
  });
}

// El titular del reporte. Sale de los datos del corte, no de una plantilla fija: si no hay nada
// vencido lo dice, en vez de dejar una frase alarmista que el tablero contradice.
function pdcTitular(items, breakdown) {
  const vencidos = items.reduce((sum, o) => sum + toNumber(o.vencidos), 0);
  const enRiesgo = items.reduce((sum, o) => sum + toNumber(o.en_riesgo), 0);
  const obrasConVencidos = items.filter((o) => toNumber(o.vencidos) > 0).length;

  const pasos = Object.entries(breakdown.por_paso || {});
  const cuello = pasos.reduce(
    (peor, [nombre, v]) => (toNumber(v.vencidos) > toNumber(peor.vencidos) ? { nombre, vencidos: v.vencidos } : peor),
    { nombre: '', vencidos: 0 },
  );

  if (!items.length) {
    return { titular: 'Todavía no hay plan de compras que seguir.', sub: 'Arma los paquetes y amárralos al cronograma para que este reporte tenga qué contar.' };
  }
  if (vencidos === 0) {
    return {
      titular: enRiesgo > 0
        ? `Nada vencido, pero ${formatInteger(enRiesgo)} compras vencen en las próximas 3 semanas.`
        : 'Ninguna compra vencida ni en riesgo a 3 semanas.',
      sub: enRiesgo > 0 ? 'Es el momento barato de moverlas: todavía hay margen.' : 'El plan va al día en el corte de hoy.',
    };
  }

  const dondeObra = obrasConVencidos === 1 ? 'en 1 obra' : `en ${formatInteger(obrasConVencidos)} obras`;
  return {
    titular: `${formatInteger(vencidos)} compras ya vencidas ${dondeObra}, y ${formatInteger(enRiesgo)} más vencen en 3 semanas.`,
    sub: toNumber(cuello.vencidos) > 0
      ? `El cuello está en «${cuello.nombre}»: ahí se acumulan ${formatInteger(cuello.vencidos)} pasos vencidos.`
      : 'Los vencimientos están repartidos, sin un paso que concentre el atraso.',
  };
}

function renderPDC(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const items = Array.isArray(data.pdc_items) ? data.pdc_items : [];
  const breakdown = data.pdc_breakdown || {};

  // Decisión 5 del spec B3: este panel ignora el selector de semana y siempre responde «hoy».
  // La fecha la pone el SERVIDOR, no el navegador: dos usuarios en husos distintos tienen que
  // ver el mismo vencido. El rótulo existe para que ignorar el selector no parezca un fallo.
  const fecha = document.getElementById('pdc-fecha-corte');
  if (fecha) {
    const hoy = items.length ? items[0].hoy : '';
    fecha.textContent = hoy ? `Al ${hoy} · no depende de la semana seleccionada` : '';
  }

  const historia = pdcTitular(items, breakdown);
  setText('pdc-titular', historia.titular);
  setText('pdc-subtitular', historia.sub);

  setText('pdc-kpi-vencidos', formatInteger(items.reduce((s, o) => s + toNumber(o.vencidos), 0)));
  setText('pdc-kpi-riesgo', formatInteger(items.reduce((s, o) => s + toNumber(o.en_riesgo), 0)));
  setText('pdc-kpi-sin-mirar', formatInteger(items.reduce((s, o) => s + toNumber(o.sin_mirar), 0)));

  // «Más adelante» se cuenta pero no se dibuja: con cientos de pasos a más de seis semanas, esa
  // barra aplasta a las que urgen y el gráfico deja de responder «cuánto tiempo queda». Va como
  // nota al pie, que es donde ese dato hace su trabajo sin robar la escala.
  const totales = breakdown.totales || {};
  const lejanos = toNumber(totales.adelante);
  setText('pdc-horizonte-nota', lejanos > 0
    ? `Otros ${formatInteger(lejanos)} pasos vencen más allá de 6 semanas; quedan fuera del gráfico para no aplastar la escala de lo urgente.`
    : '');

  renderGroupedBarChart(
    'pdc-horizonte',
    PDC_HORIZONTE.map((b) => b.label),
    [{ label: 'Pasos pendientes', data: PDC_HORIZONTE.map((b) => toNumber(totales[b.clave])), color: PDC_HORIZONTE.map((b) => b.color) }],
    { aspectRatio: 2.6 },
  );

  const pasosOrdenados = Object.entries(breakdown.por_paso || {})
    .sort((a, b) => toNumber(b[1].pendientes) - toNumber(a[1].pendientes));
  renderGroupedBarChart(
    'pdc-paso-chart',
    pasosOrdenados.map(([nombre]) => nombre),
    [
      // «Vencidos» es un subconjunto de «pendientes»: apilar el crudo contaría doble.
      { label: 'Vencidos', data: pasosOrdenados.map(([, v]) => toNumber(v.vencidos)), color: 'critical' },
      { label: 'Aún a tiempo', data: pasosOrdenados.map(([, v]) => Math.max(0, toNumber(v.pendientes) - toNumber(v.vencidos))), color: 'brand-aqua' },
    ],
    { horizontal: true, stacked: true, aspectRatio: 1.5 },
  );

  const respOrdenados = (Array.isArray(breakdown.por_responsable) ? breakdown.por_responsable : []).slice(0, 10);
  renderGroupedBarChart(
    'pdc-resp-chart',
    respOrdenados.map((r) => r.nombre || 'Sin responsable'),
    [
      { label: 'Vencidos', data: respOrdenados.map((r) => toNumber(r.vencidos)), color: 'critical' },
      { label: 'Aún a tiempo', data: respOrdenados.map((r) => Math.max(0, toNumber(r.pendientes) - toNumber(r.vencidos))), color: 'brand-aqua' },
    ],
    { horizontal: true, stacked: true, aspectRatio: 1.5 },
  );

  renderGroupedBarChart(
    'pdc-cobertura-chart',
    items.map((o) => o.obra || '--'),
    [
      { label: 'Cobertura por conteo (%)', data: items.map((o) => toNumber(o.cobertura)), color: 'brand-aqua' },
      { label: 'Cobertura por valor (%)', data: items.map((o) => toNumber(o.cobertura_valor)), color: 'brand-primary' },
    ],
    { aspectRatio: 2.6, max: 100 },
  );

  fillRows('pdc-body', rows, 3, (row) => [
    escapeHtml(row.kpi || '--'),
    `${escapeHtml(String(row.value ?? '--'))}${row.unit === '%' ? '%' : ''}`,
    escapeHtml(row.action || '--'),
  ], 'Sin datos de compras.');

  fillRows('pdc-obra-body', items, 7, (o) => [
    escapeHtml(o.obra || '--'),
    `${escapeHtml(String(o.cobertura ?? 0))}%`,
    `${escapeHtml(String(o.cobertura_valor ?? 0))}%`,
    escapeHtml(String(o.vencidos ?? 0)),
    escapeHtml(String(o.en_riesgo ?? 0)),
    escapeHtml(String(o.destinos ?? 0)),
    escapeHtml(String(o.sin_mirar ?? 0)),
  ], 'Sin obras con plan de compras.');

  const pasos = Object.entries(breakdown.por_paso || {});
  fillRows('pdc-paso-body', pasos, 3, ([nombre, v]) => [
    escapeHtml(nombre),
    escapeHtml(String(v.pendientes ?? 0)),
    escapeHtml(String(v.vencidos ?? 0)),
  ], 'Sin pasos pendientes.');

  const responsables = Array.isArray(breakdown.por_responsable) ? breakdown.por_responsable : [];
  fillRows('pdc-resp-body', responsables, 3, (r) => [
    escapeHtml(r.nombre || '--'),
    escapeHtml(String(r.pendientes ?? 0)),
    escapeHtml(String(r.vencidos ?? 0)),
  ], 'Sin trabajo pendiente asignado.');
}

function renderCIC(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const body = document.getElementById('cic-body');
  if (!body) return;
  if (!rows.length) {
    body.innerHTML = '<tr><td class=\"p-4 text-center text-gray-400\" colspan=\"5\">Sin datos de contratistas.</td></tr>';
    return;
  }
  body.innerHTML = '';
  rows.forEach((row) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class=\"p-2\">${escapeHtml(row.kpi || row.nombre || '--')}</td><td class=\"p-2\">${escapeHtml(row.contacto || '--')}</td><td class=\"p-2\">${escapeHtml(row.servicio || '--')}</td><td class=\"p-2\">${escapeHtml(row.puntaje || '--')}</td><td class=\"p-2\">${statusPill(row.status || 'activo')}</td>`;
    body.appendChild(tr);
  });
}

function renderCIP(data) {
  const rows = Array.isArray(data.scorecard) ? data.scorecard : [];
  const body = document.getElementById('cip-body');
  if (!body) return;
  if (!rows.length) {
    body.innerHTML = '<tr><td class=\"p-4 text-center text-gray-400\" colspan=\"4\">Sin datos de responsables.</td></tr>';
    return;
  }
  body.innerHTML = '';
  rows.forEach((row) => {
    const pct = toNumber(row.value);
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class=\"p-2\">${escapeHtml(row.kpi || row.nombre || '--')}</td><td class=\"p-2\">${escapeHtml(row.rol || row.cargo || '--')}</td><td class=\"p-2\">${escapeHtml(row.count || row.actividades || '--')}</td><td class=\"p-2\">${renderProgressBadge(pct)}</td>`;
    body.appendChild(tr);
  });
}

function applyFilters() {
  BI.filters = {
    ...BI.filters,
    semana: getValue('filter-semana'),
    desde: getValue('filter-desde'),
    hasta: getValue('filter-hasta'),
    sub: getValue('filter-sub'),
    resp: getValue('filter-resp'),
    etapa: getValue('filter-etapa'),
  };

  BI.dataByView = {};
  BI.activityTimeline.requestId += 1;
  closeCausalDrilldown(false);
  closeComplianceDrilldown(false);
  closeProgressDrilldown(false);
  renderActiveChips();
  closeMobileFilters();
  fetchFilterOptions();
  loadCurrentView();
}

function resetFilters() {
  BI.filters = { ...BI.filters, semana: '', desde: '', hasta: '', sub: '', resp: '', etapa: '' };
  BI.dataByView = {};
  BI.activityTimeline.requestId += 1;
  closeCausalDrilldown(false);
  closeComplianceDrilldown(false);
  closeProgressDrilldown(false);
  setValue('filter-semana', '');
  setValue('filter-desde', '');
  setValue('filter-hasta', '');
  setValue('filter-sub', '');
  setValue('filter-resp', '');
  setValue('filter-etapa', '');

  document.querySelectorAll('#project-checkbox-list input[type=\"checkbox\"]').forEach((checkbox) => {
    checkbox.checked = false;
  });

  BI.filters.projects = [];
  updateProjectDropdownText();
  updateFilterUI();
  fetchFilterOptions();
  renderActiveChips();
  renderMobileFilterState();
  loadCurrentView();
}

function syncChartControls() {
  const projectionsToggle = document.getElementById('toggle-programa-projections');
  if (projectionsToggle) {
    const stored = readSessionSetting('bi.programa.projections');
    BI.chartSettings.programaProjections = stored === null ? true : stored === 'true';
    projectionsToggle.checked = BI.chartSettings.programaProjections;
  }
}

function wireChartControlEvents() {
  const projectionsToggle = document.getElementById('toggle-programa-projections');
  if (!projectionsToggle || projectionsToggle.dataset.biBound === '1') return;
  const applyProjectionToggle = () => {
    if (BI.chartSettings.programaProjections === projectionsToggle.checked) return;
    BI.chartSettings.programaProjections = projectionsToggle.checked;
    writeSessionSetting('bi.programa.projections', String(BI.chartSettings.programaProjections));
    const data = BI.dataByView['programa-general'];
    if (data) renderProgramaGeneral(data);
  };
  projectionsToggle.addEventListener('input', applyProjectionToggle);
  projectionsToggle.addEventListener('change', applyProjectionToggle);
  projectionsToggle.dataset.biBound = '1';
}

function wireThemeEvents() {
  document.addEventListener('aia-theme-change', () => {
    BI.chartThemeCache = null;
    Object.values(BI.charts).forEach((chart) => chart.destroy());
    BI.charts = {};
    renderCurrentView();
  });
}

function removeFilter(key) {
  if (key === 'projects') {
    BI.dataByView = {};
    BI.filters.projects = [];
    updateProjectDropdownText();
    document.querySelectorAll('#project-checkbox-list input[type=\"checkbox\"]').forEach((checkbox) => {
      checkbox.checked = false;
    });
    syncFilterUIByProjects();
  } else {
    BI.filters[key] = '';
    setValue('filter-' + key, '');
  }
  renderActiveChips();
  renderMobileFilterState();
  loadCurrentView();
}

function renderActiveChips() {
  const container = document.getElementById('active-filters');
  if (!container) return;

  container.innerHTML = '';
  const labelMap = {
    projects: 'Proyectos',
    semana: 'Semana',
    desde: 'Desde',
    hasta: 'Hasta',
    sub: 'Sub-Contratista',
    resp: 'Responsable',
    etapa: 'Etapa',
  };

  const entries = [];
  if (BI.filters.projects.length) entries.push({ key: 'projects', value: selectedProjectLabels().join(', ') });
  if (BI.filters.semana) entries.push({ key: 'semana', value: BI.filters.semana });
  if (BI.filters.desde) entries.push({ key: 'desde', value: BI.filters.desde });
  if (BI.filters.hasta) entries.push({ key: 'hasta', value: BI.filters.hasta });
  if (BI.filters.sub) entries.push({ key: 'sub', value: BI.filters.sub });
  if (BI.filters.resp) entries.push({ key: 'resp', value: BI.filters.resp });
  if (BI.filters.etapa) entries.push({ key: 'etapa', value: BI.filters.etapa });

  entries.forEach((entry) => {
    const chip = document.createElement('span');
    chip.className = 'bi-chip';
    chip.innerHTML = `${labelMap[entry.key]}: <strong>${escapeHtml(entry.value)}</strong> <button type=\"button\" onclick=\"removeFilter('${entry.key}')\" aria-label=\"Quitar filtro\"><i data-lucide=\"x\" class=\"w-3 h-3\"></i></button>`;
    container.appendChild(chip);
  });
  renderMobileFilterState(entries.length);
  if (window.lucide) window.lucide.createIcons();
}

function closeMobileFilters() {
  const form = document.getElementById('filters-form');
  const button = document.getElementById('bi-mobile-filter-toggle');
  if (!form || !button) return;
  form.classList.remove('is-open');
  button.setAttribute('aria-expanded', 'false');
}

function renderMobileFilterState(count = null) {
  const counter = document.getElementById('bi-mobile-filter-count');
  if (!counter) return;
  const activeCount = count ?? (
    (BI.filters.projects.length ? 1 : 0)
    + ['semana', 'desde', 'hasta', 'sub', 'resp', 'etapa'].filter((key) => Boolean(BI.filters[key])).length
  );
  counter.textContent = String(activeCount);
}

function updateFilterUI() {
  const semanaInput = document.getElementById('filter-semana');
  const rangeContainer = document.getElementById('container-rangos');
  const helper = document.getElementById('helper-semana');
  if (!semanaInput || !rangeContainer || !helper) return;

  if (BI.filters.projects.length === 1) {
    semanaInput.disabled = false;
    helper.classList.add('hidden');
    rangeContainer.classList.add('opacity-50', 'pointer-events-none');
    populateWeeks(BI.filters.projects[0]);
    return;
  }

  semanaInput.disabled = true;
  semanaInput.innerHTML = '<option value=\"\">Seleccione proyecto(s) primero</option>';
  if (BI.filters.projects.length > 1) {
    helper.classList.remove('hidden');
    rangeContainer.classList.remove('opacity-50', 'pointer-events-none');
  } else {
    helper.classList.add('hidden');
    rangeContainer.classList.add('opacity-50', 'pointer-events-none');
  }
}

function syncFilterUIByProjects() {
  if (BI.filters.projects.length > 1) {
    document.getElementById('filter-semana')?.setAttribute('disabled', 'disabled');
    return;
  }
  if (BI.filters.projects.length === 1) {
    const projectId = BI.filters.projects[0];
    document.getElementById('filter-semana')?.removeAttribute('disabled');
    if (projectId) populateWeeks(projectId);
  }
}

function updateProjectSelection() {
  const checkboxes = document.querySelectorAll('#project-checkbox-list input[type=\"checkbox\"]');
  BI.filters.projects = Array.from(checkboxes).filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);
  BI.dataByView = {};
  updateProjectDropdownText();
  updateFilterUI();
  fetchFilterOptions();
  renderActiveChips();
  renderMobileFilterState();
}

function toggleProjectDropdown() {
  const dropdown = document.getElementById('project-checkbox-list');
  if (!dropdown) return;
  dropdown.classList.toggle('hidden');
}

function updateProjectDropdownText() {
  const text = document.getElementById('project-dropdown-text');
  if (!text) return;
  if (!BI.filters.projects.length) {
    text.textContent = 'Seleccionar proyectos...';
    return;
  }
  if (BI.filters.projects.length === 1) {
    text.textContent = projectLabelForId(BI.filters.projects[0]);
    return;
  }
  text.textContent = `${BI.filters.projects.length} proyectos seleccionados`;
}

function normalizeProjectOption(project) {
  const value = String(project.project_id || project.id || project.ID || project.Id || '').trim();
  const label = String(project.nombre || project.name || project.Proyecto_Proceso || project.proyecto || '').trim();
  return {
    value,
    label: label || (value ? `Proyecto ${value}` : 'Proyecto sin nombre'),
  };
}

function projectLabelForId(projectId) {
  const selected = String(projectId || '').trim();
  const project = BI.projects.map(normalizeProjectOption).find((item) => item.value === selected);
  return project?.label || `Proyecto ${selected}`;
}

function selectedProjectLabels() {
  return BI.filters.projects.map(projectLabelForId);
}

function renderProjectList(projects) {
  const container = document.getElementById('project-checkbox-list');
  if (!container) return;

  BI.projects = projects;
  container.innerHTML = '';
  projects.forEach((project) => {
    const { value, label } = normalizeProjectOption(project);
    if (!value) return;
    const row = document.createElement('label');
    const checked = BI.filters.projects.includes(value);
    row.className = 'bi-project-option';
    row.innerHTML = `<input type=\"checkbox\" value=\"${escapeHtml(value)}\" class=\"rounded flex-shrink-0\" ${checked ? 'checked' : ''}><span class=\"truncate\">${escapeHtml(label)}</span>`;
    const input = row.querySelector('input');
    if (input) input.addEventListener('change', updateProjectSelection);
    container.appendChild(row);
  });
  updateProjectDropdownText();
  updateFilterUI();
  renderActiveChips();
}

function fetchFilterOptions() {
  const subSelect = document.getElementById('filter-sub');
  const respList = document.getElementById('filter-resp-options');
  if (!subSelect && !respList) return Promise.resolve();
  if (!BI.filters.projects.length) {
    renderFilterOptions({ subcontratistas: [], responsables: [] });
    return Promise.resolve();
  }

  const url = new URL('/api/bi/filter-options', window.location.origin);
  BI.filters.projects.forEach((projectId) => url.searchParams.append('project_ids[]', projectId));
  if (BI.filters.semana) url.searchParams.set('semana', BI.filters.semana);
  if (BI.filters.desde) url.searchParams.set('desde', BI.filters.desde);
  if (BI.filters.hasta) url.searchParams.set('hasta', BI.filters.hasta);

  return fetch(url, { credentials: 'same-origin' })
    .then((response) => response.json())
    .then(renderFilterOptions)
    .catch(() => renderFilterOptions({ subcontratistas: [], responsables: [] }));
}

function renderFilterOptions(payload) {
  const subSelect = document.getElementById('filter-sub');
  const respList = document.getElementById('filter-resp-options');
  const subs = Array.isArray(payload?.subcontratistas) ? payload.subcontratistas : [];
  const responsables = Array.isArray(payload?.responsables) ? payload.responsables : [];

  if (subSelect) {
    const selected = BI.filters.sub || subSelect.value || '';
    subSelect.innerHTML = '<option value=\"\">Todos</option>';
    [...new Set([...subs, selected].filter(Boolean))].forEach((value) => {
      subSelect.insertAdjacentHTML('beforeend', `<option value=\"${escapeHtml(value)}\">${escapeHtml(value)}</option>`);
    });
    subSelect.value = selected;
  }

  if (respList) {
    respList.innerHTML = '';
    responsables.forEach((value) => {
      respList.insertAdjacentHTML('beforeend', `<option value=\"${escapeHtml(value)}\"></option>`);
    });
  }
}

function populateWeeks(projectId) {
  const sel = document.getElementById('filter-semana');
  if (!sel) return;
  sel.innerHTML = '<option value=\"\">Cargando semanas...</option>';
  fetch('/api/bi/weeks?project_id=' + encodeURIComponent(projectId), { credentials: 'same-origin' })
    .then((res) => res.json())
    .then((payload) => {
      const weeks = payload.weeks || [];
      sel.innerHTML = '<option value=\"\">Todas las semanas</option>';
      weeks.forEach((week) => {
        const value = week.Semana || week.semana || '';
        const label = week.Semana ? `Semana ${week.Semana}` : (week.label || String(value));
        sel.insertAdjacentHTML('beforeend', `<option value=\"${escapeHtml(String(value))}\">${escapeHtml(label)}</option>`);
      });
      if (BI.filters.semana) sel.value = BI.filters.semana;
    })
    .catch(() => {
      sel.innerHTML = '<option value=\"\">Error al consultar semanas</option>';
    });
}

function fetchProjects() {
  return fetch('/api/bi/projects', { credentials: 'same-origin' })
    .then((response) => response.json())
    .then((payload) => {
      const projects = payload.projects || [];
      renderProjectList(projects);
    });
}

function showLoading(visible) {
  const state = document.getElementById('empty-state');
  if (!state) return;
  if (visible) {
    state.classList.remove('hidden');
    const h = state.querySelector('h3');
    const p = state.querySelector('p');
    if (h) h.textContent = 'Cargando datos...';
    if (p) p.textContent = 'Consultando la base de datos.';
  } else {
    hideEmptyState();
  }
}

function showEmptyState(visible, message) {
  const state = document.getElementById('empty-state');
  if (!state) return;
  if (!visible) return hideEmptyState();
  state.classList.remove('hidden');
  const p = state.querySelector('p');
  if (p) p.textContent = message || 'No hay datos para los filtros aplicados.';
}

function hideEmptyState() {
  const state = document.getElementById('empty-state');
  if (state) state.classList.add('hidden');
}

/**
 * C-32: tabla equivalente oculta para cada canvas.
 * Recibe exactamente los mismos arrays que se entregan a Chart.js (no una copia
 * recalculada), de modo que la tabla no puede divergir de lo pintado.
 */
function syncChartDataTable(canvasId, labels, datasets) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const tableId = `${canvasId}-data-table`;
  let container = document.getElementById(tableId);
  if (!container) {
    container = document.createElement('div');
    container.id = tableId;
    container.className = 'aia-visually-hidden';
    container.dataset.chartDataTable = canvasId;
    canvas.insertAdjacentElement('afterend', container);
  }

  const describedBy = String(canvas.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
  if (!describedBy.includes(tableId)) {
    describedBy.push(tableId);
    canvas.setAttribute('aria-describedby', describedBy.join(' '));
  }

  const caption = canvas.getAttribute('aria-label') || 'Gráfico';
  const rows = Array.isArray(labels) ? labels : [];
  const series = (Array.isArray(datasets) ? datasets : []).filter(Boolean).map((dataset, index) => ({
    label: String(dataset.label || `Serie ${index + 1}`),
    data: Array.isArray(dataset.data) ? dataset.data : [],
  }));

  container.replaceChildren();

  if (!rows.length || !series.length) {
    const empty = document.createElement('p');
    empty.textContent = `${caption}: sin datos para los filtros aplicados.`;
    container.appendChild(empty);
    return;
  }

  // Se construye por DOM (no por innerHTML): los valores llegan del backend y
  // aqui no hay markup interpolado que escapar.
  const table = document.createElement('table');
  const tableCaption = document.createElement('caption');
  tableCaption.textContent = `${caption} — tabla equivalente`;
  table.appendChild(tableCaption);

  const head = document.createElement('thead');
  const headRow = document.createElement('tr');
  headRow.appendChild(chartDataTableHeader('col', 'Categoría'));
  for (const serie of series) headRow.appendChild(chartDataTableHeader('col', serie.label));
  head.appendChild(headRow);
  table.appendChild(head);

  const body = document.createElement('tbody');
  rows.forEach((rowLabel, index) => {
    const row = document.createElement('tr');
    row.appendChild(chartDataTableHeader('row', String(rowLabel ?? '')));
    for (const serie of series) {
      const cell = document.createElement('td');
      cell.textContent = chartDataTableCell(serie.data[index]);
      row.appendChild(cell);
    }
    body.appendChild(row);
  });
  table.appendChild(body);
  container.appendChild(table);
}

function chartDataTableHeader(scope, text) {
  const cell = document.createElement('th');
  cell.scope = scope;
  cell.textContent = text;
  return cell;
}

function chartDataTableCell(value) {
  if (value === null || value === undefined || value === '') return 'sin dato';
  if (typeof value === 'object') {
    const inner = value.y ?? value.value;
    return inner === null || inner === undefined ? 'sin dato' : String(inner);
  }
  return String(value);
}

function clearChartDataTable(canvasId) {
  syncChartDataTable(canvasId, [], []);
}

function renderBarChart(canvasId, labels, values, label, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas);
  const theme = chartTheme();
  const chartData = sanitizeChartData(values);
  const chartColor = resolveChartColor(color);
  syncChartDataTable(canvasId, labels, [{ label, data: chartData }]);
  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets[0].data = chartData;
    BI.charts[canvasId].data.datasets[0].backgroundColor = chartColor;
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label, data: chartData, backgroundColor: chartColor, borderRadius: 4 }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.1,
      animation: false,
      resizeDelay: 200,
      plugins: {
        legend: chartLegendOptions(theme),
        tooltip: chartTooltipOptions(theme),
      },
      scales: chartCartesianScales(theme, { y: { beginAtZero: true } }),
    },
  });
}

function renderLineLikeBars(canvasId, labels, datasets) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas);
  const theme = chartTheme();
  const norm = datasets.map((dataset) => {
    const color = resolveChartColor(dataset.color);
    return { ...dataset, data: sanitizeChartData(dataset.data), borderColor: color, backgroundColor: colorWithAlpha(color, 0.2), borderWidth: 2 };
  });
  syncChartDataTable(canvasId, labels, norm);

  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets = norm;
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'bar',
    data: { labels, datasets: norm },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.1,
      animation: false,
      resizeDelay: 200,
      plugins: {
        legend: chartLegendOptions(theme),
        tooltip: chartTooltipOptions(theme),
      },
      scales: chartCartesianScales(theme),
    },
  });
}

function renderLineChart(canvasId, labels, series, options = {}) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas);
  const theme = chartTheme();
  const compact = isCompactChart(canvas);
  const percentScale = canvasId === 'programa-curva-ejecucion' || canvasId === 'chart-curva-s';
  const datasets = series.map((serie) => {
    const color = resolveChartColor(serie.color);
    return {
      label: serie.label || '',
      data: sanitizeLineChartData(serie.data),
      borderColor: color,
      backgroundColor: colorWithAlpha(color, 0.14),
      borderWidth: 2,
      borderDash: serie.dash || [],
      tension: 0.3,
      fill: false,
      pointRadius: 0,
      pointHoverRadius: 0,
      pointHitRadius: compact ? 8 : 10,
    };
  });
  canvas.dataset.seriesCount = String(datasets.length);
  canvas.dataset.seriesLabels = datasets.map((dataset) => dataset.label).join('|');
  canvas.dataset.percentScale = percentScale ? 'true' : 'false';
  canvas._biTooltipContext = options.tooltipContext || null;
  syncChartDataTable(canvasId, labels, datasets);

  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets = datasets;
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: compact ? 1.35 : 2.3,
      animation: false,
      resizeDelay: 200,
      plugins: {
        legend: {
          display: true,
          labels: {
            color: theme.muted,
            boxWidth: compact ? 14 : 40,
            boxHeight: compact ? 8 : 12,
          },
        },
        tooltip: chartTooltipOptions(theme, {
          callbacks: lineTooltipCallbacks(),
        }),
      },
      scales: chartCartesianScales(theme, {
        x: {
          ticks: {
            autoSkip: true,
            maxTicksLimit: compact ? 6 : 12,
          },
        },
        y: {
          beginAtZero: true,
          suggestedMax: percentScale ? 100 : undefined,
          max: percentScale ? 100 : undefined,
        },
      }),
    },
  });
}

function lineTooltipCallbacks() {
  return {
    label(context) {
      const label = context.dataset?.label || 'Serie';
      const percentScale = context.chart?.canvas?.dataset?.percentScale === 'true';
      return `${label}: ${formatChartTooltipValue(context.parsed?.y, percentScale)}`;
    },
    afterLabel(context) {
      const tooltipContext = context.chart?.canvas?._biTooltipContext;
      if (tooltipContext?.kind !== 'programaExecution') return '';
      return programaExecutionTooltipLines(context, tooltipContext);
    },
  };
}

function programaExecutionTooltipContext(chart) {
  return {
    kind: 'programaExecution',
    meta: chart?.projection_meta || {},
  };
}

function syncProgramaProjectionAvailability(chart) {
  const unavailable = chart?.projection_meta?.projection_available === false;
  const note = document.getElementById('programa-projection-note');
  const toggle = document.getElementById('toggle-programa-projections');
  const control = document.querySelector('label[for="toggle-programa-projections"]');
  if (note) {
    note.textContent = unavailable
      ? 'Se requieren al menos 3 cortes con avance real positivo para activar proyecciones. Por ahora se muestra avance real vs curva teórica.'
      : '';
    note.classList.toggle('hidden', !unavailable);
  }
  if (toggle) {
    toggle.disabled = unavailable;
  }
  if (control) {
    control.classList.toggle('is-disabled', unavailable);
  }
}

function programaExecutionTooltipLines(context, tooltipContext) {
  const label = String(context.dataset?.label || '').toLowerCase();
  if (label.includes('teórica')) {
    return [
      'Meta del plan para este corte.',
      'Sirve para comparar si vamos tarde o temprano.',
      'Se calcula con cantidad/duración de actividades.',
    ];
  }
  if (label.includes('real')) {
    return [
      'Avance real reportado hasta este corte.',
      'Si está bajo la meta, hay atraso acumulado.',
      'Usa la misma ponderación del plan.',
    ];
  }

  const meta = tooltipContext.meta || {};
  if (meta.projection_available === false) {
    return [
      'Todavía no hay suficiente historia de producción para simular escenarios.',
      'Registra más cortes de avance o amplía el rango de fechas.',
      `Cortes positivos: ${formatInteger(meta.positive_increment_count)} de ${formatInteger(meta.minimum_positive_increments)} requeridos.`,
    ];
  }
  const scenario = projectionScenarioForLabel(label, meta);
  const completion = completionLabelForSeries(context.chart?.data?.labels || [], context.dataset?.data || []);
  return [
    `${scenario.action}. Fin: ${completion || 'sin cierre'}.`,
    `Rango probable ${formatConfidence(meta.confidence_level)}: ${formatInteger(meta.simulation_count)} simulaciones.`,
    `Ritmo: ${formatPercent(scenario.rate)}/sem. Últ.: ${formatPercent(meta.last_weekly_rate_pct)}/sem.`,
    `Método: Monte Carlo + curva S, ${scenario.percentile}.`,
  ];
}

function projectionScenarioForLabel(label, meta) {
  if (label.includes('pesimista')) {
    return { action: 'Plan de contención', percentile: 'P10', rate: meta.pessimistic_weekly_rate_pct };
  }
  if (label.includes('optimista')) {
    return { action: 'Oportunidad de terminar antes', percentile: 'P90', rate: meta.optimistic_weekly_rate_pct };
  }
  return { action: 'Escenario base de seguimiento', percentile: 'P50', rate: meta.likely_weekly_rate_pct };
}

function completionLabelForSeries(labels, data) {
  const index = (Array.isArray(data) ? data : []).findIndex((value) => Number.isFinite(Number(value)) && Number(value) >= 100);
  return index >= 0 ? labels[index] : '';
}

function formatChartTooltipValue(value, percentScale = false) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '--';
  const formatted = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(number);
  return percentScale ? `${formatted}%` : formatted;
}

function formatPercent(value) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '--';
  return `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(number)}%`;
}

function formatInteger(value) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '--';
  return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(number);
}

function formatConfidence(value) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '--';
  return `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(number * 100)}%`;
}

function formatSignedPp(value) {
  const number = finiteNumber(value);
  if (!Number.isFinite(number)) return '-- pp';
  return `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(number)} pp`;
}

function formatShortDate(value) {
  const raw = String(value || '').trim();
  if (!raw) return '--';
  if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;

  const date = new Date(`${raw}T00:00:00`);
  if (Number.isNaN(date.getTime())) return raw;

  return new Intl.DateTimeFormat('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date);
}

function renderComparativeProgressGauge(canvasId, chart) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas, 'gauge');
  const theme = chartTheme();
  const source = chartDatasets(chart).slice(0, 2);
  const colors = source.map((dataset, index) => resolveChartColor(dataset.color || (index === 0 ? 'status-critical' : 'brand-aqua-medium')));
  const remainder = resolveChartColor('surface-muted');
  const datasets = source.map((dataset, index) => ({
    label: dataset.label,
    data: sanitizeChartData(dataset.data),
    backgroundColor: [colors[index], remainder],
    borderColor: theme.surface,
    borderWidth: 2,
  }));
  syncGaugeLegend(canvasId, ['Avance real', 'Avance teórico'], colors);
  syncChartDataTable(canvasId, chartLabels(chart), datasets);
  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.datasets = datasets;
    BI.charts[canvasId].update('none');
    return;
  }
  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: { labels: chartLabels(chart), datasets },
    options: {
      responsive: true, maintainAspectRatio: true, aspectRatio: 2.2,
      animation: false, resizeDelay: 200, circumference: 180, rotation: 270, cutout: '58%',
      plugins: { legend: chartLegendOptions(theme, { display: false }), tooltip: chartTooltipOptions(theme) },
    },
  });
}

function renderProgressDonut(canvasId, labels, values, gauge = false, colorKey = 'brand-primary') {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas, gauge ? 'gauge' : 'donut');
  const theme = chartTheme();
  const palette = [resolveChartColor(colorKey), resolveChartColor('surface-muted')];
  const chartData = sanitizeChartData(values);
  if (gauge) {
    syncGaugeLegend(canvasId, labels, palette);
  }
  syncChartDataTable(canvasId, labels, [{ label: 'Valor', data: chartData }]);
  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets[0].data = chartData;
    BI.charts[canvasId].data.datasets[0].backgroundColor = palette;
    BI.charts[canvasId].options.circumference = gauge ? 180 : 360;
    BI.charts[canvasId].options.rotation = gauge ? 270 : 0;
    BI.charts[canvasId].update('none');
    return;
  }
  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: chartData, backgroundColor: palette }],
    },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: gauge ? 2.2 : 1.4,
        animation: false,
        resizeDelay: 200,
        circumference: gauge ? 180 : 360,
        rotation: gauge ? 270 : 0,
        cutout: '72%',
        plugins: {
          legend: chartLegendOptions(theme, { display: !gauge, position: 'bottom' }),
          tooltip: chartTooltipOptions(theme),
        },
      },
  });
}

function syncGaugeLegend(canvasId, labels, palette) {
  const fallbacks = gaugeLegendFallbackLabels(canvasId);
  const items = [
    { label: labels?.[0] || fallbacks[0], color: palette[0] },
    { label: labels?.[1] || fallbacks[1], color: palette[1] },
  ];

  items.forEach((item, index) => {
    const swatch = document.getElementById(`${canvasId}-legend-swatch-${index}`);
    const label = document.getElementById(`${canvasId}-legend-label-${index}`);
    if (swatch) {
      swatch.style.backgroundColor = item.color;
    }
    if (label) {
      label.textContent = item.label;
    }
  });
}

function gaugeLegendFallbackLabels(canvasId) {
  if (canvasId === 'programa-compliance') {
    return ['Cumplimiento cronograma', 'Brecha'];
  }
  return ['Avance real', 'Avance teórico'];
}

function updateGaugeReadout(canvasId, values, pendingLabel = 'Pendiente') {
  const valueEl = document.getElementById(`${canvasId}-value`);
  const pendingEl = document.getElementById(`${canvasId}-pending`);
  const progress = Number(values?.[0]);
  const pending = Number(values?.[1]);
  if (valueEl) valueEl.textContent = formatPercent(progress);
  if (pendingEl) pendingEl.textContent = `${pendingLabel} ${formatPercent(pending)}`;
}

function renderDoughnutChart(canvasId, labels, values, label, options = {}) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas, 'donut');
  const theme = chartTheme();
  const chartData = sanitizeChartData(values);
  const palette = (window.BiChartTheme && window.BiChartTheme.colors && window.BiChartTheme.colors.length)
    ? window.BiChartTheme.colors
    : CHART_PALETTE.map(resolveChartColor);
  const chartTotal = chartData.reduce((sum, value) => sum + toNumber(value), 0);
  const tooltip = chartTooltipOptions(theme, options.showShare ? {
    callbacks: {
      label: (context) => {
        const value = toNumber(context.raw);
        const share = chartTotal > 0 ? (value / chartTotal) * 100 : 0;
        return `${context.label}: ${formatInteger(value)} actividades (${formatPercent(share)})`;
      },
    },
  } : {});
  let lastSegmentClick = { category: '', at: 0 };
  const onClick = (event, elements) => {
    const index = elements?.[0]?.index;
    const category = Number.isInteger(index) ? labels[index] : '';
    const now = Date.now();
    if (category && category === lastSegmentClick.category && now - lastSegmentClick.at <= 400 && typeof options.onSegmentClick === 'function') {
      options.onSegmentClick(category);
      lastSegmentClick = { category: '', at: 0 };
      return;
    }
    lastSegmentClick = { category, at: now };
  };
  syncChartDataTable(canvasId, labels, [{ label, data: chartData }]);

  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets[0].data = chartData;
    BI.charts[canvasId].data.datasets[0].backgroundColor = palette;
    BI.charts[canvasId].options.onClick = onClick;
    BI.charts[canvasId].options.plugins.tooltip = tooltip;
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ label, data: chartData, backgroundColor: palette }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 1.4,
      animation: false,
      resizeDelay: 200,
      onClick,
      plugins: {
        legend: chartLegendOptions(theme, { position: 'right' }),
        tooltip,
      },
      cutout: '58%',
    },
  });
}

function renderRadarChart(canvasId, labels, values, label) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  stabilizeCanvas(canvas, 'donut');
  const theme = chartTheme();
  const chartData = sanitizeChartData(values);
  const color = resolveChartColor('brand-aqua');
  syncChartDataTable(canvasId, labels, [{ label, data: chartData }]);

  if (BI.charts[canvasId]) {
    BI.charts[canvasId].data.labels = labels;
    BI.charts[canvasId].data.datasets[0].data = chartData;
    BI.charts[canvasId].data.datasets[0].borderColor = color;
    BI.charts[canvasId].data.datasets[0].backgroundColor = colorWithAlpha(color, 0.2);
    BI.charts[canvasId].update('none');
    return;
  }

  BI.charts[canvasId] = new Chart(canvas.getContext('2d'), {
    type: 'radar',
    data: {
      labels,
      datasets: [{ label, data: chartData, borderColor: color, backgroundColor: colorWithAlpha(color, 0.2), borderWidth: 2 }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 1.35,
      animation: false,
      resizeDelay: 200,
      scales: { r: chartRadialScale(theme, { beginAtZero: true, max: 100 }) },
      plugins: {
        legend: chartLegendOptions(theme, { display: false }),
        tooltip: chartTooltipOptions(theme),
      },
    },
  });
}

function stabilizeCanvas(canvas, variant = 'standard') {
  canvas.classList.add('bi-chart-canvas');
  canvas.classList.remove('bi-chart-canvas--gauge', 'bi-chart-canvas--donut');
  if (variant === 'gauge') {
    canvas.classList.add('bi-chart-canvas--gauge');
  }
  if (variant === 'donut') {
    canvas.classList.add('bi-chart-canvas--donut');
  }
}

function valueWithUnit(item) {
  if (!item || typeof item !== 'object') return '--';
  const value = item.value == null ? '--' : item.value;
  const rawUnit = item.unit ? String(item.unit) : '';
  const unit = rawUnit === 'count' ? '' : rawUnit;
  return `${value}${unit ? ` ${unit}` : ''}`;
}

function renderProgressBadge(value) {
  if (value >= 80) return '<span class=\"inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold\">OK</span>';
  if (value >= 50) return '<span class=\"inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold\">Medio</span>';
  return '<span class=\"inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold\">Alto</span>';
}

function statusPill(status) {
  return `<span class=\"inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold ${statusBadgeClass(status)}\">${status}</span>`;
}

function statusBadgeClass(status) {
  if (!status) return 'bg-gray-100 text-gray-500';
  const lower = String(status).toLowerCase();
  if (['completa', 'activo', 'completada'].includes(lower)) return '';
  if (['pendiente', 'riesgo', 'baja'].includes(lower)) return '';
  return 'bg-gray-100 text-gray-500';
}

function chartTheme() {
  if (BI.chartThemeCache) return BI.chartThemeCache;
  const bodyStyle = getComputedStyle(document.body);
  BI.chartThemeCache = {
    text: resolveCssColor('--ds-active-text-primary') || bodyStyle.color,
    muted: resolveCssColor('--ds-active-text-secondary') || bodyStyle.color,
    grid: resolveCssColor('--ds-active-border') || bodyStyle.color,
    surface: resolveCssColor('--ds-active-surface', 'backgroundColor') || bodyStyle.backgroundColor,
    tooltip: resolveCssColor('--ds-active-surface-raised', 'backgroundColor') || bodyStyle.backgroundColor,
  };
  return BI.chartThemeCache;
}

function resolveCssColor(token, property = 'color') {
  const probe = document.createElement('span');
  probe.setAttribute('aria-hidden', 'true');
  probe.hidden = true;
  probe.style[property] = `var(${token})`;
  document.body.appendChild(probe);
  const value = getComputedStyle(probe)[property];
  probe.remove();
  return value;
}

function chartLegendOptions(theme, options = {}) {
  const labels = options.labels || {};
  return {
    ...options,
    labels: {
      color: theme.muted,
      ...labels,
    },
  };
}

function chartTooltipOptions(theme, options = {}) {
  return {
    backgroundColor: theme.tooltip,
    titleColor: theme.text,
    bodyColor: theme.text,
    borderColor: theme.grid,
    borderWidth: 1,
    ...options,
  };
}

function chartCartesianScales(theme, scales = {}) {
  return ['x', 'y'].reduce((themed, axis) => {
    themed[axis] = chartAxisOptions(theme, scales[axis] || {});
    return themed;
  }, {});
}

function chartAxisOptions(theme, axis = {}) {
  return {
    ...axis,
    ticks: {
      color: theme.muted,
      ...(axis.ticks || {}),
    },
    grid: {
      color: colorWithAlpha(theme.grid, 0.36),
      ...(axis.grid || {}),
    },
    border: {
      color: theme.grid,
      ...(axis.border || {}),
    },
  };
}

function chartRadialScale(theme, scale = {}) {
  return {
    ...scale,
    angleLines: {
      color: colorWithAlpha(theme.grid, 0.36),
      ...(scale.angleLines || {}),
    },
    grid: {
      color: colorWithAlpha(theme.grid, 0.36),
      ...(scale.grid || {}),
    },
    pointLabels: {
      color: theme.text,
      ...(scale.pointLabels || {}),
    },
    ticks: {
      color: theme.muted,
      backdropColor: colorWithAlpha(theme.surface, 0.72),
      ...(scale.ticks || {}),
    },
  };
}

function chartPayload(data, canvasId) {
  const charts = data && typeof data === 'object' ? data.charts : null;
  const chart = charts && typeof charts === 'object' ? charts[canvasId] : null;
  return chart && typeof chart === 'object' ? chart : null;
}

function chartLabels(chart, fallback = []) {
  return Array.isArray(chart?.labels) ? chart.labels : fallback;
}

function chartDatasets(chart) {
  return (Array.isArray(chart?.datasets) ? chart.datasets : []).map((dataset) => ({
    label: dataset.label || '',
    data: Array.isArray(dataset.data) ? dataset.data : [],
    color: dataset.color || 'brand-primary',
    dash: Array.isArray(dataset.dash) ? dataset.dash : [],
  }));
}

function chartDatasetData(chart, index = 0, fallback = []) {
  return sanitizeChartData((chartDatasets(chart)[index] || {}).data || fallback);
}

function chartDatasetLabel(chart, index = 0, fallback = '') {
  return (chartDatasets(chart)[index] || {}).label || fallback;
}

function chartDatasetColor(chart, index = 0, fallback = 'brand-primary') {
  return (chartDatasets(chart)[index] || {}).color || fallback;
}

function programaExecutionDatasets(chart) {
  const datasets = chartDatasets(chart);
  if (chart?.projection_meta?.projection_available === false) {
    return datasets.filter((dataset) => !isProjectionDataset(dataset));
  }
  if (BI.chartSettings.programaProjections) return datasets;

  return datasets.filter((dataset) => !isProjectionDataset(dataset));
}

function isProjectionDataset(dataset) {
  return String(dataset?.label || '').toLowerCase().includes('proyección');
}

function readSessionSetting(key) {
  try {
    return window.sessionStorage ? window.sessionStorage.getItem(key) : null;
  } catch (error) {
    return null;
  }
}

function writeSessionSetting(key, value) {
  try {
    if (window.sessionStorage) window.sessionStorage.setItem(key, value);
  } catch (error) {
    // La preferencia sigue activa en memoria aunque el navegador bloquee sessionStorage.
  }
}

function resolveChartColor(colorKey = 'brand-primary') {
  const token = CHART_COLOR_TOKENS[colorKey] || '';
  if (!token) return colorKey;
  const scope = document.querySelector('.bi-control-tower-page') || document.body || document.documentElement;
  const value = getComputedStyle(scope).getPropertyValue(token).trim() || getComputedStyle(document.documentElement).getPropertyValue(token).trim();
  return value || colorKey;
}

function colorWithAlpha(color, alpha) {
  const pct = Math.max(0, Math.min(1, alpha));
  const rgba = color.match(/^rgba\(([^,]+),\s*([^,]+),\s*([^,]+),\s*[^)]+\)$/i);
  if (rgba) return `rgba(${rgba[1]}, ${rgba[2]}, ${rgba[3]}, ${pct})`;
  const rgb = color.match(/^rgb\(([^)]+)\)$/i);
  if (rgb) return `rgba(${rgb[1]}, ${pct})`;
  const oklch = color.match(/^oklch\((.*)\)$/i);
  if (oklch) return `oklch(${oklch[1]} / ${pct})`;
  const hex = color.match(/^#([0-9a-f]{6})$/i);
  if (hex) {
    const value = Number.parseInt(hex[1], 16);
    const red = (value >> 16) & 255;
    const green = (value >> 8) & 255;
    const blue = value & 255;
    return `rgba(${red}, ${green}, ${blue}, ${pct})`;
  }
  return color;
}

function scoreValue(rows, kpi) {
  const match = (Array.isArray(rows) ? rows : []).find((row) => row.kpi === kpi);
  return toNumber(match?.value || 0);
}

function sanitizeChartData(values) {
  return (Array.isArray(values) ? values : []).map((value) => {
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
  });
}

function sanitizeLineChartData(values) {
  return (Array.isArray(values) ? values : []).map((value) => {
    if (value === null || value === undefined || value === '') return null;
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
  });
}

function isCompactChart(canvas) {
  const width = canvas.getBoundingClientRect().width || window.innerWidth || 0;
  return width < 520 || window.innerWidth < 768;
}

function finiteNumber(value) {
  if (value === null || value === undefined || value === '') return Number.NaN;
  const number = Number(value);
  return Number.isFinite(number) ? number : Number.NaN;
}

function toNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function getValue(id) {
  const el = document.getElementById(id);
  return el ? el.value : '';
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = String(text || '--');
}

// El brief del backend (ControlTowerService::composeExecutiveBrief) sigue la
// plantilla de 5 frases: estado → causa → impacto → accion → confianza. No
// trae campos "text" ni "summary": hay que componerlos aqui. Cuando no hay
// registros para el proyecto/semana, el backend ya devuelve un mensaje
// legitimo de "sin datos" (ver StorytellingService::emptyBrief) que se
// reutiliza tal cual.
function executiveBriefText(brief) {
  if (!brief || typeof brief !== 'object') return '';
  const parts = [brief.status, brief.root_cause, brief.impact, brief.priority_action]
    .map((part) => (typeof part === 'string' ? part.trim() : ''))
    .filter(Boolean);
  return parts.join(' ');
}

function escapeHtml(value) {
  const txt = document.createElement('div');
  txt.textContent = String(value || '');
  return txt.innerHTML;
}
