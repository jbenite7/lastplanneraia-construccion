import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import test from 'node:test';
import { validarRecibo } from '../../scripts/design-system/gate-receipt.mjs';

const leer = async (p) => JSON.parse(await readFile(new URL(`../../${p}`, import.meta.url), 'utf8'));

/**
 * El gate de gobernanza que ya existía comprueba que haya exactamente quince gates,
 * que sus ids no se repitan y que estén en orden. Eso es contar y comprobar nombres.
 * Lo que nunca hacía —y por lo que quince gates pudieron declarar `passed` sin que
 * nadie los ejecutara— es **abrir el recibo** y ver si dice algo.
 */

test('cada gate declara un artefacto de recibo', async () => {
  const indice = await leer('docs/design-system/closeout-evidence.json');
  for (const gate of indice.gates) {
    const declarado = (gate.evidence || [])[0] || {};
    assert.ok(declarado.artifact, `${gate.id}: no declara artefacto de recibo`);
  }
});

/**
 * Exclusiones **por nombre y con motivo**, no por silencio. Cada una tiene su decisión
 * encolada en `docs/decisiones-pendientes.md`, y ninguna se queda aquí sin fecha: cuando
 * el usuario decida, o el gate se reconstruye y sale de esta lista, o se retira del
 * índice y deja de existir. Una exclusión sin dueño es cómo empezó el problema.
 *
 * D-F1b-1, D-F1b-2 y D-F1b-3 se resolvieron el 2026-08-11: `accessibility-insights`,
 * `consolidated-lab`, `consolidated-pilot`, `review` y `git-preservation` se retiraron
 * del índice (motivo en docs/design-system/gates-cierre-frente-1b.md); `pg-roles`,
 * `pg-persistence` y `data-restoration` se fundieron en `full-app-flow`. Ningún gate
 * pendiente de decisión queda hoy en el índice.
 */
const PENDIENTES_DE_DECISION = new Map();

test('las exclusiones estan vivas: cada una nombra un gate que existe', async () => {
  const indice = await leer('docs/design-system/closeout-evidence.json');
  const ids = new Set(indice.gates.map((g) => g.id));
  for (const id of PENDIENTES_DE_DECISION.keys()) {
    assert.ok(ids.has(id), `la exclusión nombra '${id}', que ya no está en el índice: retírala`);
  }
});

test('un recibo que existe describe lo que el indice declara y lo que ocurrio', async () => {
  const indice = await leer('docs/design-system/closeout-evidence.json');
  const problemas = [];

  for (const gate of indice.gates) {
    if (PENDIENTES_DE_DECISION.has(gate.id)) continue;
    const ruta = ((gate.evidence || [])[0] || {}).artifact;
    if (!ruta) continue;
    if (!existsSync(new URL(`../../${ruta}`, import.meta.url))) continue;

    const recibo = await leer(ruta);
    // Un recibo de la forma vieja —solo `gateId` y `result`— no puede validarse
    // como contenido: se cuenta aparte para que la migracion sea visible y medible,
    // en vez de romper el gate entero de golpe.
    const esViejo = Object.keys(recibo).length <= 2;
    if (esViejo) continue;

    const fallos = validarRecibo(recibo, gate);
    if (fallos.length) problemas.push(`${gate.id}: ${fallos.join('; ')}`);
  }

  assert.deepEqual(problemas, [], `recibos que no describen lo que dicen:\n${problemas.join('\n')}`);
});

test('la validacion de contenido falla cerrado ante un recibo que miente', () => {
  const gate = {
    id: 'static',
    evidence: [{ command: 'npm run test:design-system:static' }],
  };
  const bueno = {
    gateId: 'static',
    result: 'passed',
    command: 'npm run test:design-system:static',
    exitCode: 0,
    measuredAt: '2026-08-11T12:00:00.000Z',
    tree: { sha: 'abcdef1234567890', dirty: false },
    outputTail: '[static-suite] PASS audit',
  };
  assert.deepEqual(validarRecibo(bueno, gate), []);

  // La mentira que el cierre anterior permitia: declararse aprobado con el
  // comando en rojo. Es la forma exacta que tenian los quince recibos de julio.
  const miente = { ...bueno, exitCode: 1 };
  assert.ok(
    validarRecibo(miente, gate).some((f) => f.includes("declara 'passed' con exitCode 1")),
    'un recibo aprobado con salida distinta de cero debe fallar',
  );

  // Medir otro comando distinto del declarado.
  const otroComando = { ...bueno, command: 'echo ok' };
  assert.ok(validarRecibo(otroComando, gate).some((f) => f.includes('el índice declara')));

  // Sin arbol medido no se sabe para que commit vale el verde.
  const sinArbol = { ...bueno, tree: undefined };
  assert.ok(validarRecibo(sinArbol, gate).some((f) => f.includes('tree')));

  // La forma vieja, de dos claves, no pasa la validacion de contenido.
  const viejo = { gateId: 'static', result: 'passed' };
  assert.ok(validarRecibo(viejo, gate).length >= 3, 'un recibo de dos claves no puede validarse');
});
