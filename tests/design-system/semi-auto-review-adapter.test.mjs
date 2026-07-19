import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { parseCssStructure } from '../../scripts/lib/css-structure-parser.mjs';

const adapterUrl = new URL(
  '../../public/css/design-system/adapters/semi-auto-review.css',
  import.meta.url,
);
const moduleUrl = new URL('../../public/js/modules/semi_auto_review.js', import.meta.url);

test('semi-auto adapter covers the rendered panel anatomy', async () => {
  // Given: the governed adapter extracted from the historical inline styles.
  const css = await readFile(adapterUrl, 'utf8');

  // When: the stylesheet is inspected for every visible panel region.
  const regions = [
    'sar-analysis-head',
    'sar-analysis-bar',
    'sar-analysis-step',
    'sar-assistant-head',
    'sar-assistant-card',
    'sar-summary-pill',
    'sar-group-head',
    'sar-group-count',
    'sar-card-title-row',
    'sar-change-summary',
    'sar-tech-grid',
    'sar-tech-card',
  ];

  // Then: every region has an explicit adapter contract.
  for (const region of regions) {
    assert.match(css, new RegExp(`\\.${region}\\b`), `missing .${region}`);
  }
});

test('semi-auto adapter preserves interactive state presentation', async () => {
  // Given: the state classes toggled by semi_auto_review.js.
  const css = await readFile(adapterUrl, 'utf8');

  // When/Then: collapsed, expanded, semantic, and selection states are styled.
  for (const contract of [
    /\.sar-assistant:not\(\.is-open\) \.sar-assistant-copy/,
    /\.sar-assistant:not\(\.is-open\) \.sar-assistant-feedback-actions/,
    /\.sar-card\.is-open \.sar-detail/,
    /\.sar-analysis-step\.is-running/,
    /\.sar-analysis-step\.is-done/,
    /\.sar-analysis-step\.is-error/,
    /\.sar-modality-option\.is-selected/,
    /\.sar-modality-option\.is-disabled/,
    /\.sar-row-check:checked/,
    /\.sar-row-check:focus-visible/,
  ]) {
    assert.match(css, contract);
  }
});

test('semi-auto progress animates with a compositor transform instead of layout width', async () => {
  const [css, script] = await Promise.all([
    readFile(adapterUrl, 'utf8'),
    readFile(moduleUrl, 'utf8'),
  ]);
  const progressRule = parseCssStructure(css).find(({ selector }) => (
    selector === '.sar-analysis-bar span'
  ));
  const declarations = new Map(progressRule?.declarations.map(({ property, value }) => (
    [property, value]
  )));

  assert.equal(declarations.get('width'), '100%');
  assert.match(declarations.get('transform') || '', /scaleX\(var\(--sar-analysis-progress,\s*0\)\)/);
  assert.match(declarations.get('transition') || '', /^transform\b/);
  assert.ok(declarations.has('transform-origin'));
  assert.doesNotMatch(script, /sar-analysis-bar"><span style="width:/);
  assert.match(script, /data-sar-analysis-progress/);
  assert.match(script, /style\.setProperty\('--sar-analysis-progress'/);
});

test('semi-auto interactive controls keep the canonical 44px target and focus ring', async () => {
  // Given: the adapter's shared interactive-control rule.
  const css = await readFile(adapterUrl, 'utf8');
  const interactiveRule = parseCssStructure(css).find(({ selector, declarations }) => (
    selector.includes('.semi-auto-review')
      && selector.includes('.sar-assistant-actions button')
      && declarations.some(({ property, value }) => (
        property === 'min-height' && value === 'var(--ds-target-min)'
      ))
  ));

  // When/Then: target size and focus treatment use the shared design tokens.
  assert.ok(interactiveRule, 'missing shared 44px interactive-control rule');
  assert.match(
    css,
    /:where\([^}]*\.sar-row-check:focus-visible[^}]*\)\s*\{[^}]*box-shadow:\s*var\(--ds-shadow-focus\)/s,
  );
  assert.match(
    css,
    /\.sar-row-check,[^}]*\.sar-check-all,[^}]*\.sar-modality-check\s*\{[^}]*width:\s*var\(--ds-target-min\)[^}]*height:\s*var\(--ds-target-min\)/s,
  );
  assert.match(
    css,
    /\.sar-row-check::after,[^}]*\.sar-check-all::after,[^}]*\.sar-modality-check::after\s*\{[^}]*width:\s*calc\(var\(--ds-space-4\) \+ var\(--ds-space-1\)\)/s,
  );
  assert.match(
    css,
    /\.sar-card-check\s*\{[^}]*min-width:\s*var\(--ds-target-min\)[^}]*min-height:\s*var\(--ds-target-min\)/s,
  );
  assert.match(
    css,
    /\.sar-modality-option\s*\{[^}]*min-height:\s*var\(--ds-target-min\)/s,
  );
});

test('semi-auto adapter uses governed design-system values', async () => {
  // Given: the complete adapter stylesheet.
  const css = await readFile(adapterUrl, 'utf8');

  // When/Then: visual values do not fall back to legacy aliases or raw colors.
  assert.doesNotMatch(css, /var\(--(?:space|spacing|radius|surface|text)-/);
  assert.doesNotMatch(css, /#[\da-f]{3,8}\b|\brgba?\(/i);
  assert.doesNotMatch(css, /999px/);
});
