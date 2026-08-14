import { test } from 'node:test';
import assert from 'node:assert/strict';
import { crearReglasSemanal, crearReglasIntermedia } from '../../public/js/modules/aia_ui/enablement-rules.js';

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

const ctxPI = (over = {}) => ({
  permiso: 'A', semana: 5, maxSemana: 5, semanalConfirmada: 0,
  editableProps: { Observaciones: true, Sub_Contratista: true, Responsable_AIA: true, D_y_E: true },
  ...over,
});
const celda = (over = {}) => ({
  prop: 'Observaciones', esHeader: false, tieneResponsable: true, esRestriccion: false, ...over,
});

test('I2: confirmada bloquea todo, sin excepción de rol', () => {
  const reglas = crearReglasIntermedia(ctxPI({ semanalConfirmada: 1 }));
  assert.equal(reglas.puedeEditarCelda(celda()), false);
});

test('I2: en histórica solo A y D', () => {
  const historica = { semana: 3, maxSemana: 5 };
  assert.equal(crearReglasIntermedia(ctxPI({ ...historica, permiso: 'D' })).puedeEditarCelda(celda()), true);
  assert.equal(crearReglasIntermedia(ctxPI({ ...historica, permiso: 'R' })).puedeEditarCelda(celda()), false);
});

test('I3: una fila cabecera no edita ninguna columna', () => {
  const reglas = crearReglasIntermedia(ctxPI());
  assert.equal(reglas.puedeEditarCelda(celda({ esHeader: true })), false);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: '__shared_selected', esHeader: true })), false);
});

test('I4: una restricción sin responsable queda bloqueada', () => {
  const reglas = crearReglasIntermedia(ctxPI());
  assert.equal(reglas.puedeEditarCelda(celda({ prop: 'D_y_E', esRestriccion: true, tieneResponsable: true })), true);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: 'D_y_E', esRestriccion: true, tieneResponsable: false })), false);
});

test('I5: __shared_selected ignora rol y fase en fila normal', () => {
  const reglas = crearReglasIntermedia(ctxPI({ permiso: 'V', semanalConfirmada: 1 }));
  assert.equal(reglas.puedeEditarCelda(celda()), false);
  assert.equal(reglas.puedeEditarCelda(celda({ prop: '__shared_selected' })), true);
});
