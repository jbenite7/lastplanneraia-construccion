import { test, expect } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, loginAndSelectProject, logout, getJson, selectProject } from './support/session.mjs';
import { installErrorCollectors, assertNoRuntimeErrors } from './support/assertions.mjs';

const BI_PROJECT_SCOPE = PROJECTS.slice(0, 1); // keep BI smoke focused and quick
const BI_ROUTES = [
  { key: 'overview', path: '/api/bi/control-tower' },
  { key: 'programa-general', path: '/api/bi/report/programa-general' },
  { key: 'intermedia', path: '/api/bi/report/intermedia' },
  { key: 'semanal', path: '/api/bi/report/semanal' },
  { key: 'pdc', path: '/api/bi/report/pdc' },
  { key: 'cic', path: '/api/bi/report/cic' },
  { key: 'cip', path: '/api/bi/report/cip' },
  { key: 'curva-s', path: '/api/bi/report/curva-s' },
];

const BI_NAV = [
  { id: 'nav-torre-control', view: 'torre-control', expectedTitle: 'Resumen Ejecutivo' },
  { id: 'nav-programa-general', view: 'programa-general', expectedTitle: 'Programa General' },
  { id: 'nav-curva-s', view: 'curva-s', expectedTitle: 'Curva S' },
  { id: 'nav-intermedia', view: 'intermedia', expectedTitle: 'Programación Intermedia' },
  { id: 'nav-semanal', view: 'semanal', expectedTitle: 'Programación Semanal' },
  { id: 'nav-pdc', view: 'pdc', expectedTitle: 'Plan de Compras' },
  { id: 'nav-cic', view: 'cic', expectedTitle: 'Proveedores' },
  { id: 'nav-cip', view: 'cip', expectedTitle: 'Responsables' },
];

const BI_CHARTS = [
  { view: 'torre-control', nav: 'nav-torre-control', endpoint: '/api/bi/control-tower', charts: ['chart-ppc-semanal', 'chart-pac-prog'] },
  { view: 'programa-general', nav: 'nav-programa-general', endpoint: '/api/bi/report/programa-general', charts: ['programa-curva-ejecucion', 'programa-gauge', 'programa-compliance', 'programa-dias-retraso', 'programa-cnp', 'programa-cnc', 'programa-radar-productividad'] },
  { view: 'curva-s', nav: 'nav-curva-s', endpoint: '/api/bi/report/curva-s', charts: ['chart-curva-s'] },
  { view: 'intermedia', nav: 'nav-intermedia', endpoint: '/api/bi/report/intermedia', charts: ['chart-intermedia'] },
  { view: 'semanal', nav: 'nav-semanal', endpoint: '/api/bi/report/semanal', charts: ['chart-semanal-pac'] },
];

function formatPercent(value) {
  const number = Number(value);
  if (!Number.isFinite(number)) return '--';
  return `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(number)}%`;
}

