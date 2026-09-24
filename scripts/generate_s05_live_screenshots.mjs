import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const outDir = path.join(rootDir, 'public', 'mockups', 'img');
const brainDir = '/Users/felipebenitez/.gemini/antigravity/brain/d3756c46-c51e-4147-b661-81d51c7a384a';

async function generateLiveScreenshots() {
  if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
  }
  if (!fs.existsSync(brainDir)) {
    fs.mkdirSync(brainDir, { recursive: true });
  }

  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1180, height: 820 },
    deviceScaleFactor: 2,
  });
  const page = await context.newPage();

  console.log('1. Autenticando en Docker vía Dev Door (u=test.A&p=1)...');
  await page.goto('http://localhost:8081/dev/entrar?u=test.A&p=1');
  await page.waitForLoadState('networkidle');

  console.log('2. Navegando a la ruta canónica http://localhost:8081/programa-general...');
  await page.goto('http://localhost:8081/programa-general');
  await page.waitForSelector('.programa-general-container', { timeout: 15000 });
  await page.waitForSelector('.programa-table-pro', { timeout: 15000 });
  await page.waitForSelector('.row-activity', { timeout: 15000 });

  // 1. live-docker-programa-general-dark.png (8 essential columns table - Dark)
  console.log('3. Capturando live-docker-programa-general-dark.png...');
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'dark');
    document.documentElement.setAttribute('data-aia-theme', 'dark');
    document.documentElement.classList.add('aia-theme-dark');
  });
  await page.waitForTimeout(500);

  const fileTableDark = path.join(outDir, 'live-docker-programa-general-dark.png');
  await page.screenshot({ path: fileTableDark });
  fs.copyFileSync(fileTableDark, path.join(brainDir, 'live-docker-programa-general-dark.png'));
  console.log('  -> Guardado en public/mockups/img/ y brain/');

  // 2. live-docker-drawer-dark.png (Drawer LPS open - Dark)
  console.log('4. Capturando live-docker-drawer-dark.png...');
  const firstActivity = page.locator('.row-activity').first();
  await firstActivity.click();
  await page.waitForSelector('.drawer-panel-pro.active', { timeout: 5000 });
  await page.waitForTimeout(500);

  const fileDrawerDark = path.join(outDir, 'live-docker-drawer-dark.png');
  await page.screenshot({ path: fileDrawerDark });
  fs.copyFileSync(fileDrawerDark, path.join(brainDir, 'live-docker-drawer-dark.png'));
  console.log('  -> Guardado en public/mockups/img/ y brain/');

  // Cerrar drawer
  await page.keyboard.press('Escape');
  await page.waitForTimeout(400);

  // 3. live-docker-programa-general-light.png (8 essential columns table - Light)
  console.log('5. Capturando live-docker-programa-general-light.png...');
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'light');
    document.documentElement.setAttribute('data-aia-theme', 'light');
    document.documentElement.classList.remove('aia-theme-dark');
  });
  await page.waitForTimeout(500);

  const fileTableLight = path.join(outDir, 'live-docker-programa-general-light.png');
  await page.screenshot({ path: fileTableLight });
  fs.copyFileSync(fileTableLight, path.join(brainDir, 'live-docker-programa-general-light.png'));
  console.log('  -> Guardado en public/mockups/img/ y brain/');

  // 4. live-docker-drawer-light.png (Drawer LPS open - Light)
  console.log('6. Capturando live-docker-drawer-light.png...');
  await firstActivity.click();
  await page.waitForSelector('.drawer-panel-pro.active', { timeout: 5000 });
  await page.waitForTimeout(500);

  const fileDrawerLight = path.join(outDir, 'live-docker-drawer-light.png');
  await page.screenshot({ path: fileDrawerLight });
  fs.copyFileSync(fileDrawerLight, path.join(brainDir, 'live-docker-drawer-light.png'));
  console.log('  -> Guardado en public/mockups/img/ y brain/');

  await browser.close();
  console.log('Capturas en vivo generadas con éxito.');
}

generateLiveScreenshots().catch((err) => {
  console.error('Error generando capturas en vivo:', err);
  process.exit(1);
});
