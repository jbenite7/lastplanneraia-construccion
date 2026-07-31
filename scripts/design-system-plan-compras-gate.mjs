#!/usr/bin/env node
/**
 * Artifact gate for Plan de Compras v2 (pdc-app) compiled bundle.
 * Validates public/pdc-app/assets/pdc.css for design system compliance.
 */

import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';

const root = process.cwd();
const pdcCssPath = join(root, 'public/pdc-app/assets/pdc.css');

console.log('🔍 Checking Plan de Compras v2 CSS Bundle Gate...\n');

if (!existsSync(pdcCssPath)) {
  console.error('❌ Bundle missing: public/pdc-app/assets/pdc.css does not exist.');
  console.error('   Run `cd pdc-app && npm run build` to compile the app.');
  process.exit(1);
}

const content = readFileSync(pdcCssPath, 'utf8');
const errors = [];

// 1. Verify it doesn't hardcode raw overrides for canonical --ds-active-* tokens in root
const unlayeredRootOverride = /:root\s*\{[^}]*--ds-active-bg-canvas\s*:/g;
if (unlayeredRootOverride.test(content)) {
  errors.push('Bundle redefines --ds-active-bg-canvas unlayered in :root');
}

// 2. Ensure tokens.css / aia-design-system.css tokens are referenced
if (!content.includes('var(--ds-')) {
  errors.push('Bundle does not reference any --ds-* tokens');
}

if (errors.length > 0) {
  console.error('❌ Plan de Compras Gate Failed with ' + errors.length + ' error(s):\n');
  for (const err of errors) {
    console.error('  - ' + err);
  }
  process.exit(1);
} else {
  console.log('✅ Plan de Compras CSS Bundle Gate PASSED.');
  process.exit(0);
}
