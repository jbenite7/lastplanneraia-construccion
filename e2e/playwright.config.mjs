import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  testMatch: '**/*.spec.mjs',
  testIgnore: ['**/support/**', '**/fixtures/**'],
  timeout: 120_000,
  expect: { timeout: 45_000 },
  fullyParallel: false,
  workers: parseInt(process.env.E2E_WORKERS || '1', 10),
  retries: 0,
  outputDir: './test-output',
  reporter: [
    ['html', { outputFolder: './test-results/report', open: 'never' }],
    ['list'],
  ],
  use: {
    baseURL: process.env.APP_URL || 'http://localhost:8081',
    headless: process.env.E2E_HEADLESS !== 'false',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 30_000,
  },
});