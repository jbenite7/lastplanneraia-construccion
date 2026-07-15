import { defineConfig } from '@playwright/test';

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
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8081',
    headless: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
});
