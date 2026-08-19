// Sonda de DIAGNOSTICO del frente `bug-coloreado-severidad`. No es un test: no
// asierta nada, no toca goldens y no vive en tests/. Siembra los nueve estados de
// /programacion-intermedia con el mismo mock que usa
// tests/browser/programacion-intermedia.visual.mjs, y LEE EL COLOR COMPUTADO de
// (a) la celda de la fila y (b) el chip de estado. Computado contra computado:
// nunca se compara con el valor declarado en la hoja.
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
const tema = await page.getAttribute('html', 'data-aia-theme');

const datos = await page.evaluate(() => {
  const canvas = document.createElement('canvas');
  canvas.width = 1; canvas.height = 1;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const rgba = (v) => {
    if (!v || v === 'transparent' || v === 'none') return [0, 0, 0, 0];
    ctx.globalCompositeOperation = 'copy';
    ctx.fillStyle = '#000'; ctx.fillStyle = v;
    ctx.fillRect(0, 0, 1, 1);
    const d = ctx.getImageData(0, 0, 1, 1).data;
    return [d[0], d[1], d[2], d[3] / 255];
  };
  const over = (fg, bg) => {
    const a = fg[3] + bg[3] * (1 - fg[3]);
    if (a === 0) return [0, 0, 0, 0];
    const m = (i) => (fg[i] * fg[3] + bg[i] * bg[3] * (1 - fg[3])) / a;
    return [m(0), m(1), m(2), a];
  };
  // Fondo REAL: compone la cadena de ancestros hasta la primera capa opaca.
  const fondo = (el) => {
    let acc = [0, 0, 0, 0];
    let n = el;
    while (n) {
      const c = rgba(getComputedStyle(n).backgroundColor);
      acc = over(acc, c);
      if (acc[3] >= 0.999) break;
      n = n.parentElement;
    }
    return acc;
  };
  const hex = (c) => '#' + [0, 1, 2].map((i) => Math.round(c[i]).toString(16).padStart(2, '0')).join('');

  const filas = [];
  for (const tr of document.querySelectorAll('#hot-container .ht_master tbody tr')) {
    const td = [...tr.querySelectorAll('td')].find((t) => /pi-state-/.test(t.className));
    if (!td) continue;
    const estado = (td.className.match(/pi-state-([\w-]+)/) || [])[1] || '?';
    const chip = tr.querySelector('.ops-state-chip');
    const celdaTexto = [...tr.querySelectorAll('td')].find((t) => !t.classList.contains('ops-state-td')) || td;
    filas.push({
      estado,
      etiqueta: chip ? chip.textContent.trim() : null,
      hue: chip ? chip.getAttribute('data-aia-hue') : null,
      severity: chip ? chip.getAttribute('data-aia-severity') : null,
      urgency: chip ? chip.getAttribute('data-aia-urgency') : null,
      celdaFondo: hex(fondo(celdaTexto)),
      celdaFondoDeclarado: getComputedStyle(celdaTexto).backgroundColor,
      celdaTexto: hex(rgba(getComputedStyle(celdaTexto).color)),
      chipFondo: chip ? hex(fondo(chip)) : null,
      chipTexto: chip ? hex(rgba(getComputedStyle(chip).color)) : null,
    });
  }
  return filas;
});

const lum = (h) => {
  const c = [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
};
const contraste = (a, b) => {
  const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
  return (x + 0.05) / (y + 0.05);
};

const salida = { tema, viewport: VIEWPORT, filas: datos, pares: [] };
for (let i = 0; i < datos.length; i++) {
  for (let j = i + 1; j < datos.length; j++) {
    salida.pares.push({
      a: datos[i].estado, b: datos[j].estado,
      celdaVsCelda: +contraste(datos[i].celdaFondo, datos[j].celdaFondo).toFixed(3),
      chipVsChip: datos[i].chipFondo && datos[j].chipFondo
        ? +contraste(datos[i].chipFondo, datos[j].chipFondo).toFixed(3) : null,
      celdaIdentica: datos[i].celdaFondo === datos[j].celdaFondo,
      chipIdentico: datos[i].chipFondo === datos[j].chipFondo,
    });
  }
}

writeFileSync(`${OUT}medicion-computada.json`, JSON.stringify(salida, null, 2));
await page.screenshot({ path: `${OUT}pi-nueve-estados-1180x820-dark.png` });
console.log(JSON.stringify(salida.filas, null, 2));
console.log('\nPARES IDENTICOS (celda):', salida.pares.filter((p) => p.celdaIdentica).map((p) => `${p.a} = ${p.b}`).join(' | ') || 'ninguno');
console.log('PARES IDENTICOS (chip):', salida.pares.filter((p) => p.chipIdentico).map((p) => `${p.a} = ${p.b}`).join(' | ') || 'ninguno');
await browser.close();
