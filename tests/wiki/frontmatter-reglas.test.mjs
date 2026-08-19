import { test } from 'node:test';
import assert from 'node:assert/strict';
import { deducirAreas, deducirEstado, deducirResumen, deducirTags, deducirTipo, fechaDelNombre,
  resumenDeEtiqueta, resumenDeSeccion, resumenDelTitulo, resumenEnCascada }
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

// ── La cascada de cuatro respaldos ───────────────────────────────────────────────────────────

// El caso que destapó todo: un plan de `writing-plans` abre con una cita para agentes, y el
// respaldo 1 se para ahí — justo antes del `**Goal:**`, que es el resumen que se buscaba.
const PLAN_REAL = `# Colapsado del sidebar como primitiva canónica — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: use superpowers:executing-plans.

**Goal:** Mover el pulido del rail colapsado del scope del shell al componente canónico.
`;

test('respaldo 2: la etiqueta en negrita rescata lo que el párrafo no alcanza', () => {
  assert.equal(deducirResumen(PLAN_REAL), '');
  assert.equal(resumenDeEtiqueta(PLAN_REAL),
    'Mover el pulido del rail colapsado del scope del shell al componente canónico.');
  assert.deepEqual(resumenEnCascada(PLAN_REAL), {
    texto: 'Mover el pulido del rail colapsado del scope del shell al componente canónico.',
    origen: 'etiqueta',
  });
});

test('respaldo 2 reconoce las etiquetas que este repo usa de verdad', () => {
  for (const k of ['Goal', 'Objetivo', 'Meta', 'Problema', 'Resumen']) {
    assert.equal(resumenDeEtiqueta(`# T\n\n**${k}:** La tesis.`), 'La tesis.', k);
  }
  assert.equal(resumenDeEtiqueta('# T\n\n**Decisión del usuario D-5:** Lo que pidió.'), 'Lo que pidió.');
  assert.equal(resumenDeEtiqueta('# T\n\n**Nota al pie:** irrelevante.'), '');
});

test('respaldo 3: el párrafo bajo `## Objetivo` de un goal.md', () => {
  const goal = '# Frente: x\n\n## Fase del plan\nPlan: -\n\n## Objetivo\nDevolver el workflow a verde.\n';
  assert.equal(resumenDeSeccion(goal), 'Devolver el workflow a verde.');
  assert.equal(resumenEnCascada(goal).origen, 'seccion');
});

test('respaldo 3 no se traga una sección que empieza por lista o encabezado', () => {
  assert.equal(resumenDeSeccion('# T\n\n## Objetivo\n- un punto\n'), '');
  assert.equal(resumenDeSeccion('# T\n\n## Objetivo\n\n### Sub\n'), '');
});

test('respaldo 4: el título, solo si dice algo más que dos palabras', () => {
  assert.equal(resumenDelTitulo('# Sidebar canónico del laboratorio\n'), 'Sidebar canónico del laboratorio');
  assert.equal(resumenDelTitulo('# Notas\n'), '');
  assert.equal(resumenDelTitulo('# Dos palabras\n'), '');
});

test('la cascada respeta el orden: gana el más informativo que responda', () => {
  const todo = '# Un título largo de verdad\n\nEl párrafo.\n\n**Goal:** La tesis.\n\n## Objetivo\nOtra cosa.\n';
  assert.deepEqual(resumenEnCascada(todo), { texto: 'El párrafo.', origen: 'parrafo' });
  const sinParrafo = '# Un título largo de verdad\n\n> cita\n\n**Goal:** La tesis.\n';
  assert.equal(resumenEnCascada(sinParrafo).origen, 'etiqueta');
  const soloTitulo = '# Un título largo de verdad\n\n- solo una lista\n';
  assert.equal(resumenEnCascada(soloTitulo).origen, 'titulo');
});

test('sin nada de donde sacarlo, la cascada deja un hueco visible y lo dice', () => {
  assert.deepEqual(resumenEnCascada('# Notas\n\n- a\n- b\n'), { texto: '', origen: 'ninguno' });
  assert.deepEqual(resumenEnCascada('sin encabezado'), { texto: '', origen: 'ninguno' });
});

test('los cuatro respaldos limpian markdown y recortan igual', () => {
  assert.equal(resumenDeEtiqueta('# T\n\n**Goal:** Ver **esto** y [[aquello]].'), 'Ver esto y aquello.');
  const largo = resumenDelTitulo(`# ${'palabra '.repeat(40)}`, 40);
  assert.ok(largo.length <= 41 && largo.endsWith('…'), largo);
});

test('respaldo 1 no toma una cabecera de metadatos por prosa', () => {
  // `ROADMAP.md` resumía «Fecha: 2026-03-02»; las specs, una frase cortada por la mitad.
  assert.equal(deducirResumen('# Roadmap\n\n**Fecha:** 2026-03-02\n'), '');
  const spec = '# Wiki v2\n\n**Fecha:** 2026-08-18 · **Decisión del usuario:** replantear la wiki entera.\n';
  assert.equal(deducirResumen(spec), '');
  assert.deepEqual(resumenEnCascada(spec), { texto: 'replantear la wiki entera.', origen: 'etiqueta' });
});

test('una frase que solo empieza con negrita sigue siendo prosa', () => {
  // La guarda mira `**Etiqueta:**`, no cualquier negrita: si no, se comería medio repo.
  assert.equal(deducirResumen('# T\n\n**Esto** es prosa de verdad.'), 'Esto es prosa de verdad.');
});

test('respaldo 1 tampoco toma una linea de metadato sin negritas', () => {
  // `ROADMAP.md`: `# Título` / `Fecha: 2026-03-02` / `## Objetivo Estratégico` / la prosa buena.
  const roadmap = '# ROADMAP - Gobernanza\n\nFecha: 2026-03-02\n\n## Objetivo Estratégico\n\nVisión unificada del plan.\n';
  assert.equal(deducirResumen(roadmap), '');
  assert.deepEqual(resumenEnCascada(roadmap), { texto: 'Visión unificada del plan.', origen: 'seccion' });
});

test('una frase larga con dos puntos sigue siendo prosa', () => {
  const t = '# T\n\nLa regla es esta: nada de lo que hay en la wiki es contrato, y gana el repo.\n';
  assert.equal(deducirResumen(t), 'La regla es esta: nada de lo que hay en la wiki es contrato, y gana el repo.');
});

test('respaldo 2 sigue hasta el final del parrafo, no de la linea', () => {
  const spec = '# Wiki v2\n\n**Fecha:** 2026-08-18 · **Decisión del usuario:** replantear toda la wiki\nsin perder la metodología.\n\nOtro párrafo.\n';
  assert.equal(resumenDeEtiqueta(spec), 'replantear toda la wiki sin perder la metodología.');
});
