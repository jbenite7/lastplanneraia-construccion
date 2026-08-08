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

// La suite entera tarda hoy ~33 s de pared; 180 s por paso es margen holgado
// para que un paso lento en una maquina cargada no dispare el timeout en una
// corrida normal, pero sigue matando un cuelgue real en vez de esperar para
// siempre (mismo defecto de Node que forzo el `process.exitCode` de abajo).
const STEP_TIMEOUT_MS = 180_000;

const results = steps.map(([name, args]) => {
  const r = spawnSync(process.execPath, args, { stdio: 'inherit', timeout: STEP_TIMEOUT_MS });
  const timedOut = r.error?.code === 'ETIMEDOUT';
  const ok = !timedOut && r.status === 0;
  if (timedOut) console.log(`\n[static-suite] TIMEOUT ${name} (no termino en ${STEP_TIMEOUT_MS} ms)`);
  console.log(`\n[static-suite] ${ok ? 'PASS' : 'FAIL'} ${name}`);
  return { name, ok };
});

console.log('\n[static-suite] resumen:');
for (const { name, ok } of results) console.log(`  ${ok ? '✔' : '✘'} ${name}`);
const failed = results.filter((r) => !r.ok);
// process.exit() puede colgarse contra un hilo de compilacion de V8 (Node
// 26.5.0, uv_thread_join en Maglev): con exitCode el runtime drena el event
// loop normal y no hay codigo despues de esta linea que pueda ejecutarse.
process.exitCode = failed.length === 0 ? 0 : 1;
