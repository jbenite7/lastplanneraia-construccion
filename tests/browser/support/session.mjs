import { expect } from '@playwright/test';
import { BASE_URL, CREDENTIALS } from '../fixtures/projects.mjs';

export async function login(page, credentials = CREDENTIALS) {
  await page.goto(`${BASE_URL}/login`);
  await page.locator('#usuario').fill(credentials.username);
  await page.locator('#password').fill(credentials.password);
  await Promise.all([
    page.waitForURL((url) => url.pathname === '/proyectos', { timeout: 45000 }),
    page.locator('button[type="submit"]').click(),
  ]);
  await expect(page.locator('.project-item').first()).toBeVisible({ timeout: 45000 });
}

export async function logout(page) {
  await page.goto(`${BASE_URL}/logout`);
  await page.waitForURL(/login|\/$/, { timeout: 15000 }).catch(() => {});
}

export async function selectProject(page, project) {
  const card = page.locator('.project-item').filter({
    has: page.getByRole('heading', { name: project.name, exact: true }),
  });
  await expect(card, `Project card not found: ${project.name}`).toBeVisible({ timeout: 45000 });
  await card.locator('button[type="submit"], .btn-enter').click();
  await page.waitForURL((url) => !url.toString().includes('/proyectos'), { timeout: 45000 });
}

export async function loginAndSelectProject(page, project, credentials = CREDENTIALS) {
  await login(page, credentials);
  await selectProject(page, project);
}

export async function changeWeek(page, week, destination = '/programa-general') {
  const response = await page.evaluate(
    async ({ selectedWeek, redirectTo }) => {
      const res = await fetch('/context/week', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ semana: selectedWeek }),
      });
      const payload = await res.json().catch(() => ({}));
      if (payload.success) window.location.href = redirectTo;
      return { ok: res.ok, status: res.status, payload };
    },
    { selectedWeek: week, redirectTo: destination },
  );

  expect(response.ok, JSON.stringify(response)).toBe(true);
  expect(response.payload.success, JSON.stringify(response)).toBe(true);
  await page.waitForURL(`**${destination}`, { timeout: 45000 });
  await expect(page.locator('#semana, #semana_PHP').first()).toHaveValue(String(week), { timeout: 45000 });
}

export async function postFormJson(page, url, body = {}) {
  return page.evaluate(
    async ({ apiUrl, apiBody }) => {
      const formData = new URLSearchParams();
      const append = (prefix, value) => {
        if (Array.isArray(value)) {
          value.forEach((entry, index) => append(`${prefix}[${index}]`, entry));
        } else if (value && typeof value === 'object') {
          Object.entries(value).forEach(([key, entry]) => append(`${prefix}[${key}]`, entry));
        } else {
          formData.append(prefix, value == null ? '' : String(value));
        }
      };

      Object.entries(apiBody).forEach(([key, value]) => append(key, value));
      const headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
      if (apiUrl.startsWith('/api/pdc/')) {
        headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]')?.content || '';
      }
      const res = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: formData.toString(),
      });
      const text = await res.text();
      let payload;
      try {
        payload = JSON.parse(text);
      } catch {
        payload = { parseError: true, text };
      }
      return { ok: res.ok, status: res.status, payload };
    },
    { apiUrl: url, apiBody: body },
  );
}

export async function getJson(page, url) {
  return page.evaluate(async (apiUrl) => {
    const res = await fetch(apiUrl, { credentials: 'same-origin' });
    const text = await res.text();
    let payload;
    try {
      payload = JSON.parse(text);
    } catch {
      payload = { parseError: true, text };
    }
    return { ok: res.ok, status: res.status, payload };
  }, url);
}
