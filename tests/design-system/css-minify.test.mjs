import assert from 'node:assert/strict';
import test from 'node:test';
import { minificar, quitarComentarios } from '../../scripts/css-minify.mjs';

// Los casos que romperían una expresión regular ingenua. No son hipotéticos:
// `content` con barras y `url(data:...)` existen en las hojas de este repo, y un
// comentario mal cerrado ya dejó ocho carriles en verde con el filete apagado
// (memoria/trampas/guard-de-texto-no-ve-el-parseo.md).

test('quita un comentario normal', () => {
  assert.equal(quitarComentarios('a{color:red} /* hola */ b{color:blue}').includes('hola'), false);
  assert.match(minificar('a{color:red}/* x */\nb{color:blue}'), /a\{color:red\}\s*b\{color:blue\}/);
});

test('NO toca una apertura de comentario dentro de una cadena', () => {
  const css = 'a::before{content:"/*"}b{color:red}';
  assert.equal(minificar(css), css);
});

test('NO toca un cierre de comentario dentro de una cadena', () => {
  const css = "a::after{content:'*/'}b{color:red}";
  assert.equal(minificar(css), css);
});

test('NO parte un url(data:...) que contenga barras y asteriscos', () => {
  const css = 'a{background:url(data:image/svg+xml;utf8,<svg><path d="M0 0l/*10"/></svg>)}b{color:red}';
  assert.equal(minificar(css), css);
});

test('conserva el resto de la hoja si un comentario queda sin cerrar', () => {
  // Truncar aquí borraría en silencio todo lo que viene después, que es
  // exactamente el defecto que este proyecto ya pagó una vez.
  const css = 'a{color:red}/* sin cerrar\nb{color:blue}';
  const salida = minificar(css);
  assert.ok(salida.includes('b{color:blue}'), 'la regla posterior no puede desaparecer');
});

test('preserva las at-rules y el orden de capas', () => {
  const css = '@layer reset, components;\n/* c */\n@import url("/css/x.css?v=1") layer(vendor);\na{color:red}';
  const salida = minificar(css);
  assert.ok(salida.startsWith('@layer reset, components;'));
  assert.ok(salida.includes('@import url("/css/x.css?v=1") layer(vendor);'));
});

test('no cambia el numero de llaves ni de punto y coma', () => {
  // Invariante barato y fuerte: si el despojado se comiera una regla, esto salta.
  const css = '/* a */ .x{color:red;background:blue}/* b */ .y{margin:0}';
  const cuenta = (s, c) => s.split(c).length - 1;
  const salida = minificar(css);
  for (const c of ['{', '}', ';']) assert.equal(cuenta(salida, c), cuenta(css, c), `cambio el numero de "${c}"`);
});
