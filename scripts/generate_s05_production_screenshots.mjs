import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const htmlPath = path.join(rootDir, 'public', 'mockups', 's05-production-mockup.html');
const outDir = path.join(rootDir, 'public', 'mockups', 'img');
const brainDir = '/Users/felipebenitez/.gemini/antigravity/brain/d3756c46-c51e-4147-b661-81d51c7a384a';

async function render() {
  const browser = await chromium.launch();
  const context = await browser.newContext({
    viewport: { width: 1180, height: 820 },
    deviceScaleFactor: 2
  });
  const page = await context.newPage();
  const fileUrl = 'file://' + htmlPath;

  // 1. Table 8 Essential Columns - Dark
  await page.goto(fileUrl, { waitUntil: 'networkidle' });
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'dark');
    setColumnsMode('rational');
    cerrarDrawer();
  });
  await page.waitForTimeout(400);
  const snapTableDark = path.join(outDir, 's05-production-table-dark-1180x820.png');
  await page.screenshot({ path: snapTableDark });
  fs.copyFileSync(snapTableDark, path.join(brainDir, 's05-production-table-dark-1180x820.png'));
  console.log('Generated: s05-production-table-dark-1180x820.png');

  // 2. Table 8 Essential Columns - Light
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'light');
    setColumnsMode('rational');
    cerrarDrawer();
  });
  await page.waitForTimeout(400);
  const snapTableLight = path.join(outDir, 's05-production-table-light-1180x820.png');
  await page.screenshot({ path: snapTableLight });
  fs.copyFileSync(snapTableLight, path.join(brainDir, 's05-production-table-light-1180x820.png'));
  console.log('Generated: s05-production-table-light-1180x820.png');

  // 3. Drawer LPS Open - Dark
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'dark');
    abrirDrawer(101);
  });
  await page.waitForTimeout(450);
  const snapDrawerDark = path.join(outDir, 's05-production-drawer-dark-1180x820.png');
  await page.screenshot({ path: snapDrawerDark });
  fs.copyFileSync(snapDrawerDark, path.join(brainDir, 's05-production-drawer-dark-1180x820.png'));
  console.log('Generated: s05-production-drawer-dark-1180x820.png');

  // 4. Drawer LPS Open - Light
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'light');
    abrirDrawer(101);
  });
  await page.waitForTimeout(450);
  const snapDrawerLight = path.join(outDir, 's05-production-drawer-light-1180x820.png');
  await page.screenshot({ path: snapDrawerLight });
  fs.copyFileSync(snapDrawerLight, path.join(brainDir, 's05-production-drawer-light-1180x820.png'));
  console.log('Generated: s05-production-drawer-light-1180x820.png');

  // 5. 13 Reality Columns - Dark
  await page.evaluate(() => {
    document.documentElement.setAttribute('data-theme', 'dark');
    setColumnsMode('reality');
    cerrarDrawer();
  });
  await page.waitForTimeout(400);
  const snapRealityDark = path.join(outDir, 's05-production-reality13-dark-1180x820.png');
  await page.screenshot({ path: snapRealityDark });
  fs.copyFileSync(snapRealityDark, path.join(brainDir, 's05-production-reality13-dark-1180x820.png'));
  console.log('Generated: s05-production-reality13-dark-1180x820.png');

  await browser.close();
  console.log('All production screenshots generated successfully.');
}

render().catch(err => {
  console.error(err);
  process.exit(1);
});
