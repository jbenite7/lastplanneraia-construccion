import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const drawer = read('public/js/modules/aia_ui/nav_drawer.js');
// El navbar superior legacy (public/css/navbar.css + NavbarComponent.php) se
// borró en 42ba76c y su última inyección viva se retiró al migrar /contratos,
// /listado-actividades y /pdc al shell sidebar. La context-bar es ahora
// responsabilidad del adaptador del shell.
const shellCss = read('public/css/design-system/adapters/shell-sidebar.css');
const lpsCss = read('public/css/handsontable-module.css');
const lpsDrawer = read('public/js/modules/lps_drawer.js');
const lpsView = read('views/partials/drawer_unificado.php');
const weeklyView = read('views/programacion-semanal/programacion_semanal.view.php');
const exceptions = JSON.parse(read('docs/design-system/exceptions.json'));
const theme = read('public/js/modules/aia_ui/theme.js');
const commonLoader = read('public/js/linksComunesHead2.js');
const loginView = read('views/auth/login.view.php');
const projectSelectorView = read('views/core/project_selector.view.php');
const loader = read('public/js/cargarDatosGeneralesPagina2.js');

// Tolerantes al estilo de comillas: biome formatea el JS con comillas dobles.
assert.match(drawer, /key === ["']Escape["']/);
assert.match(drawer, /aria-expanded/);
assert.match(drawer, /focus\(\)/);
assert.match(drawer, /key === ["']Tab["']/);
assert.match(drawer, /setTimeout\((?:function \(\) \{ ui\.close\.focus\(\); \}|\(\) => \{\s*ui\.close\.focus\(\);\s*\}), 450\)/);
assert.match(drawer, /function isVisibleFocusable\(element\)/);
assert.match(drawer, /getClientRects\(\)\.length/);
assert.match(drawer, /aria-hidden/);
assert.match(drawer, /ui\.drawer\.setAttribute\(["']aria-hidden["'], ["']true["']\)/);
assert.match(drawer, /ui\.drawer\.setAttribute\(["']aria-hidden["'], ["']false["']\)/);
assert.match(drawer, /ui\.drawer\.removeAttribute\(["']aria-modal["']\)/);
assert.match(drawer, /ui\.drawer\.removeAttribute\(["']role["']\)/);
assert.match(drawer, /function syncResponsiveSemantics\(isDesktop\)/);
assert.match(drawer, /function boot\(\)/);
assert.match(drawer, /DOMContentLoaded/);
// La context-bar ya no se ancla bajo un navbar superior (--navbar-height): con
// el shell sidebar es sticky en el borde superior del viewport.
assert.match(shellCss, /body\.aia-shell--sidebar \.context-bar\s*\{[^}]*position:\s*sticky[^}]*top:\s*0/s);
// El loader no debe volver a montar navegación propia: la única navegación es
// el shell sidebar. Estos guards evitan que reaparezca el navbar huérfano.
assert.doesNotMatch(loader, /navbar-aia/);
assert.doesNotMatch(loader, /shell-nav-spacer/);
assert.doesNotMatch(loader, /<div class="context-bar"/);
assert.doesNotMatch(loader, /cssLink\.href/);
assert.match(lpsCss, /\.lps-drawer\s*\{[^}]*background:\s*var\(--ds-active-bg-page\)/s);
assert.match(lpsCss, /\.lps-drawer-header\s*\{[^}]*background:\s*var\(--ds-active-surface-raised\)/s);
assert.match(lpsCss, /\.lps-card-glass\s*\{[^}]*background:\s*var\(--ds-active-surface-raised\)/s);
assert.match(lpsDrawer, /event\.key === 'Escape'/);
assert.match(lpsDrawer, /aria-hidden/);
assert.match(lpsDrawer, /sidebarTrigger\.focus\(\)/);
assert.match(lpsDrawer, /event\.key === 'Tab'/);
assert.match(lpsDrawer, /lpsFocusableSelector/);
assert.match(lpsDrawer, /event\.shiftKey/);
assert.match(lpsView, /<button[^>]*id="lps_sidebar_trigger"[^>]*aria-controls="lps_drawer"[^>]*aria-expanded="false"/s);
assert.match(lpsView, /id="lps_drawer"[^>]*aria-hidden="true"/s);
// El CSS del shell llega una sola vez vía aia-design-system.css (layer vendor);
// un <link> crudo duplicaría la cascada y reabriría el conflicto de !important.
assert.doesNotMatch(weeklyView, /<link[^>]*handsontable-module\.css/);
assert.match(weeklyView, /lps_drawer\.js\?v=20260722shell1/);
const shellBudget = exceptions.pathBudgets.find((budget) => budget.name === 'foundation-shell');
assert.ok(shellBudget, 'Foundation/Shell must have an explicit debt budget');
assert.ok(shellBudget.paths.includes('views/partials/drawer_unificado.php'));
// F0/Task 8 retiro el conmutador de tema: theme.js ya no expone la API de
// alternancia (setTheme/toggleTheme/bindThemeSwitches) ni ata el boot al
// DOMContentLoaded — aplica dark de forma sincrona. Los guards evitan que la
// API retirada reaparezca (mismo patron que los guards del navbar arriba).
// F0/Task 9 retiro tambien aia-theme-ready y aia-theme-change: con un solo
// tema no hay cambio que anunciar, y sus unicos oyentes (bi_chart_theme.js,
// bi-spa.js) cargaban despues de theme.js en views/bi/_layout.php y nunca
// podian recibirlos; aia-theme-ready no tenia ningun oyente en el repo.
assert.doesNotMatch(theme, /bindThemeSwitches/);
assert.doesNotMatch(theme, /\bsetTheme\b/);
assert.doesNotMatch(theme, /\btoggleTheme\b/);
assert.doesNotMatch(theme, /aia-theme-ready/);
assert.doesNotMatch(theme, /aia-theme-change/);
assert.doesNotMatch(loader, /function currentTheme\(/);
assert.doesNotMatch(loader, /bindThemeSwitches/);
assert.doesNotMatch(loader, /aia-theme-ready/);
assert.match(commonLoader, /theme\.js\?v=20260711foundation5/);
assert.match(commonLoader, /nav_drawer\.js\?v=20260711foundation5/);
// tokens.css llega vía el entrypoint runtime de aia-design-system.css, no por el loader.
assert.doesNotMatch(commonLoader, /tokens\.css/);
assert.match(loginView, /theme\.js\?v=<\?= filemtime\(/);
assert.match(projectSelectorView, /theme\.js\?v=<\?= filemtime\(/);
