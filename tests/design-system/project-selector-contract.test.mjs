import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { consumerContractFailures } from '../../scripts/design-system-consumer-contract.mjs';

const root = fileURLToPath(new URL('../..', import.meta.url));
const manifest = JSON.parse(await readFile(new URL('../../docs/design-system/manifests/project-selector.json', import.meta.url), 'utf8'));

test('Project Selector consumes the canonical design system contract', () => {
  assert.deepEqual(consumerContractFailures({ root, manifest }), []);
});

test('consumer contract rejects external vendors and local visual primitives', () => {
  const view = '<link href="https://cdn.example.test/adminlte.css"><div style="color:#fff" class="aia-card aia-input aia-btn aia-chip aia-empty aia-alert aia-shell"></div>';
  const css = '.bad { color: #fff; font-size: 14px; border-radius: 4px; box-shadow: 0 2px 4px #000; }';
  const failures = consumerContractFailures({ root, manifest, viewOverride: view, cssOverride: css });
  assert.ok(failures.some((failure) => failure.includes('external URL/CDN')));
  assert.ok(failures.some((failure) => failure.includes('raw hex color')));
  assert.ok(failures.some((failure) => failure.includes('local font size')));
  assert.ok(failures.some((failure) => failure.includes('local radius')));
});

test('consumer contract accepts renderForModule as canonical consumption', () => {
  const view = "<?= \\App\\View\\Components\\DesignSystemHeadComponent::renderForModule('project-selector') ?>"
    + '<div class="aia-shell aia-card aia-input aia-btn aia-chip aia-empty aia-alert"></div>';
  const css = '.ok { color: var(--ds-active-text-primary); }';
  const failures = consumerContractFailures({ root, manifest, viewOverride: view, cssOverride: css });
  assert.ok(!failures.some((failure) => failure.includes('canonical asset missing')));
});

// Tarea 8, S04: el selector React (`frontend/src/shell/proyectos/SelectorProyectos.tsx`) no es
// consumidor del manifiesto PHP de arriba — vive fuera de `views/`, montado por Vite — así que se
// verifica con aserciones directas de texto en vez de `consumerContractFailures`. Ver
// `.superpowers/sdd/2026-08-30-s04-selector-proyectos-react/task-8-brief.md` Step 1.
test('la hoja React del selector de proyectos vive en @layer module y solo consume tokens --ds-*', async () => {
  const reactCss = await readFile(new URL('../../public/css/project-selector-react.css', import.meta.url), 'utf8');
  const selector = await readFile(new URL('../../frontend/src/shell/proyectos/SelectorProyectos.tsx', import.meta.url), 'utf8');

  assert.match(reactCss, /@layer module/);
  assert.doesNotMatch(reactCss, /#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|!important/i);
  assert.doesNotMatch(selector, /style=\{|bootstrap|jquery|font-awesome/i);

  for (const token of ['--ds-active-bg-page', '--ds-active-surface-raised', '--ds-active-focus-ring']) {
    assert.ok(reactCss.includes(`var(${token})`), `missing ${token}`);
  }
});

test('frontend/index.html enlaza tokens/core, auth-react.css de S01 y la hoja S04 una vez, en orden', async () => {
  const html = await readFile(new URL('../../frontend/index.html', import.meta.url), 'utf8');

  const posiciones = [
    'tokens.css',
    'aia-design-system.css',
    'auth-react.css',
    'project-selector-react.css',
  ].map((hoja) => {
    const indice = html.indexOf(hoja);
    assert.ok(indice !== -1, `frontend/index.html debe enlazar ${hoja}`);
    return indice;
  });

  for (let i = 1; i < posiciones.length; i += 1) {
    assert.ok(posiciones[i] > posiciones[i - 1], 'las hojas no están en el orden esperado');
  }

  assert.equal(html.split('project-selector-react.css').length - 1, 1, 'project-selector-react.css debe enlazarse una sola vez');
  assert.doesNotMatch(html, /project-selector\.css/, 'el host SPA no debe cargar el CSS legacy del selector');
});
