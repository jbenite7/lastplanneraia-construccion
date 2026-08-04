import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ultimoPase, contarCommits, estadoVeracidad, mensajeVeracidad, UMBRAL_COMMITS }
  from '../../scripts/wiki-veracidad.mjs';

const LOG_SIN_PASE = [
  '- 2026-08-02 · ingest · Se funda la wiki · [[index]]',
  '- 2026-08-03 · lint · Primera pasada · [[index]]',
].join('\n');

const LOG_CON_PASES = [
  '- 2026-08-01 · veracidad · áreas revisadas: pdc · 3 páginas · [[pdc]]',
  '- 2026-08-02 · ingest · Algo · [[index]]',
  '- 2026-08-03 · veracidad · áreas revisadas: rbac · 5 páginas · [[rbac-y-rutas]]',
].join('\n');

test('sin línea veracidad devuelve null', () => {
  assert.equal(ultimoPase(LOG_SIN_PASE), null);
});

test('toma la última línea veracidad, no la primera', () => {
  assert.equal(ultimoPase(LOG_CON_PASES), '2026-08-03');
});

test('no confunde la palabra veracidad dentro del asunto de otra operación', () => {
  const log = '- 2026-08-03 · ingest · Nota sobre veracidad de los tokens · [[index]]';
  assert.equal(ultimoPase(log), null);
});

test('contarCommits cuenta las líneas que devuelve git', () => {
  const ejecutor = () => 'abc\ndef\nghi\n';
  assert.equal(contarCommits('2026-08-01', ejecutor), 3);
});

test('contarCommits devuelve 0 con salida vacía', () => {
  assert.equal(contarCommits('2026-08-01', () => '\n'), 0);
});

test('contarCommits pasa la fecha y las rutas a git, y excluye memoria/', () => {
  let recibido = null;
  contarCommits('2026-08-01', (args) => { recibido = args; return ''; });
  assert.ok(recibido.includes('--since=2026-08-01'));
  assert.ok(recibido.includes('--'), 'debe separar rutas con --');
  assert.ok(recibido.includes('src/'));
  assert.ok(recibido.includes('AGENTS.md'));
  assert.ok(!recibido.some((a) => a.includes('memoria')),
    'memoria/ no debe aparecer entre las rutas contadas');
});

test('estado no sembrado: informativo, nunca excedido', () => {
  const e = estadoVeracidad(LOG_SIN_PASE, () => 'a\n'.repeat(500));
  assert.equal(e.sembrado, false);
  assert.equal(e.excedido, false);
});

test('estado sembrado por debajo del umbral no está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => 'a\n'.repeat(UMBRAL_COMMITS));
  assert.equal(e.sembrado, true);
  assert.equal(e.desde, '2026-08-03');
  assert.equal(e.commits, UMBRAL_COMMITS);
  assert.equal(e.excedido, false, 'el umbral es «más de», no «igual o más»');
});

test('estado sembrado por encima del umbral está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => 'a\n'.repeat(UMBRAL_COMMITS + 1));
  assert.equal(e.excedido, true);
});

test('sin sembrar: aviso informativo, ningún hallazgo', () => {
  const m = mensajeVeracidad({ sembrado: false, desde: null, commits: 0, excedido: false });
  assert.equal(m.hallazgo, null);
  assert.match(m.aviso, /veracidad/i);
});

test('excedido: hallazgo con el conteo, la fecha y el umbral dentro', () => {
  const m = mensajeVeracidad({ sembrado: true, desde: '2026-08-03', commits: 57, excedido: true });
  assert.ok(m.hallazgo.includes('57'));
  assert.ok(m.hallazgo.includes('2026-08-03'));
  assert.ok(m.hallazgo.includes(String(UMBRAL_COMMITS)));
});

test('sembrado y por debajo: ningún hallazgo', () => {
  const m = mensajeVeracidad({ sembrado: true, desde: '2026-08-03', commits: 5, excedido: false });
  assert.equal(m.hallazgo, null);
});
