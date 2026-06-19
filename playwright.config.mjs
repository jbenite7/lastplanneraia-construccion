import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/browser',
  testMatch: '*.mjs',
  timeout: 120_000,
  use: {
    baseURL: 'http://localhost:8081',
    headless: true,
  },
});