function formatPp(value, { absolute = false } = {}) {
  const number = Number(value);
  if (!Number.isFinite(number)) return '-- pp';
  const normalized = absolute ? Math.abs(number) : number;
  return `${new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 }).format(normalized)} pp`;
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function firstActivityWithOwnership(activities) {
  return (Array.isArray(activities) ? activities : []).find((activity) => (
    String(activity?.activity || '').trim()
    && String(activity?.responsible || '').trim()
    && String(activity?.subcontractor || '').trim()
  )) || null;
}

function expectedRenderedDatasets(chartId, expected) {
  const datasets = expected.datasets || [];
  if (chartId === 'programa-curva-ejecucion' && expected.projection_meta?.projection_available === false) {
    return datasets.filter((dataset) => !String(dataset.label || '').includes('Proyección'));
  }
  return datasets;
}

function chartShouldRemainUnavailable(chartId, expected) {
  if (chartId === 'programa-dias-retraso') return expected?.availability !== true;
  if (chartId === 'programa-radar-productividad') return expected?.status === 'unavailable';
  return false;
}

function lastNumericPoint(values) {
  const series = Array.isArray(values) ? values : [];
  for (let index = series.length - 1; index >= 0; index--) {
    if (series[index] === null || series[index] === undefined || series[index] === '') continue;
    const number = Number(series[index]);
    if (Number.isFinite(number)) return number;
  }
  return 0;
}

async function chartState(page, canvasId) {
  return page.evaluate((id) => {
    const canvas = document.getElementById(id);
    const chart = canvas && window.Chart ? window.Chart.getChart(canvas) : null;
    if (!chart) return null;
    return {
      labels: chart.data.labels,
      datasets: chart.data.datasets.map((dataset) => ({
        label: dataset.label,
        data: dataset.data.map((value) => Number(value)),
        backgroundColor: Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor : [dataset.backgroundColor],
      })),
    };
  }, canvasId);
}

async function fetchJson(page, url, { headers = {}, redirect = 'follow' } = {}) {
  return page.evaluate(async ({ apiUrl, apiHeaders, apiRedirect }) => {
    const res = await fetch(apiUrl, {
      credentials: 'same-origin',
      headers: apiHeaders,
      redirect: apiRedirect,
    });
    const text = await res.text();
    let payload;
    try {
      payload = JSON.parse(text);
    } catch {
      payload = { parseError: true, text };
    }
    return {
      ok: res.ok,
      status: res.status,
      redirected: res.redirected,
      url: res.url,
      payload,
    };
  }, { apiUrl: url, apiHeaders: headers, apiRedirect: redirect });
}

async function gaugeLegendLayout(page) {
  return page.evaluate(() => {
    const charts = [
      { canvasId: 'programa-gauge', legendId: 'programa-gauge-legend' },
      { canvasId: 'programa-compliance', legendId: 'programa-compliance-legend' },
    ];
    const pageOverflowX = Math.max(0, document.documentElement.scrollWidth - window.innerWidth);

    const metrics = charts.map(({ canvasId, legendId }) => {
      const canvas = document.getElementById(canvasId);
      const panel = canvas?.closest('.bi-gauge-panel') || null;
      const visual = panel?.querySelector('.bi-gauge-visual') || null;
      const readout = panel?.querySelector('.bi-gauge-readout') || null;
      const legend = document.getElementById(legendId);
      const chart = canvas && window.Chart ? window.Chart.getChart(canvas) : null;
      const card = panel?.closest('.card') || null;

      if (!canvas || !chart || !panel || !visual || !readout || !legend) {
        return { id: canvasId, missing: true };
      }

      const visualRect = visual.getBoundingClientRect();
      const canvasRect = canvas.getBoundingClientRect();
      const readoutRect = readout.getBoundingClientRect();
      const legendRect = legend.getBoundingClientRect();
      const overlaps = (a, b) => !(
        a.bottom <= b.top
        || a.top >= b.bottom
        || a.right <= b.left
        || a.left >= b.right
      );

      return {
        id: canvasId,
        missing: false,
        chartLegendDisplay: Boolean(chart.options?.plugins?.legend?.display),
        legendHeight: Math.max(0, legendRect.bottom - legendRect.top),
        readoutGapToVisual: readoutRect.top - visualRect.bottom,
        legendGapToReadout: legendRect.top - readoutRect.bottom,
        visualOverlapsReadout: overlaps(visualRect, readoutRect),
        legendOverlapsVisual: overlaps(legendRect, visualRect),
        legendOverlapsReadout: overlaps(legendRect, readoutRect),
        panelOverflowX: Math.max(0, panel.scrollWidth - panel.clientWidth),
        visualOverflowX: Math.max(0, visual.scrollWidth - visual.clientWidth),
        canvasOverflowX: Math.max(0, canvasRect.right - visualRect.right, visualRect.left - canvasRect.left),
        cardOverflowX: Math.max(0, (card?.scrollWidth || 0) - (card?.clientWidth || 0)),
      };
    });

    return { pageOverflowX, metrics };
  });
}

for (const project of BI_PROJECT_SCOPE) {
  test.describe(`BI Control Tower — ${project.name}`, () => {
    test.beforeEach(async ({ page }) => {
      await loginAndSelectProject(page, project);
    });

    test.afterEach(async ({ page }) => {
      await logout(page).catch(() => {});
    });

    test('loads SPA shell from /bi/control-tower', async ({ page }) => {
      const errors = installErrorCollectors(page);
      await page.goto('/bi/control-tower', { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#current-view-title')).toHaveText(/Resumen Ejecutivo/i);
      await expect(page.locator('#view-torre-control')).toBeVisible();
      await expect(page.locator('.bi-chip')).toContainText(project.name);
      await expect(page.locator('#nav-torre-control')).toHaveClass(/active/);
      await expect(page.locator('main')).toBeVisible();
      await expect(page.locator('canvas#chart-ppc-semanal')).toBeVisible();

      assertNoRuntimeErrors(errors);
    });

    test('keeps SPA navigation in the same route when switching views', async ({ page }) => {
      const errors = installErrorCollectors(page);
      await page.goto('/bi/control-tower', { waitUntil: 'domcontentloaded' });

      for (const item of BI_NAV) {
        await page.locator(`#${item.id}`).click();
        await expect(page.locator(`#view-${item.view}`)).toBeVisible({ timeout: 15000 });
        await expect(page.locator('#current-view-title')).toHaveText(item.expectedTitle);
        await expect(page.url()).toContain('/bi/control-tower');
        await expect(page.locator(`#nav-${item.view}`)).toHaveClass(/active/);

        // hide all other sections
        for (const other of BI_NAV.filter((candidate) => candidate.view !== item.view)) {
          await expect(page.locator(`#view-${other.view}`)).toHaveClass(/hidden/);
        }
      }

      assertNoRuntimeErrors(errors);
    });

    test('supports project filter cascade: semana vs rango', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const programaGeneralRequests = [];
      page.on('request', (request) => {
        const url = request.url();
        if (url.includes('/api/bi/report/programa-general')) {
          programaGeneralRequests.push(new URL(url).searchParams.getAll('project_ids[]'));
        }
      });
      await page.goto('/bi/programa-general', { waitUntil: 'domcontentloaded' });
      await page.click('#btn-project-dropdown');

      const firstCheckbox = page.locator('#project-checkbox-list input[type=\"checkbox\"]').first();
      const firstProjectName = page.locator('#project-checkbox-list label span').first();
      await expect(firstCheckbox).toBeVisible();
      await expect(firstProjectName).toBeVisible();
      await expect(firstProjectName).not.toHaveText('');
      await expect(firstProjectName).not.toHaveCSS('color', 'rgb(255, 255, 255)');
      const selectedProjectLabel = (await firstProjectName.innerText()).trim();
      await firstCheckbox.check();
      await expect(page.locator('#project-dropdown-text')).toHaveText(selectedProjectLabel);

      await expect(page.locator('#filter-semana')).toBeEnabled();
      await expect(page.locator('#container-rangos')).toHaveClass(/opacity-50/);

      // Select one more project to force multi-project behavior
      const secondCheckbox = page.locator('#project-checkbox-list input[type=\"checkbox\"]').nth(1);
      if (await secondCheckbox.count()) {
        await secondCheckbox.check();
      }

      await expect(page.locator('#filter-semana')).toBeDisabled();
      await expect(page.locator('#container-rangos')).not.toHaveClass(/opacity-50/);

      await page.locator('#filter-resp').fill('residente');
      await expect(page.locator('#active-filters')).toContainText('Responsable');
      await page.click('button[type=\"submit\"]');
      await expect(page.locator('#active-filters')).toContainText('Responsable');
      await expect.poll(() => programaGeneralRequests.some((ids) => ids.length >= 2), { timeout: 15000 }).toBe(true);

      assertNoRuntimeErrors(errors);
    });

    test('returns BI JSON endpoints with real payload for selected project', async ({ page }) => {
      const errors = installErrorCollectors(page);
      for (const item of BI_ROUTES) {
        const url = `${item.path}?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&role=R`;
        const result = await getJson(page, url);
        expect(result.ok, `Endpoint ${item.key} failed: ${result.status}`).toBe(true);
        expect(result.payload, `Endpoint ${item.key} malformed`).toHaveProperty('respuesta', 'BIEN');
        expect(result.payload.data_source?.source_relations?.length, `Endpoint ${item.key} missing data_source`).toBeGreaterThan(0);
      }
      assertNoRuntimeErrors(errors);
    });

    test('renders charts from the real BI endpoint chart payload', async ({ page }) => {
      const errors = installErrorCollectors(page);
      await page.goto(`/bi/control-tower?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}`, { waitUntil: 'domcontentloaded' });

      for (const item of BI_CHARTS) {
        await page.locator(`#${item.nav}`).click();
        await expect(page.locator(`#view-${item.view}`)).toBeVisible({ timeout: 15000 });

        const endpoint = `${item.endpoint}?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&role=R`;
        const result = await getJson(page, endpoint);
        expect(result.ok, `Endpoint ${item.endpoint} failed`).toBe(true);

        for (const chartId of item.charts) {
          const expected = result.payload.charts?.[chartId];
          expect(expected, `${chartId} missing from endpoint payload`).toBeTruthy();
          expect(expected.source_relations?.length, `${chartId} missing source relations`).toBeGreaterThan(0);

          if (chartShouldRemainUnavailable(chartId, expected)) {
            await expect.poll(() => chartState(page, chartId), { timeout: 15000, message: `${chartId} must not fabricate a chart` }).toBeNull();
            await expect(page.locator(`#${chartId}`)).toBeHidden();
            continue;
          }

          await expect.poll(() => chartState(page, chartId), { timeout: 15000, message: `${chartId} did not render` }).not.toBeNull();
          const rendered = await chartState(page, chartId);
          expect(rendered.labels, `${chartId} labels differ from endpoint`).toEqual(expected.labels);
          const expectedDatasets = expectedRenderedDatasets(chartId, expected);
          expect(rendered.datasets.map((dataset) => dataset.data), `${chartId} datasets differ from endpoint`)
            .toEqual(expectedDatasets.map((dataset) => dataset.data.map((value) => Number(value))));
          if (chartId === 'programa-gauge') {
            await expect(page.locator('#programa-gauge-value'), `${chartId} visible value differs from endpoint`)
              .toHaveText(formatPercent(expected.datasets[0].data[0]));
            await expect(page.locator('#programa-gauge-theoretical'), `${chartId} theoretical value differs from endpoint`)
              .toContainText(formatPercent(expected.metrics?.theoretical_pct));
            await expect(page.locator('#programa-gauge-gap'), `${chartId} gap differs from endpoint`)
              .toContainText(formatPp(expected.metrics?.gap_pp));
          }
          if (chartId === 'programa-compliance') {
            await expect(page.locator('#programa-compliance-value'), `${chartId} visible value differs from endpoint`)
              .toHaveText(formatPercent(expected.metrics?.compliance_pct));
            await expect(page.locator('#programa-compliance-gap'), `${chartId} gap differs from endpoint`)
              .toContainText(formatPp(expected.metrics?.gap_pp));
          }
        }
      }

      assertNoRuntimeErrors(errors);
    });

    test('renders Programa General progress readouts from filtered multi-project payload', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const query = 'project_ids%5B%5D=70&project_ids%5B%5D=71&desde=2026-02-23&hasta=2026-05-18&sub=CONSTRUALMANZA&resp=Jhon%20Mauricio%20Sosa';
      const endpoint = `/api/bi/report/programa-general?${query}&role=R`;
      const result = await getJson(page, endpoint);
      expect(result.ok, 'filtered Programa General endpoint failed').toBe(true);
      const expected = result.payload.charts?.['programa-gauge'];
      const expectedCompliance = result.payload.charts?.['programa-compliance'];
      const curve = result.payload.charts?.['programa-curva-ejecucion'];
      expect(expected, 'filtered programa-gauge missing from endpoint payload').toBeTruthy();
      expect(expectedCompliance, 'filtered programa-compliance missing from endpoint payload').toBeTruthy();
      expect(curve, 'filtered programa-curva-ejecucion missing from endpoint payload').toBeTruthy();
      expect(Number(expected.datasets[0].data[0])).toBe(lastNumericPoint(curve.datasets[1].data));

      await page.goto(`/bi/programa-general?${query}&theme=dark`, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#view-programa-general')).toBeVisible({ timeout: 15000 });
      await expect(page.locator('#active-filters')).toContainText('Proyectos');
      await expect(page.locator('#active-filters')).toContainText('Desde');
      await expect(page.locator('#active-filters')).toContainText('Sub-Contratista');
      await expect.poll(() => chartState(page, 'programa-gauge'), { timeout: 15000 }).not.toBeNull();

      const rendered = await chartState(page, 'programa-gauge');
      expect(rendered.labels, 'filtered gauge labels differ from endpoint').toEqual(expected.labels);
      expect(rendered.datasets.map((dataset) => dataset.data), 'filtered gauge data differs from endpoint')
        .toEqual(expected.datasets.map((dataset) => dataset.data.map((value) => Number(value))));
      await expect(page.locator('#programa-gauge-value')).toHaveText(formatPercent(expected.datasets[0].data[0]));
      await expect(page.locator('#programa-gauge-theoretical')).toContainText(formatPercent(expected.metrics?.theoretical_pct));
      await expect(page.locator('#programa-gauge-gap')).toContainText(formatPp(expected.metrics?.gap_pp));
      await expect(page.locator('#programa-compliance-value')).toHaveText(formatPercent(expectedCompliance.metrics?.compliance_pct));
      await expect(page.locator('#programa-compliance-gap')).toContainText(formatPp(expectedCompliance.metrics?.gap_pp));
      await expect(page.locator('#programa-gauge-range')).toHaveText(expected.metrics?.range?.label || '');
      const performanceIndex = Number(expected.metrics?.range?.basis_value || 0);
      const rangeReason = performanceIndex < 95
        ? `Rendimiento ${formatPercent(performanceIndex)} del avance esperado · por debajo de 95%`
        : (performanceIndex > 105
          ? `Rendimiento ${formatPercent(performanceIndex)} del avance esperado · supera 105%`
          : `Rendimiento ${formatPercent(performanceIndex)} del avance esperado · tolerancia 95%–105%`);
      await expect(page.locator('#programa-gauge-range-reason')).toHaveText(rangeReason);
      await expect(page.locator('#programa-gauge-legend-label-0')).toHaveText('Avance real');
      await expect(page.locator('#programa-gauge-legend-label-1')).toHaveText('Avance teórico');
      await expect(page.locator('#programa-compliance-range')).toHaveText(expectedCompliance.metrics?.range?.label || '');
      await expect(page.locator('#programa-gauge-card')).toHaveAttribute('data-range', expected.metrics?.range?.key || '');
      await expect(page.locator('#programa-compliance-card')).toHaveAttribute('data-range', expectedCompliance.metrics?.range?.key || '');
      const semanticColor = await page.evaluate((token) => getComputedStyle(document.body).getPropertyValue(`--bi-${token}`).trim(), expected.metrics?.range?.color_token);
      const renderedGauge = await chartState(page, 'programa-gauge');
      expect(renderedGauge.datasets[0].backgroundColor[0]).toBe(semanticColor);
      await expect(page.locator('#programa-compliance-explanation'))
        .toContainText(expectedCompliance.metrics?.explanation?.headline || '');

      assertNoRuntimeErrors(errors);
    });

    test('keeps compliance and delay details scoped while separating observed delay from P50 forecast', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const query = 'project_ids%5B%5D=70&project_ids%5B%5D=71&desde=2026-02-23&hasta=2026-05-18&sub=CONSTRUALMANZA&resp=Jhon%20Mauricio%20Sosa';
      const result = await getJson(page, `/api/bi/report/programa-general?${query}&role=R`);
      const detail = await getJson(page, `/api/bi/report/programa-general/compliance-detail?${query}&role=R&limit=100`);
      const delayDetail = await getJson(page, `/api/bi/report/programa-general/delay-detail?${query}&role=R&limit=100&offset=0`);

      expect(result.ok, 'filtered Programa General endpoint failed').toBe(true);
      expect(detail.ok, 'filtered compliance-detail endpoint failed').toBe(true);
      expect(delayDetail.ok, 'filtered delay-detail endpoint failed').toBe(true);

      const gauge = result.payload.charts?.['programa-gauge'];
      const compliance = result.payload.charts?.['programa-compliance'];
      const delay = result.payload.charts?.['programa-dias-retraso'];
      const summary = detail.payload.summary || {};
      expect(gauge?.metrics, 'programa-gauge metrics missing').toBeTruthy();
      expect(compliance?.metrics, 'programa-compliance metrics missing').toBeTruthy();
      expect(delay?.datasets?.[0]?.data, 'programa-dias-retraso dataset missing').toBeTruthy();

      expect(Number(summary.real_pct)).toBeCloseTo(Number(gauge.metrics.real_pct), 1);
      expect(Number(summary.theoretical_pct)).toBeCloseTo(Number(gauge.metrics.theoretical_pct), 1);
      expect(Number(summary.compliance_pct)).toBeCloseTo(Number(compliance.metrics.compliance_pct), 1);
      expect(Number(summary.gap_pp)).toBeCloseTo(Number(compliance.metrics.gap_pp), 1);
      expect(Number(delayDetail.payload.forecast?.variation_days?.p50)).toBeCloseTo(Number(delay.datasets[0].data[0]), 1);
      expect(Number(delayDetail.payload.observed?.max_observed_delay_days)).toBeCloseTo(Number(summary.delay_days), 1);
      expect(delay.metrics).not.toHaveProperty('observed_days');

      const allowedProjects = new Set([70, 71]);
      const subFilter = 'construalmanza';
      const respFilter = 'jhon mauricio sosa';
      for (const activity of detail.payload.activities || []) {
        expect(allowedProjects.has(Number(activity.project_id)), `activity escaped project scope: ${JSON.stringify(activity)}`).toBe(true);
        expect(Number(activity.gap_pp), `activity gap is not negative: ${JSON.stringify(activity)}`).toBeLessThan(0);
        expect(String(activity.subcontractor || '').toLowerCase(), `activity subcontractor escaped filter: ${JSON.stringify(activity)}`)
          .toContain(subFilter);
        expect(String(activity.responsible || '').toLowerCase(), `activity responsible escaped filter: ${JSON.stringify(activity)}`)
          .toContain(respFilter);
      }
      for (const activity of delayDetail.payload.activities || []) {
        expect(allowedProjects.has(Number(activity.project_id)), `delayed activity escaped project scope: ${JSON.stringify(activity)}`).toBe(true);
        expect(Number(activity.observed_delay_days), `delayed activity is not overdue: ${JSON.stringify(activity)}`).toBeGreaterThan(0);
        expect(Number(activity.progress_pct), `completed activity leaked into observed delay: ${JSON.stringify(activity)}`).toBeLessThan(100);
        expect(String(activity.subcontractor || '').toLowerCase(), `delayed activity subcontractor escaped filter: ${JSON.stringify(activity)}`)
          .toContain(subFilter);
        expect(String(activity.responsible || '').toLowerCase(), `delayed activity responsible escaped filter: ${JSON.stringify(activity)}`)
          .toContain(respFilter);
      }

      assertNoRuntimeErrors(errors);
    });

    test('returns 403 JSON from Programa General detail endpoints for a role without indicadores permission', async ({ page }) => {
      await logout(page);
      await login(page, { username: 'test.C', password: 'aia2026' });

      for (const path of [
        '/api/bi/report/programa-general/compliance-detail?project_id=73&semana=1',
        '/api/bi/report/programa-general/delay-detail?project_id=73&semana=1',
        '/api/bi/report/programa-general/radar-detail?project_id=73&semana=1&axis=productividad&limit=10',
        '/api/bi/report/programa-general/cnp-detail?project_id=73&semana=1&limit=10',
        '/api/bi/report/programa-general/cnc-detail?project_id=73&semana=1&limit=10',
        '/api/bi/report/programa-general/progress-detail?project_id=73&semana=1&limit=10',
      ]) {
        const blocked = await fetchJson(page, path, { headers: { Accept: 'application/json' } });
        expect(blocked.status, path).toBe(403);
        expect(blocked.payload?.error || '', path).toMatch(/Acceso denegado/i);
      }
    });

    test('keeps Programa General detail endpoints project scoped for a user who belongs to one project', async ({ page }) => {
      await logout(page);
      await login(page, { username: 'test.R', password: 'aia2026' });
      await selectProject(page, project);

      for (const detail of [
        { path: '/api/bi/report/programa-general/compliance-detail?project_id=68&semana=6', rows: 'activities' },
        { path: '/api/bi/report/programa-general/delay-detail?project_id=68&semana=6', rows: 'activities' },
        { path: '/api/bi/report/programa-general/radar-detail?project_id=68&semana=6&axis=productividad&limit=10', rows: 'records' },
        { path: '/api/bi/report/programa-general/cnp-detail?project_id=68&semana=6&limit=10', rows: 'activities' },
        { path: '/api/bi/report/programa-general/cnc-detail?project_id=68&semana=6&limit=10', rows: 'activities' },
        { path: '/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&limit=10', rows: 'activities' },
      ]) {
        const scoped = await fetchJson(page, detail.path, { headers: { Accept: 'application/json' } });
        if (scoped.status === 403) {
          expect(scoped.payload?.error || '', detail.path).toMatch(/Acceso denegado|proyecto/i);
          continue;
        }

        expect(scoped.ok, JSON.stringify(scoped)).toBe(true);
        expect((scoped.payload?.project_ids || []).map((id) => Number(id)), detail.path).not.toContain(68);
        expect(Number(scoped.payload?.project_id), detail.path).not.toBe(68);
        for (const activity of scoped.payload?.[detail.rows] || []) {
          expect(Number(activity.project_id), `activity escaped to non-session project: ${JSON.stringify(activity)}`).not.toBe(68);
        }
      }
    });

    test('opens progress composition on mobile with actionable cards and controls', async ({ page }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      const trigger = page.locator('#programa-gauge-drilldown-trigger');
      await expect(trigger).toBeVisible();
      await trigger.click();
      await expect(page.locator('#programa-gauge-drilldown')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Lo que más falta' })).toHaveAttribute('aria-pressed', 'true');
      await expect(page.locator('#programa-gauge-drilldown-group-by')).toHaveValue('project');
      await expect.poll(() => page.locator('.programa-gauge-drilldown-card').count(), { timeout: 15000 }).toBeGreaterThan(0);
      await expect(page.locator('#programa-gauge-drilldown-table')).toBeHidden();
      const overflow = await page.locator('#programa-gauge-drilldown').evaluate((panel) => Math.max(panel.scrollWidth - panel.clientWidth, document.documentElement.scrollWidth - window.innerWidth));
      expect(overflow).toBeLessThanOrEqual(1);
    });

    test('shows theoretical cutoff, exposes the detail endpoint, and opens compliance drill-down from desktop', async ({ page }) => {
      const result = await getJson(page, '/api/bi/report/programa-general?project_id=68&semana=6&role=R');
      const gauge = result.payload.charts?.['programa-gauge'];
      const compliance = result.payload.charts?.['programa-compliance'];
      const interaction = compliance?.interaction;
      expect(gauge?.metrics).toBeTruthy();
      expect(compliance?.metrics).toBeTruthy();
      expect(interaction?.detail_endpoint).toBe('/api/bi/report/programa-general/compliance-detail');
      expect(interaction?.desktop_action).toBe('dblclick');
      expect(interaction?.mobile_action).toBe('button');

      const detail = await getJson(page, `${interaction.detail_endpoint}?project_id=68&semana=6&role=R`);
      expect(detail.ok, 'compliance detail endpoint failed').toBe(true);
      expect(Array.isArray(detail.payload?.activities)).toBe(true);
      expect(detail.payload.activities.length).toBeGreaterThan(0);
      expect(detail.payload.activities.every((activity) => Number(activity.gap_pp) < 0)).toBe(true);

      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-gauge-theoretical')).toContainText(formatPercent(gauge.metrics.theoretical_pct));
      await expect(page.locator('#programa-gauge-gap')).toContainText(formatPp(gauge.metrics.gap_pp));
      await expect(page.locator('#programa-compliance-explanation')).toContainText(compliance.metrics.explanation.headline);
      await expect(page.locator('#programa-compliance-drilldown-trigger')).toBeVisible();
      await page.locator('#programa-compliance-card').dblclick();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-trigger')).toHaveAttribute('aria-expanded', 'true');
      await expect(page.locator('#programa-compliance-drilldown-close')).toBeFocused();
      await expect(page.locator('#programa-compliance-drilldown-summary')).toContainText('Avance teórico');
      await expect.poll(async () => page.locator('#programa-compliance-drilldown tbody tr').count(), { timeout: 15000 })
        .toBeGreaterThan(0);
      await expect(page.locator('#programa-compliance-drilldown tbody tr')).toHaveCount(detail.payload.activities.length);
      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-compliance-drilldown')).toBeHidden();
      await expect(page.locator('#programa-compliance-drilldown-trigger')).toBeFocused();
    });

    test('hides empty compliance drill-down results and restores live results across button and dblclick', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const pattern = '**/api/bi/report/programa-general/compliance-detail**';
      const emptyPayload = {
        respuesta: 'BIEN',
        report_key: 'programa-general-compliance-detail',
        summary: {
          real_pct: 31.2,
          theoretical_pct: 49.8,
          compliance_pct: 62.7,
          gap_pp: -18.6,
          delay_days: 0,
          explanation: {
            headline: 'No hay actividades negativas en este corte simulado.',
            implication: 'El detalle debe mostrar estado vacío y limpiar resultados previos.',
            method: 'Mock Playwright',
          },
        },
        explanation: {
          headline: 'No hay actividades negativas en este corte simulado.',
          implication: 'El detalle debe mostrar estado vacío y limpiar resultados previos.',
          method: 'Mock Playwright',
        },
        activities: [],
      };
      const emptyRoute = async (route) => {
        await route.fulfill({
          status: 200,
          contentType: 'application/json; charset=utf-8',
          body: JSON.stringify(emptyPayload),
        });
      };

      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-compliance-drilldown-trigger')).toBeVisible();

      await page.route(pattern, emptyRoute);
      await page.locator('#programa-compliance-drilldown-trigger').click();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-empty')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-table')).toBeHidden();
      await expect(page.locator('#programa-compliance-drilldown-cards')).toBeHidden();
      await expect(page.locator('#programa-compliance-drilldown-body tr')).toHaveCount(0);
      await expect(page.locator('.programa-compliance-drilldown-card')).toHaveCount(0);
      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-compliance-drilldown')).toBeHidden();

      await page.unroute(pattern, emptyRoute);
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.locator('#programa-compliance-card').dblclick();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-empty')).toBeHidden();
      await expect.poll(async () => page.locator('#programa-compliance-drilldown-body tr').count(), { timeout: 15000 })
        .toBeGreaterThan(0);
      await expect(page.locator('#programa-compliance-drilldown-table')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-cards')).toBeHidden();

      assertNoRuntimeErrors(errors);
    });

    test('renders compliance drill-down as mobile activity cards via button without overflow', async ({ page }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      const trigger = page.locator('#programa-compliance-drilldown-trigger');
      await expect(trigger).toBeVisible();
      await trigger.click();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect(trigger).toHaveAttribute('aria-expanded', 'true');
      await expect(page.locator('#programa-compliance-drilldown-table')).toBeHidden();
      await expect.poll(async () => page.locator('.programa-compliance-drilldown-card').count(), { timeout: 15000 })
        .toBeGreaterThan(0);
      const overflow = await page.locator('#programa-compliance-drilldown').evaluate((panel) => ({
        panel: Math.max(0, panel.scrollWidth - panel.clientWidth),
        page: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
      }));
      expect(overflow.panel).toBeLessThanOrEqual(1);
      expect(overflow.page).toBeLessThanOrEqual(1);
    });

    test('renders compliance drill-down as a table on tablet/desktop without overflow', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-compliance-card').dblclick();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect.poll(async () => page.locator('#programa-compliance-drilldown-body tr').count(), { timeout: 15000 })
        .toBeGreaterThan(0);
      await expect(page.locator('#programa-compliance-drilldown-table')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-cards')).toBeHidden();

      const layout = await page.locator('#programa-compliance-drilldown').evaluate((panel) => {
        const tableWrap = panel.querySelector('#programa-compliance-drilldown-table');
        const cards = panel.querySelector('#programa-compliance-drilldown-cards');
        return {
          panelOverflow: Math.max(0, panel.scrollWidth - panel.clientWidth),
          pageOverflow: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
          tableDisplay: tableWrap ? getComputedStyle(tableWrap).display : null,
          cardsDisplay: cards ? getComputedStyle(cards).display : null,
        };
      });

      expect(layout.tableDisplay).not.toBe('none');
      expect(layout.cardsDisplay).toBe('none');
      expect(layout.panelOverflow).toBeLessThanOrEqual(1);
      expect(layout.pageOverflow).toBeLessThanOrEqual(1);
    });

    test('opens progress composition by double click and renders desktop table', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-gauge-card').dblclick();
      await expect(page.locator('#programa-gauge-drilldown')).toBeVisible();
      await expect(page.locator('#programa-gauge-drilldown-table')).toBeVisible();
      await expect(page.locator('#programa-gauge-drilldown-cards')).toBeHidden();
      await page.getByRole('button', { name: 'Lo que ya suma' }).click();
      await expect(page.getByRole('button', { name: 'Lo que ya suma' })).toHaveAttribute('aria-pressed', 'true');
      await page.locator('#programa-gauge-drilldown-group-by').selectOption('responsible');
      await expect.poll(() => page.locator('#programa-gauge-drilldown-body tr').count(), { timeout: 15000 }).toBeGreaterThan(0);
    });

    test('renders Responsable AIA and Subcontratista labels from the real payload in drill-down table and cards', async ({ page }) => {
      const detail = await getJson(page, '/api/bi/report/programa-general/compliance-detail?project_id=68&semana=6&role=R&limit=100');
      expect(detail.ok, 'compliance detail endpoint failed').toBe(true);

      const ownedActivity = firstActivityWithOwnership(detail.payload?.activities);
      expect(ownedActivity, 'real compliance-detail payload is missing an activity with responsible and subcontractor').toBeTruthy();

      const responsiblePattern = new RegExp(`Responsable AIA:\\s*${escapeRegExp(ownedActivity.responsible)}`);
      const subcontractorPattern = new RegExp(`Subcontratista:\\s*${escapeRegExp(ownedActivity.subcontractor)}`);

      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-compliance-card').dblclick();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect.poll(async () => page.locator('#programa-compliance-drilldown-body tr').count(), { timeout: 15000 })
        .toBeGreaterThan(0);

      const desktopRow = page.locator('#programa-compliance-drilldown-body tr').filter({ hasText: ownedActivity.activity }).first();
      await expect(desktopRow).toBeVisible();
      await expect.soft(desktopRow.locator('td').nth(5)).toContainText(responsiblePattern);
      await expect.soft(desktopRow.locator('td').nth(5)).toContainText(subcontractorPattern);
      const desktopOverflow = await page.locator('#programa-compliance-drilldown').evaluate((panel) => Math.max(0, panel.scrollWidth - panel.clientWidth));
      expect(desktopOverflow).toBeLessThanOrEqual(1);
      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-compliance-drilldown')).toBeHidden();

      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-compliance-drilldown-trigger').click();
      await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();
      await expect(page.locator('#programa-compliance-drilldown-table')).toBeHidden();
      await expect.poll(async () => page.locator('.programa-compliance-drilldown-card').count(), { timeout: 15000 })
        .toBeGreaterThan(0);

      const mobileCard = page.locator('.programa-compliance-drilldown-card').filter({ hasText: ownedActivity.activity }).first();
      await expect(mobileCard).toBeVisible();
      await expect.soft(mobileCard).toContainText(responsiblePattern);
      await expect.soft(mobileCard).toContainText(subcontractorPattern);
      const mobileOverflow = await page.locator('#programa-compliance-drilldown').evaluate((panel) => Math.max(0, panel.scrollWidth - panel.clientWidth));
      expect(mobileOverflow).toBeLessThanOrEqual(1);
    });

    test('keeps drill-down surfaces structurally opaque against the backdrop in mobile cards and desktop table', async ({ page }) => {
      const layouts = [
        {
          label: 'mobile-cards-dark',
          width: 390,
          height: 844,
          theme: 'dark',
          open: async () => {
            await page.locator('#programa-compliance-drilldown-trigger').click();
            await expect(page.locator('#programa-compliance-drilldown-table')).toBeHidden();
            await expect.poll(async () => page.locator('.programa-compliance-drilldown-card').count(), { timeout: 15000 })
              .toBeGreaterThan(0);
          },
          visibleSelector: '.programa-compliance-drilldown-card',
        },
        {
          label: 'desktop-table-linen',
          width: 1280,
          height: 900,
          theme: 'linen',
          open: async () => {
            await page.locator('#programa-compliance-card').dblclick();
            await expect.poll(async () => page.locator('#programa-compliance-drilldown-body tr').count(), { timeout: 15000 })
              .toBeGreaterThan(0);
            await expect(page.locator('#programa-compliance-drilldown-table')).toBeVisible();
          },
          visibleSelector: '#programa-compliance-drilldown-table',
        },
      ];

      for (const layout of layouts) {
        await page.setViewportSize({ width: layout.width, height: layout.height });
        await page.goto(`/bi/programa-general?project_id=68&semana=6&theme=${layout.theme}`, { waitUntil: 'domcontentloaded' });
        await layout.open();
        await expect(page.locator('#programa-compliance-drilldown')).toBeVisible();

        const audit = await page.evaluate((visibleSelector) => {
          function alphaFromColor(color) {
            if (!color || color === 'transparent') return 0;
            const modernAlpha = color.match(/\/\s*([0-9.]+%?)\s*\)$/);
            if (modernAlpha) {
              const rawAlpha = modernAlpha[1];
              const alpha = rawAlpha.endsWith('%')
                ? Number(rawAlpha.slice(0, -1)) / 100
                : Number(rawAlpha);
              return Number.isFinite(alpha) ? alpha : 1;
            }
            const match = color.match(/rgba?\(([^)]+)\)/i);
            if (!match) return 1;
            const parts = match[1].split(',').map((part) => part.trim());
            if (parts.length < 4) return 1;
            const alpha = Number(parts[3]);
            return Number.isFinite(alpha) ? alpha : 1;
          }

          function labelFor(element) {
            if (!element) return null;
            if (element.id) return `#${element.id}`;
            if (element.classList?.length) return `.${element.classList[0]}`;
            return element.tagName.toLowerCase();
          }

          function firstOpaqueSource(element, stopAt) {
            const chain = [];
            let current = element;
            while (current && current instanceof HTMLElement) {
              const style = getComputedStyle(current);
              const alpha = alphaFromColor(style.backgroundColor);
              chain.push({
                label: labelFor(current),
                backgroundColor: style.backgroundColor,
                alpha,
              });
              if (alpha >= 0.85) {
                return { found: true, source: labelFor(current), alpha, chain };
              }
              if (current === stopAt) break;
              current = current.parentElement;
            }
            return { found: false, source: null, alpha: 0, chain };
          }

          const drilldown = document.getElementById('programa-compliance-drilldown');
          const panel = drilldown?.querySelector('.bi-drilldown__panel') || null;
          const body = drilldown?.querySelector('.bi-drilldown__body') || null;
          const visibleSurface = drilldown?.querySelector(visibleSelector) || null;
          const backdrop = drilldown?.querySelector('.bi-drilldown__backdrop') || null;
          const pageRoot = document.querySelector('.bi-main-content') || document.body;

          if (!drilldown || !panel || !body || !visibleSurface || !backdrop) {
            return null;
          }

          return {
            backdropAlpha: alphaFromColor(getComputedStyle(backdrop).backgroundColor),
            panel: firstOpaqueSource(panel, drilldown),
            body: firstOpaqueSource(body, drilldown),
            visibleSurface: firstOpaqueSource(visibleSurface, drilldown),
            panelOverflowX: Math.max(0, panel.scrollWidth - panel.clientWidth),
            pageOverflowX: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
            pageBackground: getComputedStyle(pageRoot).backgroundColor,
          };
        }, layout.visibleSelector);

        expect.soft(audit, `${layout.label}: missing drill-down audit nodes`).not.toBeNull();
        if (audit) {
          expect.soft(audit.backdropAlpha, `${layout.label}: backdrop should dim dashboard`).toBeGreaterThan(0.1);
          expect.soft(audit.backdropAlpha, `${layout.label}: backdrop should remain translucent`).toBeLessThan(1);
          expect.soft(audit.panel.found, `${layout.label}: panel lost opaque paint source`).toBe(true);
          expect.soft(audit.body.found, `${layout.label}: body lost opaque paint source`).toBe(true);
          expect.soft(audit.visibleSurface.found, `${layout.label}: visible surface lost opaque paint source`).toBe(true);
          expect.soft(audit.panel.alpha, `${layout.label}: panel paint source is too transparent`).toBeGreaterThanOrEqual(0.85);
          expect.soft(audit.body.alpha, `${layout.label}: body paint source is too transparent`).toBeGreaterThanOrEqual(0.85);
          expect.soft(audit.visibleSurface.alpha, `${layout.label}: visible surface paint source is too transparent`).toBeGreaterThanOrEqual(0.85);
          expect.soft(audit.panelOverflowX, `${layout.label}: panel overflowX`).toBeLessThanOrEqual(1);
          expect.soft(audit.pageOverflowX, `${layout.label}: page overflowX`).toBeLessThanOrEqual(1);
        }

        await page.keyboard.press('Escape');
        await expect(page.locator('#programa-compliance-drilldown')).toBeHidden();
      }
    });

    test('keeps DOM gauge legends separated from the visual area and readout on mobile and desktop with zero horizontal overflow', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const viewports = [
        { label: 'mobile', width: 390, height: 844, theme: 'dark' },
        { label: 'desktop', width: 1280, height: 900, theme: 'linen' },
      ];

      for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto(`/bi/programa-general?project_id=68&semana=6&theme=${viewport.theme}`, { waitUntil: 'domcontentloaded' });
        await expect.poll(async () => {
          const gauge = await chartState(page, 'programa-gauge');
          const compliance = await chartState(page, 'programa-compliance');
          return Boolean(gauge && compliance);
        }, { timeout: 15000 }).toBe(true);

        const layout = await gaugeLegendLayout(page);
        expect(layout.pageOverflowX, `${viewport.label}: page overflowX`).toBeLessThanOrEqual(1);

        for (const metric of layout.metrics) {
          expect(metric.missing, `${viewport.label}: missing layout metric for ${metric.id}`).toBe(false);
          expect(metric.chartLegendDisplay, `${viewport.label}: Chart.js legend should stay disabled for ${metric.id}`).toBe(false);
          expect(metric.legendHeight, `${viewport.label}: legend hidden for ${metric.id}`).toBeGreaterThan(0);
          expect(metric.visualOverlapsReadout, `${viewport.label}: readout overlaps gauge visual for ${metric.id}`).toBe(false);
          expect(metric.legendOverlapsVisual, `${viewport.label}: legend overlaps gauge visual for ${metric.id}`).toBe(false);
          expect(metric.legendOverlapsReadout, `${viewport.label}: legend overlaps readout for ${metric.id}`).toBe(false);
          expect(metric.readoutGapToVisual, `${viewport.label}: readout touches visual for ${metric.id}`).toBeGreaterThanOrEqual(4);
          expect(metric.legendGapToReadout, `${viewport.label}: legend touches readout for ${metric.id}`).toBeGreaterThanOrEqual(4);
          expect(metric.panelOverflowX, `${viewport.label}: panel overflowX for ${metric.id}`).toBeLessThanOrEqual(1);
          expect(metric.visualOverflowX, `${viewport.label}: visual overflowX for ${metric.id}`).toBeLessThanOrEqual(1);
          expect(metric.canvasOverflowX, `${viewport.label}: canvas leaks outside visual for ${metric.id}`).toBeLessThanOrEqual(1);
          expect(metric.cardOverflowX, `${viewport.label}: card overflowX for ${metric.id}`).toBeLessThanOrEqual(1);
        }
      }

      assertNoRuntimeErrors(errors);
    });

    test('centers Programa General gauge readout on the rendered semicircle', async ({ page }) => {
      await page.setViewportSize({ width: 663, height: 1000 });
      await page.goto(`/bi/programa-general?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&theme=dark`, { waitUntil: 'domcontentloaded' });
      await expect.poll(() => chartState(page, 'programa-gauge'), { timeout: 15000 }).not.toBeNull();

      const alignment = await page.evaluate(() => {
        const canvas = document.getElementById('programa-gauge');
        const readout = document.querySelector('.bi-gauge-readout');
        const shell = readout?.parentElement;
        if (!canvas || !readout || !shell) return null;

        const canvasRect = canvas.getBoundingClientRect();
        const readoutRect = readout.getBoundingClientRect();
        const shellRect = shell.getBoundingClientRect();
        const canvasCenterX = canvasRect.left + (canvasRect.width / 2);
        const shellCenterX = shellRect.left + (shellRect.width / 2);

        return {
          horizontalOffset: Math.abs(canvasCenterX - shellCenterX),
          overflow: Math.max(0, readoutRect.right - shellRect.right, shellRect.left - readoutRect.left),
        };
      });

      expect(alignment).not.toBeNull();
      expect(alignment.horizontalOffset).toBeLessThanOrEqual(2);
      expect(alignment.overflow).toBeLessThanOrEqual(1);
    });

    test('uses two metric columns on landscape tablet without clipping cards', async ({ page }) => {
      await page.setViewportSize({ width: 1024, height: 768 });
      await page.goto(`/bi/programa-general?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&theme=dark`, { waitUntil: 'domcontentloaded' });
      await expect.poll(() => chartState(page, 'programa-compliance'), { timeout: 15000 }).not.toBeNull();

      const layout = await page.evaluate(() => {
        const canvas = document.getElementById('programa-compliance');
        const grid = canvas?.closest('.grid');
        const main = document.querySelector('.bi-main-content');
        if (!grid || !main) return null;
        const columns = getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean);
        const gridRect = grid.getBoundingClientRect();
        const mainRect = main.getBoundingClientRect();
        return {
          columnCount: columns.length,
          clippedRight: Math.max(0, gridRect.right - mainRect.right),
          pageOverflow: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
        };
      });

      expect(layout).not.toBeNull();
      expect(layout.columnCount).toBe(2);
      expect(layout.clippedRight).toBeLessThanOrEqual(1);
      expect(layout.pageOverflow).toBeLessThanOrEqual(1);
    });

    test('keeps the BI shell horizontal on landscape tablet and the radar dense on desktop', async ({ page }) => {
      await page.setViewportSize({ width: 1024, height: 768 });
      await page.goto(`/bi/programa-general?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&theme=dark`, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-radar-card')).toBeVisible();

      const tablet = await page.evaluate(() => {
        const sidebar = document.querySelector('.bi-sidebar');
        const mainShell = document.querySelector('.bi-main-shell');
        if (!sidebar || !mainShell) return null;
        const bodyStyle = getComputedStyle(document.body);
        const sidebarRect = sidebar.getBoundingClientRect();
        const mainRect = mainShell.getBoundingClientRect();
        return {
          bodyDirection: bodyStyle.flexDirection,
          bodyWrap: bodyStyle.flexWrap,
          sidebarWidth: sidebarRect.width,
          mainStartsAfterSidebar: mainRect.left >= sidebarRect.right - 1,
          pageOverflow: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
        };
      });

      expect(tablet).not.toBeNull();
      expect(tablet.bodyDirection).toBe('row');
      expect(tablet.bodyWrap).toBe('nowrap');
      expect(tablet.sidebarWidth).toBeGreaterThanOrEqual(250);
      expect(tablet.mainStartsAfterSidebar).toBe(true);
      expect(tablet.pageOverflow).toBeLessThanOrEqual(1);

      await page.setViewportSize({ width: 1440, height: 900 });
      const desktop = await page.evaluate(() => {
        const card = document.getElementById('programa-radar-card');
        const axisList = document.getElementById('programa-radar-axes');
        const parent = card?.parentElement;
        if (!card || !axisList || !parent) return null;
        const cardRect = card.getBoundingClientRect();
        const parentRect = parent.getBoundingClientRect();
        return {
          parentColumns: getComputedStyle(parent).gridTemplateColumns.split(' ').filter(Boolean).length,
          axisColumns: getComputedStyle(axisList).gridTemplateColumns.split(' ').filter(Boolean).length,
          cardWidthRatio: parentRect.width > 0 ? cardRect.width / parentRect.width : 0,
          cardOverflow: Math.max(0, card.scrollWidth - card.clientWidth),
          pageOverflow: Math.max(0, document.documentElement.scrollWidth - window.innerWidth),
        };
      });

      expect(desktop).not.toBeNull();
      expect(desktop.parentColumns).toBe(2);
      expect(desktop.axisColumns).toBe(3);
      expect(desktop.cardWidthRatio).toBeGreaterThanOrEqual(0.95);
      expect(desktop.cardOverflow).toBeLessThanOrEqual(1);
      expect(desktop.pageOverflow).toBeLessThanOrEqual(1);
    });

    test('paginates observed overdue activities while separating them from the probabilistic finish forecast', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const requests = [];
      const pattern = '**/api/bi/report/programa-general/delay-detail**';
      const activities = [
        {
          project_id: 68, project: 'Optimización Aeropuerto JMC', activity: 'Actividad vencida de prueba',
          planned_finish: '2026-06-09', cutoff: '2026-06-30', observed_delay_days: 21,
          planned_pct: 100, progress_pct: 58, critical: true,
          responsible: 'Responsable AIA', subcontractor: 'Sub-Contratista',
          implication: 'Puede desplazar la fecha final; requiere recuperación inmediata.',
        },
        {
          project_id: 68, project: 'Optimización Aeropuerto JMC', activity: 'Segunda actividad vencida de prueba',
          planned_finish: '2026-06-16', cutoff: '2026-06-30', observed_delay_days: 14,
          planned_pct: 90, progress_pct: 67, critical: false,
          responsible: 'Responsable AIA', subcontractor: 'Sub-Contratista',
          implication: 'Debe recuperar el avance para proteger la fecha final.',
        },
      ];
      const routeHandler = async (route) => {
        const url = new URL(route.request().url());
        const offset = Number(url.searchParams.get('offset') || 0);
        const pageActivities = activities.slice(offset, offset + 1);
        requests.push({
          projectId: url.searchParams.get('project_id'),
          week: url.searchParams.get('semana'),
          limit: url.searchParams.get('limit'),
          offset: url.searchParams.get('offset'),
        });
        await route.fulfill({
          status: 200,
          contentType: 'application/json; charset=utf-8',
          body: JSON.stringify({
            respuesta: 'BIEN',
            forecast: {
              metric_key: 'pg_finish_variance_days_p50', availability: true, simulation_count: 240,
              contractual_finish: '2026-07-31',
              forecast: { p10_finish: '2026-07-27', p50_finish: '2026-08-12', p90_finish: '2026-09-03' },
              variation_days: { p10: -4, p50: 12, p90: 34 },
              project_breakdown: [{
                project_id: 68, project: 'Optimización Aeropuerto JMC', status: 'delayed', availability: true,
                contractual_finish: '2026-07-31', p50_finish: '2026-08-12', variation_days: { p50: 12 },
              }],
            },
            observed: {
              metric_key: 'pg_observed_activity_delay_days', delayed_activity_count: 2,
              critical_delayed_count: 1, max_observed_delay_days: 21,
            },
            pagination: {
              limit: 50, offset, returned_count: pageActivities.length, total: activities.length,
              next_offset: offset + pageActivities.length, has_more: offset + pageActivities.length < activities.length,
            },
            activities: pageActivities,
          }),
        });
      };
      await page.route(pattern, routeHandler);
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#programa-delay-card')).toContainText('Variación probable de fecha final');
      await expect(page.locator('#programa-delay-status')).not.toContainText('Atraso operativo observado');
      await page.locator('#programa-delay-card h3').dispatchEvent('dblclick', { bubbles: true });
      await expect(page.locator('#programa-delay-drilldown')).toBeVisible();
      await expect(page.locator('#programa-delay-drilldown-close')).toBeFocused();
      await expect(page.locator('#programa-delay-drilldown-summary')).toContainText('Variación P50: +12 días');
      await expect(page.locator('#programa-delay-drilldown-projects')).toContainText('Optimización Aeropuerto JMC');
      await expect(page.locator('#programa-delay-drilldown-observed')).toContainText('Actividades vencidas: 2');
      await expect(page.locator('#programa-delay-drilldown-body tr')).toHaveCount(1);
      await expect(page.locator('#programa-delay-drilldown-results')).toBeVisible();
      expect(requests[0]).toMatchObject({ projectId: '68', week: '6', limit: '50', offset: '0' });
      await expect(page.locator('#programa-delay-drilldown-more')).toBeVisible();
      await page.locator('#programa-delay-drilldown-more').click();
      await expect(page.locator('#programa-delay-drilldown-body tr')).toHaveCount(2);
      await expect(page.locator('#programa-delay-drilldown-body')).toContainText('Segunda actividad vencida de prueba');
      expect(requests).toHaveLength(2);
      expect(requests[1]).toMatchObject({ projectId: '68', week: '6', limit: '50', offset: '1' });
      await expect(page.locator('#programa-delay-drilldown-more')).toBeHidden();

      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-delay-drilldown')).toBeHidden();
      await expect(page.locator('#programa-delay-drilldown-trigger')).toBeFocused();
      await page.setViewportSize({ width: 390, height: 844 });
      await page.locator('#programa-delay-drilldown-trigger').click();
      await expect(page.locator('#programa-delay-drilldown-results')).toBeHidden();
      await expect(page.locator('#programa-delay-drilldown-cards')).toBeVisible();
      await expect(page.locator('#programa-delay-drilldown-cards')).toContainText('21 días vencida');
      const overflow = await page.locator('#programa-delay-drilldown').evaluate((panel) => Math.max(
        0,
        panel.scrollWidth - panel.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
      ));
      expect(overflow).toBeLessThanOrEqual(1);

      await page.unroute(pattern, routeHandler);
      assertNoRuntimeErrors(errors);
    });

    test('explica los ejes del radar y abre su detalle filtrado como tabla y cards', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const requests = [];
      const pattern = '**/api/bi/report/programa-general/radar-detail**';
      const routeHandler = async (route) => {
        const url = new URL(route.request().url());
        requests.push({
          axis: url.searchParams.get('axis'),
          projectId: url.searchParams.get('project_id'),
          week: url.searchParams.get('semana'),
          limit: url.searchParams.get('limit'),
          offset: url.searchParams.get('offset'),
        });
        const offset = Number(url.searchParams.get('offset') || 0);
        await route.fulfill({
          status: 200,
          contentType: 'application/json; charset=utf-8',
          body: JSON.stringify({
            respuesta: 'BIEN',
            axis: url.searchParams.get('axis'),
            summary: { total_population: 2, eligible_count: 2, excluded_count: 0 },
            pagination: { limit: 50, offset, returned_count: 1, next_offset: offset + 1, has_more: offset === 0 },
            explanation: 'Detalle trazable del eje solicitado.',
            records: [{
              project: 'Optimización Aeropuerto JMC',
              cutoff: '6',
              activity: `Actividad de prueba del radar ${offset + 1}`,
              unit: 'm3',
              commitment: 12,
              executed: 8,
              p_completed: 0.667,
              pac: 1,
              responsible: 'Responsable AIA',
              subcontractor: 'Sub-Contratista',
              critical: true,
              eligibility: {
                productividad: { eligible: true, reason: null },
                eficiencia: { eligible: true, reason: null },
                desempeno: { eligible: true, reason: null },
              },
              exclusion_reasons: { productividad: null, eficiencia: null, desempeno: null },
            }],
          }),
        });
      };

      await page.route(pattern, routeHandler);
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#programa-radar-axes .bi-radar-axis')).toHaveCount(3);
      await expect(page.locator('#programa-radar-axes')).toContainText('Avance promedio');
      await expect(page.locator('#programa-radar-axes')).toContainText('Cumplimiento normalizado de cantidades');
      await expect(page.locator('#programa-radar-axes')).toContainText('PAC');
      await expect(page.locator('#programa-radar-axes [role="tooltip"]')).toHaveCount(3);
      await expect(page.locator('#programa-radar-detail-trigger')).toBeVisible();
      const tooltipCopy = await page.locator('#programa-radar-axes [role="tooltip"]').allTextContents();
      expect(tooltipCopy.every((copy) => !copy.includes('..'))).toBe(true);

      await page.locator('#programa-radar-card h3').dispatchEvent('dblclick', { bubbles: true });
      await expect(page.locator('#programa-radar-drilldown')).toBeVisible();
      await expect(page.locator('#programa-radar-drilldown-close')).toBeFocused();
      await expect.poll(() => page.locator('#programa-radar-drilldown-body tr').count(), { timeout: 15000 }).toBe(1);
      await expect(page.locator('#programa-radar-drilldown-results')).toContainText('Responsable AIA');
      await expect(page.locator('#programa-radar-drilldown-results')).toContainText('Sub-Contratista');
      await expect(page.locator('#programa-radar-drilldown-results')).toContainText('Elegible');
      expect(requests[0]?.projectId).toBe('68');
      expect(requests[0]?.week).toBe('6');
      expect(requests[0]?.limit).toBe('50');
      expect(requests[0]?.offset).toBe('0');

      await expect(page.locator('#programa-radar-drilldown-more')).toBeVisible();
      await page.locator('#programa-radar-drilldown-more').click();
      await expect.poll(() => page.locator('#programa-radar-drilldown-body tr').count(), { timeout: 15000 }).toBe(2);
      expect(requests.some((request) => request.offset === '1')).toBe(true);

      await page.getByRole('tab', { name: 'Avance promedio' }).focus();
      await page.keyboard.press('ArrowRight');
      await expect(page.getByRole('tab', { name: 'Cantidades normalizadas' })).toHaveAttribute('aria-selected', 'true');
      await expect(page.getByRole('tab', { name: 'Cantidades normalizadas' })).toHaveAttribute('tabindex', '0');
      await expect(page.getByRole('tab', { name: 'Avance promedio' })).toHaveAttribute('tabindex', '-1');
      await expect(page.locator('#programa-radar-drilldown-panel')).toHaveAttribute('aria-labelledby', 'programa-radar-tab-eficiencia');
      await expect.poll(() => requests.some((request) => request.axis === 'eficiencia'), { timeout: 15000 }).toBe(true);
      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-radar-drilldown')).toBeHidden();
      await expect(page.locator('#programa-radar-detail-trigger')).toBeFocused();

      await page.setViewportSize({ width: 390, height: 844 });
      await page.locator('#programa-radar-detail-trigger').dispatchEvent('click', { bubbles: true });
      await expect(page.locator('#programa-radar-drilldown-results')).toBeHidden();
      await expect.poll(() => page.locator('.bi-radar-drilldown-card').count(), { timeout: 15000 }).toBe(1);
      await expect(page.locator('#programa-radar-drilldown-body tr')).toHaveCount(0);
      const overflow = await page.locator('#programa-radar-drilldown').evaluate((panel) => Math.max(
        0,
        panel.scrollWidth - panel.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
      ));
      expect(overflow).toBeLessThanOrEqual(1);

      await page.unroute(pattern, routeHandler);
      assertNoRuntimeErrors(errors);
    });

    test('distinguishes a Radar detail failure from a valid empty population', async ({ page }) => {
      const pattern = '**/api/bi/report/programa-general/radar-detail**';
      await page.route(pattern, (route) => route.fulfill({
        status: 503,
        contentType: 'application/json; charset=utf-8',
        body: JSON.stringify({ respuesta: 'ERROR', message: 'Servicio temporalmente no disponible.' }),
      }));
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-radar-detail-trigger').click();

      await expect(page.locator('#programa-radar-drilldown-error')).toBeVisible();
      await expect(page.locator('#programa-radar-drilldown-error')).toContainText('No fue posible consultar');
      await expect(page.locator('#programa-radar-drilldown-empty')).toBeHidden();
      await expect(page.locator('#programa-radar-drilldown-results')).toBeHidden();
      await expect(page.locator('#programa-radar-drilldown-cards')).toBeHidden();

      await page.unroute(pattern);
    });

    test('expone CNP accionable con detalle responsivo y paginación coherente', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const query = 'project_id=68&semana=6&theme=linen';
      const detailRequests = [];
      page.on('request', (request) => {
        if (request.url().includes('/api/bi/report/programa-general/cnp-detail')) {
          detailRequests.push(new URL(request.url()).searchParams);
        }
      });

      const report = await getJson(page, `/api/bi/report/programa-general?${query}`);
      expect(report.ok, 'Programa General CNP report failed').toBe(true);
      const cnp = report.payload.charts?.['programa-cnp'];
      expect(cnp?.metrics?.metric_key).toBe('pg_cnp_activity_count');
      expect(Number(cnp?.metrics?.total)).toBe(33);

      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto(`/bi/programa-general?${query}`, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-cnp-insight')).toBeVisible();
      await expect(page.locator('#programa-cnp-insight')).toContainText('33');
      await expect(page.locator('#programa-cnp-drilldown-trigger')).toBeVisible();
      await expect.poll(() => chartState(page, 'programa-cnp'), { timeout: 15000 }).not.toBeNull();
      const categoryButton = page.getByRole('button', { name: 'Ver CNP de la categoría Programación' });
      await expect(categoryButton).toBeVisible();
      await categoryButton.focus();
      await page.keyboard.press('Enter');
      await expect(page.locator('#programa-causal-drilldown')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-explanation')).toContainText('Programación');
      await expect.poll(() => detailRequests.some((params) => params.get('category') === 'Programación'), { timeout: 15000 }).toBe(true);
      await page.keyboard.press('Escape');
      await expect(categoryButton).toBeFocused();

      await page.locator('#programa-cnp').scrollIntoViewIfNeeded();
      const segmentPoint = await page.evaluate(() => {
        const chart = window.Chart?.getChart(document.getElementById('programa-cnp'));
        const point = chart?.getDatasetMeta(0)?.data?.[0]?.tooltipPosition();
        return point ? { x: point.x, y: point.y } : null;
      });
      const canvasBox = await page.locator('#programa-cnp').boundingBox();
      expect(segmentPoint).toBeTruthy();
      expect(canvasBox).toBeTruthy();
      await page.mouse.dblclick(canvasBox.x + segmentPoint.x, canvasBox.y + segmentPoint.y, { delay: 80 });
      await expect(page.locator('#programa-causal-drilldown')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-explanation')).toContainText('Programación');
      await expect.poll(() => detailRequests.some((params) => params.get('category') === 'Programación'), { timeout: 15000 }).toBe(true);
      await page.locator('#programa-causal-drilldown-close').click();

      await page.locator('#programa-cnp-drilldown-trigger').click();

      await expect(page.locator('#programa-causal-drilldown')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-title')).toHaveText('Causas de No Programación');
      await expect(page.locator('#programa-causal-drilldown-summary')).toContainText(String(cnp.metrics.total));
      await expect(page.locator('#programa-causal-drilldown-table')).toBeVisible();
      await expect.poll(() => page.locator('#programa-causal-drilldown-body tr').count(), { timeout: 15000 }).toBeGreaterThan(0);
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Impacto / acción');
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Responsable');
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Crítica: No');
      await expect(page.locator('#programa-causal-drilldown a[href*="/programacion-semanal/cnp"]')).toHaveCount(0);

      const uiLimit = Number(detailRequests[0]?.get('limit') || 50);
      const detail = await getJson(page, `/api/bi/report/programa-general/cnp-detail?project_id=68&semana=6&limit=${uiLimit}&offset=0`);
      expect(detail.ok, 'CNP detail endpoint failed').toBe(true);
      expect(Number(detail.payload.summary?.total)).toBe(Number(cnp.metrics.total));
      const loadMore = page.locator('#programa-causal-drilldown-load-more');
      if (detail.payload.pagination?.has_more) {
        await expect(loadMore).toBeVisible();
        const initialRows = await page.locator('#programa-causal-drilldown-body tr').count();
        await loadMore.click();
        await expect.poll(() => page.locator('#programa-causal-drilldown-body tr').count(), { timeout: 15000 })
          .toBeGreaterThan(initialRows);
        expect(detailRequests.some((params) => Number(params.get('offset')) > 0)).toBe(true);
      } else {
        await expect(loadMore).toBeHidden();
      }

      const keys = [];
      let offset = 0;
      let total = 0;
      do {
        const pageResult = await getJson(page, `/api/bi/report/programa-general/cnp-detail?project_id=68&semana=6&limit=10&offset=${offset}&include_summary=${offset === 0 ? 1 : 0}`);
        expect(pageResult.ok, JSON.stringify(pageResult)).toBe(true);
        total = Number(pageResult.payload.pagination?.total || 0);
        keys.push(...(pageResult.payload.activities || []).map((activity) => activity.source_row_key));
        offset = Number(pageResult.payload.pagination?.next_offset || total);
        if (!pageResult.payload.pagination?.has_more) break;
      } while (offset < total);
      expect(keys).toHaveLength(total);
      expect(new Set(keys).size).toBe(total);

      await page.setViewportSize({ width: 1024, height: 768 });
      await expect(page.locator('#programa-causal-drilldown-table')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-cards')).toBeHidden();

      await page.setViewportSize({ width: 390, height: 844 });
      await expect(page.locator('#programa-causal-drilldown-table')).toBeHidden();
      await expect.poll(() => page.locator('.bi-causal-drilldown-card').count(), { timeout: 15000 }).toBeGreaterThan(0);
      await expect(page.locator('#programa-causal-drilldown-cards')).toContainText('Impacto');
      await expect(page.locator('#programa-causal-drilldown-cards')).toContainText('Acción recomendada');
      const overflow = await page.locator('#programa-causal-drilldown').evaluate((dialog) => Math.max(
        0,
        dialog.scrollWidth - dialog.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
      ));
      expect(overflow).toBeLessThanOrEqual(1);

      assertNoRuntimeErrors(errors);
    });

    test('expone CNC accionable sin inventar causas cuando el corte está vacío', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const query = 'project_id=68&semana=4&theme=dark';
      const detailRequests = [];
      page.on('request', (request) => {
        if (request.url().includes('/api/bi/report/programa-general/cnc-detail')) {
          detailRequests.push(new URL(request.url()).searchParams);
        }
      });

      const report = await getJson(page, `/api/bi/report/programa-general?${query}`);
      expect(report.ok, 'Programa General CNC report failed').toBe(true);
      const cnc = report.payload.charts?.['programa-cnc'];
      expect(cnc?.metrics?.metric_key).toBe('pg_cnc_activity_count');
      expect(Number(cnc?.metrics?.total)).toBe(8);
      expect(Number(cnc?.metrics?.zero_execution_count)).toBe(8);
      expect(Number(cnc?.metrics?.severe_gap_count)).toBe(8);
      expect(Number(cnc?.metrics?.average_completion_pct)).toBe(0);
      expect(cnc?.metrics?.categories?.[0]).toMatchObject({ category: 'Programación', count: 5 });

      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto(`/bi/programa-general?${query}`, { waitUntil: 'domcontentloaded' });
      const insight = page.locator('#programa-cnc-insight');
      await expect(insight).toBeVisible();
      await expect(insight).toContainText('8');
      await expect(insight).toContainText('Sin ejecución');
      await expect(insight).toContainText('Cumplimiento medio');
      await expect(insight).toContainText('Programación: 5');
      await expect.poll(() => chartState(page, 'programa-cnc'), { timeout: 15000 }).not.toBeNull();

      const categoryButton = page.getByRole('button', { name: 'Ver CNC de la categoría Programación' });
      await expect(categoryButton).toBeVisible();
      await categoryButton.focus();
      await page.keyboard.press('Enter');
      await expect(page.locator('#programa-causal-drilldown')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-title')).toHaveText('Causas de No Cumplimiento');
      await expect(page.locator('#programa-causal-drilldown-explanation')).toContainText('Programación');
      await expect.poll(() => detailRequests.some((params) => params.get('category') === 'Programación'), { timeout: 15000 }).toBe(true);
      await page.keyboard.press('Escape');
      await expect(categoryButton).toBeFocused();

      await page.locator('#programa-cnc-drilldown-trigger').click();
      await expect(page.locator('#programa-causal-drilldown')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-measure-heading')).toHaveText('Compromiso / ejecución');
      await expect(page.locator('#programa-causal-drilldown-summary')).toContainText('Mostradas: 8 de 8');
      await expect(page.locator('#programa-causal-drilldown-summary')).toContainText('Sin ejecución: 8');
      await expect(page.locator('#programa-causal-drilldown-summary')).toContainText('Brecha ≥ 50%: 8');
      await expect(page.locator('#programa-causal-drilldown-table')).toBeVisible();
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Compromiso:');
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Real:');
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Cumplimiento: 0%');
      await expect(page.locator('#programa-causal-drilldown-table')).toContainText('Brecha:');

      const detail = await getJson(page, '/api/bi/report/programa-general/cnc-detail?project_id=68&semana=4&limit=25&offset=0');
      expect(detail.ok, 'CNC detail endpoint failed').toBe(true);
      expect(Number(detail.payload.summary?.total)).toBe(Number(cnc.metrics.total));
      expect(detail.payload.activities).toHaveLength(8);
      expect(new Set(detail.payload.activities.map((activity) => activity.source_row_key)).size).toBe(8);
      for (const activity of detail.payload.activities) {
        expect(Number(activity.committed_quantity)).toBeGreaterThan(0);
        expect(Number(activity.executed_quantity)).toBe(0);
        expect(Number(activity.completion_pct)).toBe(0);
        expect(Number(activity.shortfall_pct)).toBe(100);
        expect(activity.execution_status).toBe('not_executed');
        expect(String(activity.responsible || activity.subcontractor)).not.toBe('');
      }

      await page.setViewportSize({ width: 390, height: 844 });
      await expect(page.locator('#programa-causal-drilldown-table')).toBeHidden();
      await expect(page.locator('.bi-causal-drilldown-card')).toHaveCount(8);
      const cards = page.locator('#programa-causal-drilldown-cards');
      await expect(cards).toContainText('Compromiso');
      await expect(cards).toContainText('Ejecución real');
      await expect(cards).toContainText('Cumplimiento');
      await expect(cards).toContainText('Subcontratista');
      const overflow = await page.locator('#programa-causal-drilldown').evaluate((dialog) => Math.max(
        0,
        dialog.scrollWidth - dialog.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
      ));
      expect(overflow).toBeLessThanOrEqual(1);
      await page.locator('#programa-causal-drilldown-close').click();

      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-cnc-insight')).toContainText('No hay CNC registradas');
      await expect(page.locator('#programa-cnc-insight')).toContainText('no inventa causas');
      await expect(page.locator('#programa-cnc-category-actions')).toBeHidden();
      const emptyChart = await chartState(page, 'programa-cnc');
      expect(emptyChart?.labels).toEqual(['Sin registros']);
      expect(emptyChart?.datasets?.[0]?.data).toEqual([0]);

      assertNoRuntimeErrors(errors);
    });

    test('conserva los registros CNP y permite reintentar si falla Cargar más', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await page.locator('#programa-cnp-drilldown-trigger').click();
      const rows = page.locator('#programa-causal-drilldown-body tr');
      await expect.poll(() => rows.count(), { timeout: 15000 }).toBeGreaterThan(0);
      const initialRows = await rows.count();
      let failedOnce = false;
      await page.route('**/api/bi/report/programa-general/cnp-detail**', async (route) => {
        const offset = Number(new URL(route.request().url()).searchParams.get('offset') || 0);
        if (offset > 0 && !failedOnce) {
          failedOnce = true;
          await route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ respuesta: 'ERROR' }) });
          return;
        }
        await route.continue();
      });

      const loadMore = page.locator('#programa-causal-drilldown-load-more');
      await expect(loadMore).toBeVisible();
      await loadMore.click();
      await expect(page.locator('#programa-causal-drilldown-load-more-error')).toBeVisible();
      await expect(rows).toHaveCount(initialRows);
      await expect(loadMore).toBeVisible();

      await loadMore.click();
      await expect(page.locator('#programa-causal-drilldown-load-more-error')).toHaveCount(0);
      await expect.poll(() => rows.count(), { timeout: 15000 }).toBeGreaterThan(initialRows);
      await page.unroute('**/api/bi/report/programa-general/cnp-detail**');
    });

    test('explica el corte con un cronograma paginado de actividades y sin overflow', async ({ page }) => {
      const errors = installErrorCollectors(page);
      const query = 'project_id=68&semana=6&theme=linen';
      const report = await getJson(page, `/api/bi/report/programa-general?${query}`);
      expect(report.ok, 'Programa General activity timeline report failed').toBe(true);

      const snapshot = report.payload.activity_snapshot;
      expect(snapshot?.metric_key).toBe('pg_activity_progress_contribution');
      expect(snapshot?.source_relations).toEqual(['programa_consolidado', 'semanas_activas']);
      expect(snapshot?.grain).toBe('project_id + Semana + unique_id');
      expect(Number(snapshot?.pagination?.total)).toBeGreaterThan(25);
      expect(snapshot?.activities).toHaveLength(25);
      expect(snapshot?.pagination?.has_more).toBe(true);
      expect(snapshot?.activities?.[0]).toEqual(expect.objectContaining({
        activity_key: expect.any(String),
        activity: expect.any(String),
        planned_start: expect.any(String),
        planned_finish: expect.any(String),
        cutoff: expect.any(String),
        weight_pct: expect.any(Number),
        real_pct: expect.any(Number),
        planned_pct: expect.any(Number),
        gap_pp: expect.any(Number),
        real_contribution_pp: expect.any(Number),
        recoverable_pp: expect.any(Number),
      }));

      const asymmetricReport = await getJson(page, '/api/bi/report/programa-general?project_ids%5B%5D=68&project_ids%5B%5D=74&semana=6');
      const asymmetricDetail = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_ids%5B%5D=68&project_ids%5B%5D=74&semana=6&limit=25&offset=0');
      expect(asymmetricReport.ok, 'asymmetric multi-project report failed').toBe(true);
      expect(asymmetricDetail.ok, 'asymmetric multi-project detail failed').toBe(true);
      expect(asymmetricReport.payload.activity_snapshot.pagination.total).toBe(asymmetricDetail.payload.pagination.total);
      expect(asymmetricReport.payload.activity_snapshot.summary).toEqual(asymmetricDetail.payload.summary);
      expect(asymmetricReport.payload.activity_snapshot.activities.map((activity) => activity.activity_key))
        .toEqual(asymmetricDetail.payload.activities.map((activity) => activity.activity_key));

      const keys = [];
      let offset = 0;
      const pageSize = 100;
      let total = 0;
      do {
        const detail = await getJson(page, `/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&limit=${pageSize}&offset=${offset}`);
        expect(detail.ok, JSON.stringify(detail)).toBe(true);
        expect(detail.payload.metric_key).toBe(snapshot.metric_key);
        expect(detail.payload.summary).toMatchObject({
          real_pct: snapshot.summary.real_pct,
          theoretical_pct: snapshot.summary.theoretical_pct,
          gap_pp: snapshot.summary.gap_pp,
        });
        total = Number(detail.payload.pagination?.total || 0);
        keys.push(...(detail.payload.activities || []).map((activity) => activity.activity_key));
        offset = Number(detail.payload.pagination?.next_offset || total);
        if (!detail.payload.pagination?.has_more) break;
      } while (offset < total);
      expect(keys).toHaveLength(total);
      expect(new Set(keys).size).toBe(total);

      const filtered = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_id=68&semana=5&sub=PROCOPAL&resp=Mildred%20Buitrago&limit=100&offset=0');
      expect(filtered.ok, 'filtered activity timeline detail failed').toBe(true);
      expect(filtered.payload.activities.length).toBeGreaterThan(0);
      expect(filtered.payload.activities.every((activity) => activity.subcontractor === 'PROCOPAL')).toBe(true);
      expect(filtered.payload.activities.every((activity) => activity.responsible === 'Mildred Buitrago')).toBe(true);

      const multiProjectRange = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_ids%5B%5D=68&project_ids%5B%5D=74&desde=2026-05-25&hasta=2026-06-15&limit=100&offset=0');
      expect(multiProjectRange.ok, 'multi-project range activity timeline detail failed').toBe(true);
      expect(new Set(multiProjectRange.payload.activities.map((activity) => Number(activity.project_id))).size).toBeGreaterThan(1);

      const earnedDetail = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&sort=earned&limit=50&offset=0');
      const criticalMissingDetail = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&sort=missing&critical_only=1&limit=50&offset=0');
      expect(earnedDetail.ok, 'earned activity ranking failed').toBe(true);
      expect(criticalMissingDetail.ok, 'critical missing activity ranking failed').toBe(true);
      expect(earnedDetail.payload.activities.length).toBeGreaterThan(0);
      expect(criticalMissingDetail.payload.activities.length).toBeGreaterThan(0);
      expect(earnedDetail.payload.activities.every((activity) => Number(activity.real_contribution_pp) > 0)).toBe(true);
      expect(criticalMissingDetail.payload.activities.every((activity) => activity.critical && Number(activity.recoverable_pp) > 0)).toBe(true);

      await page.setViewportSize({ width: 1440, height: 900 });
      await page.goto(`/bi/programa-general?${query}`, { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-activity-total')).toContainText('25 de');
      await expect(page.locator('#programa-activity-total')).toContainText('avance real');
      await expect(page.locator('#programa-activity-total')).toHaveAttribute('role', 'status');
      await expect(page.locator('#programa-activity-total')).toHaveAttribute('aria-live', 'polite');
      await expect(page.locator('#programa-activity-total')).toHaveAttribute('aria-atomic', 'true');
      await expect(page.locator('#programa-activity-cutoff')).not.toHaveText('Corte --');
      await expect(page.locator('#programa-activity-table')).toBeVisible();
      await expect(page.locator('#programa-activity-cards')).toBeHidden();
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);
      await expect(page.locator('#programa-activity-table')).toContainText('Cronograma al corte');
      await expect(page.locator('#programa-activity-table')).toContainText('Responsable AIA');
      await expect(page.locator('#programa-activity-table')).toContainText('Subcontratista');

      const initialDomKeys = await page.locator('#programa-activity-body tr').evaluateAll((rows) => rows.map((row) => row.dataset.activityKey));
      await page.locator('#programa-activity-load-more').click();
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(125);
      const loadedDomKeys = await page.locator('#programa-activity-body tr').evaluateAll((rows) => rows.map((row) => row.dataset.activityKey));
      expect(new Set(loadedDomKeys).size).toBe(loadedDomKeys.length);
      expect(loadedDomKeys.slice(0, initialDomKeys.length)).toEqual(initialDomKeys);

      const analysisTrigger = page.locator('#programa-activity-analysis-trigger');
      await expect(analysisTrigger).toHaveAttribute('aria-controls', 'programa-gauge-drilldown');
      await expect(analysisTrigger).toHaveAttribute('aria-expanded', 'false');
      await analysisTrigger.click();
      await expect(page.locator('#programa-gauge-drilldown')).toBeVisible();
      await expect(analysisTrigger).toHaveAttribute('aria-expanded', 'true');
      await expect(page.locator('#programa-gauge-drilldown-trigger')).toHaveAttribute('aria-expanded', 'false');
      await expect(page.locator('#programa-gauge-drilldown-explanation')).toContainText('Mostrando 50 de');
      const progressRows = page.locator('#programa-gauge-drilldown-body tr');
      await expect(progressRows).toHaveCount(50);
      await expect(page.locator('#programa-gauge-drilldown-load-more')).toBeVisible();

      await page.getByRole('button', { name: 'Lo que ya suma' }).click();
      await expect(progressRows.first()).toHaveAttribute('data-activity-key', earnedDetail.payload.activities[0].activity_key);
      await page.getByRole('button', { name: 'Lo que más falta' }).click();
      await page.locator('#programa-gauge-drilldown-critical-only').check();
      await expect(progressRows.first()).toHaveAttribute('data-activity-key', criticalMissingDetail.payload.activities[0].activity_key);
      await expect(progressRows).toHaveCount(Math.min(50, Number(criticalMissingDetail.payload.pagination.total)));
      await page.locator('#programa-gauge-drilldown-critical-only').uncheck();
      await expect(progressRows).toHaveCount(50);

      await page.locator('#programa-gauge-drilldown-close').focus();
      await page.keyboard.press('Shift+Tab');
      await expect(page.locator('#programa-gauge-drilldown-load-more')).toBeFocused();
      await page.keyboard.press('Tab');
      await expect(page.locator('#programa-gauge-drilldown-close')).toBeFocused();
      await page.locator('#programa-gauge-drilldown-load-more').click();
      await expect.poll(() => progressRows.count(), { timeout: 15000 }).toBeGreaterThan(50);
      await page.locator('#programa-gauge-drilldown-close').click();
      await expect(analysisTrigger).toHaveAttribute('aria-expanded', 'false');
      await expect(analysisTrigger).toBeFocused();

      await page.setViewportSize({ width: 1024, height: 768 });
      await expect(page.locator('#programa-activity-table')).toBeVisible();
      await expect(page.locator('#programa-activity-cards')).toBeHidden();

      await page.setViewportSize({ width: 390, height: 844 });
      await expect(page.locator('#programa-activity-table')).toBeHidden();
      await expect(page.locator('#programa-activity-cards')).toBeVisible();
      await expect(page.locator('.bi-programa-activity-card')).toHaveCount(125);
      await expect(page.locator('#programa-activity-cards')).toContainText('Aporte recuperable');
      await expect(page.locator('#programa-activity-cards')).toContainText('Responsable AIA');
      const mobileTitleColors = await page.evaluate(() => {
        const title = document.querySelector('.bi-programa-activity-card h4');
        const expectedToken = getComputedStyle(document.documentElement)
          .getPropertyValue('--ds-active-text-primary')
          .trim();
        const colorProbe = document.createElement('span');
        colorProbe.style.color = expectedToken;
        document.body.appendChild(colorProbe);
        const expected = getComputedStyle(colorProbe).color;
        colorProbe.remove();
        return {
          actual: title ? getComputedStyle(title).color : '',
          expected,
        };
      });
      expect(mobileTitleColors.actual).toBe(mobileTitleColors.expected);

      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      const mobileCards = page.locator('.bi-programa-activity-card');
      await expect(mobileCards).toHaveCount(25);
      await expect(mobileCards.first()).toBeVisible();
      await expect(mobileCards.first()).toContainText(snapshot.activities[0].responsible || 'Sin asignar');
      await expect(mobileCards.first()).toContainText(snapshot.activities[0].subcontractor || 'Sin asignar');

      const mobileInitialKeys = await mobileCards.evaluateAll((cards) => cards.map((card) => card.dataset.activityKey));
      await page.locator('#programa-activity-load-more').click();
      await expect(mobileCards).toHaveCount(125);
      const mobileLoadedKeys = await mobileCards.evaluateAll((cards) => cards.map((card) => card.dataset.activityKey));
      expect(new Set(mobileLoadedKeys).size).toBe(mobileLoadedKeys.length);
      expect(mobileLoadedKeys.slice(0, mobileInitialKeys.length)).toEqual(mobileInitialKeys);

      await analysisTrigger.click();
      await expect(page.locator('#programa-gauge-drilldown')).toBeVisible();
      await expect(analysisTrigger).toHaveAttribute('aria-expanded', 'true');
      const mobileProgressCards = page.locator('#programa-gauge-drilldown-cards .programa-gauge-drilldown-card');
      await expect(mobileProgressCards).toHaveCount(50);
      await expect(mobileProgressCards.first()).toContainText('Responsable AIA:');
      await expect(mobileProgressCards.first()).toContainText('Subcontratista:');
      const mobileModalOverflow = await page.locator('#programa-gauge-drilldown').evaluate((modal) => Math.max(
        0,
        modal.scrollWidth - modal.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
        ...Array.from(modal.querySelectorAll('.programa-gauge-drilldown-card'))
          .map((card) => card.scrollWidth - card.clientWidth),
      ));
      expect(mobileModalOverflow).toBeLessThanOrEqual(1);
      const mobileModalClose = page.locator('#programa-gauge-drilldown-close');
      const mobileModalLoadMore = page.locator('#programa-gauge-drilldown-load-more');
      await expect(mobileModalClose).toBeFocused();
      await page.keyboard.press('Shift+Tab');
      await expect(mobileModalLoadMore).toBeFocused();
      await page.keyboard.press('Tab');
      await expect(mobileModalClose).toBeFocused();
      await page.keyboard.press('Escape');
      await expect(page.locator('#programa-gauge-drilldown')).toBeHidden();
      await expect(analysisTrigger).toHaveAttribute('aria-expanded', 'false');
      await expect(analysisTrigger).toBeFocused();

      const darkTitleColors = await page.evaluate(() => {
        const title = document.querySelector('.bi-programa-activity-card h4');
        const expectedToken = getComputedStyle(document.documentElement)
          .getPropertyValue('--ds-active-text-primary')
          .trim();
        const colorProbe = document.createElement('span');
        colorProbe.style.color = expectedToken;
        document.body.appendChild(colorProbe);
        const expected = getComputedStyle(colorProbe).color;
        colorProbe.remove();
        return { actual: title ? getComputedStyle(title).color : '', expected };
      });
      expect(darkTitleColors.actual).toBe(darkTitleColors.expected);
      const overflow = await page.locator('.bi-programa-activities').evaluate((card) => Math.max(
        0,
        card.scrollWidth - card.clientWidth,
        document.documentElement.scrollWidth - window.innerWidth,
      ));
      expect(overflow).toBeLessThanOrEqual(1);

      const technicalModes = [
        { width: 1440, height: 900, label: 'desktop', mobile: false },
        { width: 1024, height: 768, label: 'tablet horizontal', mobile: false },
        { width: 390, height: 844, label: 'mobile', mobile: true },
      ];
      for (const theme of ['dark', 'linen']) {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`/bi/programa-general?project_id=68&semana=6&theme=${theme}`, { waitUntil: 'domcontentloaded' });
        for (const mode of technicalModes) {
          await page.setViewportSize({ width: mode.width, height: mode.height });
          await expect(page.locator('#programa-activity-total')).toContainText('25 de');
          await expect(page.locator('#programa-activity-table'))[mode.mobile ? 'toBeHidden' : 'toBeVisible']();
          await expect(page.locator('#programa-activity-cards'))[mode.mobile ? 'toBeVisible' : 'toBeHidden']();
          const modeOverflow = await page.locator('.bi-programa-activities').evaluate((block) => Math.max(
            0,
            block.scrollWidth - block.clientWidth,
            document.documentElement.scrollWidth - window.innerWidth,
            ...Array.from(block.querySelectorAll('.bi-programa-activity-table, .bi-programa-activity-card, .bi-programa-activity-actions'))
              .map((element) => element.scrollWidth - element.clientWidth),
          ));
          expect(modeOverflow, `${theme} ${mode.label} activity timeline overflow`).toBeLessThanOrEqual(1);
        }
      }

      assertNoRuntimeErrors(errors);
    });

    test('descarta una página tardía del cronograma después de cambiar el filtro', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);

      const delayedPayload = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&limit=100&offset=25');
      expect(delayedPayload.ok).toBe(true);

      let releasePage;
      const pageGate = new Promise((resolve) => { releasePage = resolve; });
      let delayedRequest = false;
      let delayedResponseDelivered = false;
      await page.route('**/api/bi/report/programa-general/progress-detail**', async (route) => {
        const offset = Number(new URL(route.request().url()).searchParams.get('offset') || 0);
        if (offset > 0 && !delayedRequest) {
          delayedRequest = true;
          await pageGate;
          delayedResponseDelivered = true;
          await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(delayedPayload.payload) });
          return;
        }
        await route.continue();
      });

      await page.locator('#programa-activity-load-more').click();
      await expect.poll(() => delayedRequest, { timeout: 15000 }).toBe(true);
      await page.locator('#filter-semana').selectOption('5');
      await page.locator('#filters-form').evaluate((form) => form.requestSubmit());
      await expect(page.locator('#active-filters')).toContainText('Semana: 5');
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);

      releasePage();
      await expect.poll(() => delayedResponseDelivered).toBe(true);
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);
      await expect(page.locator('#programa-activity-total')).toContainText('25 de');
      await page.unroute('**/api/bi/report/programa-general/progress-detail**');
    });

    test('descarta un reporte principal tardío después de aplicar filtros nuevos', async ({ page }) => {
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=linen', { waitUntil: 'domcontentloaded' });
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);

      const expectedWeek4 = await getJson(page, '/api/bi/report/programa-general?project_id=68&semana=4');
      expect(expectedWeek4.ok).toBe(true);
      const delayedWeek5 = await getJson(page, '/api/bi/report/programa-general?project_id=68&semana=5');
      expect(delayedWeek5.ok).toBe(true);
      const expectedFirstKey = expectedWeek4.payload.activity_snapshot.activities[0]?.activity_key;
      expect(expectedFirstKey).toBeTruthy();

      let releaseWeek5;
      const week5Gate = new Promise((resolve) => { releaseWeek5 = resolve; });
      let week5Delayed = false;
      let week5ResponseDelivered = false;
      await page.route('**/api/bi/report/programa-general?**', async (route) => {
        const url = new URL(route.request().url());
        if (url.searchParams.get('semana') === '5' && !week5Delayed) {
          week5Delayed = true;
          await week5Gate;
          week5ResponseDelivered = true;
          await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(delayedWeek5.payload) });
          return;
        }
        if (url.searchParams.get('semana') === '4') {
          await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(expectedWeek4.payload) });
          return;
        }
        await route.continue();
      });

      await page.locator('#filter-semana').selectOption('5');
      await page.locator('#filters-form').evaluate((form) => form.requestSubmit());
      await expect.poll(() => week5Delayed, { timeout: 15000 }).toBe(true);

      await page.locator('#filter-semana').selectOption('4');
      await page.locator('#filters-form').evaluate((form) => form.requestSubmit());
      await expect(page.locator('#active-filters')).toContainText('Semana: 4');
      await expect(page.locator('#programa-activity-body tr')).toHaveCount(25);
      await expect(page.locator('#programa-activity-body tr').first()).toHaveAttribute('data-activity-key', expectedFirstKey);

      releaseWeek5();
      await expect.poll(() => week5ResponseDelivered).toBe(true);
      await expect(page.locator('#active-filters')).toContainText('Semana: 4');
      await expect(page.locator('#programa-activity-body tr').first()).toHaveAttribute('data-activity-key', expectedFirstKey);
      await page.unroute('**/api/bi/report/programa-general?**');
    });

    test('ignora error y loading obsoletos al cerrar y reabrir composición de avance', async ({ page }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      await page.goto('/bi/programa-general?project_id=68&semana=6&theme=dark', { waitUntil: 'domcontentloaded' });
      const expectedDetail = await getJson(page, '/api/bi/report/programa-general/progress-detail?project_id=68&semana=6&limit=50&offset=0');
      expect(expectedDetail.ok).toBe(true);

      let releaseFirst;
      let releaseSecond;
      const firstGate = new Promise((resolve) => { releaseFirst = resolve; });
      const secondGate = new Promise((resolve) => { releaseSecond = resolve; });
      let requestCount = 0;
      let firstDelivered = false;
      await page.route('**/api/bi/report/programa-general/progress-detail**', async (route) => {
        requestCount += 1;
        if (requestCount === 1) {
          await firstGate;
          firstDelivered = true;
          await route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ respuesta: 'ERROR' }) });
          return;
        }
        if (requestCount === 2) {
          await secondGate;
          await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(expectedDetail.payload) });
          return;
        }
        await route.continue();
      });

      const trigger = page.locator('#programa-gauge-drilldown-trigger');
      await trigger.click();
      await expect.poll(() => requestCount).toBe(1);
      await page.locator('#programa-gauge-drilldown-close').click();
      await trigger.click();
      await expect.poll(() => requestCount).toBe(2);
      await expect(page.locator('#programa-gauge-drilldown-loading')).toBeVisible();

      releaseFirst();
      await expect.poll(() => firstDelivered).toBe(true);
      await expect(page.locator('#programa-gauge-drilldown-loading')).toBeVisible();
      await expect(page.locator('#programa-gauge-drilldown-empty')).not.toContainText('No fue posible');

      releaseSecond();
      await expect(page.locator('#programa-gauge-drilldown-loading')).toBeHidden();
      await expect(page.locator('#programa-gauge-drilldown-explanation')).toContainText('Mostrando 50 de');
      await page.unroute('**/api/bi/report/programa-general/progress-detail**');
    });

    test('renders Programa General curves as clean lines without point markers', async ({ page }) => {
      await page.goto(`/bi/programa-general?project_id=${project.projectId}&semana=${encodeURIComponent(String(project.maxWeek || 1))}&theme=linen`, { waitUntil: 'domcontentloaded' });
      await expect.poll(() => chartState(page, 'programa-curva-ejecucion'), { timeout: 15000 }).not.toBeNull();

      const style = await page.evaluate(() => {
        const canvas = document.getElementById('programa-curva-ejecucion');
        const chart = canvas && window.Chart ? window.Chart.getChart(canvas) : null;
        if (!chart) return null;
        return {
          type: chart.config.type,
          datasets: chart.data.datasets.map((dataset) => ({
            borderWidth: dataset.borderWidth,
            pointRadius: dataset.pointRadius,
            pointHoverRadius: dataset.pointHoverRadius,
          })),
        };
      });

      expect(style?.type).toBe('line');
      expect(style?.datasets.length).toBeGreaterThanOrEqual(2);
      expect(style?.datasets.every((dataset) => dataset.borderWidth >= 2)).toBe(true);
      expect(style?.datasets.every((dataset) => dataset.pointRadius === 0)).toBe(true);
      expect(style?.datasets.every((dataset) => dataset.pointHoverRadius === 0)).toBe(true);
    });

    test('toggles Programa General execution projections in the Chart.js instance', async ({ page }) => {
      const errors = installErrorCollectors(page);
      await page.goto('/bi/programa-general?project_id=68&semana=6', { waitUntil: 'domcontentloaded' });

      await expect(page.locator('#view-programa-general')).toBeVisible({ timeout: 15000 });
      await expect.poll(() => chartState(page, 'programa-curva-ejecucion'), { timeout: 15000 }).not.toBeNull();
      await expect(page.locator('#toggle-programa-projections')).toBeChecked();

      let rendered = await chartState(page, 'programa-curva-ejecucion');
      expect(rendered.datasets.map((dataset) => dataset.label)).toEqual([
        'Curva teórica total',
        'Real acumulado',
        'Proyección pesimista (Rango probable 80%)',
        'Proyección más probable',
        'Proyección optimista (Rango probable 80%)',
      ]);

      await page.locator('#toggle-programa-projections').uncheck({ force: true });
      await expect(page.locator('#toggle-programa-projections')).not.toBeChecked();
      await expect.poll(async () => {
        const state = await chartState(page, 'programa-curva-ejecucion');
        return state?.datasets.map((dataset) => dataset.label) || [];
      }, { timeout: 15000 }).toEqual(['Curva teórica total', 'Real acumulado']);

      await page.locator('#toggle-programa-projections').check({ force: true });
      await expect(page.locator('#toggle-programa-projections')).toBeChecked();
      await expect.poll(async () => {
        const state = await chartState(page, 'programa-curva-ejecucion');
        return state?.datasets.map((dataset) => dataset.label) || [];
      }, { timeout: 15000 }).toHaveLength(5);

      assertNoRuntimeErrors(errors);
    });
  });
}
