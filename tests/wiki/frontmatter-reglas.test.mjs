import { test } from 'node:test';
import assert from 'node:assert/strict';
import { deducirAreas, deducirEstado, deducirResumen, deducirTags, deducirTipo, fechaDelNombre }
  from '../../scripts/wiki-frontmatter.reglas.mjs';
import { AREAS, TAGS, TIPOS } from '../../scripts/wiki-esquema.mjs';

test('deducirTipo reparte las carpetas conocidas', () => {
  assert.equal(deducirTipo('docs/superpowers/specs/2026-08-18-x.md'), 'spec');
  assert.equal(deducirTipo('docs/archive/superpowers/plans/x.md'), 'plan');
  assert.equal(deducirTipo('goals/x/plan.md'), 'plan');
  assert.equal(deducirTipo('goals/x/goal.md'), 'goal-doc');
  assert.equal(deducirTipo('goals/x/facts.md'), 'evidencia');
  assert.equal(deducirTipo('goals/x/validation-log.md'), 'evidencia');
  assert.equal(deducirTipo('goals/x/briefs/b.md'), 'goal-doc');
  assert.equal(deducirTipo('goals/x/reports/task-9.md'), 'reporte');
  assert.equal(deducirTipo('docs/reportes/x.md'), 'reporte');
  assert.equal(deducirTipo('docs/design-system/contracts/x.md'), 'contrato');
  assert.equal(deducirTipo('docs/design-system/runtime-measurements/x.md'), 'evidencia');
  assert.equal(deducirTipo('docs/pdc-v2.md'), 'guia');
  assert.equal(deducirTipo('decisiones/wiki-t1-ejecutor.md'), 'reporte');
});

test('los contratos de la raíz se nombran uno a uno, no por patrón', () => {
  assert.equal(deducirTipo('AGENTS.md'), 'contrato');
  assert.equal(deducirTipo('CLAUDE.md'), 'contrato');
  assert.equal(deducirTipo('DESIGN.md'), 'contrato');
  assert.equal(deducirTipo('GLOSARIO.md'), 'biblia');
  assert.equal(deducirTipo('README.md'), 'guia');
});

test('todo tipo deducible está en el vocabulario cerrado', () => {
  for (const r of ['AGENTS.md', 'README.md', 'docs/x.md', 'goals/x/goal.md',
    'docs/superpowers/specs/x.md', 'docs/superpowers/plans/x.md', 'decisiones/x.md',
    'docs/reportes/x.md', 'docs/design-system/manifests/x.md']) {
    assert.ok(TIPOS.has(deducirTipo(r)), `${r} → ${deducirTipo(r)}`);
  }
});

test('deducirAreas usa la carpeta y afina con el nombre', () => {
  assert.deepEqual(deducirAreas('docs/design-system/tokens.md'), ['design-system']);
  assert.deepEqual(deducirAreas('docs/flujos/cascada.md'), ['lps']);
  assert.deepEqual(deducirAreas('goals/pdc-revision-ux/goal.md'), ['pdc']);
});

test('el área de carpeta cede ante la que dice el nombre', () => {
  // `proceso` es el cajón de sastre de goals/ y docs/superpowers/: si el nombre dice `pdc`,
  // etiquetar las dos deja la nota en un filtro donde no aporta nada.
  const a = deducirAreas('docs/superpowers/specs/2026-08-01-pdc-paquetes.md');
  assert.deepEqual(a, ['pdc']);
});

test('deducirAreas devuelve vacío antes que inventar', () => {
  assert.deepEqual(deducirAreas('docs/algo-sin-pistas.md'), []);
});

test('toda área deducida está entre las trece', () => {
  for (const r of ['docs/design-system/x.md', 'goals/pdc-x/goal.md', 'docs/flujos/x.md',
    'docs/bi/x.md', 'decisiones/x.md', 'docs/rbac-y-rutas.md', 'docs/docker-compose.md']) {
    for (const a of deducirAreas(r)) assert.ok(AREAS.has(a), `${r} → ${a}`);
  }
});

test('deducirTags marca archivo, generado y lo que declara la raíz', () => {
  assert.deepEqual(deducirTags('docs/archive/superpowers/plans/x.md'), ['archivo']);
  assert.deepEqual(deducirTags('AGENTS.md'), ['leer-antes-de-tocar']);
  assert.deepEqual(deducirTags('docs/x.md', 'hola\n<!-- generado:inicio -->\n'), ['generado']);
  assert.deepEqual(deducirTags('docs/x.md'), []);
});

test('todo tag deducido está en el vocabulario cerrado', () => {
  for (const r of ['AGENTS.md', 'DESIGN.md', 'docs/archive/x.md', 'decisiones/x.md', 'docs/x.md']) {
    for (const t of deducirTags(r, '<!-- generado:inicio -->')) assert.ok(TAGS.has(t), `${r} → ${t}`);
  }
});

