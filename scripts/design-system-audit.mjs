#!/usr/bin/env node
import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import process from 'node:process';

import { parseCssStructure } from './lib/css-structure-parser.mjs';

const root = process.cwd();
const configPath = 'docs/design-system/exceptions.json';
const baselinePath = 'docs/design-system/audit-baseline.json';
const updateBaseline = process.argv.includes('--update-baseline');

if (updateBaseline) {
  console.error('baseline updates require an approved file');
  process.exit(1);
}

const scanRoots = ['views', 'public/js', 'public/css', 'src/View/Components'];
const extensions = new Set(['.php', '.js', '.css', '.mjs']);
const canonicalDesignSystemFiles = new Set([
  'public/css/tokens.css',
  'public/css/aia-design-system.css',
]);
const hexPattern = /#[0-9a-fA-F]{3,8}\b/g;
const colorFunctionPattern = /\b(?:rgba?|hsla?|oklch|oklab|lab|lch|color)\(\s*(?:[-+]?(?:\d*\.)?\d|none\b|[a-z][\w-]*\s+[-+]?(?:\d*\.)?\d)[^)]*\)/gi;
const inlineStylePattern = /\sstyle\s*=\s*"([^"]*)"/gi;
const embeddedStylePattern = /<style\b[\s\S]*?<\/style>/gi;
const forbiddenFontPattern = /\bRoboto\b/gi;
const hardcodedRadiusPattern = /border-radius\s*:\s*\d+px/gi;
const rawTokenPattern = /var\(--(?:aia-|spacing-|radius-|shadow-|z-|opacity-|transition-)/i;
const vendorSelectorPattern = /\.(?:handsontable|dataTables[_-]|select2|ts-|swal2|toast\b|anychart|ui-)/;
const spacingPropertyPattern = /^(?:margin|padding|gap|row-gap|column-gap|inset|top|right|bottom|left)(?:-|$)/;
const typographyPropertyPattern = /^(?:font-size|line-height|letter-spacing)$/;
const literalDimensionPattern = /(?:^|[\s,(+-])\d*\.?\d+(?:px|rem|em)\b/i;
const layerOrder = [
  'reset', 'vendor', 'theme', 'base', 'layout', 'components', 'utilities', 'module',
  'legacy-overrides',
];
const allowedLayers = new Set(layerOrder);

function readJson(path, fallback) {
  if (!existsSync(path)) return fallback;
  return JSON.parse(readFileSync(path, 'utf8'));
}

function walk(dir) {
  if (!existsSync(dir)) return [];
  const entries = [];
  for (const name of readdirSync(dir)) {
    const full = join(dir, name);
    const stat = statSync(full);
    if (stat.isDirectory()) {
      entries.push(...walk(full));
    } else {
      const ext = name.slice(name.lastIndexOf('.'));
      if (extensions.has(ext)) entries.push(full);
    }
  }
  return entries;
}

function lineForIndex(content, index) {
  return content.slice(0, index).split('\n').length;
}

function addViolation(violations, rule, file, line, value, selector = null) {
  violations.push({
    rule,
    file,
    line,
    value: value.replace(/\s+/g, ' ').trim().slice(0, 160),
    selector,
  });
}

function countByRuleAndFile(violations) {
  const summary = {};
  for (const violation of violations) {
    summary[violation.rule] = summary[violation.rule] || { total: 0, files: {} };
    summary[violation.rule].total += 1;
    summary[violation.rule].files[violation.file] = (summary[violation.rule].files[violation.file] || 0) + 1;
  }
  return summary;
}

function isAllowedInline(value, allowedFragments) {
  return allowedFragments.some((fragment) => value.includes(fragment));
}

function normalizeRepoPath(path) {
  return path.replace(/^\/+/, '').replace(/\\/g, '/');
}

function normalizeSelector(selector) {
  return String(selector || '')
    .replace(/\s+/g, ' ')
    .replace(/\(\s+/g, '(')
    .replace(/\s+\)/g, ')')
    .replace(/\s*,\s*/g, ', ')
    .trim();
}

function fileMatchesConfiguredPath(file, configuredPath) {
  const normalizedPath = normalizeRepoPath(configuredPath);
  if (normalizedPath.endsWith('/')) return file.startsWith(normalizedPath);
  return file === normalizedPath || file.startsWith(`${normalizedPath}/`);
}

function pathExists(configuredPath) {
  const normalizedPath = normalizeRepoPath(configuredPath).replace(/\/$/, '');
  return existsSync(join(root, normalizedPath));
}

function countRule(violationsForPath, rule) {
  return violationsForPath.filter((violation) => violation.rule === rule).length;
}

function compareSemVer(left, right) {
  const a = String(left).split('.').map(Number);
  const b = String(right).split('.').map(Number);
  for (let index = 0; index < 3; index += 1) {
    if (a[index] !== b[index]) return (a[index] || 0) - (b[index] || 0);
  }
  return 0;
}

const config = readJson(configPath, {
  migratedPaths: [],
  pathBudgets: [],
  allowedInlineStyleFragments: [],
});
const configFailures = [];
const versionPath = 'docs/design-system/version.json';
const hasVersionContract = existsSync(versionPath);
const currentVersion = readJson(versionPath, { version: '0.0.0' }).version;
if (hasVersionContract) {
  const baselineHash = createHash('sha256')
    .update(readFileSync(baselinePath))
    .digest('hex');
  const approvalsDir = 'docs/design-system/baseline-approvals';
  const approvals = existsSync(approvalsDir)
    ? readdirSync(approvalsDir).filter((file) => file.endsWith('.json'))
      .map((file) => readJson(join(approvalsDir, file), {})) : [];
  if (!approvals.some((item) => item.designSystemVersion === currentVersion
    && item.afterHash === baselineHash)) {
    configFailures.push('baseline: missing matching approval');
  }
}
const exceptionFields = [
  'module', 'rule', 'file', 'selector', 'owner', 'reason', 'expiresAtVersion',
];

const designSystemEntrypoint = 'public/css/aia-design-system.css';
if (existsSync(designSystemEntrypoint)) {
  const source = readFileSync(designSystemEntrypoint, 'utf8');
  const declaration = source.match(/@layer\s+([^;{]+);/i)?.[1]
    ?.split(',').map((layer) => layer.trim()).filter(Boolean) || [];
  if (JSON.stringify(declaration) !== JSON.stringify(layerOrder)) {
    configFailures.push(`aia-design-system.css: layer order must be ${layerOrder.join(', ')}`);
  }
}
for (const [index, exception] of (config.exceptions || []).entries()) {
  for (const field of exceptionFields) {
    if (!exception?.[field]) configFailures.push(`exception ${index}: missing ${field}`);
  }
  if (exception?.expiresAtVersion
    && compareSemVer(currentVersion, exception.expiresAtVersion) >= 0) {
    configFailures.push(`exception ${index}: expired at ${exception.expiresAtVersion}`);
  }
}

const files = scanRoots.flatMap((dir) => walk(join(root, dir)));
const violations = [];

for (const filePath of files) {
  const file = relative(root, filePath);
  const content = readFileSync(filePath, 'utf8');
  const isCanonicalDesignSystemFile = canonicalDesignSystemFiles.has(file);
  const isDesignSystemOwnedFile = isCanonicalDesignSystemFile
    || file.startsWith('public/css/design-system/');
  const cssRules = file.endsWith('.css') ? parseCssStructure(content) : [];
  const unknownLayers = new Map();

  for (const rule of cssRules) {
    if (!rule.layer) {
      addViolation(violations, 'css-outside-layer', file, rule.line, rule.selector, rule.selector);
    } else {
      const rootLayer = rule.layer.split('.')[0];
      if (!allowedLayers.has(rootLayer) && !unknownLayers.has(rootLayer)) {
        unknownLayers.set(rootLayer, rule.line);
      }
    }
    if (!isDesignSystemOwnedFile && rule.selector.split(',')
      .some((selector) => /^(?:html|body|:root)(?:\s|>|$)/.test(selector.trim()))) {
      addViolation(violations, 'global-module-selector', file, rule.line, rule.selector, rule.selector);
    }
    if (!isDesignSystemOwnedFile && vendorSelectorPattern.test(rule.selector)) {
      addViolation(violations, 'local-vendor-override', file, rule.line, rule.selector, rule.selector);
    }
    if (!isDesignSystemOwnedFile
      && /(?:^|[\s>+~,(])\.aia-[\w-]+/.test(rule.selector)) {
      addViolation(
        violations,
        'duplicate-canonical-primitive',
        file,
        rule.line,
        rule.selector,
        rule.selector
      );
    }
    for (const declaration of rule.declarations) {
      if (declaration.important) {
        addViolation(
          violations,
          'unauthorized-important',
          file,
          declaration.line,
          `${rule.selector} { ${declaration.property}: ${declaration.value} !important }`,
          rule.selector
        );
      }
      if (!isDesignSystemOwnedFile && rawTokenPattern.test(declaration.value)) {
        addViolation(
          violations,
          'raw-token-in-module',
          file,
          declaration.line,
          `${declaration.property}: ${declaration.value}`,
          rule.selector
        );
      }
      if (!isDesignSystemOwnedFile && spacingPropertyPattern.test(declaration.property)
        && literalDimensionPattern.test(declaration.value)) {
        addViolation(violations, 'off-scale-spacing', file, declaration.line,
          `${declaration.property}: ${declaration.value}`, rule.selector);
      }
      if (!isDesignSystemOwnedFile && typographyPropertyPattern.test(declaration.property)
        && literalDimensionPattern.test(declaration.value)) {
        addViolation(violations, 'off-scale-typography', file, declaration.line,
          `${declaration.property}: ${declaration.value}`, rule.selector);
      }
      if (!isDesignSystemOwnedFile && /^(?:box|text)-shadow$/.test(declaration.property)
        && declaration.value !== 'none' && !/var\(--ds-/.test(declaration.value)) {
        addViolation(violations, 'off-scale-shadow', file, declaration.line,
          `${declaration.property}: ${declaration.value}`, rule.selector);
      }
    }
  }
  for (const [layer, line] of unknownLayers) {
    addViolation(
      violations,
      'unknown-css-layer',
      file,
      line,
      `@layer ${layer}`,
      `@layer ${layer}`
    );
  }

  if (!isCanonicalDesignSystemFile) {
    for (const match of content.matchAll(hexPattern)) {
      const previous = content.slice(Math.max(0, match.index - 8), match.index);
      if (previous.endsWith('&')) continue;
      if (previous.includes('/*') || previous.includes('//')) continue;
      addViolation(violations, 'hardcoded-hex', file, lineForIndex(content, match.index), match[0]);
    }

    for (const match of content.matchAll(colorFunctionPattern)) {
      addViolation(
        violations,
        'hardcoded-color-function',
        file,
        lineForIndex(content, match.index),
        match[0]
      );
    }
  }

  for (const match of content.matchAll(inlineStylePattern)) {
    if (isAllowedInline(match[1], config.allowedInlineStyleFragments || [])) continue;
    addViolation(violations, 'inline-style', file, lineForIndex(content, match.index), match[1]);
  }

  for (const match of content.matchAll(embeddedStylePattern)) {
    addViolation(violations, 'embedded-style-block', file, lineForIndex(content, match.index), '<style>...</style>');
  }

  if (!isCanonicalDesignSystemFile) {
    for (const match of content.matchAll(forbiddenFontPattern)) {
      addViolation(violations, 'forbidden-font-roboto', file, lineForIndex(content, match.index), match[0]);
    }
  }

  for (const match of content.matchAll(hardcodedRadiusPattern)) {
    addViolation(violations, 'hardcoded-radius', file, lineForIndex(content, match.index), match[0]);
  }
}

const auditedViolations = violations.filter((violation) => !(config.exceptions || [])
  .some((exception) => exception.rule === violation.rule
    && normalizeRepoPath(exception.file) === violation.file
    && normalizeSelector(exception.selector) === normalizeSelector(violation.selector)));
const summary = countByRuleAndFile(auditedViolations);
const pathBudgetReports = (config.pathBudgets || []).map((budget) => {
  const paths = budget.paths || [];
  const maxViolations = budget.maxViolations || {};
  const matchedViolations = auditedViolations.filter((violation) => (
    paths.some((configuredPath) => fileMatchesConfiguredPath(violation.file, configuredPath))
  ));
  const actualViolations = Object.fromEntries(
    Object.keys(maxViolations).map((rule) => [rule, countRule(matchedViolations, rule)])
  );

  return {
    name: budget.name,
    paths,
    missingPaths: paths.filter((configuredPath) => !pathExists(configuredPath)),
    maxViolations,
    actualViolations,
    totalMatchedViolations: matchedViolations.length,
    sample: matchedViolations.slice(0, 20),
  };
});
const report = {
  generatedAt: new Date().toISOString(),
  scannedRoots: scanRoots,
  totalViolations: violations.length,
  summary,
  pathBudgets: pathBudgetReports,
  sample: auditedViolations.slice(0, 80),
};

const baseline = readJson(baselinePath, { totals: {} });
const failures = [...configFailures];

for (const [rule, data] of Object.entries(summary)) {
  const allowed = Number(baseline.totals?.[rule] || 0);
  if (data.total > allowed) {
    failures.push(`${rule}: ${data.total} > baseline ${allowed}`);
  }
}

for (const [rule, allowed] of Object.entries(baseline.totals || {})) {
  if (!summary[rule] && allowed < 0) {
    failures.push(`${rule}: invalid negative baseline ${allowed}`);
  }
}

for (const budget of pathBudgetReports) {
  if (budget.missingPaths.length > 0) {
    failures.push(`${budget.name || 'path-budget'}: missing configured paths ${budget.missingPaths.join(', ')}`);
  }

  for (const [rule, allowed] of Object.entries(budget.maxViolations)) {
    const actual = budget.actualViolations[rule] || 0;
    if (actual > Number(allowed)) {
      failures.push(`${budget.name || 'path-budget'} ${rule}: ${actual} > path budget ${allowed}`);
    }
  }
}

console.log(JSON.stringify(report, null, 2));

if (failures.length > 0) {
  console.error('\nDesign system audit failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('\nDesign system audit passed against baseline.');
