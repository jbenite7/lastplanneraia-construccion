import { expect, test } from '@playwright/test';

import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const TARGETS = [
  '/api/general/list',
  '/js/modules/bi-access.js',
  '/programa-general/filtros',
];
const UPDATE_BATCH = '/api/general/update-batch';

function normalizedPath(rawUrl) {
  const url = new URL(rawUrl);
  for (const key of ['v', '_', 'cache', 'cacheBust']) url.searchParams.delete(key);
  url.searchParams.sort();
  return `${url.pathname}${url.search}`;
}

test('loads each Programa General runtime dependency once on first entry', async ({ page }) => {
  const project = PROJECTS.find(({ key }) => key === 'construction');
  expect(project).toBeTruthy();
  await loginAndSelectProject(page, project, ADMIN);
  await page.evaluate(() => sessionStorage.removeItem('pgAutoUpdateOnNextLoad'));

  const requests = [];
  page.on('request', (request) => {
    if ([...TARGETS, UPDATE_BATCH].some((target) => request.url().includes(target))) {
      requests.push(normalizedPath(request.url()));
    }
  });

  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => Boolean(window.PGHotModule?.getHotInstance?.()), null, {
    timeout: 45_000,
  });
  await page.waitForLoadState('networkidle');

  for (const target of TARGETS) {
    expect(requests.filter((request) => request.startsWith(target)), target).toHaveLength(1);
  }
  expect(requests.filter((request) => request.startsWith(UPDATE_BATCH))).toHaveLength(0);
});

test('refreshes once after returning to Programa General', async ({ page }) => {
  const project = PROJECTS.find(({ key }) => key === 'construction');
  expect(project).toBeTruthy();
  await loginAndSelectProject(page, project, ADMIN);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => Boolean(window.PGHotModule?.getHotInstance?.()), null, {
    timeout: 45_000,
  });
  await page.waitForLoadState('networkidle');
  await page.goto('/proyectos', { waitUntil: 'domcontentloaded' });

  const requests = [];
  page.on('request', (request) => {
    if ([...TARGETS, UPDATE_BATCH].some((target) => request.url().includes(target))) {
      requests.push(normalizedPath(request.url()));
    }
  });
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');
  expect(await page.evaluate(() => Boolean(window.PGHotModule?.getHotInstance?.()))).toBe(true);

  for (const target of [...TARGETS, UPDATE_BATCH]) {
    expect(requests.filter((request) => request.startsWith(target)), target).toHaveLength(1);
  }
});

test('loads once when the navigation-return refresh fails', async ({ page }) => {
  const project = PROJECTS.find(({ key }) => key === 'construction');
  expect(project).toBeTruthy();
  await loginAndSelectProject(page, project, ADMIN);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => Boolean(window.PGHotModule?.getHotInstance?.()), null, {
    timeout: 45_000,
  });
  await page.waitForLoadState('networkidle');
  await page.goto('/proyectos', { waitUntil: 'domcontentloaded' });
  await page.route(`**${UPDATE_BATCH}**`, (route) => route.fulfill({ status: 503, body: '{}' }));

  const requests = [];
  page.on('request', (request) => {
    if ([...TARGETS, UPDATE_BATCH].some((target) => request.url().includes(target))) {
      requests.push(normalizedPath(request.url()));
    }
  });
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');
  expect(await page.evaluate(() => Boolean(window.PGHotModule?.getHotInstance?.()))).toBe(true);

  for (const target of [...TARGETS, UPDATE_BATCH]) {
    expect(requests.filter((request) => request.startsWith(target)), target).toHaveLength(1);
  }
});
