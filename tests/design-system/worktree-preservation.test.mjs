import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

const ROOT = new URL('../../', import.meta.url);
const MANIFEST = 'goals/design-system-nucleo-gobernanza/worktree-preservation.json';

test('worktree preservation verifier rejects a stale snapshot', () => {
  const temporary = mkdtempSync(join(tmpdir(), 'ds-preservation-'));
  const tamperedPath = join(temporary, 'worktree-preservation.json');

  try {
    const manifest = JSON.parse(readFileSync(new URL(`../../${MANIFEST}`, import.meta.url)));
    manifest.repository.head = '0000000000000000000000000000000000000000';
    writeFileSync(tamperedPath, `${JSON.stringify(manifest, null, 2)}\n`);

    const result = spawnSync(
      process.execPath,
      ['scripts/design-system-worktree-preservation.mjs', 'check', tamperedPath],
      {
        cwd: ROOT,
        encoding: 'utf8',
      },
    );

    assert.equal(result.status, 1);
    assert.match(result.stderr, /Worktree preservation: FAIL/);
    assert.match(result.stderr, /repository changed/);
  } finally {
    rmSync(temporary, { recursive: true, force: true });
  }
});
