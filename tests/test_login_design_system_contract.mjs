import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const views = [
  read('views/auth/login.view.php'),
  read('views/auth/password-forgot.view.php'),
  read('views/auth/password-reset.view.php'),
];
const login = views[0];
const reset = views[2];
const css = read('public/css/login-brand-unified.css');
const authFormsUrl = new URL('../public/js/modules/aia_ui/auth_forms.js', import.meta.url);
assert.ok(existsSync(authFormsUrl), 'auth_forms.js must centralize auth interactions');
const authForms = readFileSync(authFormsUrl, 'utf8');
const exceptions = JSON.parse(read('docs/design-system/exceptions.json'));

for (const view of views) {
  // F0/Task 8 retiro el conmutador de tema: el parcial se borro y las vistas
  // ya no lo incluyen.
  assert.doesNotMatch(view, /partials\/auth-theme-switch\.php/);
  assert.match(view, /aia_ui\/theme\.js\?v=<\?= filemtime/);
  assert.match(view, /aia_ui\/auth_forms\.js\?v=<\?= filemtime/);
  assert.match(view, /login-brand-unified\.css\?v=<\?= filemtime/);
  assert.match(view, /data-auth-form/);
  assert.match(view, /data-loading-text=/);
}

// Cuenta cargas reales del script, no menciones del nombre: el comentario PHP de la
// vista cita `AiaAlertInterceptor.js` al documentar por que jQuery sigue siendo local.
assert.equal(
  (login.match(/<script\b[^>]*\bsrc=["'][^"']*AiaAlertInterceptor\.js/g) || []).length,
  1,
  'login.view.php must load AiaAlertInterceptor.js exactly once',
);
assert.doesNotMatch(login, /oncontextmenu/);
assert.match(login, /data-password-toggle="password"/);
assert.match(reset, /data-password-toggle="password"/);
assert.match(reset, /data-password-toggle="confirm_password"/);
assert.match(reset, /id="password-policy"/);
assert.match(reset, /aria-describedby="password-policy"/);
assert.match(reset, /minlength="6"/);
assert.match(reset, /pattern=/);

assert.match(authForms, /aria-busy/);
assert.match(authForms, /data-password-toggle/);
assert.match(authForms, /aria-pressed/);
assert.match(authForms, /MutationObserver/);
assert.doesNotMatch(css, /\.auth-theme-switch/);
assert.match(css, /\.auth-field-label/);
assert.match(css, /\.auth-link/);
assert.match(css, /\.auth-link\s*\{[^}]*color:\s*var\(--ds-active-text-primary\)\s*!important/);
assert.match(css, /\.login-title\s*\{[^}]*color:\s*var\(--ds-active-text-primary\)/, 'Login title must use theme-aware foreground contrast');
assert.match(css, /min-height:\s*var\(--ds-target-min\)/);
assert.match(css, /\.form-control:focus[\s\S]*background:\s*var\(--ds-active-surface-raised\)/);
assert.match(
  css,
  /\[data-aia-theme=['"]dark['"]\][\s\S]*\.alert-danger[\s\S]*background:\s*color-mix\(in srgb,\s*var\(--ds-color-state-critical-text\)[\s\S]*var\(--ds-active-surface-raised\)/,
  'Dark auth errors must use a dark theme-aware critical surface',
);
assert.match(css, /\.aia-password-modal\.aia-glass-popup[\s\S]*max-width:\s*calc\(100vw - 2rem\)/);
assert.doesNotMatch(css, /@layer tokens/);
assert.doesNotMatch(css, /@layer\b/, 'Login CSS must remain unlayered so it can override unlayered AdminLTE defaults');
assert.doesNotMatch(css, /(?:oklch|oklab|rgba?|hsla?|lab|lch|color\()\(/);
assert.doesNotMatch(css, /#[0-9a-f]{3,8}\b/i);

const budget = exceptions.pathBudgets.find((item) => item.name === 'login');
assert.ok(budget);
assert.ok(budget.paths.includes('views/auth/password-forgot.view.php'));
assert.ok(budget.paths.includes('views/auth/password-reset.view.php'));
assert.ok(budget.paths.includes('public/js/modules/aia_ui/auth_forms.js'));
assert.equal(budget.maxViolations['hardcoded-color-function'], 0);

// --- Login React (Tarea 11, S01): presentación responsive del acceso ------------
// El login legacy de arriba (login.view.php, S02/S03) sigue vigente y sin migrar;
// este bloque cubre exclusivamente la hoja/manifiesto del login React nuevo.

const frontendHtml = read('frontend/index.html');
const authCss = read('public/css/auth-react.css');
const manifest = JSON.parse(read('docs/design-system/manifests/auth.json'));
const marcoAcceso = read('frontend/src/shell/auth/MarcoAcceso.tsx');
const cambioClave = read('frontend/src/shell/auth/CambioClaveObligatorio.tsx');

assert.match(frontendHtml, /\/css\/auth-react\.css/, 'frontend/index.html must load auth-react.css');
// La hoja debe cargarse DESPUES del tema claro (mismo orden que documenta el brief:
// tokens -> aia-design-system -> theme-claro -> auth-react), para que module gane
// sobre las capas anteriores por orden de aparicion, no solo por @layer.
assert.ok(
  frontendHtml.indexOf('theme-claro.css') < frontendHtml.indexOf('auth-react.css'),
  'auth-react.css must be linked after theme-claro.css',
);

assert.match(authCss, /^@layer module\s*\{/m, 'auth-react.css must declare a single @layer module block');
assert.equal((authCss.match(/@layer\s+\w+\s*\{/g) || []).length, 1, 'auth-react.css must declare exactly one top-level @layer block');
assert.doesNotMatch(authCss, /#[0-9a-f]{3,8}\b|rgba?\(|oklch\(|oklab\(|hsla?\(/i, 'auth-react.css must not hardcode colors');
assert.doesNotMatch(authCss, /!important/, 'auth-react.css must not use !important');
assert.match(authCss, /var\(--ds-/, 'auth-react.css must consume --ds-* tokens');
assert.match(authCss, /\.aia-auth\b/);
assert.match(authCss, /\.aia-auth__layout\b/);
assert.match(authCss, /\.aia-auth__dialog\b/);
assert.match(authCss, /min-block-size:\s*var\(--ds-target-min\)/);
assert.match(authCss, /@media \(min-width: 73\.75rem\)/, 'desktop (1180px, canonical viewport) two-panel breakpoint');
assert.match(authCss, /@media \(min-width: 90rem\)/, 'wide (1440px) two-panel breakpoint');
assert.match(authCss, /@media \(max-width: 24\.375rem\)/, 'mobile (390px) dialog-as-panel breakpoint');
assert.match(authCss, /@media \(prefers-reduced-motion: reduce\)/);

// La disposición de dos paneles se ancla en `.aia-auth`, montado por MarcoAcceso
// junto a `.aia-shell` — nunca en su lugar, para no perder la primitiva canónica.
assert.match(marcoAcceso, /className="aia-shell aia-auth"/);
assert.match(marcoAcceso, /aia-auth__layout/);
assert.match(cambioClave, /aia-modal-surface aia-auth__dialog/);

assert.ok(manifest.sources.includes('frontend/src/shell/auth/PantallaLogin.tsx'));
assert.ok(manifest.sources.includes('frontend/src/shell/auth/MarcoAcceso.tsx'));
assert.ok(manifest.sources.includes('public/css/auth-react.css'));
// El legacy S02/S03 sigue declarado como fuente (sigue existiendo y sirviéndose),
// pero nunca como migrado: no hay entrada React para password-forgot/password-reset.
assert.ok(manifest.sources.includes('views/auth/password-forgot.view.php'));
assert.ok(manifest.sources.includes('views/auth/password-reset.view.php'));
assert.ok(!manifest.sources.some((source) => /password-forgot|password-reset/.test(source) && source.endsWith('.tsx')));

assert.deepEqual(manifest.layouts.slice().sort(), ['desktop', 'mobile', 'tablet', 'wide']);
for (const state of ['normal', 'error', 'focus', 'busy', 'password-change', 'cancel-confirmation']) {
  assert.ok(manifest.states.includes(state), `auth.json states must include "${state}"`);
}
assert.ok(manifest.roles.includes('anonymous'));
assert.ok(manifest.roles.includes('pending-password'));
assert.doesNotMatch(manifest.persistence.theme, /^none\b/, 'auth.json must no longer claim the React login has no theme persistence');
