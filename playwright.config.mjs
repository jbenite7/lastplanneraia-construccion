import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/browser',
  testMatch: '*.mjs',
  testIgnore: ['**/fixtures/**', '**/support/**'],
  timeout: 120_000,
  workers: 1,
  use: {
    baseURL: 'http://localhost:8081',
    headless: true,
  },
});
