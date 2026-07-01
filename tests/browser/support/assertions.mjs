import { expect } from '@playwright/test';
import { BASE_URL } from '../fixtures/projects.mjs';

export function installErrorCollectors(page) {
  const errors = {
    pageErrors: [],
    consoleErrors: [],
    serverErrors: [],
  };

  const approvedConsoleErrors = [
    'Error fetching notifications: TypeError: Failed to fetch',
  ];
  const approvedConsoleErrorPatterns = [
    'Error fetching notifications: TypeError: Failed to fetch',
    'Error cargando códigos: TypeError: Failed to fetch',
    'MapeoManual] Error en fetch:  TypeError: Failed to fetch',
  ];

  page.on('pageerror', (error) => errors.pageErrors.push(error.message));
  page.on('console', (message) => {
    const text = message.text();
    const isApproved = approvedConsoleErrors.includes(text)
      || approvedConsoleErrorPatterns.some((pattern) => text.includes(pattern));
    if (message.type() === 'error' && !isApproved) {
      errors.consoleErrors.push(text);
    }
  });
  page.on('response', (response) => {
    if (response.status() >= 500) errors.serverErrors.push(`${response.status()} ${response.url()}`);
  });

  return errors;
}

export function assertNoRuntimeErrors(errors) {
  expect(errors.pageErrors, 'Unhandled page errors').toEqual([]);
  expect(errors.consoleErrors, 'console.error messages').toEqual([]);
  expect(errors.serverErrors, 'HTTP 500+ responses').toEqual([]);
}

export function normalizeText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

export async function assertProjectContext(page, project) {
  await page.waitForSelector('#baseDatos', { state: 'attached', timeout: 15000 }).catch(() => {});
  await expect.poll(async () => page.evaluate(() => {
    const nonEmptyInput = [...document.querySelectorAll('#baseDatos')].find((input) => input.value);
    return nonEmptyInput?.value || '';
  }), { timeout: 15000 }).toBe(project.dbPrefix);

  const state = await page.evaluate(() => {
    const nonEmptyInput = [...document.querySelectorAll('#baseDatos')].find((input) => input.value);
    const projectInput = document.querySelector('#proyecto, #proyecto_PHP');
    return {
      area: window.__PROJECT_AREA__ || null,
      dbPrefix: nonEmptyInput?.value || '',
      projectName: projectInput?.value || '',
      url: location.href,
    };
  });

  expect(state.dbPrefix, JSON.stringify(state)).toBe(project.dbPrefix);
  if (state.area) expect(state.area).toBe(project.area);
  if (state.projectName) expect(state.projectName).toBe(project.name);
}

export async function assertRestrictionConfig(page, project) {
  const response = await page.evaluate(async () => {
    const res = await fetch('/api/general/restriction-config', { credentials: 'same-origin' });
    return { ok: res.ok, status: res.status, payload: await res.json().catch(() => ({})) };
  });

  expect(response.ok, JSON.stringify(response)).toBe(true);
  expect(response.payload.area).toBe(project.area);
  expect(response.payload.hardRestrictions).toEqual(project.hardRestrictions);
  expect(response.payload.softRestrictions).toEqual(project.softRestrictions);
}

export async function assertNavbarForProject(page, project) {
  const navState = await page.evaluate((ids) => {
    const displayOf = (id) => {
      const el = document.getElementById(id);
      if (!el) return 'missing';
      const target = el.closest('li') || el;
      return getComputedStyle(target).display;
    };
    return Object.fromEntries(ids.map((id) => [id, displayOf(id)]));
  }, [...new Set([...project.expectedVisibleNav, ...project.expectedHiddenNav])]);

  for (const id of project.expectedVisibleNav) {
    expect(navState[id], `Expected visible nav ${id}`).not.toBe('none');
    expect(navState[id], `Expected present nav ${id}`).not.toBe('missing');
  }

  for (const id of project.expectedHiddenNav) {
    expect(['none', 'missing'], `Expected hidden nav ${id}; got ${navState[id]}`).toContain(navState[id]);
  }
}

export async function assertHeaders(page, expectedHeaders) {
  const readHeaders = async () => page.evaluate(() => (
    [...document.querySelectorAll('.handsontable .htCore thead th .colHeader, #dt_cliente thead th')]
      .map((header) => header.textContent || '')
      .filter(Boolean)
  ));
  await expect.poll(readHeaders, { timeout: 15000 }).not.toEqual([]);
  const headers = await readHeaders();
  const normalizedHeaders = headers.map(normalizeText);
  for (const header of expectedHeaders) {
    expect(normalizedHeaders, `Missing header ${header}`).toContain(normalizeText(header));
  }
}

export async function expectUsablePage(page, url, selectorCandidates = ['.handsontable', '#dt_cliente', 'main', 'body']) {
  await page.goto(`${BASE_URL}${url}`, { waitUntil: 'commit', timeout: 30000 });
  for (const selector of selectorCandidates) {
    try {
      await page.waitForSelector(selector, { state: 'attached', timeout: 10000 });
      return;
    } catch {
      // Try the next selector candidate.
    }
  }
  throw new Error(`No usable selector found for ${url}`);
}
