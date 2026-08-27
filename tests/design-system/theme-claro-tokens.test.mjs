// Contrato del sistema de tokens de dos temas para la hoja de Intermedia (Task 8,
// plan 2026-08-26-ola1-torre-etapa-piloto.md, entrada 18 de la Bitácora del piloto).
//
// Decisión de Felipe (entrada 18): el tema claro se ancla a la paleta medida en
// aia.com.co (neutros zinc, casi acromáticos) + el manual AIA v1.0 para los acentos
// de marca (construcción/arquitectura difieren entre sitio y manual; manda el
// manual). El set semántico se define COMPLETO de una vez, no solo lo que la hoja
// consume hoy (deroga el YAGNI del paso 1 del brief). El oscuro NO cambia de píxel:
// se re-declara por los mismos slots `--ds-active-*`, así que este archivo también
// hace de anti-regresión del oscuro vigente.
//
// Este test debe FALLAR HOY: `public/css/design-system/theme-claro.css` no existe
// todavía. Lo implementa el rol B — este archivo es solo el contrato (regla dura
// del repo: quien escribe la prueba no escribe la implementación).
import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { execSync } from 'node:child_process';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const RUTA_TEMA_CLARO = join(raiz, 'public/css/design-system/theme-claro.css');
const RUTA_TOKENS = join(raiz, 'public/css/tokens.css');
const RUTA_OVERRIDES = join(raiz, 'public/css/design-system/entrypoints/theme-overrides.css');

// ---------------------------------------------------------------------------
// Utilidades de color: parseo de #hex / rgb() / rgba(), composición de alpha
// sobre un fondo, luminancia relativa WCAG y ratio de contraste. Puras, sin
// DOM (no dependen de canvas/document) para poder correr con `node --test`.
// ---------------------------------------------------------------------------

const parseColor = (valor) => {
  const v = valor.trim();
  const hex = v.match(/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/);
  if (hex) {
    let h = hex[1];
    if (h.length === 3) {
      h = h.split('').map((c) => c + c).join('');
    }
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    const a = h.length === 8 ? parseInt(h.slice(6, 8), 16) / 255 : 1;
    return [r, g, b, a];
  }
  const rgb = v.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/);
  if (rgb) {
    return [Number(rgb[1]), Number(rgb[2]), Number(rgb[3]), rgb[4] === undefined ? 1 : Number(rgb[4])];
  }
  throw new Error(`color no soportado por el parser puro del test: "${valor}"`);
};

// Compone `fg` (con su alpha) sobre `bg` (opaco o no) — normal alpha compositing.
const componer = (fg, bg) => {
  const [fr, fg_, fb, fa] = fg;
  const [br, bgg, bb, ba] = bg;
  const a = fa + ba * (1 - fa);
  if (a === 0) return [0, 0, 0, 0];
  const mezcla = (canalFg, canalBg) => (canalFg * fa + canalBg * ba * (1 - fa)) / a;
  return [mezcla(fr, br), mezcla(fg_, bgg), mezcla(fb, bb), a];
};

const luminancia = ([r, g, b]) => {
  const canal = (c) => {
    const v = c / 255;
    return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
  };
  return 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b);
};

const ratioContraste = (colorA, colorB, fondoOpaco = [255, 255, 255, 1]) => {
  const a = parseColor(colorA);
  const b = parseColor(colorB);
  const aCompuesto = a[3] < 1 ? componer(a, fondoOpaco) : a;
  const bCompuesto = b[3] < 1 ? componer(b, fondoOpaco) : b;
  const [claro, oscuro] = [luminancia(aCompuesto), luminancia(bCompuesto)].sort((x, y) => y - x);
  return (claro + 0.05) / (oscuro + 0.05);
};

// ---------------------------------------------------------------------------
// Lectura de tokens desde una hoja CSS: valor crudo de una declaración
// `--nombre: valor;` (soporta hex, rgb()/rgba() y `var(--otro)` como alias).
// ---------------------------------------------------------------------------

const valorDeclarado = (css, nombreToken) => {
  const re = new RegExp(`${nombreToken.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*:\\s*([^;]+);`);
  const m = css.match(re);
  return m ? m[1].trim() : null;
};

// Resuelve un valor de token, siguiendo una cadena de `var(--x)` hasta un
// literal de color, buscando en las hojas provistas en orden.
const resolverToken = (nombreToken, hojas, profundidadMax = 6) => {
  let actual = nombreToken;
  for (let i = 0; i < profundidadMax; i += 1) {
    let crudo = null;
    for (const hoja of hojas) {
      crudo = valorDeclarado(hoja, actual);
      if (crudo) break;
    }
    if (!crudo) return null;
    const varMatch = crudo.match(/^var\((--[a-z0-9-]+)\)$/i);
    if (!varMatch) return crudo;
    actual = varMatch[1];
  }
  throw new Error(`cadena de var() demasiado profunda resolviendo ${nombreToken}`);
};

