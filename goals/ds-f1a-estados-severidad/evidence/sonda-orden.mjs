// Sonda de DIAGNOSTICO de la Task 4 (botón "Agrupar por gravedad") del frente
// `ds-f1a-estados-severidad`. No es un test: no asierta nada, no toca goldens y
// no vive en tests/. Siembra los mismos nueve estados que
// tests/browser/programacion-intermedia.visual.mjs y sonda-despues.mjs, entra
// por la puerta de servicio, y captura la tabla con el botón apagado y
// encendido, volcando en ambos casos el orden de los `Id` de fila.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = 'http://localhost:8081';
const VIEWPORT = { width: 1180, height: 820 };
const OUT = new URL('.', import.meta.url).pathname;

const FILAS = [
  { unique_id: 101, Id: 101, Titulo: 0, Actividad: 'Pilotaje eje A', Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'L. Marin', Semanas_Inicio: -2, Ejecutado: 0, D_y_E: '100%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '50%', Modelo: 'N/A', Ruta_Critica: '1', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 102, Id: 102, Titulo: 0, Actividad: 'Excavacion sotano 1', Sub_Contratista: 'Movitierra', Responsable_AIA: 'C. Rojas', Semanas_Inicio: -1, Ejecutado: 0, D_y_E: '33%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '50%', Pdto_Cons: '100%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 103, Id: 103, Titulo: 0, Actividad: 'Vigas de cimentacion', Sub_Contratista: 'Estructuras Andinas', Responsable_AIA: 'C. Rojas', Semanas_Inicio: 0, Ejecutado: 0, D_y_E: '100%', Materiales: '66%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 104, Id: 104, Titulo: 0, Actividad: 'Placa nivel 1', Sub_Contratista: 'Estructuras Andinas', Responsable_AIA: 'M. Torres', Semanas_Inicio: 1, Ejecutado: 0, D_y_E: '100%', Materiales: '33%', MdeO: '100%', Equipos: '66%', Predecesora: '100%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 105, Id: 105, Titulo: 0, Actividad: 'Muros pantalla eje 3', Sub_Contratista: 'Cimentaciones SAS', Responsable_AIA: 'M. Torres', Semanas_Inicio: 1, Ejecutado: 0.45, D_y_E: '66%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 106, Id: 106, Titulo: 0, Actividad: 'Mamposteria nivel 2', Sub_Contratista: 'Obra Blanca Ltda', Responsable_AIA: 'L. Marin', Semanas_Inicio: 2, Ejecutado: 0, D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%', Pdto_Cons: '100%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 107, Id: 107, Titulo: 0, Actividad: 'Redes hidrosanitarias piso 2', Sub_Contratista: 'Hidraulicos JR', Responsable_AIA: 'A. Pena', Semanas_Inicio: 3, Ejecutado: 0, D_y_E: '66%', Materiales: '33%', MdeO: '100%', Equipos: '100%', Predecesora: '50%', Pdto_Cons: '50%', Modelo: '100%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 108, Id: 108, Titulo: 0, Actividad: 'Instalacion electrica piso 3', Sub_Contratista: 'Electricos del Valle', Responsable_AIA: 'A. Pena', Semanas_Inicio: 5, Ejecutado: 0, D_y_E: '33%', Materiales: '0%', MdeO: '66%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: '0%', Modelo: '50%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
  { unique_id: 109, Id: 109, Titulo: 0, Actividad: 'Fachada flotante oriente', Sub_Contratista: 'Fachadas Integrales', Responsable_AIA: 'L. Marin', Semanas_Inicio: 8, Ejecutado: 0, D_y_E: '0%', Materiales: '0%', MdeO: '33%', Equipos: '66%', Predecesora: '0%', Pdto_Cons: '0%', Modelo: '0%', Ruta_Critica: '0', alerta_crisis: 0, Observaciones: '' },
];

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1 });
const page = await context.newPage();

await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: false }) }));
await page.route('**/programacion-intermedia/filtros', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }) }));
await page.route('**/api/pi/list**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }) }));

// Puerta de servicio: nunca /login (AGENTS.md §Seguridad).
await page.goto(`${BASE}/dev/entrar?u=test.R`);
if (new URL(page.url()).pathname !== '/proyectos') {
  throw new Error(`la puerta de desarrollo no autentico: aterrizo en ${page.url()}`);
}
const card = page.locator('.project-item').filter({ has: page.getByRole('heading', { name: 'Da Porto', exact: true }) });
await card.locator('button[type="submit"], .btn-enter').first().click();
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 45000 });
await page.request.post(`${BASE}/context/week`, { data: { semana: 1 } });

await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await page.goto(`${BASE}/programacion-intermedia`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 });
await page.waitForFunction(() => document.querySelectorAll('#hot-container .ht_master tbody tr').length >= 9, null, { timeout: 45000 });

const leerOrdenYNiveles = () => document.querySelectorAll('#hot-container .ht_master tbody tr')
  ? Array.from(document.querySelectorAll('#hot-container .ht_master tbody tr')).map((tr) => ({
    id: (window.PIHotModule.getHotInstance().getSourceData()[[...tr.parentElement.children].indexOf(tr)] || {}).Id ?? null,
    railNivel: tr.getAttribute('data-aia-severity-rail'),
  }))
  : [];

// Botón apagado (estado inicial): orden del programa.
const boton = page.locator('#btn-agrupar-gravedad');
const apagadoAriaPressed = await boton.getAttribute('aria-pressed');
const antes = await page.evaluate(leerOrdenYNiveles);
await page.screenshot({ path: `${OUT}orden-boton-apagado.png` });

// Pulsa: se enciende.
await boton.click();
await page.waitForFunction(() => document.getElementById('btn-agrupar-gravedad').getAttribute('aria-pressed') === 'true');
const encendidoAriaPressed = await boton.getAttribute('aria-pressed');
const despues = await page.evaluate(leerOrdenYNiveles);
await page.screenshot({ path: `${OUT}orden-boton-encendido.png` });

// Pulsa otra vez: debe volver EXACTAMENTE al orden del programa.
await boton.click();
await page.waitForFunction(() => document.getElementById('btn-agrupar-gravedad').getAttribute('aria-pressed') === 'false');
const vuelta = await page.evaluate(leerOrdenYNiveles);

const salida = {
  apagadoAriaPressed,
  encendidoAriaPressed,
  ordenAntes: antes,
  ordenDespues: despues,
  ordenAlVolver: vuelta,
  vueltaExacta: JSON.stringify(vuelta) === JSON.stringify(antes),
};

writeFileSync(`${OUT}orden-gravedad.json`, JSON.stringify(salida, null, 2));
console.log(JSON.stringify(salida, null, 2));
await browser.close();
