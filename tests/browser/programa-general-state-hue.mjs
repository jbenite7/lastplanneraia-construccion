// Cruza el contrato con el PIXEL, que es lo que ningun guard hacia.
//
// `state-tint-ladder.test.mjs:170` comprueba que ningun modulo repita matiz
// recorriendo `semantics.moduleMappings` — es decir, leyendo el JSON contra si
// mismo. Una declaracion validandose a si misma esta verde por construccion, y
// por eso «Actividad Futura» y «En Curso» llevaron dias pintandose identicas
// mientras el contrato declaraba matices distintos.
//
// Necesita filas reales: sobre una grilla vacia este test se quedaria verde sin
// haber medido nada, que es exactamente el fallo que viene a corregir.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';
import { PROJECTS } from './fixtures/projects.mjs';
import { loginAndSelectProject, logout } from './support/session.mjs';
import { installContrastProbe, measure } from './support/contrast.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const AA_MIN = 4.5;

const SEMANTICS = JSON.parse(readFileSync(
  fileURLToPath(new URL('../../docs/design-system/state-semantics.json', import.meta.url)),
  'utf8',
));
const PG_STATES = SEMANTICS.moduleMappings.find((m) => m.module === 'programa-general').states;

// Cada fila ejercita exactamente uno de los siete matices del contrato. La
// prueba mide el badge que pinta la tabla React, no la leyenda estática: si el
// mapeo de un estado vuelve a un color duplicado, el pixel y el contraste fallan.
const FILAS = [
  { unique_id: 1, Id: 1, Consecutivo_en_Programa: 1, Titulo: 0, Actividad: 'Cimentacion eje 4', Estado: 'Terminada', Ruta_Critica: 0, Ejecutado: 1 },
  { unique_id: 2, Id: 2, Consecutivo_en_Programa: 2, Titulo: 0, Actividad: 'Muros nivel 2', Estado: 'En Curso', Ruta_Critica: 0, Ejecutado: 0.5 },
  { unique_id: 3, Id: 3, Consecutivo_en_Programa: 3, Titulo: 0, Actividad: 'Redes', Estado: 'Actividad Futura', Ruta_Critica: 0 },
  { unique_id: 4, Id: 4, Consecutivo_en_Programa: 4, Titulo: 0, Actividad: 'Electrica', Estado: 'Debe Iniciar', Ruta_Critica: 0 },
  { unique_id: 5, Id: 5, Consecutivo_en_Programa: 5, Titulo: 0, Actividad: 'Losa nivel 3', Estado: 'Atrasada', Ruta_Critica: 0 },
  { unique_id: 6, Titulo: 0, Actividad: 'Cubierta', Estado: '', Ruta_Critica: 0 },
  { unique_id: 7, Id: 7, Consecutivo_en_Programa: 7, Titulo: 0, Actividad: 'Cubierta ala norte', Estado: 'Fuera de Ventana', Ruta_Critica: 0 },
];

const CONTEXTO_REACT = {
  proyecto: { id: 1, nombre: 'Da Porto', codigo: 'da_porto', tipo: 'Construccion' },
  semana: { numero: 1, confirmada: false, esPasada: false },
  permisos: { puedeVer: true, puedeEditar: true, puedeCorteXlsx: true, puedeLote: false, readDrawer: true, writeDrawer: true },
  catalogos: { unidades: [], codigos: [], profesionales: [], subcontratistas: [] },
  csrf_token: 'hue-fixture',
  csrf_shell: 'hue-fixture',
};

const MATIZ_POR_FILA = {
  1: 'neutral',
  2: 'blue',
  3: 'green',
  4: 'orange',
  5: 'red',
  6: 'violet',
  7: 'teal',
};

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('cada matiz declarado se pinta distinto y legible', async ({ page }) => {
  await page.route('**/api/programa-general/context', (r) => r.fulfill({
    contentType: 'application/json', body: JSON.stringify(CONTEXTO_REACT),
  }));
  await page.route('**/api/general/list**', (r) => r.fulfill({
    contentType: 'application/json', body: JSON.stringify(FILAS),
  }));

  await installContrastProbe(page);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await page.goto('/programa-general', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('table.programa-table-pro tbody tr.row-activity')).toHaveCount(FILAS.length);

  const fondos = await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 1; canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const srgb = (value) => {
      ctx.clearRect(0, 0, 1, 1); ctx.fillStyle = '#000'; ctx.fillStyle = value; ctx.fillRect(0, 0, 1, 1);
      const d = ctx.getImageData(0, 0, 1, 1).data;
      return `${d[0]},${d[1]},${d[2]}`;
    };
    const out = {};
    const huesByRow = { 1: 'neutral', 2: 'blue', 3: 'green', 4: 'orange', 5: 'red', 6: 'violet', 7: 'teal' };
    for (const [id, hue] of Object.entries(huesByRow)) {
      const chip = document.querySelector(`tr[data-unique-id="${id}"] .status-pill`);
      if (chip) out[hue] = srgb(getComputedStyle(chip).backgroundColor);
    }
    return out;
  });

  const matices = Object.keys(fondos);
  expect(matices.length, 'no se pinto ningun chip: revisa que la grilla traiga filas').toBeGreaterThan(1);

  // Los siete matices del contrato para este modulo, no un subconjunto: si
  // falta alguno el guard vuelve a estar ciego para ese estado.
  //
  // De aqui abajo las aserciones son `soft`: comprueban propiedades
  // INDEPENDIENTES del mismo conjunto de chips (cobertura, colision, intrusos,
  // contraste). Con aserciones duras, la primera que falla esconde a las otras
  // tres y hacen falta tantas corridas como defectos haya. El cambio es de
  // REPORTE: ningun umbral ni banda se relaja.
  const declaradosTodos = new Set(PG_STATES.map((s) => s.hue));
  const faltantes = [...declaradosTodos].filter((h) => !matices.includes(h));
  expect.soft(faltantes, `matices declarados que la grilla no ejercito: ${faltantes}`).toEqual([]);

  // Dos matices distintos no pueden resolver al mismo pixel: ese es el defecto.
  const colisiones = [];
  for (const a of matices) {
    for (const b of matices) {
      if (a < b && fondos[a] === fondos[b]) colisiones.push(`${a} y ${b} pintan ${fondos[a]}`);
    }
  }
  expect.soft(colisiones, `matices distintos con el mismo color:\n${colisiones.join('\n')}`).toEqual([]);

  // Y cada matiz visible tiene que estar declarado en el contrato.
  const declarados = new Set(PG_STATES.map((s) => s.hue));
  const intrusos = matices.filter((h) => !declarados.has(h));
  expect.soft(intrusos, `matices que el contrato no declara: ${intrusos}`).toEqual([]);

  const bajos = [];
  for (const hue of matices) {
    const id = Object.entries(MATIZ_POR_FILA).find(([, matiz]) => matiz === hue)?.[0];
    const medida = id ? await measure(page, `tr[data-unique-id="${id}"] .status-pill`) : null;
    if (!medida || typeof medida.ratio !== 'number') { bajos.push(`${hue}: la sonda no pudo medir`); continue; }
    if (medida.ratio < AA_MIN) bajos.push(`${hue}: ${medida.ratio.toFixed(2)}:1`);
  }
  expect.soft(bajos, `chips bajo AA:\n${bajos.join('\n')}`).toEqual([]);

  await logout(page);
});
