// Contrato de legibilidad de la barra lateral (revisión visual de Programa General, 2026-09-29).
//
// Tres defectos que Felipe vio en pantalla y que ningún gate atrapaba:
//   1. Tema claro: los íconos del riel colapsado salían en `--ds-active-text-secondary` (gris zinc,
//      sigue el tema de la PÁGINA) sobre un riel que siempre es verde (`--ds-nav-bg-light`):
//      contraste 1.12:1. Los íconos son SVG con aria-hidden, así que axe no los mide.
//   2. Tema oscuro: `body.aia-shell--sidebar .aia-sidebar__link { background: transparent }` (guard
//      de theme-claro-tokens.test.mjs), en la capa `legacy-overrides` (la última), anulaba el fondo
//      del enlace activo y del hover, pero el texto seguía en el color pensado para ir SOBRE ese
//      fondo (`--ds-active-action-text`, casi negro): ilegible.
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
test('el ítem activo y el hover del riel llevan texto del RIEL, no el de la página ni el "sobre relleno"', () => {
  // Causa: `body.aia-shell--sidebar .aia-sidebar__link { background: transparent }` vive en la capa
  // `legacy-overrides` (la última) y anula el relleno del activo y del hover, pero el `color` de
  // esos estados sigue viniendo de capas inferiores y está pensado para leerse SOBRE ese relleno:
  // `--ds-active-action-text` (casi negro en oscuro) o `--ds-active-text-primary` (#18181b en claro).
  // Convención ya decidida en project-selector-react.css: el activo se marca con anillo + texto del
  // riel, sin relleno («lo que aprobó el golden canónico»). Aquí se generaliza a todo el shell.
  const lista = reglas(ADAPTADOR);
  const base = lista.find((r) => /^body\.aia-shell--sidebar \.aia-sidebar__link$/.test(r.selector) && declara(r.cuerpo, 'background') === 'transparent');
  assert.ok(base, 'el guard de theme-claro-tokens (background: transparent del enlace) debe seguir existiendo');

  const activo = lista.find((r) => /^body\.aia-shell--sidebar \.aia-sidebar__link\[aria-current="page"\]$/.test(r.selector));
  assert.ok(activo, 'falta `body.aia-shell--sidebar .aia-sidebar__link[aria-current="page"]` con el color del riel');
  assert.equal(declara(activo.cuerpo, 'color'), 'var(--ds-active-nav-text)');
  assert.equal(declara(activo.cuerpo, 'background'), null, 'el activo no lleva relleno: se marca con anillo, como en project-selector-react.css');
  assert.match(declara(activo.cuerpo, 'box-shadow') ?? '', /--aia-green-light/, 'el activo se marca con el anillo del sistema (--aia-green-light)');

  const hover = lista.find((r) => /^body\.aia-shell--sidebar \.aia-sidebar__link:hover:not\(\[aria-disabled="true"\]\)$/.test(r.selector));
  assert.ok(hover, 'falta el color del hover del enlace del riel: el de la base (--ds-active-text-primary) queda a 2:1 sobre el verde');
  assert.equal(declara(hover.cuerpo, 'color'), 'var(--ds-active-nav-text)');

  // El color del riel se lee sobre el fondo del riel en los dos temas.
  const fondoClaro = rgbDe(resolver('--ds-nav-bg-light', 'claro'));
  for (const [tema, fondo] of [['claro', fondoClaro], ['oscuro', [0, 0, 0]]]) {
    const texto = rgbDe(resolver('--ds-active-nav-text', tema));
    assert.ok(texto, `--ds-active-nav-text (${tema}) no resuelve a #hex`);
    const ratio = contraste(texto, fondo);
    assert.ok(ratio >= 4.5, `texto del riel, tema ${tema}: ${ratio.toFixed(2)}:1 (mínimo 4.5:1)`);
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

// 4 y 5 -----------------------------------------------------------------------
// Revisión visual de Programa General (Felipe, 2026-09-29): «comprime la altura de los botones de la
// sidebar para que quepan en la pantalla sin scroll, en desktop» y «las etiquetas de cada opción
// (como la de "Expandir menú") no se ven en las demás». Las dos son el mismo diseño original del
// riel, «cero scroll + flyouts» (comentario de shell-sidebar.css), que el shell React perdió: en
// #app-shell-nav el nav tiene overflow-y:auto —hay menús largos por rol— y ese overflow recorta las
// píldoras que salen por la derecha. Solución: comprimir en escritorio para que el menú quepa, y
// soltar el recorte SOLO cuando el nav mide que cabe (data-menu-cabe, lo pone BarraLateral).
function bloquesMedia(ruta, condicion) {
  const texto = leer(ruta).replace(/\/\*[\s\S]*?\*\//g, '');
  const salida = [];
  let desde = 0;
  while ((desde = texto.indexOf(`@media ${condicion}`, desde)) !== -1) {
    const abre = texto.indexOf('{', desde);
    let nivel = 1;
    let j = abre + 1;
    while (nivel && j < texto.length) {
      if (texto[j] === '{') nivel += 1;
      else if (texto[j] === '}') nivel -= 1;
      j += 1;
    }
    salida.push(texto.slice(abre + 1, j - 1));
    desde = j;
  }
  return salida.join('\n');
}
const reglasDeTexto = (texto) => [...texto.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((m) => ({ selector: m[1].trim().replace(/\s+/g, ' '), cuerpo: m[2] }));

test('en escritorio los botones del riel miden 36 px (piso WCAG 2.5.8 = 24 px) y el canónico de 44 px no se toca', () => {
  const escritorio = reglasDeTexto(bloquesMedia(ADAPTADOR, '(min-width: 75rem)'));
  const aside = escritorio.find((r) => r.selector === 'body.aia-shell--sidebar .aia-navigation--sidebar[data-shell-pattern="sidebar"]' && declara(r.cuerpo, '--ds-sidebar-item-min-height'));
  assert.ok(aside, 'falta, dentro de `@media (min-width: 75rem)`, la compresión de --ds-sidebar-item-min-height para el aside del shell');
  const alto = declara(aside.cuerpo, '--ds-sidebar-item-min-height');
  const px = Number.parseFloat(alto) * (alto.endsWith('rem') ? 16 : 1);
  assert.ok(px >= 24 && px <= 40, `el alto compacto (${alto} = ${px}px) debe estar entre el piso de WCAG 2.5.8 (24px) y 40px`);
  const util = escritorio.find((r) => /:is\(\.aia-sidebar__toggle, \.aia-sidebar__utility\)$/.test(r.selector) && declara(r.cuerpo, 'min-height'));
  assert.ok(util, 'el toggle y las utilidades del pie también se comprimen en escritorio');
  assert.equal(declara(util.cuerpo, 'min-height'), alto, 'toggle y utilidades miden lo mismo que los ítems');
  // El canónico (laboratorio del design system, design-system-lab-sidebar.mjs exige 44) no cambia.
  assert.equal(resolver('--ds-target-min', 'oscuro'), '44px');
  const base = (declaraciones.get('--ds-sidebar-item-min-height') ?? []).filter((d) => !d.archivo.endsWith('adapters/shell-sidebar.css'));
  assert.ok(base.length > 0 && base.every((d) => d.valor === 'var(--ds-target-min)'), 'el token base de --ds-sidebar-item-min-height sigue siendo var(--ds-target-min) = 44px fuera del shell de escritorio');
});

test('el riel suelta el recorte de etiquetas y flyouts, colapsado o desplegado, solo cuando el menú cabe', () => {
  const escritorio = reglasDeTexto(bloquesMedia(ADAPTADOR, '(min-width: 75rem)'));
  const suelta = escritorio.find((r) => /\[data-menu-cabe="true"\] \.aia-sidebar__nav$/.test(r.selector));
  assert.ok(suelta, 'falta la regla que suelta el overflow del nav cuando data-menu-cabe="true"');
  assert.equal(declara(suelta.cuerpo, 'overflow'), 'visible');
  // Los flyouts de semana salen por la derecha también con el riel desplegado: la regla no puede
  // limitarse al estado colapsado (pedido de Felipe, 2026-09-29).
  assert.ok(!/data-sidebar-state/.test(suelta.selector), 'el overflow visible debe valer colapsado y desplegado, sin filtrar por data-sidebar-state');
  // No debe soltarse siempre: un menú largo (25-30 ítems) conserva su scroll propio
  // (tests/browser/shell-runtime-react-layout.spec.mjs).
  const incondicional = reglas(ADAPTADOR).filter((r) => /#app-shell-nav \.aia-sidebar__nav$/.test(r.selector) && declara(r.cuerpo, 'overflow') === 'visible');
  assert.equal(incondicional.length, 0, 'el overflow visible no puede ser incondicional en #app-shell-nav');
  const base = reglas(ADAPTADOR).find((r) => /#app-shell-nav \.aia-sidebar__nav$/.test(r.selector) && /overflow-y\s*:\s*auto/.test(r.cuerpo));
  assert.ok(base, 'el scroll propio del nav de React (overflow-y: auto) debe seguir siendo la base');
});

test('el modal de semanas se centra en escritorio con margin: auto sobre .aia-dialog .shell-week-dialog', () => {
  const escritorio = reglasDeTexto(bloquesMedia(ADAPTADOR, '(min-width: 75rem)'));
  const regla = escritorio.find((r) => r.selector.trim() === '.aia-dialog .shell-week-dialog');
  assert.ok(regla, 'falta, dentro de `@media (min-width: 75rem)`, la regla que centra el <dialog> de semanas');
  assert.equal(declara(regla.cuerpo, 'margin'), 'auto');
});
