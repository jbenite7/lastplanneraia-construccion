import { defineConfig } from '@playwright/test';
import { BASE_URL } from './tests/browser/fixtures/base-url.mjs';

export default defineConfig({
  testDir: './tests/browser',
  testMatch: '*.mjs',
  testIgnore: ['**/fixtures/**', '**/support/**'],
  timeout: 120_000,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  outputDir: './test-output',
  reporter: [
    ['html', { outputFolder: './test-results/report', open: 'never' }],
    ['list'],
  ],
  snapshotPathTemplate: '{testDir}/__screenshots__/{testFilePath}/{arg}{ext}',
  expect: {
    toHaveScreenshot: {
      animations: 'disabled',
      caret: 'hide',
      // Piso comun para los goldens que no fijan el suyo (laboratorio y cajon LPS). Baja de 0,005 a
      // 0,002 por el mismo hallazgo que apreto los dos specs de rejilla: una tolerancia amplia deja
      // pasar cambios reales de diseno. Medido: con la tolerancia en 0, tres corridas seguidas sin
      // tocar nada no produjeron ni un pixel de diferencia.
      maxDiffPixelRatio: 0.002,
      scale: 'css',
    },
  },
  use: {
    // Mismo origen que usan los helpers de sesión (login/logout con URL absoluta): si divergen, el
    // login entra en un stack y las rutas relativas de `page.goto` aterrizan en otro, sin sesión.
    baseURL: BASE_URL,
    headless: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
});
