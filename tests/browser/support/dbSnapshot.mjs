import fs from 'fs';
import os from 'os';
import path from 'path';
import { createHash } from 'crypto';
import { execFileSync, spawnSync } from 'child_process';
import { GLOBAL_TABLES } from '../fixtures/projects.mjs';
import {
  DATABASE_COMMAND_TIMEOUT_MS,
  assertIsolatedComposeEnvironment,
  boundedDatabaseCommand,
  databaseDumpScript,
  isolatedComposeArgs,
  listDatabaseTables,
  parseAutoIncrements,
  readAutoIncrementRows,
} from './databaseSnapshotSupport.mjs';

function dockerCompose(args, options = {}, env = process.env) {
  return execFileSync('docker', isolatedComposeArgs(args, env), {
    cwd: process.cwd(),
    encoding: 'utf8',
    stdio: options.stdio || ['ignore', 'pipe', 'pipe'],
    input: options.input,
    timeout: DATABASE_COMMAND_TIMEOUT_MS,
  });
}

function mysql(sql, env = process.env) {
  for (let attempt = 1; attempt <= 3; attempt += 1) {
    try {
      return dockerCompose([
        'exec', '-T', 'db', 'sh', '-lc',
        boundedDatabaseCommand('mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -N -e "$1"'),
        'mysql-e2e', sql,
      ], {}, env);
    } catch (error) {
      const detail = `${error?.message || ''}\n${error?.stderr || ''}`;
      if (!/Can.t connect|socket|connection reset/i.test(detail) || attempt === 3) throw error;
      spawnSync('docker', isolatedComposeArgs(['exec', '-T', 'db', 'sh', '-lc', 'sleep 1'], env), {
        cwd: process.cwd(), encoding: 'utf8', timeout: DATABASE_COMMAND_TIMEOUT_MS,
      });
    }
  }
  return '';
}

export function runSql(sql) {
  return mysql(sql);
}

