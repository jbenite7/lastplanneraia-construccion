import { chromium } from 'playwright';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outDir = path.join(rootDir, 'docs', 'superpowers', 'evidence', 's05-ronda-1-2');
const baseUrl = process.env.LPS_BASE_URL ?? 'http://localhost:8081';
const viewport = { width: 1180, height: 820 };

function nombre(tema, estado) {
  return path.join(outDir, `programa-general-${tema}-${estado}.png`);
}

async function fijarTema(page, tema) {
  await page.addInitScript((value) => localStorage.setItem('aia-theme', value), tema);
  await page.evaluate((value) => {
    localStorage.setItem('aia-theme', value);
    document.documentElement.setAttribute('data-aia-theme', value);
  }, tema);
}

async function abrirPrograma(page, tema) {
  await fijarTema(page, tema);
  await page.goto(`${baseUrl}/programa-general`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('table.programa-table-pro tbody tr.row-activity', { timeout: 45_000 });
  await page.waitForFunction((esperado) => document.documentElement.dataset.aiaTheme === esperado, tema);
  await page.evaluate(() => document.fonts.ready);
}

async function capturar(page, archivo) {
  await page.screenshot({ path: archivo, animations: 'disabled' });
  console.log(`  ${path.relative(rootDir, archivo)}`);
}

async function generarTema(page, tema) {
  await abrirPrograma(page, tema);
  await capturar(page, nombre(tema, '08-columnas'));

  await page.getByRole('button', { name: /13 Cols Reales/i }).click();
  await page.waitForSelector('table.programa-table-pro thead th:nth-child(13)');
  await capturar(page, nombre(tema, '13-columnas'));

  const viewportTabla = page.locator('.table-viewport');
  await viewportTabla.evaluate((element) => { element.scrollTop = element.scrollHeight; });
  await page.waitForTimeout(150);
  await capturar(page, nombre(tema, 'ultima-fila'));

  await abrirPrograma(page, tema);
  const rail = page.locator('[data-shell-pattern="sidebar"]');
  await page.getByRole('button', { name: /Expandir menú|Colapsar menú/ }).click();
  await rail.waitFor({ state: 'visible' });
  await capturar(page, nombre(tema, 'riel-expandido'));

  await page.getByRole('button', { name: 'Colapsar menú' }).click();
  await capturar(page, nombre(tema, 'riel-colapsado'));

  await page.locator('#ctxSemanaBadge').click();
  await page.locator('#ctxWeekMenu[role="menu"]').waitFor({ state: 'visible' });
  await capturar(page, nombre(tema, 'menu-semana'));

  await page.keyboard.press('Escape');
  await page.getByRole('button', { name: /Drawer LPS/i }).click();
  await page.getByRole('dialog', { name: 'Editor Contextual LPS' }).waitFor({ state: 'visible' });
  await capturar(page, nombre(tema, 'drawer'));
}

async function generateLiveScreenshots() {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport, deviceScaleFactor: 1 });
  const page = await context.newPage();

  try {
    console.log('Autenticando por la puerta de desarrollo con el proyecto Da Porto...');
    await page.goto(`${baseUrl}/dev/entrar?u=test.A&p=${encodeURIComponent('Da Porto')}`, { waitUntil: 'domcontentloaded' });
    if (new URL(page.url()).pathname === '/login') throw new Error('La puerta de desarrollo no está habilitada para test.A.');

    for (const tema of ['light', 'dark']) {
      console.log(`Capturando ${tema} a 1180x820...`);
      await generarTema(page, tema);
    }
  } finally {
    await browser.close();
  }
}

generateLiveScreenshots().catch((error) => {
  console.error('Error generando evidencia S05:', error);
  process.exit(1);
});
