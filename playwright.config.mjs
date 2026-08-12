import { defineConfig } from '@playwright/test';
import { BASE_URL } from './tests/browser/fixtures/base-url.mjs';

export default defineConfig({
  testDir: './tests/browser',
  testMatch: '*.mjs',
  // Los tres `.mjs` de abajo NO son specs: son scripts autoejecutables que se lanzan con `node`
  // (así los invocan `goals/sidebar-todos-modulos/plan.md:48` y los planes de F0/F1) y terminan
  // llamando a `process.exit()`. Vivían dentro de este `testDir`, así que Playwright los importaba
  // en la fase de RECOLECCIÓN y ese `exit(0)` mataba la corrida entera antes de ejecutar un solo
  // spec, devolviendo código 0: `npx playwright test` daba VERDE habiendo probado NADA
  // (`--list` reportaba `Total: 0 tests in 0 files`). Medido el 2026-08-07; el verde falso llevaba
  // ocultos, entre otros, los goldens de PG/PI en rojo y ~75 specs desfasadas.
  // Se excluyen en vez de moverlos para no invalidar las rutas que ya citan planes y goals.
  testIgnore: [
    '**/fixtures/**',
    '**/support/**',
    '**/handsontable-ancho-tabla.mjs',
    '**/shell-sidebar-rollout.mjs',
    '**/shell-week-admin.mjs',
  ],
  timeout: 120_000,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  outputDir: './test-output',
  reporter: [
    ['html', { outputFolder: './test-results/report', open: 'never' }],
    ['list'],
  ],
  // D-GAC-4 (2026-08-12): los goldens se separan por plataforma. Habia un solo
  // juego para macOS y Linux, y eso hacia que el carril visual pasara en local
  // y fallara en CI: 18 de 20 con ratio 0,03 contra una tolerancia de 0,002,
  // todas las familias con casi el mismo numero de pixeles distintos. El recibo
  // verde de `runtime` era honesto y solo valia en la maquina que lo midio.
  // Nadie lo vio en un mes porque `needs: design-system-static` mantenia el job
  // apagado. Los 60 aprobados NO se tocan: se mueven a su carpeta de plataforma.
  snapshotPathTemplate: '{testDir}/__screenshots__/{platform}/{testFilePath}/{arg}{ext}',
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