// ---------------------------------------------------------------------------
// Fixtures leídos HOY del repo (verificados contra el código real, no contra
// lo que dicta el encargo) — anti-regresión del tema oscuro vigente.
// ---------------------------------------------------------------------------

let tokensCss = '';
let overridesCss = '';
if (existsSync(RUTA_TOKENS)) tokensCss = readFileSync(RUTA_TOKENS, 'utf8');
if (existsSync(RUTA_OVERRIDES)) overridesCss = readFileSync(RUTA_OVERRIDES, 'utf8');

const DARK_VIGENTE = {
  '--ds-color-bg-canvas-dark': '#0b100d',
  '--ds-color-bg-page-dark': '#111a15',
  '--ds-color-text-primary-dark': '#f7faf8',
  '--ds-color-text-secondary-dark': '#c7d4cc',
  '--ds-color-surface-dark': 'rgba(28, 36, 31, 0.92)',
  '--ds-color-surface-raised-dark': 'rgba(35, 48, 41, 0.86)',
};

test('el tema oscuro vigente sigue intacto en tokens.css (fixture leído hoy del repo)', () => {
  assert.ok(tokensCss.length > 0, 'no se pudo leer public/css/tokens.css para fijar el fixture dark');
  for (const [token, esperado] of Object.entries(DARK_VIGENTE)) {
    const real = valorDeclarado(tokensCss, token);
    assert.equal(real, esperado, `${token} debería seguir siendo ${esperado} y vale: ${real}`);
  }
});

// ---------------------------------------------------------------------------
// Contrato del tema claro: existencia del archivo.
// ---------------------------------------------------------------------------

test('public/css/design-system/theme-claro.css existe', () => {
  assert.ok(
    existsSync(RUTA_TEMA_CLARO),
    'public/css/design-system/theme-claro.css no existe todavía — lo crea el rol B (Task 8, ' +
      'implementación del tema claro). Este test fija el contrato antes de esa implementación.',
  );
});

// A partir de aquí, todo lo que dependa del contenido del archivo se salta con
// un mensaje explícito si el archivo no existe, para no ahogar el fallo
// anterior (que ya es la señal principal) con errores de lectura en cascada.

const temaClaroExiste = existsSync(RUTA_TEMA_CLARO);
const temaClaroCss = temaClaroExiste ? readFileSync(RUTA_TEMA_CLARO, 'utf8') : '';

test('theme-claro.css declara el bloque de selectores del tema light', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  assert.match(
    temaClaroCss,
    /\[data-aia-theme=["']light["']\]/,
    'falta el selector [data-aia-theme="light"] (mismo patrón que theme-overrides.css para dark)',
  );
  assert.match(
    temaClaroCss,
    /\.aia-theme-light\b/,
    'falta el selector .aia-theme-light (mismo patrón que theme-overrides.css para dark)',
  );
});

test('theme-claro.css re-vincula los --ds-active-* hacia los tokens light', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  const activosMinimos = [
    '--ds-active-bg-canvas',
    '--ds-active-bg-page',
    '--ds-active-surface',
    '--ds-active-surface-raised',
    '--ds-active-text-primary',
    '--ds-active-text-secondary',
    '--ds-active-border',
    '--ds-active-border-control',
    '--ds-active-focus-ring',
  ];
  for (const activo of activosMinimos) {
    const valor = valorDeclarado(temaClaroCss, activo);
    assert.ok(valor, `${activo} no se re-declara dentro del bloque light de theme-claro.css`);
    assert.match(
      valor,
      /-light\)?$/,
      `${activo} debería apuntar a un token *-light (o var(--...-light)) y vale: ${valor}`,
    );
  }
});

// ---------------------------------------------------------------------------
// Inventario de slots light — valores exactos decididos por Felipe (entrada 18).
// ---------------------------------------------------------------------------

const SLOTS_LIGHT_EXACTOS = {
  '--ds-color-bg-canvas-light': '#fafafa',
  '--ds-color-bg-page-light': '#ffffff',
  '--ds-color-surface-light': '#ffffff',
  '--ds-color-surface-raised-light': '#f4f4f5',
  '--ds-color-surface-glass-light': 'rgba(255, 255, 255, 0.8)',
  '--ds-color-text-primary-light': '#18181b',
  '--ds-color-text-secondary-light': '#52525b',
  '--ds-color-icon-muted-light': '#a1a1aa',
  '--ds-color-border-separator-light': 'rgba(24, 24, 27, 0.10)',
  '--ds-color-border-control-light': '#71717a',
  '--ds-color-focus-ring-light': '#1a5633',
  '--ds-color-action-primary-bg-light': '#1a5633',
  '--ds-color-action-primary-text-light': '#ffffff',
};

