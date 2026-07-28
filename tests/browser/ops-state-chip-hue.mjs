import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject } from './support/session.mjs';

const project = PROJECTS.find(({ key }) => key === 'construction');
const VIEWPORT = { width: 1180, height: 820 };

// La celda de estado de /programacion-intermedia pintaba su chip con una regla
// por nombre de clase -diez reglas en la hoja del modulo, una por
// `.pi-state-<key>`- en vez de declarar que estado es y dejar que la capa de
// componentes lo pinte. Con el eje de matiz ya en el contrato y la primitiva ya
// en `states-feedback.css`, esas diez reglas eran duplicacion.
//
// Este test verifica el contrato de datos, no el color concreto: que el chip
// declare el matiz y el nivel que el contrato le asigna a su estado, y que el
// color que acaba pintando sea el que la escalera da para ese matiz. Asi sigue
// valiendo cuando la escalera cambie de valores.
const SEVERITY_BY_LEVEL = {
  neutral: { severity: 'none', urgency: 'none' },
  healthy: { severity: 'low', urgency: 'none' },
  attention: { severity: 'medium', urgency: 'soon' },
  urgent: { severity: 'high', urgency: 'now' },
};

async function contractStates(module) {
  const semantics = JSON.parse(await readFile('docs/design-system/state-semantics.json', 'utf8'));
  const mapping = semantics.moduleMappings.find((m) => m.module === module);
  const hues = Object.fromEntries(semantics.hues.map((h) => [h.id, h.tint]));
  return { states: mapping.states, hues };
}

async function readChips(page) {
  return page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    const paint = (color, backdrop) => {
      ctx.fillStyle = backdrop;
      ctx.fillRect(0, 0, 1, 1);
      ctx.fillStyle = color;
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
      return [r, g, b];
    };
    const pageBackground = getComputedStyle(document.body).backgroundColor;

    return [...document.querySelectorAll('.ops-state-td .ops-state-chip')].map((chip) => ({
      hue: chip.dataset.aiaHue ?? null,
      severity: chip.dataset.aiaSeverity ?? null,
      urgency: chip.dataset.aiaUrgency ?? null,
      label: chip.textContent.replace(/\s+/g, ' ').trim(),
      painted: paint(getComputedStyle(chip).backgroundColor, pageBackground),
    }));
  });
}

async function resolveTint(page, tintToken) {
  return page.evaluate((token) => {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d');
    const probe = document.createElement('div');
    probe.style.cssText = `position:absolute;left:-9999px;background-color:var(${token});`;
    document.body.appendChild(probe);
    const declared = getComputedStyle(probe).backgroundColor;
    probe.remove();
    ctx.fillStyle = getComputedStyle(document.body).backgroundColor;
    ctx.fillRect(0, 0, 1, 1);
    ctx.fillStyle = declared;
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
    return [r, g, b];
  }, tintToken);
}

function toHex([r, g, b]) {
  return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
}

function separation(a, b) {
  return Math.max(...a.map((v, i) => Math.abs(v - b[i])));
}

test('la celda de estado de Intermedia declara matiz y nivel', async ({ page }) => {
  const { states, hues } = await contractStates('programacion-intermedia');
  const byHue = new Map(states.map((s) => [s.hue, s]));

  await page.setViewportSize(VIEWPORT);
  await loginAndSelectProject(page, project);
  await page.goto('/programacion-intermedia', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.ops-state-td .ops-state-chip').first())
    .toBeVisible({ timeout: 45000 });

  const chips = await readChips(page);
  expect(chips.length, 'la grilla no renderizó ninguna celda de estado').toBeGreaterThan(0);

  const known = new Set(states.map((s) => s.hue));
  for (const chip of chips) {
    expect(chip.hue, `el chip «${chip.label}» no declara data-aia-hue`).toBeTruthy();
    expect(known, `«${chip.label}» declara el matiz ${chip.hue}, que no está en el contrato`)
      .toContain(chip.hue);

    // El nivel viaja como severity+urgency, que es el par que la capa de
    // componentes ya usa; tiene que ser el del estado, no uno cualquiera.
    const level = byHue.get(chip.hue).level;
    const expectedPair = SEVERITY_BY_LEVEL[level];
    expect(
      { severity: chip.severity, urgency: chip.urgency },
      `«${chip.label}» (matiz ${chip.hue}) debería declarar el par de ${level}`,
    ).toEqual(expectedPair);

    // Y el color pintado tiene que ser el de la escalera para ese matiz: si el
    // modulo lo sigue eligiendo por su cuenta, aqui se separa.
    const tint = await resolveTint(page, hues[chip.hue]);
    expect(
      separation(chip.painted, tint),
      `«${chip.label}» pinta ${toHex(chip.painted)} pero la escalera da `
      + `${toHex(tint)} para ${chip.hue} (${hues[chip.hue]})`,
    ).toBeLessThanOrEqual(2);
  }
});
