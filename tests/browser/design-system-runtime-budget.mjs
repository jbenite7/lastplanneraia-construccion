import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { gzipSync } from 'node:zlib';

import { expect, test } from '@playwright/test';

import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const REPORT_PATH = path.resolve(
  process.env.DS_RUNTIME_REPORT || 'test-output/design-system-runtime-budget.json',
);
const EXPECTED_THEME = 'dark';
const MEASUREMENT_KIND = process.env.DS_RUNTIME_MEASUREMENT_KIND || 'current';
const MEASUREMENT_VERSION = process.env.DS_RUNTIME_VERSION || null;
const MEASUREMENT_SOURCE_REF = process.env.DS_RUNTIME_SOURCE_REF || null;
const LAB_ASSET_PATTERN = /(?:\/internal\/design-system|\/design-system\/lab\.|design_system_lab\.js)/;

const sha256 = (value) => createHash('sha256').update(value).digest('hex');
const round = (value) => Math.round(value * 1000) / 1000;

function applicationPath(rawUrl) {
  const url = new URL(rawUrl);
  return url.pathname;
}

function normalizeRequest(rawUrl) {
  const url = new URL(rawUrl);
  for (const key of ['v', '_', 'cache', 'cacheBust']) url.searchParams.delete(key);
  url.hash = '';
  url.searchParams.sort();
  return `${url.origin}${url.pathname}${url.search}`;
}

async function measureAssets(page, resources) {
  const origin = new URL(page.url()).origin;
  const candidates = new Map();
  for (const resource of resources) {
    const url = new URL(resource.name);
    if (url.origin !== origin || !/\.(?:css|js)$/i.test(url.pathname)) continue;
    if (!candidates.has(url.pathname)) candidates.set(url.pathname, resource.name);
  }

  const assets = [];
  for (const [assetPath, url] of [...candidates.entries()].sort(([left], [right]) => left.localeCompare(right))) {
    const response = await page.request.get(url);
    expect(response.ok(), `asset failed: ${url}`).toBe(true);
    const body = await response.body();
    assets.push({
      path: assetPath,
      type: assetPath.endsWith('.css') ? 'css' : 'js',
      rawBytes: body.byteLength,
      gzipBytes: gzipSync(body, { level: 9 }).byteLength,
      sha256: sha256(body),
    });
  }
  return assets;
}

