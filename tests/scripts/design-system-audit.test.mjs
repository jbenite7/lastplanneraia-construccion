import assert from 'node:assert/strict';
import { mkdtempSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';

const auditScript = resolve('scripts/design-system-audit.mjs');

function writeFixture(root, path, content) {
  const target = join(root, path);
  mkdirSync(dirname(target), { recursive: true });
  writeFileSync(target, content);
}

function runAudit(root, args = []) {
  return spawnSync(process.execPath, [auditScript, ...args], {
    cwd: root,
    encoding: 'utf8',
  });
}

function parseReport(stdout) {
  const jsonEnd = stdout.lastIndexOf('\n}') + 2;
  return JSON.parse(stdout.slice(0, jsonEnd));
}

test('detecta las nueve funciones de color CSS literales en rutas con presupuesto', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', JSON.stringify({
      totals: { 'hardcoded-color-function': 9 },
    }));
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({
      pathBudgets: [{
        name: 'fixture',
        paths: ['views/example.php'],
        maxViolations: { 'hardcoded-color-function': 0 },
      }],
    }));
    writeFixture(root, 'views/example.php', `
      rgb(1 2 3); rgba(1, 2, 3, .4); hsl(1 2% 3%); hsla(1, 2%, 3%, .4);
      oklch(50% .2 120); oklab(.5 .1 .1); lab(50% 1 2); lch(50% 20 120);
      color(display-p3 1 0 0);
    `);

    const result = runAudit(root);
    assert.equal(result.status, 1);
    const report = parseReport(result.stdout);
    assert.equal(report.summary['hardcoded-color-function'].total, 9);
    assert.equal(report.pathBudgets[0].actualViolations['hardcoded-color-function'], 9);
    assert.match(result.stderr, /hardcoded-color-function: 9 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('permite funciones de color en los archivos canonicos de tokens', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'public/css/tokens.css', '@layer theme { :root { --brand: oklch(50% .2 120); } }');
    writeFixture(
      root,
      'public/css/aia-design-system.css',
      '@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;\n@layer components { .overlay { background: rgba(0, 0, 0, .4); } }'
    );

    const result = runAudit(root);
    const report = parseReport(result.stdout);

    assert.equal(result.status, 0);
    assert.equal(report.summary['hardcoded-color-function'], undefined);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('rechaza regenerar el baseline desde la CLI', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    const baseline = '{"version":1,"totals":{}}\n';
    writeFixture(root, 'docs/design-system/audit-baseline.json', baseline);
    writeFixture(root, 'public/css/example.css', '.x { color: #fff; }');

    const result = runAudit(root, ['--update-baseline']);

    assert.equal(result.status, 1);
    assert.match(result.stderr, /baseline updates require an approved file/);
    assert.equal(readFileSync(join(root, 'docs/design-system/audit-baseline.json'), 'utf8'), baseline);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('rechaza excepciones sin responsable', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ exceptions: [{
      module: 'fixture', rule: 'hardcoded-hex', file: 'public/css/example.css',
      selector: '.x', reason: 'legacy', expiresAtVersion: '1.0.0',
    }] }));

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /exception 0: missing owner/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('rechaza excepciones vencidas en la version actual', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'docs/design-system/version.json', '{"version":"1.0.0"}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ exceptions: [{
      module: 'fixture', rule: 'hardcoded-hex', file: 'public/css/example.css',
      selector: '.x', owner: 'AIA', reason: 'legacy', expiresAtVersion: '1.0.0',
    }] }));

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /exception 0: expired at 1.0.0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('rechaza un baseline sin archivo de aprobacion', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"version":1,"totals":{}}\n');
    writeFixture(root, 'docs/design-system/version.json', '{"version":"0.1.0"}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', '{"exceptions":[]}\n');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /baseline: missing matching approval/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta CSS propio fuera de una capa registrada', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"css-outside-layer":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'css-outside-layer': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '.outside { color: var(--ds-text); }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /css-outside-layer: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('rechaza orden de capas incompleto y nombres de capa no registrados', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', '{"exceptions":[]}\n');
    writeFixture(
      root,
      'public/css/aia-design-system.css',
      '@layer reset, vendor, theme, base, components, overrides;\n@layer overrides { .x { color: var(--ds-text); } }',
    );

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /aia-design-system\.css: layer order must be reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides/);
    assert.match(result.stderr, /unknown-css-layer: 1 > baseline 0/);
    assert.match(result.stdout, /"unknown-css-layer"/);
    assert.match(result.stdout, /public\/css\/aia-design-system\.css/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('acepta el contrato completo de capas deterministas', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', '{"exceptions":[]}\n');
    writeFixture(
      root,
      'public/css/aia-design-system.css',
      '@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;\n@layer components { .x { color: var(--ds-text); } }',
    );

    const result = runAudit(root);
    assert.equal(result.status, 0, result.stderr);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta important no autorizado mediante declaraciones parseadas', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"unauthorized-important":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'unauthorized-important': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer components { .x { color: red !important; } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /unauthorized-important: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta tokens raw o legacy en CSS de modulo', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"raw-token-in-module":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'raw-token-in-module': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .x { gap: var(--spacing-md); } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /raw-token-in-module: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta selectores globales no acotados en CSS de modulo', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"global-module-selector":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'global-module-selector': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { body .x { color: var(--ds-text); } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /global-module-selector: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta overrides vendor dentro de CSS de modulo', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"local-vendor-override":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'local-vendor-override': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .handsontable td { color: var(--ds-text); } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /local-vendor-override: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta primitivas canonicas duplicadas en CSS de modulo', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"duplicate-canonical-primitive":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'duplicate-canonical-primitive': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .aia-btn { color: var(--ds-text); } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /duplicate-canonical-primitive: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta spacing literal fuera de la escala semantica', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"off-scale-spacing":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'off-scale-spacing': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .x { gap: 13px; } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /off-scale-spacing: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta tipografia literal fuera de la escala semantica', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"off-scale-typography":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'off-scale-typography': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .x { font-size: 13px; } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /off-scale-typography: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('detecta sombras literales fuera de la escala semantica', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{"off-scale-shadow":1}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ pathBudgets: [{
      name: 'fixture', paths: ['public/css/example.css'],
      maxViolations: { 'off-scale-shadow': 0 },
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .x { box-shadow: 0 1px 3px currentColor; } }');

    const result = runAudit(root);
    assert.equal(result.status, 1);
    assert.match(result.stderr, /off-scale-shadow: 1 > path budget 0/);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('permite una excepcion exacta de regla archivo y selector', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    writeFixture(root, 'docs/design-system/exceptions.json', JSON.stringify({ exceptions: [{
      module: 'fixture', rule: 'unauthorized-important', file: 'public/css/example.css',
      selector: '.x', owner: 'AIA', reason: 'vendor bridge', expiresAtVersion: '1.0.0',
    }] }));
    writeFixture(root, 'public/css/example.css', '@layer module { .x { color: red !important; } }');

    const result = runAudit(root);
    assert.equal(result.status, 0, result.stderr);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});

test('emite completo un reporte grande aun cuando el gate falla', () => {
  const root = mkdtempSync(join(tmpdir(), 'design-system-audit-'));
  try {
    writeFixture(root, 'docs/design-system/audit-baseline.json', '{"totals":{}}\n');
    for (let index = 0; index < 800; index += 1) {
      writeFixture(root, `public/css/fixtures/example-${index}.css`, (
        `@layer module { .x${index} { color: #fff; } }`
      ));
    }

    const result = runAudit(root);
    assert.equal(result.status, 1);
    const report = parseReport(result.stdout);
    assert.equal(report.summary['hardcoded-hex'].total, 800);
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
});
