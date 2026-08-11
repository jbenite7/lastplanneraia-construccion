#!/usr/bin/env node
/**
 * Migra los recibos de un gate a la forma nueva, resolviendo el baile de dos tiempos
 * que el contrato exige y que es fácil hacer mal a mano.
 *
 *   node scripts/design-system/migrate-receipts.mjs <gateId> [<gateId> ...]
 *   node scripts/design-system/migrate-receipts.mjs --dry-run <gateId>
 *
 * Por qué existe: al intentar la migración a mano el 2026-08-11 salieron tres
 * requisitos que no son evidentes leyendo el índice, y que hacen que regenerar el
 * recibo NO baste (`scripts/design-system-closeout-contract.mjs:88-113` y
 * `scripts/design-system-evidence-receipt.mjs:120-130`):
 *
 *   1. `verifiedAt` con formato exacto `YYYY-MM-DDTHH:MM:SSZ` —sin milisegundos— y
 *      posterior a `generatedAt`. Un gate que no esté `passed` lo lleva en `null`.
 *   2. El artefacto tiene que estar **commiteado**, y `sourceRef` resolver al commit
 *      donde ese archivo coincide. De ahí los dos tiempos.
 *   3. `artifactSha256` se recalcula con cada recibo nuevo.
 *
 * El primer tiempo escribe los recibos y los commitea; el segundo apunta el índice
 * a ese commit. Entre medias no queda ningún estado publicable a medias.
 *
 * **No decide nada.** Recibe la lista de gates que sobreviven a las decisiones
 * `D-F1b-1`, `D-F1b-2` y `D-F1b-3`; los que se retiren no se le pasan, y se sacan
 * del índice aparte, con su motivo escrito.
 */
import { spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { generarRecibo } from './gate-receipt.mjs';

const raiz = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const INDICE = path.join(raiz, 'docs/design-system/closeout-evidence.json');

const git = (...args) => {
  const r = spawnSync('git', args, { cwd: raiz, encoding: 'utf8' });
  if (r.status !== 0) throw new Error(`git ${args.join(' ')} → ${r.stderr.trim()}`);
  return r.stdout.trim();
};

/** El contrato rechaza los milisegundos. Es el fallo más fácil de cometer aquí. */
const sinMilisegundos = (iso) => `${new Date(iso).toISOString().slice(0, 19)}Z`;

const args = process.argv.slice(2);
const dryRun = args.includes('--dry-run');
const gates = args.filter((a) => !a.startsWith('--'));

if (gates.length === 0) {
  console.error('uso: node scripts/design-system/migrate-receipts.mjs [--dry-run] <gateId> [...]');
  process.exit(2);
}

if (git('status', '--porcelain') !== '' && !dryRun) {
  console.error('El árbol tiene cambios sin commitear. Un recibo tomado sobre un árbol sucio no');
  console.error('describe ningún commit, y el paso 2 necesita un commit limpio al que apuntar.');
  process.exit(2);
}

// ── Primer tiempo: ejecutar cada gate y escribir su recibo ──────────────────────
const recibos = new Map();
for (const gateId of gates) {
  const recibo = generarRecibo(gateId, { escribir: !dryRun });
  recibos.set(gateId, recibo);
  console.log(`  ${gateId}: ${recibo.result} (exit ${recibo.exitCode})`);
}

if (dryRun) {
  console.log('\n--dry-run: no se escribió ni se commiteó nada.');
  process.exit(0);
}

git('add', ...gates.map((g) => `docs/design-system/evidence/${g}.json`));
git('commit', '-m', `chore(design-system): recibos reales de ${gates.join(', ')}\n\nGenerados ejecutando cada gate. El resultado se deriva del codigo de salida.`);
const sourceRef = git('rev-parse', 'HEAD');
console.log(`\nPrimer tiempo commiteado: ${sourceRef.slice(0, 8)}`);

// ── Segundo tiempo: apuntar el índice a ese commit ──────────────────────────────
const indice = JSON.parse(readFileSync(INDICE, 'utf8'));
for (const gate of indice.gates) {
  const recibo = recibos.get(gate.id);
  if (!recibo) continue;
  const ruta = path.join(raiz, `docs/design-system/evidence/${gate.id}.json`);
  const ev = gate.evidence[0];
  ev.artifactSha256 = createHash('sha256').update(readFileSync(ruta)).digest('hex');
  ev.exitCode = recibo.exitCode;
  ev.sourceRef = sourceRef;
  // El indice deja de poder afirmar `passed` cuando el recibo dice otra cosa.
  gate.status = recibo.result === 'passed' ? 'passed' : 'blocked';
  gate.verifiedAt = gate.status === 'passed' ? sinMilisegundos(recibo.measuredAt) : null;
}
writeFileSync(INDICE, `${JSON.stringify(indice, null, 2)}\n`);

console.log('Segundo tiempo escrito en closeout-evidence.json.');
console.log('Ahora: `npm run test:design-system:static` y commitear el índice aparte.');
