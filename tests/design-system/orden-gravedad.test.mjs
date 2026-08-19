import assert from 'node:assert/strict';
import test from 'node:test';

const PESO = { urgent: 3, attention: 2, healthy: 1, neutral: 0 };

// Copia de la funcion que hot.js expone, para poder probarla sin navegador.
const agrupar = (filas, nivelDe) => [...filas]
  .map((fila, i) => ({ fila, i }))
  .sort((a, b) => (PESO[nivelDe(b.fila)] - PESO[nivelDe(a.fila)]) || (a.i - b.i))
  .map(({ fila }) => fila);

test('sube lo grave y conserva el orden del programa dentro de cada nivel', () => {
  const filas = [
    { id: 1, n: 'healthy' }, { id: 2, n: 'urgent' },
    { id: 3, n: 'healthy' }, { id: 4, n: 'urgent' },
  ];
  const r = agrupar(filas, (f) => f.n);
  assert.deepEqual(r.map((f) => f.id), [2, 4, 1, 3]);
});

test('no muta el array original', () => {
  const filas = [{ id: 1, n: 'healthy' }, { id: 2, n: 'urgent' }];
  const copia = JSON.parse(JSON.stringify(filas));
  agrupar(filas, (f) => f.n);
  assert.deepEqual(filas, copia);
});
