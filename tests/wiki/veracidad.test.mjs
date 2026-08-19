import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ultimoPase, contarCommits, estadoVeracidad, mensajeVeracidad, UMBRAL_COMMITS,
  esSoloFrontmatter, partirCommits } from '../../scripts/wiki-veracidad.mjs';

// Salida realista de `git log --pretty=%H --name-only`: un sha, una línea en blanco y sus
// archivos. Los dobles la fabrican porque desde el 2026-08-19 el contador ya no cuenta líneas:
// mira qué tocó cada commit para poder descartar los de solo frontmatter.
const sha = (n) => n.toString(16).padStart(40, '0');
const salidaLog = (n, archivos = ['src/x.php']) =>
  Array.from({ length: n }, (_, i) => `${sha(i + 1)}\n\n${archivos.join('\n')}\n`).join('\n');

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

test('contarCommits cuenta los commits que devuelve git', () => {
  assert.equal(contarCommits('2026-08-01', () => salidaLog(3)), 3);
});

// Un merge que solo une no lista archivos bajo `--cc`; uno que resolvió un conflicto con
// contenido propio sí. Comprobado con un control positivo en un repo de juguete antes de
// apoyarse en ello: se creó un merge con resolución propia y otro limpio, y `--cc --name-only`
// solo listó el primero.
test('un merge que solo une no cuenta: su contenido ya está en los commits originales', () => {
  assert.equal(contarCommits('2026-08-01', () => `${sha(1)}\n\n${sha(2)}\n`), 0);
});

test('un merge que resolvió un conflicto SÍ cuenta: ese contenido no está en ningún otro sitio', () => {
  // Bajo `--cc`, listar archivos es precisamente la señal de que el merge aportó algo propio.
  const log = `${sha(1)}\n\n${sha(2)}\n\nsrc/x.php\n`;
  assert.equal(contarCommits('2026-08-01', () => log), 1);
});

test('contarCommits pide --cc, que es lo que hace distinguibles los dos merges', () => {
  let recibido = null;
  contarCommits('2026-08-01', (args) => { recibido = args; return ''; });
  assert.ok(recibido.includes('--cc'), 'sin --cc los dos tipos de merge son indistinguibles');
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
  const e = estadoVeracidad(LOG_SIN_PASE, () => salidaLog(500));
  assert.equal(e.sembrado, false);
  assert.equal(e.excedido, false);
});

test('estado sembrado por debajo del umbral no está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => salidaLog(UMBRAL_COMMITS));
  assert.equal(e.sembrado, true);
  assert.equal(e.desde, '2026-08-03');
  assert.equal(e.commits, UMBRAL_COMMITS);
  assert.equal(e.excedido, false, 'el umbral es «más de», no «igual o más»');
});

