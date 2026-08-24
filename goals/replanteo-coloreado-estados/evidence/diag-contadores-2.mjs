// Diagnostico de los contadores de la leyenda. Reemplaza a `diag-contadores.mjs`,
// que entraba al proyecto "Da Porto" buscandolo por su tarjeta y dejo de encontrarlo.
// Este entra por la puerta de servicio con `p=`, como manda AGENTS.md.
import { chromium } from 'playwright';

const BASE = 'http://localhost:8081';
const RUTA = process.argv[2] || '/programa-general';

const navegador = await chromium.launch();
const contexto = await navegador.newContext({ viewport: { width: 1180, height: 820 } });
const pagina = await contexto.newPage();

await pagina.goto(`${BASE}/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`, { waitUntil: 'domcontentloaded' });
await pagina.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await pagina.goto(`${BASE}${RUTA}`, { waitUntil: 'domcontentloaded' });
await pagina.waitForTimeout(3000);

const datos = await pagina.evaluate(() => {
  const hex = (s) => {
    const m = (s || '').match(/\d+/g);
    return m ? '#' + m.slice(0, 3).map((n) => (+n).toString(16).padStart(2, '0')).join('') : s;
  };
  const chips = [...document.querySelectorAll('[class*="filter-chip"], [class*="legend-item"]')];
  return chips.slice(0, 14).map((chip) => ({
    clase: chip.className.toString().slice(0, 44),
    texto: (chip.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 28),
    fondo: hex(getComputedStyle(chip).backgroundColor),
    hijos: [...chip.children].map((hijo) => ({
      clase: (hijo.className || '').toString().slice(0, 34),
      fondo: hex(getComputedStyle(hijo).backgroundColor),
      texto: (hijo.textContent || '').trim().slice(0, 12),
    })),
  }));
});

console.log(JSON.stringify(datos, null, 1));
console.log('URL:', pagina.url());
console.log('titulo:', await pagina.title());
console.log('texto:', (await pagina.evaluate(() => document.body.innerText)).slice(0, 300));
console.log('clases con "legend" o "filter":');
console.log(await pagina.evaluate(() => [...new Set([...document.querySelectorAll('*')].map(n => n.className && n.className.toString()).filter(c => c && (c.includes('legend') || c.includes('filter') || c.includes('count'))))].slice(0, 25).join('\n')));
await navegador.close();
