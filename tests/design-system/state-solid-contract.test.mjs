// MIDE (desde 2026-08-20, replanteo direccion B): (1) que tokens.css declare
// exactamente los hex del catalogo `hues` del contrato para solid/solidText/row
// y los dos colores del filete; (2) WCAG computado DESDE el contrato:
// chip solido vs su texto >= 4.5:1, chip vs tinte de fila >= 3:1, y filete
// vs cada tinte de fila >= 3:1.
//
// Computado contra el contrato, no una declaracion contra si misma: los hex
// viven en dos sitios (state-semantics.json y tokens.css) y este guard se pone
// rojo si divergen o si un ajuste de hex rompe un ratio. El par mas justo del
// sistema es red: 4.67:1 — cualquier oscurecimiento del solido o aclarado del
// texto lo tumba, y ese es exactamente el caso que este archivo caza.
// El rojo del manual AIA (#e53935) NO se usa porque falla AA (4.01:1);
// procedencia: auditoria de marca + accesibilidad del 2026-08-20.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const contrato = JSON.parse(readFileSync(join(raiz, 'docs/design-system/state-semantics.json'), 'utf8'));
const tokensCss = readFileSync(join(raiz, 'public/css/tokens.css'), 'utf8');

const HUES = ['red', 'orange', 'amber', 'violet', 'teal', 'blue', 'green', 'neutral'];
const RAIL = { urgent: '#ff7a6e', attention: '#ffd23f', ready: '#7ee2a8' };

const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const ratio = (a, b) => {
  const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
  return (x + 0.05) / (y + 0.05);
};
const tokenValue = (name) => {
  const m = tokensCss.match(new RegExp(`${name}\\s*:\\s*(#[0-9a-fA-F]{6})`));
  assert.ok(m, `token ${name} no declarado como hex literal en tokens.css`);
  return m[1].toLowerCase();
};
const hueEntry = (hue) => {
  const e = (contrato.hues || []).find((h) => h.id === hue);
  assert.ok(e, `contrato sin entrada de hue ${hue}`);
  assert.ok(e.solid && e.solidText && e.row, `contrato sin solid/solidText/row para ${hue}`);
  return e;
};

test('tokens.css y el contrato declaran los mismos solidos, textos y tintes de fila', () => {
  for (const hue of HUES) {
    const e = hueEntry(hue);
    assert.equal(tokenValue(`--ds-state-solid-${hue}`), e.solid.toLowerCase(), `solid ${hue} diverge del contrato`);
    assert.equal(tokenValue(`--ds-state-solid-${hue}-text`), e.solidText.toLowerCase(), `solidText ${hue} diverge del contrato`);
    assert.equal(tokenValue(`--ds-state-row-${hue}`), e.row.toLowerCase(), `row ${hue} diverge del contrato`);
  }
  assert.equal(tokenValue('--ds-severity-rail-color-urgent'), RAIL.urgent, 'filete urgent diverge');
  assert.equal(tokenValue('--ds-severity-rail-color-attention'), RAIL.attention, 'filete attention diverge');
  assert.equal(tokenValue('--ds-severity-rail-color-ready'), RAIL.ready, 'filete ready diverge');
});

test('WCAG desde el contrato: chip >=4.5 con su texto, >=3 con su fila; filete >=3 sobre toda fila', () => {
  for (const hue of HUES) {
    const e = hueEntry(hue);
    const rTexto = ratio(e.solid, e.solidText);
    const rFila = ratio(e.solid, e.row);
    assert.ok(rTexto >= 4.5, `${hue}: chip vs su texto ${rTexto.toFixed(2)} < 4.5`);
    assert.ok(rFila >= 3, `${hue}: chip vs su fila ${rFila.toFixed(2)} < 3`);
    for (const nivel of Object.keys(RAIL)) {
      const rRail = ratio(RAIL[nivel], e.row);
      assert.ok(rRail >= 3, `filete ${nivel} vs fila ${hue}: ${rRail.toFixed(2)} < 3`);
    }
  }
});
