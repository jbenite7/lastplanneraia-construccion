import { mkdir, writeFile } from 'node:fs/promises';
// fileURLToPath y no URL.pathname: la ruta del repo contiene un espacio
// ("Crucial X6") y pathname lo percent-encodea, rompiendo el path en disco.
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { PROJECTS } from './fixtures/projects.mjs';
import { login, logout } from './support/session.mjs';

const SURFACES = {
  'project-selector': { routes: ['/proyectos'], authenticated: true },
  auth: { routes: ['/login', '/password/forgot', '/password/reset'], authenticated: false },
};
const VIEWPORTS = [
  { width: 1180, height: 820 },
  { width: 1440, height: 900 },
];
const CI_ADMIN = { username: 'test.A', password: 'aia2026' };
const surfaceId = process.env.DRYRUN_SURFACE ?? 'project-selector';
const phase = process.env.DRYRUN_PHASE ?? 'before';
const surface = SURFACES[surfaceId];
const outDir = new URL(
  `../../docs/design-system/evidence/entrypoint-segmentation/${surfaceId}/${phase}/`,
  import.meta.url,
);
const slug = (route) => route.replaceAll('/', '-').replace(/^-/, '') || 'root';

test(`dry-run ${surfaceId} (${phase})`, async ({ page }) => {
  await mkdir(outDir, { recursive: true });
  const consoleEntries = [];
  page.on('console', (message) => {
    if (['error', 'warning'].includes(message.type())) {
      consoleEntries.push({ type: message.type(), text: message.text() });
    }
  });
  const cssRequests = new Set();
  page.on('request', (request) => {
    if (request.resourceType() === 'stylesheet' || request.url().includes('.css')) {
      cssRequests.add(new URL(request.url()).pathname);
    }
  });

  if (surface.authenticated) {
    const project = PROJECTS.find(({ key }) => key === 'construction');
    expect(project, 'construction project fixture required').toBeTruthy();
    await login(page, CI_ADMIN);
  }
  const report = {};
  try {
    for (const route of surface.routes) {
      report[route] = {};
      for (const viewport of VIEWPORTS) {
        await page.setViewportSize(viewport);
        const response = await page.goto(route, { waitUntil: 'networkidle' });
        expect(response?.status(), `${route} must respond`).toBeLessThan(400);
        const links = await page
          .locator('link[rel="stylesheet"]')
          .evaluateAll((nodes) => nodes.map((node) => new URL(node.href).pathname));
        report[route][`${viewport.width}x${viewport.height}`] = { links };
        await page.screenshot({
          path: fileURLToPath(new URL(`${slug(route)}-${viewport.width}x${viewport.height}.png`, outDir)),
          fullPage: false,
        });
      }
      const axe = await new AxeBuilder({ page }).analyze();
      report[route].axeViolations = axe.violations.map(({ id, impact, nodes }) => ({
        id,
        impact,
        nodes: nodes.length,
      }));
    }
  } finally {
    if (surface.authenticated) await logout(page).catch(() => {});
  }
  report.cssRequests = [...cssRequests].sort();
  await writeFile(new URL('stylesheets.json', outDir), `${JSON.stringify(report, null, 2)}\n`);
  await writeFile(new URL('console.json', outDir), `${JSON.stringify(consoleEntries, null, 2)}\n`);
});
