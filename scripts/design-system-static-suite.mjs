// scripts/design-system-static-suite.mjs
// Corre TODOS los pasos de la suite estática aunque alguno falle.
// Razón: la cadena && ocultaba contracts, consumer-contract y audit tras el
// primer rojo — la trampa documentada el 2026-08-03 en la matriz de dark mode.
import { spawnSync } from 'node:child_process';
import { readdirSync } from 'node:fs';

const steps = [
  ['entrypoint-partition', ['scripts/design-system-entrypoint-partition.mjs']],
  ['unlayered-delivery', ['scripts/design-system-unlayered-delivery.mjs']],
  ['bi-utilities', ['scripts/design-system-bi-utilities.mjs']],
  ['table-contract', ['scripts/design-system-table-contract.mjs']],
  ['node-tests', ['--test', ...['tests/design-system', 'tests/scripts'].flatMap((d) => globTests(d))]],
  ['contracts', ['scripts/design-system-contracts.mjs']],
  ['consumer-contract', ['scripts/design-system-consumer-contract.mjs']],
  ['audit', ['scripts/design-system-audit.mjs']],
];

function globTests(dir) {
  return readdirSync(dir)
    .filter((f) => f.endsWith('.test.mjs'))
    .filter((f) => dir !== 'tests/scripts' || f === 'design-system-audit.test.mjs')
    .map((f) => `${dir}/${f}`);
}

const results = steps.map(([name, args]) => {
  const r = spawnSync(process.execPath, args, { stdio: 'inherit' });
  const ok = r.status === 0;
  console.log(`\n[static-suite] ${ok ? 'PASS' : 'FAIL'} ${name}`);
  return { name, ok };
});

console.log('\n[static-suite] resumen:');
for (const { name, ok } of results) console.log(`  ${ok ? '✔' : '✘'} ${name}`);
const failed = results.filter((r) => !r.ok);
process.exit(failed.length === 0 ? 0 : 1);
