import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const budget = JSON.parse(readFileSync(
  new URL('../../docs/design-system/lab-performance-budget.json', import.meta.url),
  'utf8',
));
const VIEWPORTS = budget.viewports.map((value) => {
  const [width, height] = value.split('x').map(Number);
  return { name: value, width, height };
});
const OUTPUT = path.join(process.cwd(), 'test-output', 'design-system-lab-performance.json');
const SOURCE_PATHS = [
  'public/css',
  'public/js/modules/aia_ui',
  'views/design-system',
  'src/View/Components/DesignSystemHeadComponent.php',
  'package.json',
];

const median = (values) => [...values].sort((left, right) => left - right)[Math.floor(values.length / 2)];
const sha256 = (value) => createHash('sha256').update(value).digest('hex');

function readSourceContext() {
  const diff = execFileSync(
    'git',
    ['diff', 'HEAD', '--binary', '--', ...SOURCE_PATHS],
    { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 },
  );
  const untrackedPaths = execFileSync(
    'git',
    ['ls-files', '--others', '--exclude-standard', '-z', '--', ...SOURCE_PATHS],
    { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 },
  ).split('\0').filter(Boolean).sort();
  const fingerprintParts = [Buffer.from(diff)];
  for (const sourcePath of untrackedPaths) {
    fingerprintParts.push(
      Buffer.from(`\0untracked:${sourcePath}\0`),
      readFileSync(sourcePath),
    );
  }

  return {
    worktreeDiffSha256: sha256(Buffer.concat(fingerprintParts)),
    untrackedPaths,
  };
}

function summarize(samples) {
  const numericKeys = [
    'firstContentfulPaintMs', 'cumulativeLayoutShift', 'longTaskCount',
    'maxLongTaskMs', 'totalLongTaskMs', 'resourceCount', 'cssRequestCount',
    'cssTransferBytes', 'cssDecodedBytes',
  ];
  return Object.fromEntries(numericKeys.map((key) => [key, median(samples.map((sample) => sample[key]))]));
}

async function measureColdLoad(browser, viewport, sampleIndex) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
  });
  const page = await context.newPage();
  await page.addInitScript(() => {
    window.__aiaLabPerformance = { cumulativeLayoutShift: 0, longTasks: [] };
    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (!entry.hadRecentInput) window.__aiaLabPerformance.cumulativeLayoutShift += entry.value;
      }
    }).observe({ type: 'layout-shift', buffered: true });
    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) window.__aiaLabPerformance.longTasks.push(entry.duration);
    }).observe({ type: 'longtask', buffered: true });
  });

  try {
    await loginAndSelectProject(page, PROJECTS[0], ADMIN);
    const session = await context.newCDPSession(page);
    await session.send('Network.clearBrowserCache');
    const response = await page.goto(budget.route, { waitUntil: 'domcontentloaded' });
    expect(response?.status()).toBe(200);
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(500);

    return await page.evaluate(({ viewportName, index }) => {
      const resources = performance.getEntriesByType('resource');
      const cssResources = resources.filter((entry) => new URL(entry.name).pathname.endsWith('.css'));
      const longTasks = window.__aiaLabPerformance.longTasks;
      const stylesheetPaths = cssResources.map((entry) => new URL(entry.name).pathname).sort();
      return {
        sampleIndex: index,
        viewport: viewportName,
        firstContentfulPaintMs: Math.round(
          performance.getEntriesByName('first-contentful-paint')[0]?.startTime ?? 0,
        ),
        cumulativeLayoutShift: Number(window.__aiaLabPerformance.cumulativeLayoutShift.toFixed(6)),
        longTaskCount: longTasks.length,
        maxLongTaskMs: Number(Math.max(0, ...longTasks).toFixed(3)),
        totalLongTaskMs: Number(longTasks.reduce((total, duration) => total + duration, 0).toFixed(3)),
        resourceCount: resources.length,
        cssRequestCount: cssResources.length,
        cssTransferBytes: cssResources.reduce((total, entry) => total + entry.transferSize, 0),
        cssDecodedBytes: cssResources.reduce((total, entry) => total + entry.decodedBodySize, 0),
        stylesheetPaths,
      };
    }, { viewportName: viewport.name, index: sampleIndex });
  } finally {
    await context.close();
  }
}

test('laboratory preserves three cold desktop samples within its CSS and rendering budget', async ({ browser }) => {
  test.setTimeout(120_000);
  const measurements = {};

  for (const viewport of VIEWPORTS) {
    const samples = [];
    for (let sampleIndex = 1; sampleIndex <= budget.sampling.sampleCountPerViewport; sampleIndex += 1) {
      samples.push(await measureColdLoad(browser, viewport, sampleIndex));
    }
    measurements[viewport.name] = { samples, median: summarize(samples) };
  }

  const sourceRef = execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();
  const sourceContext = readSourceContext();
  const artifact = {
    schemaVersion: 1,
    kind: 'measurement',
    recordedAt: new Date().toISOString(),
    source: {
      kind: 'working-tree-on-head',
      sourceRef,
      ...sourceContext,
    },
    route: budget.route,
    theme: budget.theme,
    density: budget.density,
    sampling: budget.sampling,
    measurements,
  };
  mkdirSync(path.dirname(OUTPUT), { recursive: true });
  writeFileSync(OUTPUT, `${JSON.stringify(artifact, null, 2)}\n`);
  await test.info().attach('laboratory-performance', {
    body: Buffer.from(JSON.stringify(artifact, null, 2)),
    contentType: 'application/json',
  });

  for (const viewport of VIEWPORTS) {
    const aggregate = measurements[viewport.name].median;
    for (const [metric, maximum] of Object.entries(budget.budgets)) {
      expect(aggregate[metric], `${viewport.name}: ${metric}`).toBeLessThanOrEqual(maximum);
    }
    for (const sample of measurements[viewport.name].samples) {
      expect(sample.stylesheetPaths).toContain('/css/design-system/lab-entrypoint.css');
      expect(sample.stylesheetPaths).not.toContain('/css/aia-design-system.css');
      expect(sample.stylesheetPaths).not.toContain('/css/styles.css');
    }
  }
});
