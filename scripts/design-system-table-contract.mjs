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

// Convierte un valor CSS de longitud (rem o px) a px. Asume 1rem = 16px — el tamaño
// raíz por defecto del navegador, que es lo que también asume DESIGN.md §5 bis al
// documentar la escala en px. Si algún día el proyecto fija un `font-size` distinto en
// `:root` (ninguna hoja del repo lo hace hoy), este supuesto y el de DESIGN.md quedan
// desalineados a la vez — no es un riesgo nuevo de este gate.
function toPx(rawValue) {
  const match = /^(-?[\d.]+)(rem|px)$/.exec(rawValue.trim());
  if (!match) return null;
  const [, num, unit] = match;
  const n = parseFloat(num);
  return unit === 'rem' ? n * 16 : n;
}

// Extrae el valor literal (rem/px) de un token `--nombre: valor;` en tokens.css. No
// resuelve `var(...)` ni `calc(...)` — todos los tokens medidos aquí se declaran con un
// literal directo hoy; si alguno pasara a depender de otro token, este parser lo
// reportaría como [TOKEN UNPARSEABLE] en vez de fallar en silencio con un falso verde.
function extractPxValue(cssText, tokenName) {
  const re = new RegExp(`${tokenName}\\s*:\\s*([^;]+);`);
  const match = re.exec(cssText);
  if (!match) return { raw: null, px: null };
  const raw = match[1].trim();
  return { raw, px: toPx(raw) };
}

// Suelo medido de este gate (Task 19, 2026-08-03): WCAG 2.2 SC 2.5.8 (AA) exige 24×24px
// para objetivos de interacción. `--ds-table-row-h` y `--ds-table-header-h` quedan
// EXACTAMENTE en ese mínimo por decisión del dueño del producto (DESIGN.md §5 bis,
// PRODUCT.md §Accessibility & Inclusion) — no hay margen, así que este gate tampoco lo
// da: cualquier valor por debajo de 24px es una regresión de accesibilidad, no una
// preferencia visual.
const MIN_INTERACTION_PX = 24;
// Piso duro de fuente de la escala densa (DESIGN.md §5 bis): 11px, reservado a
// elementos secundarios. El dato principal (`--ds-table-cell-font-size`) no debe bajar
// de este piso tampoco — solo puede tocarlo, nunca cruzarlo.
const MIN_FONT_FLOOR_PX = 11;

function checkMinPx(cssText, tokenName, minPx, standardCitation) {
  const { raw, px } = extractPxValue(cssText, tokenName);
  if (raw === null) {
    errors.push(`[TOKEN MISSING] ${tokenName} not defined in public/css/tokens.css`);
    return;
  }
  if (px === null) {
    errors.push(
      `[TOKEN UNPARSEABLE] ${tokenName}: "${raw}" no es un literal en rem/px medible. ` +
      `Este gate solo entiende literales directos (p.ej. "1.5rem" o "24px"); si el valor ` +
      `depende de var()/calc(), decláralo como literal o actualiza este gate para resolverlo.`
    );
    return;
  }
  if (px < minPx) {
    errors.push(
      `[BELOW ACCESSIBILITY FLOOR] ${tokenName}: ${raw} = ${px}px, por debajo del mínimo ` +
      `exigido de ${minPx}px (${standardCitation}). Encontrado ${px}px, mínimo ${minPx}px.`
    );
  }
}

console.log('🔍 Checking Design System Table Contract...\n');

// 1. Check tokens.css for --ds-table-* tokens
const tokensContent = checkFileExists('public/css/tokens.css', 'Tokens CSS');
if (tokensContent) {
  const requiredTableTokens = [
    '--ds-table-row-h',
    '--ds-table-header-h',
    '--ds-table-cell-font-size',
    '--ds-table-header-font-size',
    '--ds-table-font-size-floor',
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

  // 1b. Mide el suelo, no solo lo declara (Task 19, revisión 2026-08-03): un token
  // presente pero por debajo del mínimo pasaba en verde con el gate anterior.
  checkMinPx(tokensContent, '--ds-table-row-h', MIN_INTERACTION_PX, 'WCAG 2.2 SC 2.5.8 Target Size (Minimum), AA');
  checkMinPx(tokensContent, '--ds-table-header-h', MIN_INTERACTION_PX, 'WCAG 2.2 SC 2.5.8 Target Size (Minimum), AA');
  checkMinPx(tokensContent, '--ds-table-font-size-floor', MIN_FONT_FLOOR_PX, 'DESIGN.md §5 bis, piso duro de la escala densa');
  checkMinPx(tokensContent, '--ds-table-cell-font-size', MIN_FONT_FLOOR_PX, 'DESIGN.md §5 bis, piso duro de la escala densa');

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
