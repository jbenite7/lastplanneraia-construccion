import { test, expect } from '@playwright/test';
import { CREDENTIALS } from './fixtures/projects.mjs';
import {
  changeWeek,
  login,
  selectProject,
} from './support/session.mjs';

const DA_PORTO = {
  name: 'Preconstrucción Da Porto',
  projectId: 76,
  dbPrefix: 'da_porto',
};

async function openCnp(page, viewport) {
  await page.setViewportSize(viewport);
  await login(page, CREDENTIALS);
  await selectProject(page, DA_PORTO);
  const response = page.waitForResponse((item) => (
    item.url().includes('/api/cnp/list') && item.request().method() === 'POST'
  ));
  await changeWeek(page, 1, '/programacion-semanal/cnp');
  expect((await response).ok(), 'CNP list request').toBe(true);
  await page.waitForFunction(() => {
    const jq = window.jQuery;
    if (!jq?.fn?.dataTable?.isDataTable('#dt_cliente')) return false;
    return jq('#dt_cliente').DataTable().rows().count() > 0;
  }, null, { timeout: 30000 });
}

test('PSLegacyCards conserva un solo listener mobile al repetir attach', async ({ page }) => {
  await page.addInitScript(() => {
    const prototype = window.MediaQueryList.prototype;
    const addEventListener = prototype.addEventListener;
    window.__qaMobileChangeListeners = [];
    prototype.addEventListener = function (type, listener, options) {
      if (type === 'change' && this.media === '(max-width: 1179px)') {
        window.__qaMobileChangeListeners.push(listener);
      }
      return addEventListener.call(this, type, listener, options);
    };
  });
  await openCnp(page, { width: 390, height: 844 });

  const state = await page.evaluate(() => {
    const table = window.jQuery('#dt_cliente').DataTable();
    const container = document.querySelector('#ps-legacy-card-view');
    window.PSLegacyCards.attach(table, 'cnp');
    window.PSLegacyCards.attach(table, 'cnp');
    window.PSLegacyCards.attach(table, 'cnp');
    const descriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
    let renders = 0;
    Object.defineProperty(container, 'innerHTML', {
      configurable: true,
      get() { return descriptor.get.call(this); },
      set(value) { renders += 1; descriptor.set.call(this, value); },
    });
    const listeners = window.__qaMobileChangeListeners;
    listeners.forEach((listener) => listener({ matches: true }));
    const liveRenders = renders;

    table.destroy();
    table.rows = function () { throw new Error('render sobre tabla destruida'); };
    renders = 0;
    const errors = [];
    listeners.forEach((listener) => {
      try { listener({ matches: true }); } catch (error) { errors.push(error.message); }
    });
    return { errors, listenerCount: listeners.length, liveRenders, staleRenders: renders };
  });

  expect(state.listenerCount).toBe(1);
  expect(state.liveRenders).toBe(1);
  expect(state.staleRenders).toBe(0);
  expect(state.errors).toEqual([]);
});

test('recargar CNP restaura scroll con un handler draw de un solo uso', async ({ page }) => {
  await openCnp(page, { width: 787, height: 750 });

  const restoreHandlerCount = () => page.evaluate(() => {
    const events = window.jQuery._data(document.querySelector('#dt_cliente'), 'events');
    return (events?.draw || []).filter(({ handler }) => {
      const source = String(handler);
      return source.includes('dataTables_scrollBody') && source.includes('scrollTop(posicion)');
    }).length;
  });

  const cycle = async (scrollTop) => {
    await page.locator('#dt_cliente tbody button.editar').first().click();
    const response = page.waitForResponse((item) => item.url().includes('/api/cnp/list'));

    await page.evaluate((value) => {
      document.querySelector('.dataTables_scrollBody').scrollTop = value;
      document.querySelector('#btn_cancelar_editar').click();
    }, scrollTop);
    expect((await response).ok(), 'CNP reload request').toBe(true);
    await page.waitForFunction(() => window.jQuery('#dt_cliente').DataTable().rows().count() > 0);
    await expect.poll(() => page.evaluate(() => (
      document.querySelector('.dataTables_scrollBody').scrollTop
    ))).toBeGreaterThanOrEqual(scrollTop - 1);
    return restoreHandlerCount();
  };

  expect(await cycle(120)).toBe(0);
  expect(await cycle(180)).toBe(0);
});
