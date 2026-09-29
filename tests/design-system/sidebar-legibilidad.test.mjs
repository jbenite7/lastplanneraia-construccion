// Contrato de legibilidad de la barra lateral (revisión visual de Programa General, 2026-09-29).
//
// Tres defectos que Felipe vio en pantalla y que ningún gate atrapaba:
//   1. Tema claro: los íconos del riel colapsado salían en `--ds-active-text-secondary` (gris zinc,
//      sigue el tema de la PÁGINA) sobre un riel que siempre es verde (`--ds-nav-bg-light`):
//      contraste 1.12:1. Los íconos son SVG con aria-hidden, así que axe no los mide.
//   2. Tema oscuro: `body.aia-shell--sidebar .aia-sidebar__link { background: transparent }` (guard
//      de theme-claro-tokens.test.mjs) tiene más especificidad que la regla del enlace activo y le
//      borraba el fondo, pero el texto seguía en `--ds-active-action-text` (casi negro): ilegible.
//   3. La barra de scroll nativa (15 px) se veía sobre el riel. El riel debe poder desplazarse por
//      rueda, teclado y táctil, pero sin dibujar la barra.
//
// Sin DOM: resuelve los tokens de los archivos CSS y mide el contraste WCAG con las mismas
// fórmulas que theme-claro-tokens.test.mjs.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join, relative } from 'node:path';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const CSS = join(raiz, 'public/css');
const leer = (ruta) => readFileSync(join(raiz, ruta), 'utf8');

// --- tokens ---------------------------------------------------------------
function archivosCss(dir) {
  const salida = [];
  for (const nombre of readdirSync(dir)) {
    if (nombre === 'dist-css') continue; // espejo generado
    const ruta = join(dir, nombre);
    if (statSync(ruta).isDirectory()) salida.push(...archivosCss(ruta));
    else if (nombre.endsWith('.css')) salida.push(ruta);
  }
  return salida;
}

