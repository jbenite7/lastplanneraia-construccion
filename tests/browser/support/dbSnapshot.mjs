import fs from 'fs';
import os from 'os';
import path from 'path';
import { execFileSync, spawnSync } from 'child_process';
import { GLOBAL_TABLES } from '../fixtures/projects.mjs';

function dockerCompose(args, options = {}) {
  return execFileSync('docker', ['compose', ...args], {
    cwd: process.cwd(),
    encoding: 'utf8',
    stdio: options.stdio || ['ignore', 'pipe', 'pipe'],
    input: options.input,
  });
}

function mysql(sql) {
  return dockerCompose([
    'exec',
    '-T',
    'db',
    'sh',
    '-lc',
    'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -N -e "$1"',
    'mysql-e2e',
    sql,
  ]);
}

export function runSql(sql) {
  return mysql(sql);
}

function tableExists(table) {
  const escaped = table.replace(/'/g, "''");
  return mysql(`SHOW TABLES LIKE '${escaped}';`).trim() === table;
}

export class ProjectDbSnapshot {
  constructor(project, tables = GLOBAL_TABLES) {
    this.project = project;
    this.tables = tables;
    this.existingTables = [];
    this.filePath = path.join(
      os.tmpdir(),
      `lps-aia-e2e-${project.projectId}-${Date.now()}-${Math.random().toString(16).slice(2)}.sql`,
    );
  }

  capture() {
    this.existingTables = this.tables.filter(tableExists);
    if (this.existingTables.length === 0) {
      fs.writeFileSync(this.filePath, '', 'utf8');
      return this;
    }

    const dump = spawnSync('docker', [
      'compose',
      'exec',
      '-T',
      'db',
      'sh',
      '-lc',
      'where="project_id=$1"; shift; mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --no-create-info --skip-triggers --single-transaction --where="$where" "$MYSQL_DATABASE" "$@"',
      'dump-e2e',
      String(this.project.projectId),
      ...this.existingTables,
    ], {
      cwd: process.cwd(),
      encoding: 'utf8',
      maxBuffer: 50 * 1024 * 1024,
    });

    if (dump.status !== 0) {
      throw new Error(`mysqldump failed: ${dump.stderr || dump.stdout}`);
    }

    fs.writeFileSync(this.filePath, dump.stdout, 'utf8');
    return this;
  }

  restore() {
    if (this.existingTables.length === 0) return;

    const deletes = this.existingTables
      .map((table) => `DELETE FROM \`${table}\` WHERE project_id = ${this.project.projectId};`)
      .join('\n');
    mysql(`SET FOREIGN_KEY_CHECKS=0;\n${deletes}\nSET FOREIGN_KEY_CHECKS=1;`);

    const sql = fs.readFileSync(this.filePath, 'utf8');
    if (sql.trim()) {
      const restore = spawnSync('docker', [
        'compose',
        'exec',
        '-T',
        'db',
        'sh',
        '-lc',
        'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"',
      ], {
        cwd: process.cwd(),
        input: sql,
        encoding: 'utf8',
        maxBuffer: 50 * 1024 * 1024,
      });

      if (restore.status !== 0) {
        throw new Error(`mysql restore failed: ${restore.stderr || restore.stdout}`);
      }
    }
  }

  dispose() {
    if (fs.existsSync(this.filePath)) fs.unlinkSync(this.filePath);
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
