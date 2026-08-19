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

test('un commit sin archivos listados cuenta: es un merge y no se puede descartar', () => {
  assert.equal(contarCommits('2026-08-01', () => `${sha(1)}\n\n${sha(2)}\n`), 2);
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
