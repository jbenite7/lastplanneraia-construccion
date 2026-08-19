import { test } from 'node:test';
import assert from 'node:assert/strict';
import { AREAS, TAGS, TIPOS, TIPOS_FUENTE, TIPOS_WIKI, bloqueFrontmatter, campo, deducirCapa,
  lista, revisarFrontmatter } from '../../scripts/wiki-esquema.mjs';

const fm = (o) => Object.entries(o).map(([k, v]) => `${k}: ${v}`).join('\n');
const BUENO = { tipo: 'decision', estado: 'vigente', fecha: '2026-08-18', resumen: 'Algo' };

test('los nueve tipos de wiki y los ocho de fuente conviven en TIPOS', () => {
  assert.equal(TIPOS_WIKI.size, 9);
  assert.equal(TIPOS_FUENTE.size, 8);
  assert.equal(TIPOS.size, 17);
  for (const t of [...TIPOS_WIKI, ...TIPOS_FUENTE]) assert.ok(TIPOS.has(t), t);
});

test('las trece áreas no cambian en v2', () => {
  assert.equal(AREAS.size, 13);
});

test('el vocabulario de tags es el cerrado de la spec', () => {
  assert.deepEqual([...TAGS].sort(), ['archivo', 'dashboard', 'generado', 'leer-antes-de-tocar',
    'pendiente', 'plantilla', 'trampa']);
});

test('`moc` ya no es un tag: `tipo: mapa` significa MOC', () => {
  // Salió el 2026-08-19. Un mapa de área es una CLASE de página, con estructura propia y fija;
  // las clases viven en `tipo`. El tag habría existido solo para parchear una página mal tipada.
  assert.ok(!TAGS.has('moc'));
  assert.equal(revisarFrontmatter('tags: [moc]', { rel: 'memoria/x.md' }).length, 1);
});

test('deducirCapa reparte las tres capas por ruta', () => {
  assert.equal(deducirCapa('memoria/index.md'), 'wiki');
  assert.equal(deducirCapa('memoria/mapas/pdc.md'), 'wiki');
  assert.equal(deducirCapa('docs/wiki-operacion.md'), 'esquema');
  assert.equal(deducirCapa('docs/pdc-v2.md'), 'fuente');
  assert.equal(deducirCapa('AGENTS.md'), 'fuente');
  assert.equal(deducirCapa('goals/x/goal.md'), 'fuente');
});

test('deducirCapa no confunde una carpeta que empieza igual', () => {
  assert.equal(deducirCapa('memorias-del-equipo/x.md'), 'fuente');
});

test('bloqueFrontmatter solo acepta el bloque que abre el archivo', () => {
  assert.equal(bloqueFrontmatter('---\ntipo: log\n---\ncuerpo'), 'tipo: log');
  assert.equal(bloqueFrontmatter('# Título\n\n---\n\nUna regla horizontal'), null);
  assert.equal(bloqueFrontmatter('sin nada'), null);
});

test('bloqueFrontmatter tolera un archivo que es solo frontmatter', () => {
  assert.equal(bloqueFrontmatter('---\ntipo: log\n---'), 'tipo: log');
});

test('campo devuelve undefined cuando el campo está vacío', () => {
  assert.equal(campo('tipo: decision', 'tipo'), 'decision');
  assert.equal(campo('tipo:', 'tipo'), undefined);
  assert.equal(campo('estado: vigente', 'tipo'), undefined);
});

test('lista lee las dos formas de YAML y quita comillas', () => {
  assert.deepEqual(lista('areas: [pdc, rbac]', 'areas'), ['pdc', 'rbac']);
  assert.deepEqual(lista('tags: ["moc", \'archivo\']', 'tags'), ['moc', 'archivo']);
  assert.deepEqual(lista('areas:\n  - pdc\n  - rbac\n', 'areas'), ['pdc', 'rbac']);
  assert.deepEqual(lista('tipo: decision', 'areas'), []);
});

test('frontmatter correcto no da hallazgos', () => {
  assert.deepEqual(revisarFrontmatter(fm(BUENO), { rel: 'memoria/x.md', obligatorios: Object.keys(BUENO) }), []);
});

test('exige los campos obligatorios que le pidan, y solo esos', () => {
  const fallos = revisarFrontmatter('tipo: decision', { rel: 'memoria/x.md', obligatorios: ['tipo', 'resumen'] });
  assert.equal(fallos.length, 1);
  assert.equal(fallos[0].campo, 'resumen');
});