for (const [token, esperado] of Object.entries(SLOTS_LIGHT_EXACTOS)) {
  test(`${token} vale exactamente ${esperado}`, { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
    const real = resolverToken(token, [temaClaroCss, tokensCss]);
    assert.equal(real, esperado, `${token} debería resolver a ${esperado} y resuelve a: ${real}`);
  });
}

test('--ds-color-icon-muted-light nunca se usa como token de texto (2.46:1 sobre blanco, falla AA)', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  // Documentación ejecutable: el propio valor NO alcanza 4.5:1 sobre blanco —
  // confirma por qué el token es SOLO iconografía decorativa, nunca texto.
  const r = ratioContraste('#a1a1aa', '#ffffff');
  assert.ok(r < 4.5, `--ds-color-icon-muted-light da ${r.toFixed(2)}:1 sobre blanco — se esperaba <4.5:1 (por eso no es texto)`);
  assert.doesNotMatch(
    temaClaroCss,
    /--ds-color-text-(primary|secondary)-light\s*:\s*(var\(--ds-color-icon-muted-light\)|#a1a1aa)/,
    'icon-muted-light no debe cablearse como text-primary-light ni text-secondary-light',
  );
});

const ACENTOS_DOMINIO_LIGHT = {
  corporativo: '#1a5633',
  construccion: '#b55211',
  inmobiliario: '#00a499',
  arquitectura: '#6752bf',
};

test('acentos de dominio light usan los valores del manual AIA v1.0 (no del sitio)', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  for (const [dominio, hex] of Object.entries(ACENTOS_DOMINIO_LIGHT)) {
    const buscado = new RegExp(`${hex}`, 'i');
    assert.match(
      temaClaroCss + tokensCss,
      buscado,
      `el acento de dominio "${dominio}" (${hex}, manual AIA v1.0) no aparece declarado en theme-claro.css ni tokens.css`,
    );
  }
});

test('los estados light ya declarados en tokens.css se cablean a los activos, no se re-declaran con otro valor', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  const NIVELES = ['success', 'warning', 'critical', 'info'];
  for (const nivel of NIVELES) {
    for (const canal of ['bg', 'text']) {
      const tokenBase = `--ds-color-state-${nivel}-${canal}-light`;
      const valorBase = valorDeclarado(tokensCss, tokenBase);
      assert.ok(valorBase, `tokens.css no declara ${tokenBase} — se esperaba que ya existiera`);
      // theme-claro.css no debe redeclarar el mismo token con un valor distinto.
      const redeclarado = valorDeclarado(temaClaroCss, tokenBase);
      if (redeclarado) {
        assert.equal(
          redeclarado,
          valorBase,
          `${tokenBase} se redeclara en theme-claro.css con un valor distinto al de tokens.css`,
        );
      }
    }
  }
});

test('--ds-color-action-primary-hover-light existe y su contraste con el texto primario de acción es >=4.5:1', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  const hover = resolverToken('--ds-color-action-primary-hover-light', [temaClaroCss, tokensCss]);
  assert.ok(hover, '--ds-color-action-primary-hover-light no está declarado');
  const texto = resolverToken('--ds-color-action-primary-text-light', [temaClaroCss, tokensCss]) || '#ffffff';
  const r = ratioContraste(hover, texto);
  assert.ok(r >= 4.5, `hover vs texto de acción da ${r.toFixed(2)}:1 — se esperaba >=4.5:1`);
});

// ---------------------------------------------------------------------------
// Pares de contraste WCAG medidos sobre los tokens light resueltos.
// ---------------------------------------------------------------------------

