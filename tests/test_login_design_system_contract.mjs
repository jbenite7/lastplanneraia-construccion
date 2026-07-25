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

assert.equal((login.match(/AiaAlertInterceptor\.js/g) || []).length, 1);
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
