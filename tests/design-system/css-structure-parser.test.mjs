import assert from 'node:assert/strict';
import test from 'node:test';

import { parseCssStructure } from '../../scripts/lib/css-structure-parser.mjs';

test('parses nested layers without treating string braces as blocks', () => {
  const rules = parseCssStructure(`
    .outside { content: "}"; color: var(--raw); }
    @layer components {
      @media (min-width: 768px) {
        .inside { padding: var(--ds-space-md) !important; }
      }
    }
  `);

  assert.deepEqual(rules.map(({ selector, layer }) => ({ selector, layer })), [
    { selector: '.outside', layer: null },
    { selector: '.inside', layer: 'components' },
  ]);
  assert.equal(rules[1].declarations[0].important, true);
});
