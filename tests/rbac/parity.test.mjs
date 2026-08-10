import assert from 'node:assert/strict';
import test from 'node:test';
import {
  ejecutarGate,
  formatearExcepcionNoUsada,
  formatearSoloCliente,
  formatearValorDistinto,
} from '../../scripts/rbac-parity.mjs';

test('la matriz de capacidades del servidor y la del cliente coinciden (salvo excepciones declaradas)', () => {
  const resultado = ejecutarGate();

  if (!resultado.ok) {
    const detalle = [
      ...resultado.valoresDistintos.map(formatearValorDistinto),
      ...resultado.soloCliente.map(formatearSoloCliente),
      ...resultado.excepcionesNoUsadas.map(formatearExcepcionNoUsada),
    ].join('\n');
    assert.fail(`Divergencias RBAC servidor/cliente:\n${detalle}`);
  }

  assert.equal(resultado.valoresDistintos.length, 0);
  assert.equal(resultado.soloCliente.length, 0);
  assert.equal(resultado.excepcionesNoUsadas.length, 0);
});
