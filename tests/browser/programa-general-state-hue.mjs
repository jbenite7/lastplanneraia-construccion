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
import { changeWeek, loginAndSelectProject, logout } from './support/session.mjs';
import { installContrastProbe, measure } from './support/contrast.mjs';

const ADMIN = { username: 'test.A', password: 'aia2026' };
const AA_MIN = 4.5;

const SEMANTICS = JSON.parse(readFileSync(
  fileURLToPath(new URL('../../docs/design-system/state-semantics.json', import.meta.url)),
  'utf8',
));
const PG_STATES = SEMANTICS.moduleMappings.find((m) => m.module === 'programa-general').states;

// Las mismas filas del mock de la prueba visual, para que ambas midan lo
// mismo, mas una septima para el matiz `amber`. Sin `Consecutivo` ni `Id` la
// fila cae a `sin-datos`; `Titulo` distinto de 0 se clasificaria como cabecera.
//
// `con-alerta-restricciones` no depende del mock de `restriction-config`
// (linea 42 mas abajo): `getRestrictionConfig()` en hot.js cae a
// `_DEFAULT_RESTRICTION_CONFIG` tanto si la respuesta es `success:false` como
// si `window.__RESTRICTION_CONFIG__` aun no llego, asi que los umbrales duros
// por defecto (D_y_E, Materiales, MdeO, Equipos, Predecesora) siguen activos.
// Lo que dispara el amber es `getRestrictionAlertKey()` (hot.js:718-748):
// necesita `Titulo: 0`, una restriccion dura por debajo de su umbral (aqui
// `D_y_E: 0.5` contra el umbral 1.0) y `Semanas_Inicio` para elegir el label
// r0/r1/r2-3/r4-6 (aqui `0` -> `r0`). Sin `Ejecutado` el ratio cae a 0, que no
// alcanza el escape `ejecutado >= 0.999`.
const FILAS = [
  { Id: 1, Consecutivo: 1, Titulo: 0, Actividad: 'Cimentacion eje 4', Estado: 'Terminada', Ruta_Critica: '0' },
  { Id: 2, Consecutivo: 2, Titulo: 0, Actividad: 'Muros nivel 2', Estado: 'En curso', Ruta_Critica: '0' },
  { Id: 3, Consecutivo: 3, Titulo: 0, Actividad: 'Redes', Estado: 'Actividad futura', Ruta_Critica: '0' },
  { Id: 4, Consecutivo: 4, Titulo: 0, Actividad: 'Electrica', Estado: 'Debe iniciar', Ruta_Critica: '0' },
  { Id: 5, Consecutivo: 5, Titulo: 0, Actividad: 'Losa nivel 3', Estado: 'Atrasada', Ruta_Critica: '0' },
  { Titulo: 0, Actividad: 'Cubierta', Estado: '', Ruta_Critica: '0' },
  {
    Id: 7, Consecutivo: 7, Titulo: 0, Actividad: 'Fachada', Estado: 'Debe iniciar', Ruta_Critica: '0',
    Semanas_Inicio: 0, D_y_E: 0.5,
  },
];

test.use({ viewport: { width: 1180, height: 820 }, colorScheme: 'dark' });

test('cada matiz declarado se pinta distinto y legible', async ({ page }) => {
  await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":false}' }));
  await page.route('**/api/general/codigos**', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":true,"data":[]}' }));
  await page.route('**/programa-general/filtros', (r) => r.fulfill({ contentType: 'application/json', body: '{"success":true,"data":{}}' }));
  await page.route('**/api/general/list**', (r) => r.fulfill({
    contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }),
  }));

  await installContrastProbe(page);
  await loginAndSelectProject(page, PROJECTS[0], ADMIN);
  await changeWeek(page, PROJECTS[0].maxWeek, '/programa-general');
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => document.querySelectorAll('#hot-container .ops-state-chip').length > 0, null, { timeout: 20000 });

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
    for (const chip of document.querySelectorAll('#hot-container .ops-state-chip')) {
      out[chip.getAttribute('data-aia-hue')] = srgb(getComputedStyle(chip).backgroundColor);
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
    const medida = await measure(page, `#hot-container .ops-state-chip[data-aia-hue="${hue}"]`);
    if (!medida || typeof medida.ratio !== 'number') { bajos.push(`${hue}: la sonda no pudo medir`); continue; }
    if (medida.ratio < AA_MIN) bajos.push(`${hue}: ${medida.ratio.toFixed(2)}:1`);
  }
  expect.soft(bajos, `chips bajo AA:\n${bajos.join('\n')}`).toEqual([]);

  await logout(page);
});
