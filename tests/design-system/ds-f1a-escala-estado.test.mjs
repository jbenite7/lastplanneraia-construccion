import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

// El contrato de la escala de estado (DS-F1a) vive en dos archivos que se validan entre si:
// el JSON es la fuente ejecutable y el Markdown lo explica. Esta prueba existe para que
// separarlos rompa la suite en vez de degradarse en silencio, que es como se llego a tener
// cuatro documentos con jurisdiccion sobre los estados y ninguno de acuerdo con los demas.
const read = (file) => readFile(new URL(`../../${file}`, import.meta.url), 'utf8');
const contrato = async () => JSON.parse(await read('docs/design-system/ds-f1a-escala-estado.json'));

const NIVELES = ['urgente', 'atencion', 'controlado'];
const SIN_GRAVEDAD = ['fuera-de-ventana', 'sin-datos'];

test('los niveles de gravedad son exactamente tres', async () => {
  const c = await contrato();
  assert.deepEqual(Object.keys(c.niveles), NIVELES);
});

test('cada estado declara un nivel conocido o se declara sin gravedad', async () => {
  const c = await contrato();
  for (const e of c.estados) {
    if (e.nivel === null) {
      assert.ok(SIN_GRAVEDAD.includes(e.id), `${e.id} sin nivel y no esta en SIN_GRAVEDAD`);
    } else {
      assert.ok(NIVELES.includes(e.nivel), `${e.id} declara nivel desconocido: ${e.nivel}`);
    }
  }
});

test('solo urgente y atencion dibujan barra; controlado es la ausencia', async () => {
  const c = await contrato();
  assert.equal(c.niveles.urgente.barra, true);
  assert.equal(c.niveles.atencion.barra, true);
  assert.equal(c.niveles.controlado.barra, false);
});

test('ningun id de estado se repite', async () => {
  const c = await contrato();
  const ids = c.estados.map((e) => e.id);
  assert.equal(new Set(ids).size, ids.length);
});

// Trece porcentajes redondeados a una decima no suman 100 exacto: la medicion real da 99,9.
// La tolerancia es de medio punto para que el redondeo no haga fallar la prueba, y sigue
// siendo suficientemente estrecha para cazar un estado olvidado -el mas pequeno que no es
// cero vale 0,1- o uno contado dos veces.
test('los porcentajes suman 100 con medio punto de tolerancia', async () => {
  const c = await contrato();
  const suma = c.estados.reduce((a, e) => a + e.porcentaje, 0);
  assert.ok(Math.abs(suma - 100) <= 0.5, `suman ${suma.toFixed(1)}, no 100`);
});

test('las dos asignaciones propuestas conservan su marca de revocables', async () => {
  const c = await contrato();
  const revocables = c.estados.filter((e) => e.revocable === true).map((e) => e.id);
  assert.deepEqual(revocables.sort(), ['debe-iniciar-esta-semana', 'en-liberacion-de-restricciones']);
});

// El spec exige que cada estado declare su origen. No es adorno: siete de los trece los produce
// `pg_calculate_status` y seis no los produce nadie hoy, y esa diferencia decide si un estado
// sobrevive al proximo recalculo o se borra solo -que es justo lo que le estaba pasando al
// estado de 7+ semanas.
test('cada estado declara quien lo produce', async () => {
  const c = await contrato();
  const ORIGENES = ['pg_calculate_status', 'legacy-sin-productor'];
  for (const e of c.estados) {
    assert.ok(ORIGENES.includes(e.origen), `${e.id} declara origen desconocido: ${e.origen}`);
  }
});

test('cada canal declara que dato transporta', async () => {
  const c = await contrato();
  assert.equal(c.canales.fondo.transporta, 'identidad-y-horizonte');
  assert.equal(c.canales.barra.transporta, 'gravedad');
});

// El Markdown explica el contrato y el JSON lo ejecuta. Esta prueba los ata: si alguien anade
// un estado al JSON y se olvida del Markdown, o renombra una etiqueta en uno solo, la suite se
// pone roja. Sin esto, los dos archivos se separan en silencio -que es exactamente como el
// repositorio llego a tener cuatro documentos de estados que no coinciden entre si.
test('el markdown nombra cada estado del contrato con su etiqueta exacta', async () => {
  const c = await contrato();
  const md = await read('docs/design-system/ds-f1a-escala-estado.md');
  for (const e of c.estados) {
    assert.ok(md.includes(e.etiqueta), `el markdown no nombra "${e.etiqueta}"`);
  }
});
