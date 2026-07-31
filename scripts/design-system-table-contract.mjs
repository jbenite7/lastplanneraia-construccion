#!/usr/bin/env node
/**
 * Static & contract gate for Last Planner AIA Table Design System
 * Validates existence, structure, and integrity of --ds-table-* tokens,
 * --ds-cell-state-* semantic scale, grid shell definitions, and shared vocabulary.
 */

import { readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';
import process from 'node:process';

const root = process.cwd();
let errors = [];

function checkFileExists(path, description) {
  const fullPath = join(root, path);
  if (!existsSync(fullPath)) {
    errors.push(`[MISSING FILE] ${description}: ${path}`);
    return null;
  }
  return readFileSync(fullPath, 'utf8');
}

console.log('🔍 Checking Design System Table Contract...\n');

// 1. Check tokens.css for --ds-table-* tokens
const tokensContent = checkFileExists('public/css/tokens.css', 'Tokens CSS');
if (tokensContent) {
  const requiredTableTokens = [
    '--ds-table-row-h',
    '--ds-table-cell-pad-x',
    '--ds-table-cell-pad-y',
    '--ds-table-header-bg',
    '--ds-table-header-fg',
    '--ds-table-border',
    '--ds-table-zebra',
    '--ds-table-row-hover',
    '--ds-table-row-selected',
    '--ds-table-cell-focus',
    '--ds-table-empty-bg',
    '--ds-table-empty-fg'
  ];

  for (const token of requiredTableTokens) {
    if (!tokensContent.includes(token)) {
      errors.push(`[TOKEN MISSING] ${token} not defined in public/css/tokens.css`);
    }
  }

  // 2. Check tokens.css for --ds-cell-state-* semantic scale (7 steps, bg + fg each)
  const scaleSteps = ['neutral', 'ok', 'atencion', 'riesgo', 'critico', 'bloqueado', 'sin-datos'];
  for (const step of scaleSteps) {
    const bgToken = `--ds-cell-state-${step}-bg`;
    const fgToken = `--ds-cell-state-${step}-fg`;
    if (!tokensContent.includes(bgToken)) {
      errors.push(`[SEMANTIC SCALE MISSING] ${bgToken} not defined in public/css/tokens.css`);
    }
    if (!tokensContent.includes(fgToken)) {
      errors.push(`[SEMANTIC SCALE MISSING] ${fgToken} not defined in public/css/tokens.css`);
    }
  }
}

// 3. Check core.css for .aia-grid-shell variables
const coreCssContent = checkFileExists('public/css/design-system/core.css', 'Core CSS');
if (coreCssContent) {
  const requiredVars = ['--_row-h:', '--_cell-px:', '--_cell-py:', '--_header-bg:', '--_header-fg:', '--_border:'];
  if (!coreCssContent.includes('.aia-grid-shell')) {
    errors.push(`[SHELL MISSING] .aia-grid-shell rule missing in public/css/design-system/core.css`);
  } else {
    for (const v of requiredVars) {
      if (!coreCssContent.includes(v)) {
        errors.push(`[SHELL VAR MISSING] ${v} not mapped in .aia-grid-shell in core.css`);
      }
    }
  }
}

// 4. Check shared JS vocabulary module
const vocabContent = checkFileExists('public/js/modules/shared/cell-state-vocabulary.mjs', 'Cell State Vocabulary Module');
if (vocabContent) {
  if (!vocabContent.includes('CELL_STATE')) {
    errors.push(`[VOCABULARY ERROR] CELL_STATE export missing in cell-state-vocabulary.mjs`);
  }
  if (!vocabContent.includes('STATE_MAP')) {
    errors.push(`[VOCABULARY ERROR] STATE_MAP export missing in cell-state-vocabulary.mjs`);
  }
}

// Summary
if (errors.length > 0) {
  console.error('❌ Table Contract Gate Failed with ' + errors.length + ' error(s):\n');
  for (const err of errors) {
    console.error('  - ' + err);
  }
  process.exit(1);
} else {
  console.log('✅ Table Contract Gate PASSED: Tokens, 7-step semantic scale, shell rules & JS vocabulary are valid.');
  process.exit(0);
}
