import path from 'path';
import fs from 'fs';

/**
 * Generates a findings.md file per test with error evidence.
 * Called from afterEach to collect all errors captured during the test.
 *
 * @param {import('@playwright/test').TestInfo} testInfo - Playwright test info
 * @param {object} errors - Error collector from installErrorCollectors()
 * @param {Array<string>} [errors.pageErrors=[]] - Unhandled page errors
 * @param {Array<string>} [errors.consoleErrors=[]] - console.error messages
 * @param {Array<string>} [errors.serverErrors=[]] - HTTP 4xx/5xx responses
 * @param {Array<string>} [errors.assertionErrors=[]] - Assertion failure messages
 */
export function generateFindings(testInfo, errors) {
  const { pageErrors = [], consoleErrors = [], serverErrors = [], assertionErrors = [] } = errors;

  const slug = testInfo.title.replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
  const dir = path.join(testInfo.outputDir, 'findings', slug);
  fs.mkdirSync(dir, { recursive: true });

  const lines = [];

  // Header
  lines.push(`# Findings: ${testInfo.title}`);
  lines.push('');
  lines.push('| Field | Value |');
  lines.push('|---|---|');
  lines.push(`| File | ${testInfo.file} |`);
  lines.push(`| Status | ${testInfo.status} |`);
  lines.push(`| Duration | ${testInfo.duration}ms |`);
  if (testInfo.retry) lines.push(`| Retry | ${testInfo.retry} |`);
  lines.push('');

  const total = pageErrors.length + consoleErrors.length + serverErrors.length + assertionErrors.length;

  // Page errors
  if (pageErrors.length > 0) {
    lines.push(`## Page Errors (${pageErrors.length})`);
    pageErrors.forEach((e, i) => lines.push(`${i + 1}. \`${e}\``));
    lines.push('');
  }

  // Console errors
  if (consoleErrors.length > 0) {
    lines.push(`## Console Errors (${consoleErrors.length})`);
    consoleErrors.forEach((e, i) => lines.push(`${i + 1}. \`${e}\``));
    lines.push('');
  }

  // HTTP 4xx/5xx
  if (serverErrors.length > 0) {
    lines.push(`## HTTP Errors (${serverErrors.length})`);
    serverErrors.forEach((e, i) => lines.push(`${i + 1}. ${e}`));
    lines.push('');
  }

  // Assertion failures
  if (assertionErrors.length > 0) {
    lines.push(`## Assertion Failures (${assertionErrors.length})`);
    assertionErrors.forEach((e, i) => lines.push(`${i + 1}. ${e}`));
    lines.push('');
  }

  // Evidence links
  lines.push('## Evidence');
  lines.push(`- **Screenshot:** \`${testInfo.outputPath('screenshot.png')}\``);
  if (testInfo.attachments && testInfo.attachments.length > 0) {
    testInfo.attachments.forEach((att) => {
      lines.push(`- **${att.name}:** \`${att.path || '<inline>'}\``);
    });
  } else {
    lines.push('- **Trace:** use `npm run report` for interactive trace');
    lines.push('- **Video:** check `test-results/` for `.webm` files');
  }

  // Summary
  lines.push('');
  lines.push('---');
  lines.push(`**Total errors:** ${total}`);
  if (total === 0) lines.push('**Verdict:** Clean — no runtime or assertion errors detected.');

  const findingsPath = path.join(dir, 'findings.md');
  fs.writeFileSync(findingsPath, lines.join('\n'));

  return findingsPath;
}

/**
 * Collects assertion errors in addition to the existing error collector.
 * Wrap expect() calls to catch SoftAssertionErrors.
 *
 * @param {object} errors - Error collector object
 * @returns {object} The same errors object with assertionErrors array
 */
export function attachAssertionCollector(errors) {
  if (!errors.assertionErrors) errors.assertionErrors = [];
  return errors;
}