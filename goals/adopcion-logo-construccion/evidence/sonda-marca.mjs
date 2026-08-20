// Sonda de las cuatro superficies de marca. No comprueba que el archivo exista
// -eso ya lo dice un `ls`- sino que la pagina SIRVA la marca: que el recurso
// responda 200 y que el nodo este en el DOM. Un <img> con src roto pasa
// cualquier comprobacion de fichero y deja la superficie sin logo.
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';
const BASE = 'http://localhost:8081';
const OUT = new URL('.', import.meta.url).pathname;

const b = await chromium.launch();
const c = await b.newContext({ viewport: { width: 1180, height: 820 } });
const page = await c.newPage();
const fallos = [];
const filas = [];

async function mirar(nombre, url, autenticado) {
  const rotos = [];
  page.removeAllListeners('response');
  page.on('response', (r) => { if (r.status() >= 400 && /\.(svg|png|ico|webp)$/i.test(new URL(r.url()).pathname)) rotos.push(`${r.status()} ${new URL(r.url()).pathname}`); });
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(900);
  const marcas = await page.evaluate(() => {
    const fuentes = new Set();
    for (const img of document.querySelectorAll('img')) {
      if (/logo|marca|brand|mark|isotipo/i.test(img.src) || /logo|marca|brand/i.test(img.className)) fuentes.add(img.src);
    }
    for (const el of document.querySelectorAll('svg use')) {
      const href = el.getAttribute('href') || el.getAttribute('xlink:href') || '';
      if (href) fuentes.add(href);
    }
    // Un logo puede venir por background-image O por mask-image sobre un <span>
    // vacio, que es como lo pinta /login (`login-brand-mark`). Mirar solo
    // `backgroundImage` daba un falso rojo en esa superficie: la sonda decia
    // «ninguna marca» y la captura ensenaba el isotipo. Corregido el 2026-08-19
    // MIRANDO la captura, no razonando sobre el DOM.
    for (const el of document.querySelectorAll('[class*="logo"], [class*="brand"], [class*="lockup"]')) {
      const cs = getComputedStyle(el);
      for (const prop of [cs.backgroundImage, cs.maskImage, cs.webkitMaskImage]) {
        const m = prop && prop !== 'none' && prop.match(/url\(["']?([^"')]+)/);
        if (m) fuentes.add(m[1]);
      }
      if (el.querySelector('svg')) fuentes.add('(svg inline en ' + (el.className || el.tagName) + ')');
    }
    return [...fuentes];
  });
  const legado = marcas.some((m) => m.includes('aia-last-planner-mark.svg'));
  filas.push({ superficie: nombre, url, marcas, recursosRotos: rotos, legado });
  if (!marcas.length) fallos.push(`${nombre}: ninguna marca en el DOM`);
  if (rotos.length) fallos.push(`${nombre}: recursos de imagen rotos -> ${rotos.join(', ')}`);
  if (legado) fallos.push(`${nombre}: sigue sirviendo el SVG legado aia-last-planner-mark.svg`);
  await page.screenshot({ path: `${OUT}marca-${nombre}-1180x820-dark.png` });
}

// Sin sesion
await mirar('login', `${BASE}/login`, false);
// Con sesion: shell del producto
await page.goto(`${BASE}/dev/entrar?u=test.R&p=${encodeURIComponent('Da Porto')}`);
await page.waitForURL((u) => !u.toString().includes('/proyectos'), { timeout: 30000 }).catch(() => {});
await page.evaluate(() => localStorage.setItem('aia-theme', 'dark'));
await mirar('shell', `${BASE}/programa-general`, true);
await mirar('proyectos', `${BASE}/proyectos`, true);
await mirar('admin', `${BASE}/admin`, true);

const favicon = await page.request.get(`${BASE}/favicon.ico`);
console.log(`favicon.ico -> ${favicon.status()}`);
if (favicon.status() !== 200) fallos.push(`favicon.ico responde ${favicon.status()}`);

for (const f of filas) {
  console.log(`\n${f.superficie.padEnd(12)} ${f.url}`);
  console.log(`  marcas en el DOM : ${f.marcas.length ? f.marcas.map((m) => m.replace(BASE, '')).join('\n                     ') : 'NINGUNA'}`);
  console.log(`  recursos rotos   : ${f.recursosRotos.length ? f.recursosRotos.join(', ') : 'ninguno'}`);
}
writeFileSync(`${OUT}marca.json`, JSON.stringify(filas, null, 2));
if (fallos.length) { console.log('\nSONDA EN ROJO:'); for (const f of fallos) console.log('  - ' + f); process.exitCode = 1; }
else console.log('\nSONDA EN VERDE: las cuatro superficies sirven marca, sin recursos rotos y sin el SVG legado.');
await b.close();