function tableExists(table) {
  const escaped = table.replace(/'/g, "''");
  return mysql(`SHOW TABLES LIKE '${escaped}';`).trim() === table;
}

export class ProjectDbSnapshot {
  constructor(project, tables = GLOBAL_TABLES, options = {}) {
    this.project = project;
    this.tables = tables;
    this.environment = options.env || process.env;
    this.requireComposeIsolation = !options.mysql && !options.spawnSync;
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    this.mysqlCommand = options.mysql || ((sql) => mysql(sql, this.environment));
    this.spawnCommand = options.spawnSync || spawnSync;
    this.composeArgs = (args) => (this.requireComposeIsolation
      ? isolatedComposeArgs(args, this.environment)
      : ['compose', ...args]);
    this.sleep = options.sleep || ((seconds) => spawnSync(
      'docker',
      this.composeArgs(['exec', '-T', 'db', 'sh', '-lc', `sleep ${seconds}`]),
      { cwd: process.cwd(), encoding: 'utf8', timeout: DATABASE_COMMAND_TIMEOUT_MS },
    ));
    this.restoreAttempts = options.restoreAttempts || 3;
    this.allTables = options.allTables === true;
    this.autoIncrements = new Map();
    this.existingTables = [];
    this.filePath = path.join(
      os.tmpdir(),
      `lps-aia-e2e-${project.projectId}-${Date.now()}-${Math.random().toString(16).slice(2)}.sql`,
    );
    this.captured = false;
  }

  capture() {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    this.existingTables = this.allTables
      ? listDatabaseTables(this.mysqlCommand)
      : this.tables.filter((table) => {
        const escaped = table.replace(/'/g, "''");
        return this.mysqlCommand(`SHOW TABLES LIKE '${escaped}';`).trim() === table;
      });
    if (this.existingTables.length === 0) {
      fs.writeFileSync(this.filePath, '', 'utf8');
      this.captured = true;
      return this;
    }

    if (this.allTables) {
      this.autoIncrements = parseAutoIncrements(readAutoIncrementRows(this.mysqlCommand));
    }

    let dump;
    for (let attempt = 1; attempt <= 3; attempt += 1) {
      const dumpScript = databaseDumpScript(this.allTables);
      const dumpArgs = this.allTables
        ? ['dump-e2e', ...this.existingTables]
        : ['dump-e2e', String(this.project.projectId), ...this.existingTables];
      dump = this.spawnCommand('docker', this.composeArgs([
        'exec',
        '-T',
        'db',
        'sh',
        '-lc',
        dumpScript,
        ...dumpArgs,
      ]), {
        cwd: process.cwd(),
        encoding: 'utf8',
        maxBuffer: 200 * 1024 * 1024,
        timeout: DATABASE_COMMAND_TIMEOUT_MS,
      });

      if (dump.status === 0) break;

      const message = `${dump.error?.message || ''}\n${dump.stderr || ''}\n${dump.stdout || ''}`;
      if (!message.includes('Deadlock found') || attempt === 3) break;

      this.sleep(1);
    }

    if (dump.status !== 0) {
      throw new Error(`mysqldump failed: ${dump.error?.message || dump.stderr || dump.stdout}`);
    }

    fs.writeFileSync(this.filePath, dump.stdout, 'utf8');
    this.captured = true;
    return this;
  }

  restore() {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    if (!this.captured || this.existingTables.length === 0) return;
    // MUTACION F-AB (temporal, se revierte): restauracion de base convertida en no-op
    // para comprobar que el gate full-app-flow sabe ponerse rojo por el eje de restauracion.
    return;

    const deletes = this.existingTables
      .map((table) => (this.allTables
        ? `DELETE FROM \`${table}\`;`
        : `DELETE FROM \`${table}\` WHERE project_id = ${this.project.projectId};`))
      .join('\n');
    const resetSequences = this.allTables
      ? [...this.autoIncrements]
        .map(([table, value]) => `ALTER TABLE \`${table}\` AUTO_INCREMENT = ${value};`)
        .join('\n')
      : '';
    const sql = fs.readFileSync(this.filePath, 'utf8');
    const restoreSql = [
      'SET SESSION sql_log_bin=0;',
      'SET FOREIGN_KEY_CHECKS=0;',
      'START TRANSACTION;',
      deletes,
      sql,
      'COMMIT;',
      resetSequences,
      'SET FOREIGN_KEY_CHECKS=1;',
    ].join('\n');
    for (let attempt = 1; attempt <= this.restoreAttempts; attempt += 1) {
      try {
        const restore = this.spawnCommand('docker', this.composeArgs([
          'exec',
          '-T',
          'db',
          'sh',
          '-lc',
          boundedDatabaseCommand('mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'),
        ]), {
          cwd: process.cwd(),
          input: restoreSql,
          encoding: 'utf8',
          maxBuffer: 200 * 1024 * 1024,
          timeout: DATABASE_COMMAND_TIMEOUT_MS,
        });
        if (restore.status === 0) return;

        const detail = this.restoreFailureDetail(restore);
        if (!this.isTransientRestoreFailure(detail) || attempt === this.restoreAttempts) {
          throw new Error(`mysql restore failed: ${detail}`);
        }
      } catch (error) {
        const detail = error instanceof Error ? error.message : String(error);
        if (!this.isTransientRestoreFailure(detail) || attempt === this.restoreAttempts) throw error;
      }
      this.sleep(attempt);
    }
  }

  fingerprint() {
    if (this.requireComposeIsolation) assertIsolatedComposeEnvironment(this.environment);
    if (!this.captured || this.existingTables.length === 0) return 'empty';
    const dumpScript = databaseDumpScript(this.allTables, true);
    const dumpArgs = this.allTables
      ? ['dump-fingerprint', ...this.existingTables]
      : ['dump-fingerprint', String(this.project.projectId), ...this.existingTables];
    const dump = this.spawnCommand('docker', this.composeArgs([
      'exec', '-T', 'db', 'sh', '-lc',
      dumpScript,
      ...dumpArgs,
    ]), {
      cwd: process.cwd(),
      encoding: 'utf8',
      maxBuffer: 200 * 1024 * 1024,
      timeout: DATABASE_COMMAND_TIMEOUT_MS,
    });
    if (dump.status !== 0) {
      throw new Error(`mysqldump fingerprint failed: ${this.restoreFailureDetail(dump)}`);
    }
    const rows = dump.stdout.split('\n')
      .filter((line) => line.startsWith('INSERT INTO '))
      .sort()
      .join('\n');
    const sequences = this.allTables
      ? readAutoIncrementRows(this.mysqlCommand)
      : '';
    return createHash('sha256').update(`${rows}\n${sequences}`).digest('hex');
  }

  restoreFailureDetail(result) {
    return [
      `status=${result.status ?? 'null'}`,
      `signal=${result.signal || 'none'}`,
      `code=${result.error?.code || 'none'}`,
      result.error?.message || '',
      result.stderr || '',
      result.stdout || '',
    ].filter(Boolean).join(' | ');
  }

  isTransientRestoreFailure(detail) {
    return /EPIPE|ECONNRESET|Deadlock found|Lock wait timeout exceeded|Cannot connect to the Docker daemon/i.test(detail);
  }

  dispose() {
    if (fs.existsSync(this.filePath)) fs.unlinkSync(this.filePath);
  }
}

export class DatabaseSnapshot extends ProjectDbSnapshot {
  constructor(options = {}) {
    super({ projectId: 'database' }, [], { ...options, allTables: true });
  }
}

export function countE2ERows() {
  const tables = ['profesionales', 'subcontratistas', 'cambios', 'actividades', 'pdc'].filter(tableExists);
  if (tables.length === 0) return 0;

  const selectors = {
    profesionales: "nombre LIKE 'E2E %' OR email LIKE 'e2e-%'",
    subcontratistas: "subcontratista LIKE 'E2E %' OR correo_contacto LIKE 'e2e-%'",
    cambios: "descripcion LIKE 'E2E %' OR justificacion LIKE 'E2E %'",
    actividades: "actividad LIKE 'E2E %' OR descripcionActividad LIKE 'E2E %'",
    pdc: "paqueteContratacion LIKE 'E2E %' OR contratos LIKE 'E2E %'",
  };

  const union = tables
    .filter((table) => selectors[table])
    .map((table) => `SELECT COUNT(*) AS n FROM \`${table}\` WHERE ${selectors[table]}`)
    .join(' UNION ALL ');

  if (!union) return 0;
  const rows = mysql(`SELECT SUM(n) FROM (${union}) AS counts;`).trim();
  return Number(rows || 0);
}
