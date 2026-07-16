import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import test from 'node:test';

const readJson = async (file) => JSON.parse(await readFile(
  new URL(`../../docs/design-system/${file}`, import.meta.url), 'utf8',
));

const withoutCssComments = (css) => css.replace(
  /\/\*[\s\S]*?\*\//g,
  (comment) => comment.replace(/[^\n]/g, ' '),
);

const scanSpacingTokens = ({ stylesheets, tokens }) => {
  const canonicalTokens = withoutCssComments(tokens);
  const declared = new Set([...canonicalTokens.matchAll(/(--ds-space-[\w-]+)\s*:/g)].map(([, token]) => token));
  return stylesheets.flatMap(({ file, css }) => (
    [...withoutCssComments(css).matchAll(/var\(\s*(--ds-space-[\w-]+)/g)]
      .filter(([, token]) => !declared.has(token))
      .map((match) => ({
        file,
        line: css.slice(0, match.index).split('\n').length,
        token: match[1],
      }))
  ));
};

test('always-visible filters are approved and canonical', async () => {
  const homologation = await readJson('homologation.json');
  const approvals = await readJson('family-approvals.json');
  const catalog = await readJson('component-catalog.json');
  const family = homologation.families.find(({ id }) => id === 'forms-filters');
  const approved = family.candidates.filter(({ status }) => status === 'approved');
  const approval = approvals.approvals.find(({ familyId }) => familyId === 'forms-filters');
  const field = catalog.components.find(({ id }) => id === 'field');
  const filter = catalog.components.find(({ id }) => id === 'filter');

  assert.deepEqual(approved.map(({ id }) => id), ['inline-fields']);
  assert.equal(approval.candidateId, 'inline-fields');
  assert.equal(field.maturity, 'stable');
  assert.equal(field.visualApproval.status, 'approved');
  assert.equal(filter.kind, 'canonical');
  assert.equal(filter.maturity, 'candidate');
  assert.equal(filter.visualApproval.status, 'approved');
  assert.ok(filter.api.includes('DesignSystemComponent::filterForm'));
});

test('form controls reserve internal padding and include a Select2 multi-select reference', async () => {
  const css = await readFile('public/css/design-system/components/filter-form.css', 'utf8');
  const view = await readFile('views/design-system/families/forms-filters.php', 'utf8');
  assert.match(css, /\.aia-input,\s*\.aia-select,\s*\.aia-textarea\s*\{[\s\S]*box-sizing:\s*border-box[\s\S]*padding-block:\s*var\(--ds-space-3\)[\s\S]*padding-inline:\s*var\(--ds-space-4\)/);
  assert.match(css, /\.aia-input\[type='file'\]\s*\{[\s\S]*display:\s*flex[\s\S]*align-items:\s*center/);
  assert.match(css, /\.aia-input\[type='file'\]::file-selector-button\s*\{[\s\S]*padding:/);
  assert.match(css, /@layer components\s*\{[\s\S]*\.aia-input,\s*\.aia-select,\s*\.aia-textarea\s*\{[\s\S]*padding-block:\s*var\(--ds-space-3\)[\s\S]*padding-inline:\s*var\(--ds-space-4\)/);
  assert.match(view, /select2-container--multiple/);
  assert.match(view, /data-select2-multi/);
});

test('public styles reference only canonical declared spacing tokens', async () => {
  const [cssFiles, tokens] = await Promise.all([
    readdir(new URL('../../public/css/', import.meta.url), { recursive: true }),
    readFile('public/css/tokens.css', 'utf8'),
  ]);
  const stylesheets = await Promise.all(cssFiles
    .filter((file) => file.endsWith('.css'))
    .sort()
    .map(async (file) => ({
      file,
      css: await readFile(new URL(`../../public/css/${file}`, import.meta.url), 'utf8'),
    })));
  const css = stylesheets.find(({ file }) => file === 'design-system/components/filter-form.css')?.css ?? '';
  const undefinedReferences = scanSpacingTokens({ stylesheets, tokens });

  assert.deepEqual(undefinedReferences, []);
  assert.match(css, /\.aia-field:has\(> \.aia-input\[type='file'\]\)\s*{[^}]*align-content:\s*center/s);
  assert.match(css, /\.aia-switch input\s*{[^}]*height:\s*var\(--ds-space-6\)/s);
});

test('spacing token scan parses whitespace and ignores commented declarations and references', () => {
  const undefinedReferences = scanSpacingTokens({
    tokens: ':root { --ds-space-1: 0.25rem; /* --ds-space-comment-only: 5rem; */ }',
    stylesheets: [{
      file: 'adversarial.css',
      css: '.probe { gap: var(\n  --ds-space-comment-only\n); }\n/* padding: var(--ds-space-comment-reference); */',
    }],
  });

  assert.deepEqual(undefinedReferences, [{
    file: 'adversarial.css',
    line: 1,
    token: '--ds-space-comment-only',
  }]);
});
