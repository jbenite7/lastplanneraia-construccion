// Contrato runtime del flyout de gestión de Semanas y sus diálogos.
// Endpoints interceptados: nunca muta la BD compartida.
import { chromium } from 'playwright';
import { BASE_URL, CREDENTIALS } from './fixtures/projects.mjs';

const results = [];
const check = (name, ok, detail) => {
  results.push({ name, ok });
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name} — ${detail}`);
};

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

const calls = { cic: [], crear: [], eliminar: [], contexto: [], semanalSave: [], semanalAutoProgram: [] };
await page.route('**/verificarCICActualizada.php', async (route) => {
  calls.cic.push(route.request().postData());
  await route.fulfill({ contentType: 'application/json', body: '0' });
});
await page.route('**/nueva_semana.php*', async (route) => {
  calls.crear.push({ url: route.request().url(), body: route.request().postData() });
  await route.fulfill({ contentType: 'application/json', body: '[5, 0, 1, 1]' });
});
await page.route('**/eliminar_semana.php*', async (route) => {
  calls.eliminar.push({ url: route.request().url(), body: route.request().postData() });
  await route.fulfill({ contentType: 'application/json', body: '{"puedeEliminar":"SI","maxSemana":4}' });
});
// El aterrizaje por defecto del proyecto pasa por /programacion-semanal, cuyo bootstrap
// dispara save (opcion=sanear) y auto-program reales: se interceptan para no mutar la BD.
await page.route('**/api/semanal/save*', (route) => {
  calls.semanalSave.push(route.request().url());
  return route.fulfill({ contentType: 'application/json', body: '{"respuesta":"OK"}' });
});
await page.route('**/api/semanal/auto-program*', (route) => {
  calls.semanalAutoProgram.push(route.request().url());
  return route.fulfill({ contentType: 'application/json', body: '{"respuesta":"OK"}' });
});

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
await page.goto(`${BASE_URL}/programacion-intermedia`);
await page.waitForSelector('[data-shell-pattern="sidebar"]', { timeout: 20000 });

// Stub del redirect de contexto para capturar sin navegar
await page.evaluate(() => {
  window.__weekCalls = [];
  window.cambiarSemanaSesion = (week, path) => { window.__weekCalls.push({ week, path }); };
});

const shellData = await page.evaluate(() => JSON.parse(document.getElementById('shellWeekMenusData').textContent));
const maxSemana = shellData.maxSemana;

// Mismo algoritmo que formatEnd() en shell_week_admin.js: Date en T00:00:00 local, +6 días.
const formatEnd = (startIso) => {
  const d = new Date(`${startIso}T00:00:00`);
  if (Number.isNaN(d.getTime())) return '';
  d.setDate(d.getDate() + 6);
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
};

// 1) Estructura del flyout de gestión
const s = await page.evaluate(() => {
  const li = document.querySelector('[data-destination-id="semanas-proyecto"]').closest('li');
  const panel = li.querySelector('.shell-week-flyout');
  return {
    isButton: document.querySelector('[data-destination-id="semanas-proyecto"]').tagName === 'BUTTON',
    hasPanel: !!panel,
    weeks: panel ? panel.querySelectorAll('[data-shell-week]').length : 0,
    createBtn: !!document.getElementById('shellWeekCreateOpen'),
    trash: panel ? panel.querySelectorAll('[data-shell-delete-week]').length : 0,
  };
});
check('ítem Semanas es botón con flyout', s.isButton && s.hasPanel, JSON.stringify(s));
check('flyout lista semanas y acciones', s.weeks > 0 && s.createBtn && s.trash === 1, `weeks=${s.weeks} trash=${s.trash}`);

// 2) Diálogo crear: fecha sugerida + preview viva + flujo interceptado
await page.evaluate(() => document.getElementById('shellWeekCreateOpen').click());
await page.waitForTimeout(200);
const create = await page.evaluate(() => ({
  open: document.getElementById('shellWeekCreateDialog').open,
  fecha: document.getElementById('shellWeekCreateDate').value,
  preview: document.getElementById('shellWeekCreatePreview').textContent,
}));
const fechaSugeridaVacia = !shellData.fechaSugerida;
check('diálogo crear abre con fecha sugerida',
  create.open && (fechaSugeridaVacia
    ? /^\d{4}-\d{2}-\d{2}$/.test(create.fecha)
    : create.fecha === shellData.fechaSugerida),
  fechaSugeridaVacia
    ? `fechaSugerida vacía en backend, fallback a regex de formato — ${JSON.stringify(create)}`
    : JSON.stringify({ ...create, fechaSugerida: shellData.fechaSugerida }));

const finEsperado = formatEnd(create.fecha);
check('preview viva calcula el fin (+6 días)',
  finEsperado !== '' && create.preview.includes(create.fecha) && create.preview.includes(finEsperado),
  `preview="${create.preview}" inicio=${create.fecha} finEsperado=${finEsperado}`);

await page.evaluate(() => document.getElementById('shellWeekCreateSubmit').click());
await page.waitForTimeout(600);
const afterCreate = await page.evaluate(() => window.__weekCalls);
check('crear: pre-check CIC + POST + redirect a PG semana nueva',
  calls.cic.length === 1 && calls.crear.length === 1
    && calls.crear[0].body.includes('opcion=nueva_sem')
    && /(?:^|&)_csrf_token=[a-f0-9]{64}(?:&|$)/.test(calls.crear[0].body)
    && afterCreate.some((c) => c.week === 5 && c.path === '/programa-general'),
  JSON.stringify({ cic: calls.cic.length, crear: calls.crear.length, redirects: afterCreate }));

// 3) Diálogo eliminar: flujo interceptado
await page.evaluate(() => document.querySelector('[data-shell-delete-week]').click());
await page.waitForTimeout(200);
const delOpen = await page.evaluate(() => document.getElementById('shellWeekDeleteDialog').open);
check('diálogo eliminar abre desde el trash', delOpen, String(delOpen));
await page.evaluate(() => document.getElementById('shellWeekDeleteSubmit').click());
await page.waitForTimeout(600);
const afterDelete = await page.evaluate(() => window.__weekCalls);
check('eliminar: POST correcto + redirect a semana-1',
  calls.eliminar.length === 1
    && calls.eliminar[0].body.includes('opcion=eliminar_sem')
    && /(?:^|&)_csrf_token=[a-f0-9]{64}(?:&|$)/.test(calls.eliminar[0].body)
    && afterDelete.some((c) => c.week === maxSemana - 1),
  JSON.stringify({ eliminar: calls.eliminar.length, redirects: afterDelete }));

// 4) Diálogo eliminar: branch ERROR del backend muestra el mensaje real (no "undefined")
await page.evaluate(() => {
  window.AIA = window.AIA || {};
  window.AIA.Notice = {
    error: (m) => { window.__lastError = m; return Promise.resolve(); },
    warning: (m) => { window.__lastWarn = m; return Promise.resolve(); },
  };
});
await page.unroute('**/eliminar_semana.php*');
await page.route('**/eliminar_semana.php*', async (route) => {
  calls.eliminar.push({ url: route.request().url(), body: route.request().postData() });
  await route.fulfill({ contentType: 'application/json', body: '{"respuesta":"ERROR","mensaje":"Fallo simulado"}' });
});
await page.evaluate(() => document.querySelector('[data-shell-delete-week]').click());
await page.waitForTimeout(200);
await page.evaluate(() => document.getElementById('shellWeekDeleteSubmit').click());
await page.waitForTimeout(400);
const lastError = await page.evaluate(() => window.__lastError);
check('eliminar: error del backend se muestra (no "Semana undefined")', lastError === 'Fallo simulado', `lastError=${JSON.stringify(lastError)}`);

// 5) Afordancia del botón "+ Nueva semana": borde visible (no solo texto de color)
const createAfford = await page.evaluate(() => {
  const cs = getComputedStyle(document.getElementById('shellWeekCreateOpen'));
  const invisible = (v) => v === 'transparent' || v.startsWith('rgba(0, 0, 0, 0)');
  return { border: cs.borderTopWidth, borderColor: cs.borderTopColor, bg: cs.backgroundColor, visible: cs.borderTopWidth !== '0px' && !invisible(cs.borderTopColor) && !invisible(cs.backgroundColor) };
});
check('botón crear tiene afordancia de botón (borde y fondo visibles)', createAfford.visible, JSON.stringify(createAfford));

// 6) Estado EXPANDIDO: presupuesto cero-scroll + flyout visible (no recortado).
//    Regresión de "los dropdowns no se despliegan en expandido" (el nav canónico
//    trae overflow-y:auto que recortaba el .shell-week-flyout) y "sin scroll".
await page.evaluate(() => {
  document.querySelectorAll('dialog[open]').forEach((d) => d.close());
  const shell = document.querySelector('[data-shell-pattern="sidebar"]');
  if (shell.dataset.sidebarState === 'collapsed') shell.querySelector('[data-sidebar-toggle]').click();
});
await page.waitForTimeout(450);
const expandedLayout = await page.evaluate(() => {
  const shell = document.querySelector('[data-shell-pattern="sidebar"]');
  const nav = shell.querySelector('.aia-sidebar__nav');
  const bn = shell.querySelector('.aia-sidebar__brand-name');
  return {
    state: shell.dataset.sidebarState,
    navScrolls: nav.scrollHeight > nav.clientHeight + 1,
    contextPresent: !!shell.querySelector('.aia-sidebar__context'),
    brandTruncated: bn ? bn.scrollWidth > bn.clientWidth + 1 : null,
    footerInView: shell.querySelector('.aia-sidebar__footer').getBoundingClientRect().bottom <= window.innerHeight + 1,
  };
});
check('expandido: el nav no hace scroll (presupuesto cero-scroll) y footer en viewport',
  expandedLayout.state === 'expanded' && expandedLayout.navScrolls === false && expandedLayout.footerInView,
  JSON.stringify(expandedLayout));
check('expandido: sin bloque de contexto duplicado (vive en la context-bar)',
  expandedLayout.contextPresent === false, `contextPresent=${expandedLayout.contextPresent}`);
check('expandido: marca "Last Planner AIA" sin truncar',
  expandedLayout.brandTruncated === false, `brandTruncated=${expandedLayout.brandTruncated}`);

await page.evaluate(() => document.querySelector('[data-destination-id="semanas-proyecto"]').closest('li').classList.add('shell-week-open'));
await page.waitForTimeout(320);
const flyoutVisible = await page.evaluate(() => {
  const panel = document.querySelector('[data-destination-id="semanas-proyecto"]').closest('li').querySelector('.shell-week-flyout');
  const cs = getComputedStyle(panel);
  const r = panel.getBoundingClientRect();
  const hit = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
  return { opacity: cs.opacity, visibility: cs.visibility, painted: !!hit && (panel === hit || panel.contains(hit)) };
});
check('expandido: flyout de semanas visible y pintado (no recortado por overflow)',
  flyoutVisible.visibility === 'visible' && parseFloat(flyoutVisible.opacity) > 0.9 && flyoutVisible.painted === true,
  JSON.stringify(flyoutVisible));

console.log(
  `\nauditoría de red — mutaciones colaterales interceptadas (PS bootstrap): `
  + `semanal/save=${calls.semanalSave.length} semanal/auto-program=${calls.semanalAutoProgram.length} `
  + `(fulfill-eadas por page.route, cero llegó al backend real)`,
);

await browser.close();
const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} checks OK`);
process.exit(failed.length ? 1 : 0);
