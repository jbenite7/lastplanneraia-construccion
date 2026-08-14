import { test } from 'node:test';
import assert from 'node:assert/strict';
import { crearReglasSemanal } from '../../public/js/modules/aia_ui/enablement-rules.js';

const ctx = (over = {}) => ({
  permiso: 'A', semana: 5, maxSemana: 5, semanalConfirmada: 0, ...over,
});

test('S1: una prop fuera de editableProps es readOnly siempre', () => {
  const reglas = crearReglasSemanal(ctx());
  assert.equal(reglas.isPropReadOnly('Actividad'), true);
});

test('S2: en semana histórica solo A y D editan', () => {
  const historica = { semana: 3, maxSemana: 5 };
  assert.equal(crearReglasSemanal(ctx({ ...historica, permiso: 'A' })).isPropReadOnly('Ubicacion'), false);
  assert.equal(crearReglasSemanal(ctx({ ...historica, permiso: 'R' })).isPropReadOnly('Ubicacion'), true);
});

test('S3: Ejecutado_Real solo en fase de calificación, y para roles editores', () => {
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 0 })).isPropReadOnly('Ejecutado_Real'), true);
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 1 })).isPropReadOnly('Ejecutado_Real'), false);
  assert.equal(crearReglasSemanal(ctx({ semanalConfirmada: 1, permiso: 'V' })).isPropReadOnly('Ejecutado_Real'), true);
});

test('S3 antes que S2: Ejecutado_Real ignora la semana histórica, y es deliberado', () => {
  const reglas = crearReglasSemanal(ctx({ permiso: 'R', semana: 3, maxSemana: 5, semanalConfirmada: 1 }));
  assert.equal(reglas.isPropReadOnly('Ubicacion'), true);
  assert.equal(reglas.isPropReadOnly('Ejecutado_Real'), false);
});

test('S4: confirmada bloquea compromiso y responsables', () => {
  const reglas = crearReglasSemanal(ctx({ semanalConfirmada: 1 }));
  for (const prop of ['Compromiso', 'Sub_Contratista', 'Responsable_AIA']) {
    assert.equal(reglas.isPropReadOnly(prop), true, prop);
  }
});

test('el alias de permiso se respeta: P es D, U es V', () => {
  assert.equal(crearReglasSemanal(ctx({ permiso: 'P', semana: 3, maxSemana: 5 })).isPropReadOnly('Ubicacion'), false);
  assert.equal(crearReglasSemanal(ctx({ permiso: 'U' })).isPropReadOnly('Ubicacion'), true);
});