test('measures the Programa General runtime budget surface', async ({ page }, testInfo) => {
  test.setTimeout(120_000);
  const project = PROJECTS.find(({ key }) => key === 'construction');
  expect(project, 'sanitized construction fixture is required').toBeTruthy();

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.addInitScript((expectedTheme) => {
    const probe = { firstPaintTheme: null, observations: [] };
    window.__dsRuntimeThemeProbe = probe;
    const attach = () => {
      const root = document.documentElement;
      if (!root || probe.attached) return false;
      probe.attached = true;
      const record = (phase) => {
        probe.observations.push({
          phase,
          theme: root.getAttribute('data-aia-theme'),
          at: performance.now(),
        });
      };
      record('initial');
      new MutationObserver(() => record('theme-mutation')).observe(root, {
        attributes: true,
        attributeFilter: ['data-aia-theme'],
      });
      requestAnimationFrame(() => {
        probe.firstPaintTheme = root.getAttribute('data-aia-theme');
        record('first-paint');
      });
      document.addEventListener('DOMContentLoaded', () => record('dom-content-loaded'), { once: true });
      return true;
    };
    if (!attach()) {
      const rootObserver = new MutationObserver(() => {
        if (attach()) rootObserver.disconnect();
      });
      rootObserver.observe(document, { childList: true, subtree: true });
    }
    window.__dsRuntimeExpectedTheme = expectedTheme;
  }, EXPECTED_THEME);

  await loginAndSelectProject(page, project, ADMIN);
  await page.evaluate((theme) => localStorage.setItem('aia-theme', theme), EXPECTED_THEME);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => Boolean(
    window.PGHotModule?.getHotInstance?.()
      || document.querySelector('#hot-container .ht_master.handsontable'),
  ), null, { timeout: 45_000 });

  const initializationMs = round(await page.evaluate(() => performance.now()));
  const filterButton = page.locator('#hot-container .ht_clone_top:visible .changeType:visible').first();
  await expect(filterButton).toBeVisible({ timeout: 15_000 });
  const interactionStart = await page.evaluate(() => performance.now());
  await filterButton.click();
  const filterMenu = page.locator('.htDropdownMenu:visible').first();
  await expect(filterMenu).toBeVisible();
  const handsontableInteractionMs = round(
    await page.evaluate((startedAt) => performance.now() - startedAt, interactionStart),
  );
  await page.keyboard.press('Escape');
  await expect(filterMenu).toBeHidden();

  const browserState = await page.evaluate((expectedTheme) => {
    const resources = performance.getEntriesByType('resource').map((entry) => ({
      name: entry.name,
      initiatorType: entry.initiatorType,
    }));
    const probe = window.__dsRuntimeThemeProbe || { firstPaintTheme: null, observations: [] };
    const mismatches = probe.observations.filter(({ phase, theme }) => (
      phase !== 'initial' && theme && theme !== expectedTheme
    ));
    return {
      resources,
      themeProbe: probe,
      themeFlashCount: (probe.firstPaintTheme === expectedTheme ? 0 : 1) + mismatches.length,
      currentTheme: document.documentElement.dataset.aiaTheme || null,
      html: document.documentElement.outerHTML,
    };
  }, EXPECTED_THEME);
  expect(browserState.currentTheme).toBe(EXPECTED_THEME);

  const assets = await measureAssets(page, browserState.resources);
  const requestCounts = new Map();
  for (const resource of browserState.resources) {
    const key = normalizeRequest(resource.name);
    requestCounts.set(key, (requestCounts.get(key) || 0) + 1);
  }
  const duplicates = [...requestCounts.entries()]
    .filter(([, count]) => count > 1)
    .map(([request, count]) => ({ request, count }))
    .sort((left, right) => left.request.localeCompare(right.request));
  const duplicateRequestCount = duplicates.reduce((total, { count }) => total + count - 1, 0);
  const adapterAssets = assets
    .map(({ path: assetPath }) => assetPath)
    .filter((assetPath) => assetPath.includes('/css/design-system/adapters/'));
  const laboratoryAssets = browserState.resources
    .map(({ name }) => applicationPath(name))
    .filter((assetPath, index, values) => LAB_ASSET_PATTERN.test(assetPath) && values.indexOf(assetPath) === index)
    .sort();
  const htmlSha256 = sha256(browserState.html);
  const sourceTreeHash = sha256(JSON.stringify(assets));
  const version = MEASUREMENT_VERSION
    || JSON.parse(await readFile('docs/design-system/version.json', 'utf8')).version;
  const sourceRef = MEASUREMENT_SOURCE_REF
    || execFileSync('git', ['rev-parse', 'HEAD'], { encoding: 'utf8' }).trim();

  const report = {
    $schema: 'https://lastplanneraia.com/schemas/design-system-runtime-budget-v1.json',
    schemaVersion: 1,
    kind: 'measurement',
    measurementKind: MEASUREMENT_KIND,
    status: 'measured',
    designSystemVersion: version,
    route: '/programa-general',
    viewport: '1440x900',
    theme: EXPECTED_THEME,
    fixture: 'sanitized-pilot-v1',
    sourceRef,
    sourceTreeHash,
    recordedAt: new Date().toISOString(),
    metrics: {
      cssGzipBytes: assets.filter(({ type }) => type === 'css').reduce((sum, asset) => sum + asset.gzipBytes, 0),
      jsGzipBytes: assets.filter(({ type }) => type === 'js').reduce((sum, asset) => sum + asset.gzipBytes, 0),
      adapterAssets,
      duplicateRequestCount,
      themeFlashCount: browserState.themeFlashCount,
      initializationMs,
      handsontableInteractionMs,
      laboratoryAssets,
    },
    provenance: {
      cacheMode: 'navigation-resource-timing-plus-normalized-fetch',
      htmlSha256,
      assets,
      duplicateRequests: duplicates,
      themeProbe: browserState.themeProbe,
      interactionKind: 'column-filter-menu',
      node: process.version,
      playwrightProject: testInfo.project.name,
    },
  };

  await mkdir(path.dirname(REPORT_PATH), { recursive: true });
  await writeFile(REPORT_PATH, `${JSON.stringify(report, null, 2)}\n`);
  await testInfo.attach('design-system-runtime-budget.json', {
    body: Buffer.from(JSON.stringify(report, null, 2)),
    contentType: 'application/json',
  });
});