test('deducirEstado solo distingue lo archivado; nunca deroga', () => {
  assert.equal(deducirEstado('docs/archive/x.md'), 'cerrado');
  assert.equal(deducirEstado('docs/x.md'), 'vigente');
});

test('fechaDelNombre lee el prefijo ISO y nada más', () => {
  assert.equal(fechaDelNombre('docs/superpowers/specs/2026-08-18-wiki.md'), '2026-08-18');
  assert.equal(fechaDelNombre('docs/pdc-v2.md'), '');
  assert.equal(fechaDelNombre('docs/archive/20260601_pi_x.md'), '');
});

test('deducirResumen toma el párrafo que sigue al H1', () => {
  assert.equal(deducirResumen('# Título\n\nLa primera frase.\nY su continuación.\n\nOtra cosa.'),
    'La primera frase. Y su continuación.');
});

test('deducirResumen se calla si tras el H1 viene otro encabezado o una lista', () => {
  assert.equal(deducirResumen('# Título\n\n## Sección\n\nProsa de más abajo.'), '');
  assert.equal(deducirResumen('# Título\n\n- un punto\n- otro'), '');
  assert.equal(deducirResumen('# Título\n\n| a | b |\n|---|---|'), '');
  assert.equal(deducirResumen('sin encabezado ninguno'), '');
});

test('deducirResumen ignora frontmatter, comentarios HTML y bloques de código', () => {
  assert.equal(deducirResumen('---\ntipo: guia\n---\n# T\n\n<!-- nota -->\nLa prosa.'), 'La prosa.');
  assert.equal(deducirResumen('# T\n\n<!-- solo un comentario -->\n'), '');
});

test('deducirResumen limpia markdown y recorta por palabra', () => {
  assert.equal(deducirResumen('# T\n\nVer **esto** y [[aquello]] y [un enlace](http://x).'),
    'Ver esto y aquello y un enlace.');
  const largo = deducirResumen(`# T\n\n${'palabra '.repeat(40)}`, 40);
  assert.ok(largo.length <= 41, largo);
  assert.ok(largo.endsWith('…'));
});

// ── Cómo se escribe el bloque ────────────────────────────────────────────────────────────────
import { ORDEN, aplicar, faltantes, render } from '../../scripts/wiki-frontmatter.reglas.mjs';
import { bloqueFrontmatter } from '../../scripts/wiki-esquema.mjs';

const PROP = { capa: 'fuente', tipo: 'guia', estado: 'vigente', fecha: '2026-08-18', fuente: 'docs/x.md', resumen: 'Algo' };

test('sin frontmatter faltan todas las claves de la propuesta, en el orden del esquema', () => {
  assert.deepEqual(faltantes(null, PROP), ['capa', 'tipo', 'estado', 'fecha', 'fuente', 'resumen']);
  assert.ok(ORDEN.indexOf('capa') < ORDEN.indexOf('resumen'));
});

test('una clave ya puesta no se repite, y una vacía cuenta como puesta', () => {
  assert.deepEqual(faltantes('tipo: contrato', PROP), ['capa', 'estado', 'fecha', 'fuente', 'resumen']);
  assert.deepEqual(faltantes('resumen:', PROP), ['capa', 'tipo', 'estado', 'fecha', 'fuente']);
});

test('aplicar antepone un bloque nuevo sin tocar el cuerpo', () => {
  const cuerpo = '# Título\n\nEl cuerpo.\n';
  const salida = aplicar(cuerpo, PROP, faltantes(null, PROP));
  assert.ok(salida.endsWith(cuerpo));
  assert.equal(bloqueFrontmatter(salida), render(PROP, faltantes(null, PROP)));
});

test('aplicar fusiona en un frontmatter ajeno sin reescribirlo ni reordenarlo', () => {
  // El caso `DESIGN.md`: su bloque lo leen otras herramientas.
  const orig = '---\nname: Last Planner AIA\ncolors:\n  corporate: "#1a5633"\n---\n\n# T\n\nCuerpo.\n';
  const salida = aplicar(orig, PROP, faltantes(bloqueFrontmatter(orig), PROP));
  assert.ok(salida.includes('name: Last Planner AIA'), 'conserva la clave ajena');
  assert.ok(salida.includes('  corporate: "#1a5633"'), 'conserva el anidado ajeno');
  assert.ok(salida.includes('capa: fuente'), 'añade las del esquema');
  assert.ok(salida.endsWith('# T\n\nCuerpo.\n'), 'no toca el cuerpo');
});

test('el backfill es idempotente: la segunda pasada no cambia nada', () => {
  const orig = '# Título\n\nEl cuerpo.\n';
  const una = aplicar(orig, PROP, faltantes(null, PROP));
  const dos = aplicar(una, PROP, faltantes(bloqueFrontmatter(una), PROP));
  assert.equal(dos, una);
});

test('aplicar con cero claves devuelve el texto tal cual', () => {
  assert.equal(aplicar('# T\n', PROP, []), '# T\n');
});