test('sin obligatorios, un frontmatter vacío pasa: solo se valida lo que está', () => {
  assert.deepEqual(revisarFrontmatter('', { rel: 'docs/x.md' }), []);
});

test('un tipo de fuente es válido también en una página de wiki y al revés', () => {
  // El esquema no ata `tipo` a `capa`: eso lo decide quien escribe, no el vocabulario.
  assert.deepEqual(revisarFrontmatter('tipo: spec', { rel: 'memoria/x.md' }), []);
  assert.deepEqual(revisarFrontmatter('tipo: trampa', { rel: 'docs/x.md' }), []);
});

test('rechaza tipo, estado, fecha, área y tag fuera de sus listas', () => {
  const fallos = revisarFrontmatter(fm({
    tipo: 'inventado', estado: 'quizas', fecha: '18/08/2026',
    areas: '[pdc, marketing]', tags: '[brillante]',
  }), { rel: 'memoria/x.md' });
  assert.deepEqual(fallos.map((f) => f.campo).sort(), ['areas', 'estado', 'fecha', 'tags', 'tipo']);
});

test('capa ausente no es un hallazgo: v2 es retrocompatible', () => {
  assert.deepEqual(revisarFrontmatter(fm(BUENO), { rel: 'memoria/x.md' }), []);
});

test('capa presente tiene que ser una de las tres', () => {
  const fallos = revisarFrontmatter('capa: intermedia', { rel: 'memoria/x.md' });
  assert.equal(fallos.length, 1);
  assert.match(fallos[0].detalle, /capa desconocida/);
});

test('capa presente tiene que coincidir con la que implica la ruta', () => {
  assert.deepEqual(revisarFrontmatter('capa: wiki', { rel: 'memoria/x.md' }), []);
  const fallos = revisarFrontmatter('capa: fuente', { rel: 'memoria/x.md' });
  assert.equal(fallos.length, 1);
  assert.match(fallos[0].detalle, /dice 'fuente' y su ruta implica 'wiki'/);
});

test('sin ruta, capa se valida contra el vocabulario pero no contra la ruta', () => {
  assert.deepEqual(revisarFrontmatter('capa: fuente'), []);
});

// ── Moldes (plantillas) ──────────────────────────────────────────────────────────────────────

test('un molde no tiene que rellenar ningún campo obligatorio', () => {
  assert.deepEqual(revisarFrontmatter('tipo: decision\nfecha:\nresumen:',
    { rel: 'memoria/templates/decision.md', molde: true, obligatorios: ['tipo', 'estado', 'fecha', 'resumen'] }), []);
});

test('un molde puede llevar el marcador de fecha de Obsidian', () => {
  assert.deepEqual(revisarFrontmatter('fecha: {{date:YYYY-MM-DD}}',
    { rel: 'memoria/templates/x.md', molde: true }), []);
  // La misma fecha en una página de verdad sigue siendo un hallazgo.
  assert.equal(revisarFrontmatter('fecha: {{date:YYYY-MM-DD}}', { rel: 'memoria/x.md' }).length, 1);
});

test('un molde declara la capa del documento que produce, no la suya', () => {
  // La plantilla de una spec vive en memoria/ y declara `capa: fuente`.
  assert.deepEqual(revisarFrontmatter('capa: fuente',
    { rel: 'memoria/templates/spec.md', molde: true }), []);
});

test('la exención del molde es estrecha: el vocabulario se le sigue exigiendo', () => {
  const fallos = revisarFrontmatter('tipo: inventado\nestado: quizas\nareas: [marketing]\ntags: [plantilla, brillante]\ncapa: intermedia',
    { rel: 'memoria/templates/x.md', molde: true });
  assert.deepEqual(fallos.map((f) => f.campo).sort(), ['areas', 'capa', 'estado', 'tags', 'tipo']);
});

test('un campo vacio no se traga la linea siguiente', () => {
  // Devolvía `areas: [design-system]` como si fuera la fecha, y el lint lo reportaba así.
  const fm = 'tipo: guia\nfecha: \nareas: [design-system]\nresumen: Algo';
  assert.equal(campo(fm, 'fecha'), undefined);
  assert.equal(campo(fm, 'tipo'), 'guia');
  assert.equal(campo(fm, 'resumen'), 'Algo');
  assert.deepEqual(revisarFrontmatter(fm, { rel: 'docs/x.md' }), []);
});
