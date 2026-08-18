import { test } from 'node:test';
import assert from 'node:assert/strict';
import { separarCapitulo } from '../../public/js/modules/aia_ui/card-title.js';

test('separa el capitulo envuelto en small', () => {
  const r = separarCapitulo('Contrato estudio de suelos <small>[Capítulo: CONTRATOS, PRECONSTRUCCIÓN DA PORTO]</small>');
  assert.equal(r.titulo, 'Contrato estudio de suelos');
  assert.equal(r.capitulo, 'CONTRATOS, PRECONSTRUCCIÓN DA PORTO');
});

test('separa el capitulo sin small y con espacios', () => {
  const r = separarCapitulo('Excavación manual  [ Capítulo :  PRELIMINARES ] ');
  assert.equal(r.titulo, 'Excavación manual');
  assert.equal(r.capitulo, 'PRELIMINARES');
});

test('acepta Capitulo sin tilde', () => {
  assert.equal(separarCapitulo('Muro [Capitulo: ESTRUCTURA]').capitulo, 'ESTRUCTURA');
});

test('sin capitulo devuelve null y el titulo intacto', () => {
  const r = separarCapitulo('Vaciado de placa');
  assert.equal(r.titulo, 'Vaciado de placa');
  assert.equal(r.capitulo, null);
});

test('entrada vacia o nula no revienta', () => {
  assert.deepEqual(separarCapitulo(''), { titulo: '', capitulo: null });
  assert.deepEqual(separarCapitulo(null), { titulo: '', capitulo: null });
});

test('quita etiquetas HTML del titulo, como hace getPlainActivityLabel', () => {
  assert.equal(separarCapitulo('<b>Muro</b> en bloque').titulo, 'Muro en bloque');
});
