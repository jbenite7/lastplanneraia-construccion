import { test } from 'node:test';
import assert from 'node:assert/strict';
import { debeSerFlotante, UMBRAL_FLOTANTE } from '../../public/js/modules/aia_ui/shell-drawer.js';

test('el umbral por defecto es 1180 y el borde cae del lado fijo', () => {
  assert.equal(UMBRAL_FLOTANTE, 1180);
  assert.equal(debeSerFlotante(1179), true);
  assert.equal(debeSerFlotante(1180), false);
  assert.equal(debeSerFlotante(390), true);
  assert.equal(debeSerFlotante(1440), false);
});

test('un ancho no numerico no vuelve flotante la navegacion', () => {
  assert.equal(debeSerFlotante(undefined), false);
  assert.equal(debeSerFlotante(NaN), false);
});
