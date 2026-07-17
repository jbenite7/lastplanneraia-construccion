import fs from 'fs';
import { test, expect } from '@playwright/test';
import { ProjectDbSnapshot } from './support/dbSnapshot.mjs';

const project = { projectId: 73 };

function preparedSnapshot(options) {
  const snapshot = new ProjectDbSnapshot(project, ['actividades'], options);
  snapshot.existingTables = ['actividades'];
  snapshot.captured = true;
  fs.writeFileSync(snapshot.filePath, 'INSERT INTO actividades VALUES (73);', 'utf8');
  return snapshot;
}

test('reintenta el ciclo completo después de EPIPE', () => {
  let imports = 0;
  const sleeps = [];
  const inputs = [];
  const snapshot = preparedSnapshot({
    spawnSync: (_command, _args, options) => {
      imports += 1;
      inputs.push(options.input);
      if (imports === 1) {
        return { status: null, signal: null, error: { code: 'EPIPE', message: 'spawnSync docker EPIPE' } };
      }
      return { status: 0, signal: null, stdout: '', stderr: '' };
    },
    sleep: (seconds) => sleeps.push(seconds),
  });

  try {
    snapshot.restore();
    expect(imports).toBe(2);
    expect(sleeps).toEqual([1]);
    expect(inputs.every((input) => input.includes('START TRANSACTION;'))).toBe(true);
    expect(inputs.every((input) => input.includes('DELETE FROM `actividades`'))).toBe(true);
    expect(inputs.every((input) => input.includes('INSERT INTO actividades'))).toBe(true);
    expect(inputs.every((input) => input.includes('COMMIT;'))).toBe(true);
  } finally {
    snapshot.dispose();
  }
});

test('no reintenta un error SQL determinista', () => {
  let imports = 0;
  const snapshot = preparedSnapshot({
    spawnSync: () => {
      imports += 1;
      return { status: 1, signal: null, stdout: '', stderr: 'ERROR 1064: syntax error' };
    },
    sleep: () => { throw new Error('no debe esperar'); },
  });

  try {
    expect(() => snapshot.restore()).toThrow(/status=1.*ERROR 1064: syntax error/);
    expect(imports).toBe(1);
  } finally {
    snapshot.dispose();
  }
});

test('el segundo ciclo elimina una importación parcial antes de restaurar', () => {
  let rows = ['estado-actual'];
  let imports = 0;
  const snapshot = preparedSnapshot({
    spawnSync: (_command, _args, options) => {
      imports += 1;
      rows = [];
      expect(options.input).toContain('START TRANSACTION;');
      if (imports === 1) {
        rows.push('fila-parcial');
        return { status: null, signal: null, error: { code: 'EPIPE', message: 'spawnSync docker EPIPE' } };
      }
      rows.push('snapshot-completo');
      return { status: 0, signal: null, stdout: '', stderr: '' };
    },
    sleep: () => {},
  });

  try {
    snapshot.restore();
    expect(rows).toEqual(['snapshot-completo']);
    expect(imports).toBe(2);
  } finally {
    snapshot.dispose();
  }
});

test('falla explícitamente después de agotar los reintentos transitorios', () => {
  let imports = 0;
  const sleeps = [];
  const snapshot = preparedSnapshot({
    spawnSync: () => {
      imports += 1;
      return { status: null, signal: null, error: { code: 'EPIPE', message: 'spawnSync docker EPIPE' } };
    },
    sleep: (seconds) => sleeps.push(seconds),
  });

  try {
    expect(() => snapshot.restore()).toThrow(/EPIPE/);
    expect(imports).toBe(3);
    expect(sleeps).toEqual([1, 2]);
  } finally {
    snapshot.dispose();
  }
});
