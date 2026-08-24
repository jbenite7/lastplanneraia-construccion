// Sonda de DIAGNOSTICO del frente `ds-f1a-estados-severidad`, Task 6 — /programa-general.
// Copia de `sonda-despues.mjs` (Task 3, /programacion-intermedia), adaptada al modulo y a los
// siete estados del contrato `docs/design-system/state-semantics.json`. No es un test: no asierta
// nada, no toca goldens y no vive en tests/. Computado contra computado: nunca se compara con el
// valor declarado en la hoja.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = 'http://localhost:8081';
const VIEWPORT = { width: 1180, height: 820 };
const OUT = new URL('.', import.meta.url).pathname;

// Siete filas, una por estado del contrato. Las seis primeras nacen totalmente
// liberadas (D_y_E/Materiales/MdeO/Equipos en 100%, Predecesora en 100%) y con
// `Semanas_Inicio` lejano, para que `getRestrictionAlertKey` no les pinte una
// alerta encima y el fondo que se mida sea el del Estado base. La septima es la
// unica con restricciones pendientes y `Semanas_Inicio: 1`, para forzar
// `con-alerta-restricciones` (r1, amber) — ese estado no tiene un valor de
// `Estado` propio, lo dispara la alerta.
const LIBERADA = { D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '100%' };
const FILAS = [
  { unique_id: 201, Id: 201, Titulo: 0, Actividad: 'Cimentacion torre B', Estado: 'Actividad Futura', Ejecutado: 0, unidad: 'm3', Semanas_Inicio: 20, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 202, Id: 202, Titulo: 0, Actividad: 'Estructura nivel 3', Estado: 'En Curso', Ejecutado: 0.45, unidad: 'm3', Semanas_Inicio: -3, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 203, Id: 203, Titulo: 0, Actividad: 'Mamposteria nivel 1', Estado: 'Terminada', Ejecutado: 1, unidad: 'm2', Semanas_Inicio: -8, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 204, Id: 204, Titulo: 0, Actividad: 'Fachada oriente', Estado: 'Debe Iniciar', Ejecutado: 0, unidad: 'm2', Semanas_Inicio: 0, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 205, Id: 205, Titulo: 0, Actividad: 'Redes electricas piso 2', Estado: 'Atrasada', Ejecutado: 0.1, unidad: 'gl', Semanas_Inicio: -1, Ruta_Critica: '1', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 206, Id: 206, Titulo: 0, Actividad: 'Actividad sin clasificar', Estado: 'Sin Datos', Ejecutado: 0, unidad: '', Semanas_Inicio: 999, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
  { unique_id: 207, Id: 207, Titulo: 0, Actividad: 'Vigas de amarre eje 5', Estado: 'Fuera de Ventana', Ejecutado: 0, unidad: 'ml', Semanas_Inicio: 12, Ruta_Critica: '0', alerta_crisis: 0, ...LIBERADA },
];

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: VIEWPORT, deviceScaleFactor: 1 });
const page = await context.newPage();

await page.route('**/api/general/restriction-config**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: false }) }));
await page.route('**/programa-general/filtros', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: {} }) }));
await page.route('**/api/general/codigos**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: [] }) }));
await page.route('**/api/general/list**', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, data: FILAS }) }));

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
await page.goto(`${BASE}/programa-general`, { waitUntil: 'domcontentloaded' });
await page.waitForFunction(() => Boolean(document.querySelector('#hot-container .handsontable')));
await page.locator('#loading').waitFor({ state: 'hidden', timeout: 45000 }).catch(() => {});
await page.waitForFunction(() => document.querySelectorAll('#hot-container .ht_master tbody tr').length >= 7, null, { timeout: 45000 });
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
    const tds = [...tr.querySelectorAll('td')];
    const stateTds = tds.filter((t) => /\bpg-state-/.test(t.className));
    if (stateTds.length === 0) continue;
    // La alerta de restriccion (r0/r1/r2-3/r4-6) se APPENDEA tras el estado
    // base y gana la cascada (misma especificidad, definida despues en la
    // hoja): si esta presente es lo que de verdad se ve pintado.
    const clasesEstado = stateTds[0].className.match(/pg-state-([\w-]+)/g) || [];
    const claves = clasesEstado.map((c) => c.replace('pg-state-', ''));
    const alerta = claves.find((c) => /^r(0|1|2-3|4-6)$/.test(c));
    const estado = alerta || claves[0] || '?';
    const chip = tr.querySelector('.ops-state-chip');
    const celda = stateTds[0];
    filas.push({
      estado,
      etiqueta: chip ? chip.textContent.trim() : null,
      hue: chip ? chip.getAttribute('data-aia-hue') : null,
      severity: chip ? chip.getAttribute('data-aia-severity') : null,
      urgency: chip ? chip.getAttribute('data-aia-urgency') : null,
      celdaFondo: hex(fondo(celda)),
      celdaFondoDeclarado: getComputedStyle(celda).backgroundColor,
      celdaTexto: hex(rgba(getComputedStyle(celda).color)),
      chipFondo: chip ? hex(fondo(chip)) : null,
      chipTexto: chip ? hex(rgba(getComputedStyle(chip).color)) : null,
      railGrosor: getComputedStyle(celda).boxShadow,
      railNivel: tr.getAttribute('data-aia-severity-rail'),
      railNivelCelda: celda.getAttribute('data-aia-severity-rail'),
    });
  }
  return filas;
});


await page.waitForTimeout(900);
const filas2 = await page.evaluate(() => {
  const out = [];
  for (const tr of document.querySelectorAll('#hot-container .ht_master tbody tr')) {
    const tds = [...tr.querySelectorAll('td')];
    const primera = tds.find((t) => t.offsetWidth > 0);
    if (!primera) continue;
    out.push({
      trRail: tr.getAttribute('data-aia-severity-rail'),
      tdRail: primera ? primera.getAttribute('data-aia-severity-rail') : null,
      tdShadow: primera ? getComputedStyle(primera).boxShadow.slice(0, 90) : null,
      tdClases: primera ? primera.className.slice(0, 80) : null,
    });
  }
  return out;
});
console.log('DIAG', JSON.stringify(filas2, null, 1));

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
      celdaIdentica: datos[i].celdaFondo === datos[j].celdaFondo,
    });
  }
}

writeFileSync(`${OUT}medicion-pg-postfix.json`, JSON.stringify(salida, null, 2));
await page.screenshot({ path: `${OUT}pg-siete-estados-postfix-1180x820-dark.png` });
console.log(JSON.stringify(salida.filas, null, 2));
console.log('\nPARES IDENTICOS (celda):', salida.pares.filter((p) => p.celdaIdentica).map((p) => `${p.a} = ${p.b}`).join(' | ') || 'ninguno');
await browser.close();