const declaraciones = new Map(); // nombre -> [{ archivo, valor }]
for (const ruta of archivosCss(CSS)) {
  const texto = readFileSync(ruta, 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
  for (const m of texto.matchAll(/(--[\w-]+)\s*:\s*([^;{}]+);/g)) {
    if (!declaraciones.has(m[1])) declaraciones.set(m[1], []);
    declaraciones.get(m[1]).push({ archivo: relative(CSS, ruta), valor: m[2].trim() });
  }
}

/** Resuelve un token a su valor final. Tema claro: manda theme-claro.css; oscuro: lo demás. */
function resolver(nombre, tema, profundidad = 0) {
  if (profundidad > 12) throw new Error(`Ciclo de tokens en ${nombre}`);
  const todas = declaraciones.get(nombre) ?? [];
  const enClaro = todas.filter((d) => d.archivo.endsWith('theme-claro.css'));
  const enOscuro = todas.filter((d) => !d.archivo.endsWith('theme-claro.css'));
  const elegida = tema === 'claro' ? (enClaro[0] ?? enOscuro[0]) : enOscuro[0];
  if (!elegida) return null;
  const v = elegida.valor.match(/^var\((--[\w-]+)\)$/);
  return v ? resolver(v[1], tema, profundidad + 1) : elegida.valor;
}

// --- color ----------------------------------------------------------------
function rgbDe(valor) {
  const hex = valor?.match(/^#([0-9a-f]{6})$/i);
  if (!hex) return null;
  const n = parseInt(hex[1], 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}
const luminancia = ([r, g, b]) => {
  const f = (c) => {
    const s = c / 255;
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
};
const contraste = (a, b) => {
  const [la, lb] = [luminancia(a), luminancia(b)];
  return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
};

// --- reglas CSS (solo los bloques de más adentro: regla { declaraciones }) --
function reglas(ruta) {
  const texto = leer(ruta).replace(/\/\*[\s\S]*?\*\//g, '');
  return [...texto.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((m) => ({
    selector: m[1].trim().replace(/\s+/g, ' '),
    cuerpo: m[2],
  }));
}
const declara = (cuerpo, prop) => cuerpo.match(new RegExp(`(?:^|;|\\s)${prop}\\s*:\\s*([^;]+)`))?.[1]?.trim() ?? null;

const NAVEGACION = 'public/css/design-system/components/navigation.css';
const ADAPTADOR = 'public/css/design-system/adapters/shell-sidebar.css';

// 1 -------------------------------------------------------------------------
test('los íconos del riel colapsado se leen sobre el fondo del riel en los dos temas', () => {
  const REPOSO = /\[data-sidebar-state="collapsed"\] \.aia-sidebar__link \.aia-icon$/;
  const HOVER = /\[data-sidebar-state="collapsed"\] \.aia-sidebar__link:hover \.aia-icon/;
  for (const archivo of [NAVEGACION, ADAPTADOR]) {
    const lista = reglas(archivo);
    const reposo = lista.find((r) => REPOSO.test(r.selector));
    const hover = lista.find((r) => HOVER.test(r.selector));
    assert.ok(reposo, `${archivo}: falta la regla de reposo del ícono del riel colapsado`);
    assert.ok(hover, `${archivo}: falta la regla de hover del ícono del riel colapsado`);
    for (const [estado, regla] of [['reposo', reposo], ['hover', hover]]) {
      const color = declara(regla.cuerpo, 'color');
      const token = color?.match(/^var\((--[\w-]+)\)$/)?.[1];
      assert.ok(token, `${archivo}: el color del ícono (${estado}) debe ser un token, no ${color}`);
      for (const tema of ['claro', 'oscuro']) {
        const fondo = rgbDe(resolver('--ds-nav-bg-light', 'claro'));
        // El riel es verde de marca en claro; en oscuro es casi negro (--ds-color-nav-dark) y no
        // se parsea sin OKLCH: ahí se mide contra el negro absoluto, que es la peor cota para texto claro.
        const rgbFondo = tema === 'claro' ? fondo : [0, 0, 0];
        const rgbIcono = rgbDe(resolver(token, tema));
        assert.ok(rgbIcono, `${archivo}: ${token} (${tema}) no resuelve a un #hex`);
        const ratio = contraste(rgbIcono, rgbFondo);
        assert.ok(
          ratio >= 3,
          `${archivo}: ícono en ${estado}, tema ${tema}: ${token} = ${resolver(token, tema)} da ${ratio.toFixed(2)}:1 sobre el riel (mínimo 3:1)`,
        );
      }
    }
  }
});

// 2 -------------------------------------------------------------------------
test('el ítem activo del riel conserva un fondo propio que gana al `background: transparent` del adaptador', () => {
  const lista = reglas(ADAPTADOR);
  const base = lista.find((r) => /^body\.aia-shell--sidebar \.aia-sidebar__link$/.test(r.selector) && declara(r.cuerpo, 'background') === 'transparent');
  assert.ok(base, 'el guard de theme-claro-tokens (background: transparent del enlace) debe seguir existiendo');
  const activo = lista.find((r) => /^body\.aia-shell--sidebar \.aia-sidebar__link\[aria-current="page"\]$/.test(r.selector));
  assert.ok(activo, 'falta `body.aia-shell--sidebar .aia-sidebar__link[aria-current="page"]` con fondo propio: el transparent lo deja sin fondo');
  assert.equal(declara(activo.cuerpo, 'background'), 'var(--ds-active-action-primary)');
  // El texto activo (--ds-active-action-text) se lee sobre ese fondo en los dos temas.
  for (const tema of ['claro', 'oscuro']) {
    const fondo = rgbDe(resolver('--ds-active-action-primary', tema));
    const texto = rgbDe(resolver('--ds-active-action-text', tema));
    assert.ok(fondo && texto, `tokens del ítem activo no resuelven a #hex en ${tema}`);
    const ratio = contraste(texto, fondo);
    assert.ok(ratio >= 4.5, `ítem activo, tema ${tema}: texto sobre fondo da ${ratio.toFixed(2)}:1 (mínimo 4.5:1)`);
  }
});

// 3 -------------------------------------------------------------------------
test('el riel se desplaza sin dibujar la barra de scroll', () => {
  const nav = reglas(NAVEGACION).find((r) => r.selector === '.aia-sidebar__nav' && /overflow-y\s*:\s*auto/.test(r.cuerpo));
  assert.ok(nav, 'no se encontró la regla base de .aia-sidebar__nav con overflow-y: auto');
  assert.equal(declara(nav.cuerpo, 'scrollbar-width'), 'none', '.aia-sidebar__nav debe declarar scrollbar-width: none');
  const webkit = reglas(NAVEGACION).find((r) => r.selector === '.aia-sidebar__nav::-webkit-scrollbar');
  assert.ok(webkit && declara(webkit.cuerpo, 'display') === 'none', 'falta `.aia-sidebar__nav::-webkit-scrollbar { display: none }` para Chrome y Safari');
  assert.match(nav.cuerpo, /overflow-y\s*:\s*auto/, 'el riel no debe perder el scroll: overflow-y sigue en auto');
});
