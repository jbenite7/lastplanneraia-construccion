import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { UMBRAL_CARDS, shouldRenderCards } from '../../public/js/modules/aia_ui/view-switch.js';

/**
 * El hallazgo TB-1 (2026-08-18) no fue un fallo de ninguna de las dos piezas:
 * fue que nadie las midio juntas. `tablet-viewport-scale.js` inflaba el ancho
 * CSS un 17,6 % y `view-switch.js` cortaba contra ese ancho inflado, asi que un
 * iPad Pro de 1024 px reportaba 1204 y recibia la grilla. Esta prueba existe
 * para que las dos reglas no puedan volver a separarse en silencio.
 */

function cargarIife() {
  const fuente = readFileSync(new URL('../../public/js/tablet-viewport-scale.js', import.meta.url), 'utf8');
  const noop = () => {};
  const ventana = {
    screen: { width: 0, height: 0 },
    innerWidth: 0,
    addEventListener: noop,
    clearTimeout: noop,
    setTimeout: noop,
    matchMedia: () => ({ matches: false }),
    dispatchEvent: noop,
    CustomEvent: function CustomEvent() {},
    CSS: { supports: () => true },
    navigator: { userAgent: '', maxTouchPoints: 0, platform: '' },
    document: {
      readyState: 'complete',
      documentElement: { classList: { add: noop, remove: noop }, style: { removeProperty: noop } },
      body: { style: { removeProperty: noop } },
      querySelector: () => null,
      createElement: () => ({ setAttribute: noop, getAttribute: () => null }),
      head: { appendChild: noop },
      addEventListener: noop,
      createEvent: () => ({ initCustomEvent: noop }),
    },
  };
  ventana.window = ventana;
  vm.createContext(ventana);
  vm.runInContext(fuente, ventana);
  return ventana.AIATabletViewport;
}

test('el umbral del escalado es el mismo que el de las tarjetas, no una copia suelta', () => {
  const api = cargarIife();
  assert.equal(api.UMBRAL, UMBRAL_CARDS);
});

test('el viewport solo se escala donde hay grilla que encoger', () => {
  const { debeEscalarViewport } = cargarIife();
  assert.equal(debeEscalarViewport(768), false, 'iPad de 768 recibe tarjetas: escalar no hace caber nada');
  assert.equal(debeEscalarViewport(1024), false, 'iPad Pro: es el aparato para el que se hicieron las tarjetas');
  assert.equal(debeEscalarViewport(1180), true);
  assert.equal(debeEscalarViewport(1366), true, 'iPad Pro apaisado si ve grilla, y ahi el 0.85 hace caber columnas');
});

test('ningun ancho fisico recibe escalado y tarjetas a la vez — es la contradiccion que abrio TB-1', () => {
  const { debeEscalarViewport } = cargarIife();
  for (let ancho = 320; ancho <= 1600; ancho += 1) {
    const escala = debeEscalarViewport(ancho) ? 0.85 : 1;
    const anchoCss = Math.round(ancho / escala);
    assert.equal(
      shouldRenderCards(anchoCss),
      shouldRenderCards(ancho),
      `a ${ancho}px fisicos el ancho CSS (${anchoCss}) decide distinto que el fisico`,
    );
  }
});

test('una lectura imposible de la pantalla no dispara el escalado', () => {
  const { debeEscalarViewport } = cargarIife();
  assert.equal(debeEscalarViewport(undefined), false);
  assert.equal(debeEscalarViewport(NaN), false);
});
