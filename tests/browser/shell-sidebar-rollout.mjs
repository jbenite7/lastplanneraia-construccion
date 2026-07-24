// Harness data-driven de verificación del rollout del shell sidebar canónico.
// Recorre ALL_ROUTES: las rutas en MIGRATED se verifican en profundidad (colapsado
// por defecto, toggle, cero-scroll del nav en ambos estados, sin overflow horizontal,
// ítem activo con aria-current); las demás reportan PENDING sin fallar el test.
// Cada migración futura solo agrega su ruta a MIGRATED.
import { chromium } from 'playwright';
import { BASE_URL, CREDENTIALS } from './fixtures/projects.mjs';

const results = [];
const check = (name, ok, detail) => {
  results.push({ name, ok });
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name} — ${detail}`);
};

const ALL_ROUTES = [
  { route: '/programacion-intermedia', active: 'programacion-intermedia', label: 'Programación Intermedia' },
  { route: '/programa-general', active: 'programa-general', label: 'Programa General' },
  { route: '/profesionales', active: 'profesionales', label: 'Profesionales' },
  { route: '/subcontratistas', active: 'subcontratistas', label: 'Subcontratistas' },
  { route: '/control-cambios', active: 'control-cambios', label: 'Control de Cambios' },
  { route: '/programa-general-actualizar', active: 'actualizar-cronograma', label: 'Actualizar Cronograma' },
  { route: '/programacion-semanal', active: 'programacion-semanal', label: 'Programación Semanal' },
  { route: '/indicadores', active: 'indicadores', label: 'Indicadores LPS' },
  { route: '/bi/control-tower', active: 'control-tower', label: 'Control Tower - Informes' },
];
const MIGRATED = new Set(['/programacion-intermedia']); // se irá ampliando módulo a módulo

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

// Interceptar mutaciones para no tocar la BD compartida.
await page.route('**/api/semanal/save*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"OK"}' })
));
await page.route('**/api/semanal/auto-program*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"OK"}' })
));
await page.route('**/nueva_semana.php*', (route) => route.fulfill({ contentType: 'application/json', body: '0' }));
await page.route('**/eliminar_semana.php*', (route) => route.fulfill({ contentType: 'application/json', body: '0' }));
await page.route('**/verificarCICActualizada.php', (route) => route.fulfill({ contentType: 'application/json', body: '0' }));

await page.goto(`${BASE_URL}/login`);
await page.locator('#usuario').fill(CREDENTIALS.username);
await page.locator('#password').fill(CREDENTIALS.password);
await Promise.all([
  page.waitForURL((u) => u.pathname === '/proyectos', { timeout: 45000 }),
  page.locator('button[type="submit"]').click(),
]);
await page.locator('.project-item').first().waitFor({ timeout: 45000 });
await page.locator('.project-item button[type="submit"], .project-item .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });

for (const r of ALL_ROUTES) {
  if (!MIGRATED.has(r.route)) {
    console.log(`PENDING ${r.route}`);
    continue;
  }

  await page.goto(`${BASE_URL}${r.route}`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('[data-shell-pattern="sidebar"]', { timeout: 20000 });

  // 1) Default colapsado
  const initialState = await page.evaluate(() => document.querySelector('[data-shell-pattern="sidebar"]').dataset.sidebarState);
  check(`[${r.label}] default colapsado`, initialState === 'collapsed', `sidebarState=${initialState}`);

  // 2) Toggle expande y colapsa
  await page.evaluate(() => document.querySelector('[data-sidebar-toggle]').click());
  await page.waitForTimeout(450);
  const expandedState = await page.evaluate(() => document.querySelector('[data-shell-pattern="sidebar"]').dataset.sidebarState);
  await page.evaluate(() => document.querySelector('[data-sidebar-toggle]').click());
  await page.waitForTimeout(450);
  const recollapsedState = await page.evaluate(() => document.querySelector('[data-shell-pattern="sidebar"]').dataset.sidebarState);
  check(`[${r.label}] toggle expande y colapsa`,
    expandedState === 'expanded' && recollapsedState === 'collapsed',
    `expanded=${expandedState} recollapsed=${recollapsedState}`);

  // 3) Cero-scroll del nav en ambos estados (colapsado ya está activo; medir, expandir, medir, volver a colapsar)
  const navBudgetCollapsed = await page.evaluate(() => {
    const nav = document.querySelector('.aia-sidebar__nav');
    return nav.scrollHeight <= nav.clientHeight + 1;
  });
  await page.evaluate(() => document.querySelector('[data-sidebar-toggle]').click());
  await page.waitForTimeout(450);
  const navBudgetExpanded = await page.evaluate(() => {
    const nav = document.querySelector('.aia-sidebar__nav');
    return nav.scrollHeight <= nav.clientHeight + 1;
  });
  check(`[${r.label}] cero-scroll del nav en ambos estados`,
    navBudgetCollapsed && navBudgetExpanded,
    `collapsed=${navBudgetCollapsed} expanded=${navBudgetExpanded}`);

  // 4) Sin overflow horizontal del documento en ambos estados (actualmente expandido)
  const overflowExpanded = await page.evaluate(() => (
    document.documentElement.scrollWidth - document.documentElement.clientWidth <= 1
  ));
  await page.evaluate(() => document.querySelector('[data-sidebar-toggle]').click());
  await page.waitForTimeout(450);
  const overflowCollapsed = await page.evaluate(() => (
    document.documentElement.scrollWidth - document.documentElement.clientWidth <= 1
  ));
  check(`[${r.label}] sin overflow horizontal en ambos estados`,
    overflowExpanded && overflowCollapsed,
    `expanded=${overflowExpanded} collapsed=${overflowCollapsed}`);

  // 5) Ítem activo
  const activeOk = await page.evaluate((activeId) => (
    !!document.querySelector(`[data-shell-pattern="sidebar"] [data-destination-id="${activeId}"][aria-current="page"]`)
  ), r.active);
  check(`[${r.label}] ítem activo con aria-current`, activeOk, `active=${r.active} found=${activeOk}`);

  // Deja el estado colapsado al terminar (el contexto/page se reutiliza entre rutas).
  const finalState = await page.evaluate(() => document.querySelector('[data-shell-pattern="sidebar"]').dataset.sidebarState);
  if (finalState !== 'collapsed') {
    await page.evaluate(() => document.querySelector('[data-sidebar-toggle]').click());
    await page.waitForTimeout(450);
  }
}

await browser.close();
const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} checks OK`);
process.exit(failed.length ? 1 : 0);