test('pares de contraste WCAG del tema claro', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, async (t) => {
  const hojas = [temaClaroCss, tokensCss];
  const resolver = (token) => {
    const v = resolverToken(token, hojas);
    assert.ok(v, `token ${token} no resuelto para medir contraste`);
    return v;
  };

  const canvas = resolver('--ds-color-bg-canvas-light');
  const page = resolver('--ds-color-bg-page-light');
  const surface = resolver('--ds-color-surface-light');
  const raised = resolver('--ds-color-surface-raised-light');
  const textPrimary = resolver('--ds-color-text-primary-light');
  const textSecondary = resolver('--ds-color-text-secondary-light');
  const actionBg = resolver('--ds-color-action-primary-bg-light');
  const actionText = resolver('--ds-color-action-primary-text-light');
  const focusRing = resolver('--ds-color-focus-ring-light');
  const borderControl = resolver('--ds-color-border-control-light');
  const corporativo = ACENTOS_DOMINIO_LIGHT.corporativo;

  const PARES_4_5 = [
    ['text-primary sobre canvas', textPrimary, canvas],
    ['text-primary sobre page', textPrimary, page],
    ['text-primary sobre surface', textPrimary, surface],
    ['text-primary sobre raised', textPrimary, raised],
    ['text-secondary sobre canvas', textSecondary, canvas],
    ['text-secondary sobre surface', textSecondary, surface],
    ['text-secondary sobre raised', textSecondary, raised],
    ['action-primary-text sobre action-primary-bg', actionText, actionBg],
    ['corporativo como texto sobre canvas', corporativo, canvas],
    ['corporativo como texto sobre surface', corporativo, surface],
  ];
  for (const [nombre, fg, bg] of PARES_4_5) {
    await t.test(`${nombre} >= 4.5:1`, () => {
      const r = ratioContraste(fg, bg);
      assert.ok(r >= 4.5, `${nombre} da ${r.toFixed(2)}:1 — se esperaba >=4.5:1`);
    });
  }

  const PARES_3 = [
    ['focus-ring sobre canvas', focusRing, canvas],
    ['focus-ring sobre surface', focusRing, surface],
    ['border-control sobre canvas', borderControl, canvas],
    ['border-control sobre surface', borderControl, surface],
  ];
  for (const [nombre, fg, bg] of PARES_3) {
    await t.test(`${nombre} >= 3:1`, () => {
      const r = ratioContraste(fg, bg);
      assert.ok(r >= 3, `${nombre} da ${r.toFixed(2)}:1 — se esperaba >=3:1`);
    });
  }

  const NIVELES = ['success', 'warning', 'critical', 'info'];
  for (const nivel of NIVELES) {
    await t.test(`estado ${nivel} light: bg/text >= 4.5:1`, () => {
      const bg = resolver(`--ds-color-state-${nivel}-bg-light`);
      const text = resolver(`--ds-color-state-${nivel}-text-light`);
      const r = ratioContraste(text, bg);
      assert.ok(r >= 4.5, `estado ${nivel} light da ${r.toFixed(2)}:1 — se esperaba >=4.5:1`);
    });
  }
});

test('border-separator-light existe pero sin umbral de contraste (WCAG 1.4.11 no lo gobierna)', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  const valor = resolverToken('--ds-color-border-separator-light', [temaClaroCss, tokensCss]);
  assert.ok(valor, '--ds-color-border-separator-light no está declarado');
});

test('inmobiliario (#00a499) NO alcanza 4.5:1 sobre blanco y por tanto no se usa como texto', { skip: !temaClaroExiste && 'theme-claro.css no existe' }, () => {
  const r = ratioContraste('#00a499', '#ffffff');
  assert.ok(
    r < 4.5 && r > 3,
    `#00a499 sobre blanco da ${r.toFixed(2)}:1 — se esperaba entre 3:1 y 4.5:1 (falla AA de texto, queda como acento de datos/dominio)`,
  );
  assert.doesNotMatch(
    temaClaroCss,
    /--ds-color-text-(primary|secondary)-light\s*:\s*(var\(--ds-color-domain-real-estate[a-z-]*\)|#00a499)/i,
    'inmobiliario (#00a499) no debe cablearse a un slot de texto (text-primary-light / text-secondary-light)',
  );
});

// ---------------------------------------------------------------------------
// Cero hex fuera de tokens en ct-app (fuera de las hojas de tokens mismas).
// ---------------------------------------------------------------------------

test('ct-app/src no declara hex literales fuera de sus archivos de tokens', () => {
  let salida = '';
  try {
    salida = execSync(
      "grep -rnE '#[0-9a-fA-F]{3,8}\\b' --include='*.css' --include='*.tsx' --include='*.ts' ct-app/src " +
        "| grep -v 'ct-app/src/lib/tokens.css'",
      { cwd: raiz, encoding: 'utf8' },
    );
  } catch (err) {
    // grep sale con código 1 cuando no encuentra coincidencias — eso es el caso feliz.
    if (err.status === 1) {
      salida = '';
    } else {
      throw err;
    }
  }
  assert.equal(
    salida.trim(),
    '',
    `ct-app/src tiene hex literales fuera de tokens.css:\n${salida}`,
  );
});
