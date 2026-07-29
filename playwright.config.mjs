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
      maxDiffPixelRatio: 0.005,
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
