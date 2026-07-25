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
  { route: '/programacion-semanal/cic', active: 'programacion-semanal', label: 'CIC' },
  { route: '/programacion-semanal/cnc', active: 'programacion-semanal', label: 'CNC' },
  { route: '/programacion-semanal/cnp', active: 'programacion-semanal', label: 'CNP' },
  { route: '/indicadores', active: 'indicadores', label: 'Indicadores LPS' },
  { route: '/listado-actividades', active: 'listado-actividades', label: 'Familias de Actividades' },
  { route: '/contratos', active: 'contratos', label: 'Paquetes de Contratación' },
  { route: '/pdc', active: 'plan-compras', label: 'Plan de Compras' },
  { route: '/bi/control-tower', active: 'control-tower', label: 'Control Tower - Informes' },
  { route: '/bi/programa-general', active: 'control-tower', label: 'Control Tower - Programa General' },
  { route: '/bi/intermedia', active: 'control-tower', label: 'Control Tower - Prog. Intermedia' },
  { route: '/bi/semanal', active: 'control-tower', label: 'Control Tower - Programación Semanal' },
  { route: '/bi/pdc', active: 'control-tower', label: 'Control Tower - Plan de Compras' },
  { route: '/bi/contratistas', active: 'control-tower', label: 'Control Tower - Proveedores (CIC)' },
  { route: '/bi/responsables', active: 'control-tower', label: 'Control Tower - Responsables (CIP)' },
  { route: '/bi/curva-s', active: 'control-tower', label: 'Control Tower - Curva S' },
];
const MIGRATED = new Set(['/programacion-intermedia', '/programa-general', '/profesionales', '/subcontratistas', '/control-cambios', '/programa-general-actualizar', '/programacion-semanal', '/programacion-semanal/cic', '/programacion-semanal/cnc', '/programacion-semanal/cnp', '/indicadores', '/listado-actividades', '/contratos', '/pdc', '/bi/control-tower', '/bi/programa-general', '/bi/intermedia', '/bi/semanal', '/bi/pdc', '/bi/contratistas', '/bi/responsables', '/bi/curva-s']); // se irá ampliando módulo a módulo

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

// F0/Task 8: la deuda registrada en la review final del sub-goal Control Tower
// (.superpowers/sdd/progress.md, seccion CT-Final) pedia este assert. Un
// localStorage.aia-theme = "linen" heredado de antes del retiro del
// conmutador no debe poder devolver ninguna ruta a claro: theme-bootstrap.js
// ya no lee esa clave, asi que sembrarla debe ser un no-op observable.
await page.addInitScript(() => {
  try { window.localStorage.setItem('aia-theme', 'linen'); } catch { /* modo privado */ }
});

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
// /api/cic/list NO es solo-lectura pese al nombre: CicApiController::list() ejecuta
// syncPac()/generateMissingSubs()/updateIntegral() (UPDATE/INSERT reales) antes de
// responder. Se intercepta con la forma vacía que espera el DataTables de CIC.view.php
// ({"data":[...]}) para que la vista siga renderizando sin tocar la BD compartida.
await page.route('**/api/cic/list*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"data":[]}' })
));
// Subvistas CIC/CNC/CNP (task 8): sus propios endpoints de guardado/mutación.
await page.route('**/api/cic/save*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"BIEN"}' })
));
await page.route('**/api/cnc/save*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"BIEN"}' })
));
await page.route('**/api/cnp/save*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"BIEN"}' })
));
await page.route('**/api/cnp/reprogramar*', (route) => (
  route.fulfill({ contentType: 'application/json', body: '{"respuesta":"BIEN"}' })
));

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

  // 0) Regresion CT-Final: aia-theme=linen heredado no debe devolver la ruta a claro.
  // theme.js se inyecta dinamicamente (async=false, encolado detras de sweetalert2)
  // en las rutas no-BI: un script insertado asi no bloquea domcontentloaded, asi que
  // leer data-aia-theme justo despues de domcontentloaded solo es determinista en las
  // 8 rutas /bi/* (carga sincrona via views/bi/_layout.php:97). En el resto se corre
  // el riesgo de leer el atributo "dark" estatico del <html> del SSR antes de que
  // theme.js haya tenido oportunidad de aplicar un tema obsoleto. window.AiaDesignSystem
  // se define y el atributo se aplica dentro del mismo bloque sincrono top-level del
  // IIFE de theme.js (sin await/setTimeout entre medio), asi que esperar a que el
  // global exista es equivalente a esperar a que theme.js haya terminado de ejecutarse
  // por completo, incluida la escritura del atributo.
  await page.waitForFunction(() => window.AiaDesignSystem);
  const themeWithStaleLinen = await page.evaluate(() => document.documentElement.getAttribute('data-aia-theme'));
  check(`[${r.label}] aia-theme=linen heredado no vuelve a claro`,
    themeWithStaleLinen === 'dark', `data-aia-theme=${themeWithStaleLinen}`);

  // Robustez: limpiar estado persistido para que el check de "default colapsado"
  // sea genuino, no un remanente de una ruta anterior en el mismo contexto.
  await page.evaluate(() => localStorage.removeItem('aia-sidebar-state'));
  await page.reload({ waitUntil: 'domcontentloaded' });
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

  // 6) [Control Tower] Cajón derecho de filtros (CT-2): trigger existe, click abre, Escape cierra.
  if (r.route === '/bi/control-tower') {
    const triggerExists = await page.evaluate(() => !!document.querySelector('[data-bi-filter-trigger]'));
    check(`[${r.label}] trigger de filtros existe`, triggerExists, `found=${triggerExists}`);

    await page.evaluate(() => document.querySelector('[data-bi-filter-trigger]').click());
    await page.waitForTimeout(350);
    const drawerOpenOk = await page.evaluate(() => {
      const drawer = document.querySelector('[data-bi-filter-drawer]');
      return !!drawer && !drawer.hidden && drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'false';
    });
    check(`[${r.label}] click en trigger abre [data-bi-filter-drawer]`, drawerOpenOk, `open=${drawerOpenOk}`);

    await page.keyboard.press('Escape');
    await page.waitForTimeout(350);
    const drawerClosedOk = await page.evaluate(() => {
      const drawer = document.querySelector('[data-bi-filter-drawer]');
      return !!drawer && !drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'true';
    });
    check(`[${r.label}] Escape cierra el cajón de filtros`, drawerClosedOk, `closed=${drawerClosedOk}`);
  }

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
