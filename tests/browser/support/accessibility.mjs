import AxeBuilder from '@axe-core/playwright';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const BLOCKING_IMPACTS = new Set(['critical', 'serious']);
export const WCAG_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

export function approvedAccessibilityScenarios(homologation) {
  const scenarios = [];
  for (const family of homologation?.families || []) {
    if (!(family.candidates || []).some(({ status }) => status === 'approved')) continue;
    for (const theme of family.themes || []) {
      for (const viewport of family.viewports || []) {
        const match = /^(\d+)x(\d+)$/.exec(viewport);
        if (!match) throw new Error(`invalid accessibility viewport: ${viewport}`);
        scenarios.push({
          family: family.id,
          theme,
          viewport,
          size: { width: Number(match[1]), height: Number(match[2]) },
        });
      }
    }
  }
  return scenarios;
}

function selectorFor(node) {
  const target = Array.isArray(node?.target) ? node.target : [];
  return target.map((part) => String(part)).join(' > ');
}

function violationEntries(results, surface) {
  return (results?.violations || []).flatMap((violation) => (
    (violation.nodes || []).map((node) => ({
      rule: violation.id,
      impact: violation.impact || 'unknown',
      surface,
      selector: selectorFor(node),
    }))
  ));
}

function fingerprint(entry) {
  return [entry.rule, entry.impact, entry.surface, entry.selector].join('|');
}

export function fingerprintViolations(results, surface) {
  return violationEntries(results, surface).map(fingerprint).sort();
}

export function validateAccessibilityExceptions(exceptions, now = new Date().toISOString().slice(0, 10)) {
  if (!Array.isArray(exceptions)) throw new Error('accessibility exceptions must be an array');
  for (const exception of exceptions) {
    if (!exception?.fingerprint || exception.fingerprint.includes('*')) {
      throw new Error('accessibility exception requires an exact fingerprint');
    }
    for (const field of [
      'surface', 'rule', 'impact', 'selector', 'owner', 'reason', 'milestone', 'expiresAt',
    ]) {
      if (!exception[field]) throw new Error(`accessibility exception requires ${field}`);
    }
    const declaredFingerprint = [
      exception.rule, exception.impact, exception.surface, exception.selector,
    ].join('|');
    if (exception.fingerprint !== declaredFingerprint) {
      throw new Error('accessibility exception fingerprint must match declared fields');
    }
    if (exception.expiresAt < now) throw new Error(`expired accessibility exception: ${exception.fingerprint}`);
  }
  return exceptions;
}

export function evaluateAccessibility(results, { surface, baseline = [], exceptions = [], now } = {}) {
  if (!surface) throw new Error('accessibility surface is required');
  validateAccessibilityExceptions(exceptions, now);
  const approved = new Set(exceptions.map((exception) => exception.fingerprint));
  const known = new Set(baseline);
  const outcome = { blocking: [], reported: [], excepted: [], existing: [], newFindings: [] };
  for (const entry of violationEntries(results, surface)) {
    const finding = { ...entry, fingerprint: fingerprint(entry) };
    (known.has(finding.fingerprint) ? outcome.existing : outcome.newFindings).push(finding);
    if (approved.has(finding.fingerprint)) {
      outcome.excepted.push(finding);
    } else if (BLOCKING_IMPACTS.has(finding.impact)) {
      outcome.blocking.push(finding);
    } else {
      outcome.reported.push(finding);
    }
  }
  return outcome;
}

export async function loadAccessibilityGovernance(root = process.cwd()) {
  const directory = path.join(root, 'docs/design-system');
  const [baseline, exceptions] = await Promise.all([
    readFile(path.join(directory, 'a11y-baseline.json'), 'utf8').then(JSON.parse),
    readFile(path.join(directory, 'a11y-exceptions.json'), 'utf8').then(JSON.parse),
  ]);
  if (baseline.designSystemVersion !== exceptions.designSystemVersion) {
    throw new Error('accessibility governance versions must match');
  }
  return {
    designSystemVersion: baseline.designSystemVersion,
    baseline: baseline.fingerprints,
    exceptions: exceptions.exceptions,
  };
}

export async function scanAccessibility(page, options = {}) {
  const governance = await loadAccessibilityGovernance(options.governanceRoot);
  const builder = new AxeBuilder({ page }).withTags(WCAG_TAGS);
  if (options.include) builder.include(options.include);
  const results = await builder.analyze();
  const outcome = evaluateAccessibility(results, {
    ...options,
    baseline: options.baseline ?? governance.baseline,
    exceptions: options.exceptions ?? governance.exceptions,
  });
  const report = {
    designSystemVersion: governance.designSystemVersion,
    surface: options.surface,
    url: page.url(),
    generatedAt: new Date().toISOString(),
    ...outcome,
  };
  if (options.reportPath) {
    await mkdir(path.dirname(options.reportPath), { recursive: true });
    await writeFile(options.reportPath, `${JSON.stringify(report, null, 2)}\n`);
  }
  return report;
}
