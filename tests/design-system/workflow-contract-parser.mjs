import assert from 'node:assert/strict';

const scalar = (value) => {
  const trimmed = value.trim();
  if (trimmed === 'true') return true;
  if (trimmed === 'false') return false;
  return trimmed.replace(/^(['"])(.*)\1$/, '$2');
};

const assign = (target, key, value) => {
  assert.equal(Object.hasOwn(target, key), false, `duplicate workflow key: ${key}`);
  target[key] = scalar(value);
};

export function parseJobSteps(source, jobId) {
  const lines = source.split('\n');
  const jobStart = lines.findIndex((line) => line === `  ${jobId}:`);
  assert.notEqual(jobStart, -1, `missing workflow job: ${jobId}`);

  const jobEndOffset = lines.slice(jobStart + 1)
    .findIndex((line) => /^  [a-zA-Z0-9_-]+:$/.test(line));
  const jobEnd = jobEndOffset === -1 ? lines.length : jobStart + 1 + jobEndOffset;
  const stepsStart = lines
    .slice(jobStart, jobEnd)
    .findIndex((line) => line === '    steps:');
  assert.notEqual(stepsStart, -1, `missing steps for workflow job: ${jobId}`);

  const steps = [];
  let current;
  let nestedKey;
  let blockTarget;
  let blockIndent = 0;

  for (let index = jobStart + stepsStart + 1; index < jobEnd; index += 1) {
    const line = lines[index];
    const indent = line.match(/^ */)?.[0].length ?? 0;

    if (blockTarget && indent > blockIndent) {
      const { target, key } = blockTarget;
      target[key] += `${target[key] ? '\n' : ''}${line.slice(blockIndent + 2)}`;
      continue;
    }
    blockTarget = undefined;

    const stepMatch = line.match(/^      - ([a-zA-Z0-9_-]+):\s*(.*)$/);
    if (stepMatch) {
      current = {};
      steps.push(current);
      nestedKey = undefined;
      assign(current, stepMatch[1], stepMatch[2]);
      continue;
    }
    if (!current) continue;

    const propertyMatch = line.match(/^        ([a-zA-Z0-9_-]+):\s*(.*)$/);
    if (propertyMatch) {
      const [, key, value] = propertyMatch;
      nestedKey = value === '' ? key : undefined;
      if (value === '|') {
        current[key] = '';
        blockTarget = { target: current, key };
        blockIndent = indent;
      } else if (value !== '') {
        assign(current, key, value);
      } else {
        current[key] = {};
      }
      continue;
    }

    const nestedMatch = line.match(/^          ([a-zA-Z0-9_-]+):\s*(.*)$/);
    if (nestedMatch && nestedKey) {
      const [, key, value] = nestedMatch;
      if (value === '|') {
        current[nestedKey][key] = '';
        blockTarget = { target: current[nestedKey], key };
        blockIndent = indent;
      } else {
        assign(current[nestedKey], key, value);
      }
    }
  }

  return steps;
}