test('estado sembrado por encima del umbral está excedido', () => {
  const e = estadoVeracidad(LOG_CON_PASES, () => salidaLog(UMBRAL_COMMITS + 1));
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

// ── Los commits de solo frontmatter no son deriva de código ──────────────────────────────────
//
// El 2026-08-19 la alarma saltó con 69 commits y el código de producto tocado eran OCHO archivos:
// el resto era el backfill de la wiki v2, que puso frontmatter en 413 archivos de `docs/` sin
// cambiar una línea de contenido. La wiki disparaba su propia alarma por escribirse.

const DIFF_BACKFILL = `@@ -0,0 +1,8 @@
+---
+capa: fuente
+tipo: guia
+estado: vigente
+fecha: 2026-08-18
+areas: [pdc]
+resumen: Algo
+---
+`;

const DIFF_FUSION = `@@ -1,2 +1,5 @@
+capa: fuente
+tipo: contrato
+resumen: Algo
 ---
 name: Last Planner AIA`;

test('esSoloFrontmatter reconoce un bloque nuevo antepuesto', () => {
  assert.equal(esSoloFrontmatter(DIFF_BACKFILL), true);
});

test('esSoloFrontmatter reconoce una fusión en un frontmatter ajeno', () => {
  assert.equal(esSoloFrontmatter(DIFF_FUSION), true);
});

test('un cambio de cuerpo NO es solo frontmatter, aunque toque la cabecera', () => {
  assert.equal(esSoloFrontmatter(`${DIFF_BACKFILL}
@@ -40,0 +41 @@
+Una frase nueva en el cuerpo.`), false);
});

test('una lista de Markdown en las primeras líneas no se confunde con metadato', () => {
  // Cumple «todas las líneas parecen metadato» pero su hunk no empieza en la 1.
  assert.equal(esSoloFrontmatter('@@ -12,0 +13 @@\n+  - un punto de una lista'), false);
});

test('reescribir la cabecera con contenido real tampoco cuela', () => {
  // Cumple «el hunk empieza en la 1» pero mete una línea que no es metadato.
  assert.equal(esSoloFrontmatter('@@ -1,2 +1,3 @@\n+# Un título nuevo\n capa: wiki'), false);
});

test('ante la duda, cuenta: un diff vacío o ilegible no exime', () => {
  assert.equal(esSoloFrontmatter(''), false);
  assert.equal(esSoloFrontmatter('   \n'), false);
  assert.equal(esSoloFrontmatter('algo que no es un diff'), false);
});

test('partirCommits empareja cada sha con sus archivos', () => {
  const out = `${sha(1)}\n\ndocs/a.md\ndocs/b.md\n${sha(2)}\n\nsrc/x.php\n`;
  assert.deepEqual(partirCommits(out), [
    { sha: sha(1), archivos: ['docs/a.md', 'docs/b.md'] },
    { sha: sha(2), archivos: ['src/x.php'] },
  ]);
});

test('contarCommits descuenta el backfill y conserva el código', () => {
  // Dos commits: uno de puro frontmatter sobre .md, otro que toca PHP.
  const log = `${sha(1)}\n\ndocs/a.md\ndocs/b.md\n${sha(2)}\n\nsrc/x.php\n`;
  const ejecutor = (args) => (args[0] === 'log' ? log : DIFF_BACKFILL);
  assert.equal(contarCommits('2026-08-01', ejecutor), 1);
});

test('un commit que toca un .php no se inspecciona siquiera: cuenta', () => {
  const log = `${sha(1)}\n\nsrc/x.php\n`;
  let pidioDiff = false;
  const ejecutor = (args) => {
    if (args[0] === 'show') { pidioDiff = true; return DIFF_BACKFILL; }
    return log;
  };
  assert.equal(contarCommits('2026-08-01', ejecutor), 1);
  assert.equal(pidioDiff, false, 'no hace falta leer el diff si toca algo que no es prosa');
});

test('un commit de .md con cuerpo tocado sigue contando', () => {
  const log = `${sha(1)}\n\ndocs/a.md\n`;
  const cuerpo = '@@ -30,0 +31 @@\n+Una frase nueva.';
  const ejecutor = (args) => (args[0] === 'log' ? log : cuerpo);
  assert.equal(contarCommits('2026-08-01', ejecutor), 1);
});

// ── Autoridad contra intención ───────────────────────────────────────────────────────────────
// Decidido el 2026-08-19 tras medir 404 commits: 232 tocaban código, **118 tocaban un documento
// con autoridad** y solo 54 eran pura intención. Excluir «la prosa» en bloque habría silenciado
// esos 118, que sí pueden volver falsa una página de la wiki.

import { TIPOS_CON_AUTORIDAD, mandaSobreElCodigo } from '../../scripts/wiki-veracidad.mjs';

const tipos = (mapa) => (f) => (f in mapa ? mapa[f] : null);

test('los tres tipos con autoridad, y solo esos tres', () => {
  assert.deepEqual([...TIPOS_CON_AUTORIDAD].sort(), ['biblia', 'contrato', 'guia']);
});

test('una guía manda: si cambia, la página que la cita puede quedar falsa', () => {
  assert.equal(mandaSobreElCodigo(['docs/pdc-v2.md'], tipos({ 'docs/pdc-v2.md': 'guia' })), true);
  assert.equal(mandaSobreElCodigo(['AGENTS.md'], tipos({ 'AGENTS.md': 'contrato' })), true);
  assert.equal(mandaSobreElCodigo(['GLOSARIO.md'], tipos({ 'GLOSARIO.md': 'biblia' })), true);
});

test('una spec o un plan no mandan: describen lo que aún no se ha construido', () => {
  const m = { 'docs/superpowers/specs/x.md': 'spec', 'docs/superpowers/plans/x.md': 'plan' };
  assert.equal(mandaSobreElCodigo(Object.keys(m), tipos(m)), false);
});

test('reporte, evidencia y goal-doc tampoco: son historia', () => {
  const m = { 'a.md': 'reporte', 'b.md': 'evidencia', 'c.md': 'goal-doc' };
  assert.equal(mandaSobreElCodigo(Object.keys(m), tipos(m)), false);
});

test('basta UNO con autoridad para que el commit entero cuente', () => {
  const m = { 'plan.md': 'plan', 'spec.md': 'spec', 'docs/pdc-v2.md': 'guia' };
  assert.equal(mandaSobreElCodigo(Object.keys(m), tipos(m)), true);
});

test('un archivo sin `tipo` declarado cuenta: la regla falla hacia el ruido', () => {
  // La alarma se apoya en un metadato que mantiene la propia wiki y que dedujo un script desde
  // la ruta. Un documento mal tipado se silenciaría a sí mismo, así que ante la duda, suena.
  assert.equal(mandaSobreElCodigo(['docs/recien-creado.md'], tipos({})), true);
  const m = { 'plan.md': 'plan', 'docs/sin-declarar.md': null };
  assert.equal(mandaSobreElCodigo(Object.keys(m), tipos(m)), true);
});

test('un archivo borrado o renombrado cuenta, por la misma razón', () => {
  assert.equal(mandaSobreElCodigo(['docs/ya-no-existe.md'], () => { throw new Error('ENOENT'); }), true);
});

test('el filtro completo: una spec no cuenta, una guía sí', () => {
  const log = (f) => `${sha(1)}\n\n${f}\n`;
  const conDiff = (args) => (args[0] === 'show' ? '--- a/x\n+++ b/x\n+prosa nueva\n' : log(ARCHIVO));
  let ARCHIVO = 'docs/superpowers/specs/x.md';
  assert.equal(contarCommits('2026-08-01', conDiff, tipos({ 'docs/superpowers/specs/x.md': 'spec' })), 0);
  ARCHIVO = 'docs/pdc-v2.md';
  assert.equal(contarCommits('2026-08-01', conDiff, tipos({ 'docs/pdc-v2.md': 'guia' })), 1);
});

test('un commit que toca código no pasa por la regla de tipos', () => {
  // Si tocara, un `.php` sin `tipo` declarado entraría por la puerta de «sin declarar» en vez de
  // por la suya, y el día que alguien invierta esa guarda dejaría de contar sin que se note.
  const log = `${sha(1)}\n\nsrc/Core/Database.php\n`;
  assert.equal(contarCommits('2026-08-01', () => log, () => { throw new Error('no se llama'); }), 1);
});
