---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-08-28
areas: [design-system]
fuente: docs/superpowers/plans/2026-08-28-fase-cero-temas-y-forma.md
resumen: "Construir la capa compartida de las dos specs hermanas — paleta clara calibrada, bandera de gravedad, forma y densidad nuevas, conmutador de tema — de la que…"
---

# Fase cero de temas y forma — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir la capa compartida de las dos specs hermanas — paleta clara calibrada,
bandera de gravedad, forma y densidad nuevas, conmutador de tema — de la que todos los
módulos van a consumir, con sus guards en verde.

**Architecture:** Todo vive en la capa de tokens (`public/css/tokens.css`), las hojas de
tema (`theme-claro.css`, `theme-overrides.css`) y el core del design system
(`public/css/design-system/`), con un guard nuevo por contrato tocado. Ningún módulo se
migra en esta fase: la fase cero termina cuando las piezas existen, están medidas y el
laboratorio las exhibe. Los módulos (PG primero) tienen planes propios posteriores.

**Tech Stack:** CSS custom properties en `@layer theme`, Node test runner
(`tests/design-system/*.test.mjs`), PHP 8.3 en Docker para el exportador, JS vanilla para
el conmutador.

**Specs:**
- `docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design.md` (D1–D24)
- `docs/superpowers/specs/2026-08-28-forma-bordes-radios-relieves-design.md` (F1–F40)

**Serie:** Plan 1 de la serie. El plan de Programa General (primer módulo, D23) se escribe
al cerrar este, con el método destilado. Después: PI, PS, PDC/SPA, resto, admin (tokens),
Torre (cierre).

## Global Constraints

- Docker Compose es el runtime: PHP corre con `docker compose exec app …`, nunca host.
- Los tests Node corren con `node --test tests/design-system/<archivo>.test.mjs`.
- Tema claro = «papel de obra» (D1); dark no cambia salvo lo aquí listado (D18).
- Regla madre de forma: «la forma anuncia la función» (spec de forma, sección homónima).
- Todo hex nuevo vive en `tokens.css`; ningún hex suelto en hojas de componente.
- Publicación del frente por PR con CI verde (política 2026-08-26 de AGENTS.md).
- La rama del frente: `temas-y-forma-fase-cero`, colgada de `main`.
- No committear `.env`, evidencia local ni trabajo ajeno; staging selectivo siempre.

---

### Task 1: La paleta clara de estado, con su guard

**Files:**
- Modify: `public/css/tokens.css` (bloque nuevo tras `--ds-state-row-neutral`, ~línea 425)
- Test: `tests/design-system/paleta-clara-estado.test.mjs` (nuevo)

**Interfaces:**
- Produces: 8 tokens `--ds-state-tint-<hue>-light` (fondos de fila claros), 16 tokens
  `--ds-state-solid-<hue>-light` / `--ds-state-solid-<hue>-light-text` (chips claros),
  que `theme-claro.css` (Task 2) y los planes de módulo consumen.

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/paleta-clara-estado.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('public/css/tokens.css', 'utf8');

// D7+D5 spec temas / F-tabla spec forma: valores calibrados t=0,5 (azul t=0,7).
const TINTES = {
  '--ds-state-tint-red-light': '#f6c3c3',
  '--ds-state-tint-orange-light': '#f8c9a5',
  '--ds-state-tint-amber-light': '#ffecb2',
  '--ds-state-tint-violet-light': '#dad4f5',
  '--ds-state-tint-green-light': '#c2e2d3',
  '--ds-state-tint-blue-light': '#c1d5ec',
  '--ds-state-tint-teal-light': '#c8efec',
  '--ds-state-tint-neutral-light': '#e4e4e7',
};

// Chips claros: principal del manual; ámbar/teal/azul en peldaño oscuro (D6/D7).
const CHIPS = {
  '--ds-state-solid-red-light': '#c62828',
  '--ds-state-solid-orange-light': '#b55211',
  '--ds-state-solid-amber-light': '#a16207',
  '--ds-state-solid-violet-light': '#6752bf',
  '--ds-state-solid-green-light': '#1a5633',
  '--ds-state-solid-blue-light': '#2a5a8f',
  '--ds-state-solid-teal-light': '#007a71',
  '--ds-state-solid-neutral-light': '#52525b',
};

for (const [token, hex] of Object.entries({ ...TINTES, ...CHIPS })) {
  test(`${token} declara ${hex}`, () => {
    const re = new RegExp(`${token}\\s*:\\s*${hex}\\b`, 'i');
    assert.match(css, re, `${token} debe declarar ${hex} en tokens.css`);
  });
}

// Contrastes: texto blanco de chip >= 4.5 (AA); chip vs su tinte >= 3 (WCAG 1.4.11).
const lin = (c) => { const v = c / 255; return v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };
const lum = (hex) => {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
  return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
};
const ratio = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p); return (x + 0.05) / (y + 0.05); };

const PAREJAS = [
  ['#c62828', '#f6c3c3'], ['#b55211', '#f8c9a5'], ['#a16207', '#ffecb2'],
  ['#6752bf', '#dad4f5'], ['#1a5633', '#c2e2d3'], ['#2a5a8f', '#c1d5ec'],
  ['#007a71', '#c8efec'], ['#52525b', '#e4e4e7'],
];
for (const [chip, tinte] of PAREJAS) {
  test(`chip ${chip} alcanza 3:1 contra su tinte ${tinte}`, () => {
    assert.ok(ratio(chip, tinte) >= 3, `${ratio(chip, tinte).toFixed(2)}:1`);
  });
  test(`blanco alcanza 4.5:1 sobre chip ${chip}`, () => {
    assert.ok(ratio('#ffffff', chip) >= 4.5, `${ratio('#ffffff', chip).toFixed(2)}:1`);
  });
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test tests/design-system/paleta-clara-estado.test.mjs`
Expected: FAIL — los tokens no existen todavía en `tokens.css`.

- [ ] **Step 3: Declarar los 24 tokens en tokens.css**

En `public/css/tokens.css`, después del bloque `--ds-state-row-*` (~línea 425), añadir:

```css
    /* PALETA CLARA DE ESTADO (specs 2026-08-28, D5/D6/D7 temas + F-tabla forma).
       Tintes de fila: interpolación t=0,5 entre muy_claro y claro del manual —
       primer punto medido donde los 28 pares se distinguen (dE-OK >= 0,035) con
       texto #1a1a1a a 11,2:1. El azul va a t=0,7 (rampa --aia-blue-* rescatada,
       rebautizada «En marcha») para despegar del violeta (dE 0,0364).
       Chips: tono principal de cada familia con texto blanco; ámbar, teal y azul
       usan un peldaño más oscuro que su principal porque el principal falla AA
       con blanco (azul 4,06:1; medición en paleta-clara-estado.test.mjs).
       El texto sobre tinte claro es el texto normal (#1a1a1a): NO nace una
       familia de textos pastel claros (consecuencia 6 de la spec de temas). */
    --ds-state-tint-red-light: #f6c3c3;
    --ds-state-tint-orange-light: #f8c9a5;
    --ds-state-tint-amber-light: #ffecb2;
    --ds-state-tint-violet-light: #dad4f5;
    --ds-state-tint-green-light: #c2e2d3;
    --ds-state-tint-blue-light: #c1d5ec;
    --ds-state-tint-teal-light: #c8efec;
    --ds-state-tint-neutral-light: #e4e4e7;

    --ds-state-solid-red-light: #c62828;
    --ds-state-solid-red-light-text: #ffffff;
    --ds-state-solid-orange-light: #b55211;
    --ds-state-solid-orange-light-text: #ffffff;
    --ds-state-solid-amber-light: #a16207;
    --ds-state-solid-amber-light-text: #ffffff;
    --ds-state-solid-violet-light: #6752bf;
    --ds-state-solid-violet-light-text: #ffffff;
    --ds-state-solid-green-light: #1a5633;
    --ds-state-solid-green-light-text: #ffffff;
    --ds-state-solid-blue-light: #2a5a8f;
    --ds-state-solid-blue-light-text: #ffffff;
    --ds-state-solid-teal-light: #007a71;
    --ds-state-solid-teal-light-text: #ffffff;
    --ds-state-solid-neutral-light: #52525b;
    --ds-state-solid-neutral-light-text: #ffffff;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test tests/design-system/paleta-clara-estado.test.mjs`
Expected: PASS — 32 asserts (16 declaraciones + 16 contrastes) en verde. Si el ámbar
`#a16207` falla algún contraste, oscurecerlo de a 4 puntos de luminancia hasta pasar y
anotar el hex final en la spec de temas (D7 lo deja como «candidato a medir»).

- [ ] **Step 5: Commit**

```bash
git add public/css/tokens.css tests/design-system/paleta-clara-estado.test.mjs
git commit -m "feat(ds): paleta clara de estado calibrada con guard de contrastes"
```

---

### Task 2: El tema claro completo — theme-claro.css re-vincula el estado

**Files:**
- Modify: `public/css/design-system/theme-claro.css`
- Modify: `public/css/tokens.css` (dividir `--ds-shell-background` por tema, ~línea 604)
- Test: `tests/design-system/theme-claro-tokens.test.mjs` (ampliar)

**Interfaces:**
- Consumes: los 24 tokens de Task 1.
- Produces: bloque `[data-aia-theme="light"]` con `--ds-active-state-*` re-vinculados y
  `--ds-active-shell-background` sin velo — lo que consume todo módulo en claro.

- [ ] **Step 1: Ampliar el test del tema claro**

Añadir a `tests/design-system/theme-claro-tokens.test.mjs`:

```js
test('theme-claro re-vincula los tintes de estado a la paleta -light', () => {
  const css = readFileSync('public/css/design-system/theme-claro.css', 'utf8');
  for (const hue of ['red', 'orange', 'amber', 'violet', 'green', 'blue', 'teal', 'neutral']) {
    assert.match(css, new RegExp(`--ds-active-state-tint-${hue}\\s*:\\s*var\\(--ds-state-tint-${hue}-light\\)`));
    assert.match(css, new RegExp(`--ds-active-state-solid-${hue}\\s*:\\s*var\\(--ds-state-solid-${hue}-light\\)`));
  }
  assert.match(css, /--ds-active-shell-background\s*:\s*linear-gradient/,
    'el shell claro es neutro: sin radial verde (D11)');
});

test('theme-overrides ancla los --ds-active-state-* oscuros a los valores vigentes', () => {
  const css = readFileSync('public/css/design-system/entrypoints/theme-overrides.css', 'utf8');
  assert.match(css, /--ds-active-state-tint-red\s*:\s*var\(--ds-state-tint-red\)/);
  assert.match(css, /--ds-active-state-solid-red\s*:\s*var\(--ds-state-solid-red\)/);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/theme-claro-tokens.test.mjs`
Expected: FAIL — los alias `--ds-active-state-*` no existen.

- [ ] **Step 3: Declarar la capa activa de estado en ambos temas**

En `theme-overrides.css`, dentro del bloque `:root, [data-aia-theme="dark"]`:

```css
    /* Vocabulario de estado por tema (spec temas D7): la capa activa que los
       módulos consumen. En penumbra apunta a los valores vigentes (D18: la
       penumbra no se recalibra). */
    --ds-active-state-tint-red: var(--ds-state-tint-red);
    --ds-active-state-tint-orange: var(--ds-state-tint-orange);
    --ds-active-state-tint-amber: var(--ds-state-tint-amber);
    --ds-active-state-tint-violet: var(--ds-state-tint-violet);
    --ds-active-state-tint-green: var(--ds-state-tint-green);
    --ds-active-state-tint-blue: var(--ds-state-tint-blue);
    --ds-active-state-tint-teal: var(--ds-state-tint-teal);
    --ds-active-state-tint-neutral: var(--ds-state-tint-neutral);
    --ds-active-state-solid-red: var(--ds-state-solid-red);
    --ds-active-state-solid-red-text: var(--ds-state-solid-red-text);
    --ds-active-state-solid-orange: var(--ds-state-solid-orange);
    --ds-active-state-solid-orange-text: var(--ds-state-solid-orange-text);
    --ds-active-state-solid-amber: var(--ds-state-solid-amber);
    --ds-active-state-solid-amber-text: var(--ds-state-solid-amber-text);
    --ds-active-state-solid-violet: var(--ds-state-solid-violet);
    --ds-active-state-solid-violet-text: var(--ds-state-solid-violet-text);
    --ds-active-state-solid-green: var(--ds-state-solid-green);
    --ds-active-state-solid-green-text: var(--ds-state-solid-green-text);
    --ds-active-state-solid-blue: var(--ds-state-solid-blue);
    --ds-active-state-solid-blue-text: var(--ds-state-solid-blue-text);
    --ds-active-state-solid-teal: var(--ds-state-solid-teal);
    --ds-active-state-solid-teal-text: var(--ds-state-solid-teal-text);
    --ds-active-state-solid-neutral: var(--ds-state-solid-neutral);
    --ds-active-state-solid-neutral-text: var(--ds-state-solid-neutral-text);
    --ds-active-shell-background: var(--ds-shell-background);
```

En `theme-claro.css`, dentro del bloque `[data-aia-theme="light"]`, el espejo con
sufijo `-light` en el lado derecho (mismas 25 líneas, cada `var(--ds-state-…)` cambia a
`var(--ds-state-…-light)`), más:

```css
    /* Lienzo neutro (D11): sin el radial verde — el contraste no depende de la
       zona de pantalla y el tinte verde de «controlado» no se diluye. */
    --ds-active-shell-background:
      linear-gradient(180deg, var(--ds-color-bg-page-light), var(--ds-color-bg-canvas-light));
```

En `tokens.css`, `--ds-shell-background` no se toca (queda como valor de penumbra); en
`core.css` el `.aia-shell` cambia `background: var(--ds-shell-background)` por
`background: var(--ds-active-shell-background)`.

- [ ] **Step 4: Run to verify it passes**

Run: `node --test tests/design-system/theme-claro-tokens.test.mjs`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add public/css/design-system/theme-claro.css public/css/design-system/entrypoints/theme-overrides.css public/css/design-system/core.css tests/design-system/theme-claro-tokens.test.mjs
git commit -m "feat(ds): capa activa de estado por tema y lienzo claro neutro"
```

---

### Task 3: La bandera de gravedad — componente aia-flag en ambos temas

**Files:**
- Create: `public/css/design-system/components/gravity-flag.css`
- Modify: `public/css/tokens.css` (tokens de bandera; retirar `--ds-severity-rail-*`
  queda para el plan de PG — los consume el módulo vivo)
- Test: `tests/design-system/gravity-flag.test.mjs` (nuevo)

**Interfaces:**
- Produces: clases `.aia-flag` (contenedor de fila con bandera), `.aia-flag--urgent`,
  `.aia-flag--attention`; tokens `--ds-flag-width: 26px`, `--ds-flag-urgent-bg`,
  `--ds-flag-attention-bg`, `--ds-flag-urgent-ink`, `--ds-flag-attention-ink` (por
  tema, vía `--ds-active-flag-*`). Glifos como `background-image` SVG data-URI
  (octágono y reloj **dibujados con trazos** — D8: nunca fuente de emoji).

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/gravity-flag.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync('public/css/design-system/components/gravity-flag.css', 'utf8');
const tokens = readFileSync('public/css/tokens.css', 'utf8');

test('la bandera mide 26px y solo existen dos niveles', () => {
  assert.match(tokens, /--ds-flag-width\s*:\s*26px/);
  assert.match(css, /\.aia-flag--urgent\b/);
  assert.match(css, /\.aia-flag--attention\b/);
  assert.doesNotMatch(css, /\.aia-flag--ready\b/, 'D5: listo NO lleva bandera');
  assert.doesNotMatch(css, /\.aia-flag--healthy\b/, 'D5: la calma NO lleva bandera');
});

test('los glifos son SVG dibujado, nunca fuente', () => {
  assert.match(css, /url\("data:image\/svg\+xml/, 'glifo como data-URI');
  assert.doesNotMatch(css, /Font Awesome|content\s*:\s*"\\/, 'D8: sin fuente de iconos en la bandera');
});

test('el color de la bandera es por nivel, no por matiz (D4)', () => {
  assert.match(tokens, /--ds-flag-urgent-bg\s*:\s*var\(--ds-state-solid-red\)/);
  assert.match(tokens, /--ds-flag-urgent-bg-light\s*:\s*var\(--ds-state-solid-red-light\)/);
  assert.match(tokens, /--ds-flag-attention-bg\s*:\s*var\(--ds-state-solid-amber\)/);
  assert.match(tokens, /--ds-flag-attention-bg-light\s*:\s*var\(--ds-state-solid-amber-light\)/);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/gravity-flag.test.mjs`
Expected: FAIL — el archivo no existe.

- [ ] **Step 3: Tokens de bandera en tokens.css**

Tras el bloque de la paleta clara (Task 1):

```css
    /* BANDERA DE GRAVEDAD (spec temas D2-D5, D8; spec forma F3). Sustituye al
       filete --ds-severity-rail-* (su retiro efectivo va con el plan de PG, que
       es quien lo consume hoy). Color por NIVEL (D4): rojo urgente, ámbar
       atención — ratifica «urgencia now siempre usa critical en el acento».
       Solo dos niveles (D5): la ausencia es la señal de calma. */
    --ds-flag-width: 26px;
    --ds-flag-urgent-bg: var(--ds-state-solid-red);
    --ds-flag-urgent-ink: var(--ds-state-solid-red-text);
    --ds-flag-attention-bg: var(--ds-state-solid-amber);
    --ds-flag-attention-ink: var(--ds-state-solid-amber-text);
    --ds-flag-urgent-bg-light: var(--ds-state-solid-red-light);
    --ds-flag-urgent-ink-light: var(--ds-state-solid-red-light-text);
    --ds-flag-attention-bg-light: var(--ds-state-solid-amber-light);
    --ds-flag-attention-ink-light: var(--ds-state-solid-amber-light-text);
```

Y en `theme-overrides.css` / `theme-claro.css` los activos:
`--ds-active-flag-urgent-bg: var(--ds-flag-urgent-bg);` (dark) /
`…: var(--ds-flag-urgent-bg-light);` (claro) — cuatro pares por tema.

- [ ] **Step 4: El componente**

```css
/* public/css/design-system/components/gravity-flag.css
   Bandera de gravedad: pestaña sólida de altura completa al inicio de la fila,
   con glifo dibujado (octágono de pare = urgente; reloj = atención — D8, mezcla
   elegida por Felipe). La esquina superior del marco pertenece a la cabecera
   (F3): la bandera nunca la toca. */
@layer components {
  .aia-flag {
    position: relative;
    padding-inline-start: var(--ds-flag-width);
  }

  .aia-flag::before {
    content: "";
    position: absolute;
    inset-block: 0;
    inset-inline-start: 0;
    width: var(--ds-flag-width);
    background-color: var(--_flag-bg);
    background-repeat: no-repeat;
    background-position: center;
    background-size: 14px 14px;
    background-image: var(--_flag-glyph);
  }

  .aia-flag--urgent {
    --_flag-bg: var(--ds-active-flag-urgent-bg);
    /* Octágono de pare, trazo 2px, tinta del nivel. currentColor no sirve en
       data-URI: el trazo va literal por tema vía los dos bloques de abajo. */
    --_flag-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linejoin='round'%3E%3Cpath d='M8 3h8l5 5v8l-5 5H8l-5-5V8z'/%3E%3Cpath d='M12 8v5'/%3E%3Ccircle cx='12' cy='16.5' r='0.5' fill='%23ffffff'/%3E%3C/svg%3E");
  }

  .aia-flag--attention {
    --_flag-bg: var(--ds-active-flag-attention-bg);
    /* Reloj: el tiempo se acaba. Tinta oscura sobre ámbar. */
    --_flag-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233a2c04' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3.5 2.5'/%3E%3C/svg%3E");
  }

  /* En penumbra la tinta del octágono es la oscura del chip rojo (#3c0a06). */
  :root .aia-flag--urgent,
  [data-aia-theme="dark"] .aia-flag--urgent {
    --_flag-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233c0a06' stroke-width='2' stroke-linejoin='round'%3E%3Cpath d='M8 3h8l5 5v8l-5 5H8l-5-5V8z'/%3E%3Cpath d='M12 8v5'/%3E%3Ccircle cx='12' cy='16.5' r='0.5' fill='%233c0a06'/%3E%3C/svg%3E");
  }
  [data-aia-theme="light"] .aia-flag--urgent {
    --_flag-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linejoin='round'%3E%3Cpath d='M8 3h8l5 5v8l-5 5H8l-5-5V8z'/%3E%3Cpath d='M12 8v5'/%3E%3Ccircle cx='12' cy='16.5' r='0.5' fill='%23ffffff'/%3E%3C/svg%3E");
  }
}
```

Registrar la hoja en el entrypoint: en
`public/css/design-system/entrypoints/core.css` añadir
`@import url("../components/gravity-flag.css") layer(components);` junto a los demás
componentes.

- [ ] **Step 5: Run to verify it passes**

Run: `node --test tests/design-system/gravity-flag.test.mjs`
Expected: PASS.

- [ ] **Step 6: Verificación visual en el laboratorio**

Añadir al laboratorio (`views/design-system-lab` o la vista que
`docs/design-system/manifests/laboratory.json` declare) un escenario con cuatro filas:
urgente, atención, calma con tinte, calma neutra — en ambos temas. Abrir con el navegador
integrado (`preview_start` + navegar a la ruta del laboratorio) y confirmar: bandera
visible de reojo, glifos nítidos a 14px, sin esquina mordida bajo cabecera.

- [ ] **Step 7: Commit**

```bash
git add public/css/tokens.css public/css/design-system/components/gravity-flag.css public/css/design-system/entrypoints/core.css tests/design-system/gravity-flag.test.mjs
git commit -m "feat(ds): bandera de gravedad con glifos dibujados, ambos temas"
```

---

### Task 4: Forma — radios de dato, pozo, botón hundido, pisos, foco doble, scroll

**Files:**
- Modify: `public/css/tokens.css` (tokens de forma nuevos)
- Modify: `public/css/design-system/core.css` (botón, campo, foco)
- Test: `tests/design-system/forma-fase-cero.test.mjs` (nuevo)

**Interfaces:**
- Produces: `--ds-radius-data: 0.25rem` (4px, el radio del dato — F1/F2),
  `--ds-elevation-rest/float/top` (F7), `--ds-focus-ring-double` (F20),
  `--ds-input-well-bg` (F10), scroll teñido global (F29), botón hundido (F9).

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/forma-fase-cero.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync('public/css/tokens.css', 'utf8');
const core = readFileSync('public/css/design-system/core.css', 'utf8');

test('F1/F2: existe el radio de dato y es 4px', () => {
  assert.match(tokens, /--ds-radius-data\s*:\s*0\.25rem/);
});

test('F7: tres pisos de elevación con nombre', () => {
  assert.match(tokens, /--ds-elevation-rest\s*:\s*var\(--ds-shadow-xs\)/);
  assert.match(tokens, /--ds-elevation-float\s*:\s*var\(--ds-shadow-md\)/);
  assert.match(tokens, /--ds-elevation-top\s*:\s*var\(--ds-shadow-lg\)/);
});

test('F9: el botón se hunde al presionar', () => {
  assert.match(core, /\.aia-btn:active\b[^}]*translateY\(1px\)/s);
  assert.match(core, /\.aia-btn:active\b[^}]*inset/s);
});

test('F10: el campo es pozo', () => {
  assert.match(tokens, /--ds-input-well-bg\s*:\s*var\(--ds-active-surface-raised\)/);
  assert.match(core, /\.aia-input[^{]*\{[^}]*var\(--ds-input-well-bg\)/s);
});

test('F20: anillo de foco doble — halo del fondo + marca', () => {
  assert.match(tokens, /--ds-shadow-focus\s*:\s*0 0 0 2px var\(--ds-active-bg-canvas\),\s*0 0 0 4px var\(--ds-active-focus-ring\)/);
});

test('F29: scroll teñido con tokens del tema', () => {
  assert.match(core, /scrollbar-color\s*:\s*var\(--ds-active-border-control\)\s+transparent/);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/forma-fase-cero.test.mjs`
Expected: FAIL en los seis tests.

- [ ] **Step 3: Tokens de forma en tokens.css**

Junto a los semánticos de radio (~línea 541):

```css
    /* Radio de DATO (spec forma F1/F2): chips de estado, celdas destacadas y
       banderas — «el marco abraza, el dato es recto». Los controles y marcos
       conservan su escala; SOLO el vocabulario de la tabla usa este. */
    --ds-radius-data: 0.25rem;
```

Junto a las sombras (~línea 555):

```css
    /* Tres pisos de elevación (F7): la sombra dice qué ES la pieza.
       reposo = pegado al papel · flotante = menús y avisos · techo = modal.
       Los --ds-shadow-* quedan como materia prima; los módulos consumen ESTOS. */
    --ds-elevation-rest: var(--ds-shadow-xs);
    --ds-elevation-float: var(--ds-shadow-md);
    --ds-elevation-top: var(--ds-shadow-lg);

    /* Pozo del campo (F10): el fondo un paso hundido — dos canales dicen
       «aquí se escribe» junto al borde de control medido. */
    --ds-input-well-bg: var(--ds-active-surface-raised);
```

Reemplazar la declaración existente de `--ds-shadow-focus` (~línea 559) por:

```css
    /* Foco DOBLE (F20): halo del color del fondo + anillo de marca. Visible
       sobre los ocho tintes y los dos temas por construcción. */
    --ds-shadow-focus: 0 0 0 2px var(--ds-active-bg-canvas), 0 0 0 4px var(--ds-active-focus-ring);
```

Y en la sección de sombras, añadir la escala de tinta clara que `theme-claro.css`
re-vincula (D10):

```css
    /* Sombras de tinta para papel (spec temas D10): las de penumbra manchan
       sobre blanco. theme-claro re-vincula --ds-shadow-* a estas. */
    --ds-shadow-xs-light: 0 1px 2px rgba(24, 24, 27, 0.06);
    --ds-shadow-sm-light: 0 1px 3px rgba(24, 24, 27, 0.08);
    --ds-shadow-md-light: 0 8px 24px rgba(24, 24, 27, 0.14);
    --ds-shadow-lg-light: 0 16px 48px rgba(24, 24, 27, 0.2);
    --ds-shadow-xl-light: 0 24px 72px rgba(24, 24, 27, 0.26);
```

(En `theme-claro.css`: `--ds-shadow-xs: var(--ds-shadow-xs-light);` … los cinco.)

- [ ] **Step 4: Core — botón hundido, campo pozo, scroll teñido**

En `core.css`, tras `.aia-btn:hover` (~línea 225):

```css
  /* F9: plano que se hunde — el clic se ve como se siente. Contacto, no vuelo. */
  .aia-btn:active {
    transform: translateY(1px);
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.25);
  }
```

En la regla de `.aia-input, .aia-select, .aia-textarea` (~línea 296), cambiar
`background: var(--ds-active-surface);` por `background: var(--ds-input-well-bg);`.

Al final de la capa `components` de `core.css`:

```css
  /* F29: scroll teñido a tamaño nativo — color del tema, agarre del sistema. */
  .aia-shell {
    scrollbar-color: var(--ds-active-border-control) transparent;
  }
  .aia-table-shell, .aia-grid-shell, .aia-page {
    scrollbar-color: var(--ds-active-border-control) transparent;
  }
```

- [ ] **Step 5: Run to verify it passes**

Run: `node --test tests/design-system/forma-fase-cero.test.mjs`
Expected: PASS (6/6).

- [ ] **Step 6: Regression — la suite estática del DS sigue en verde**

Run: `npm run test:design-system:static`
Expected: PASS. Si el contrato de partición o algún hash de contrato protesta por
`core.css`/`tokens.css`, leer el contrato que falla ANTES de tocar el baseline: los
cambios de esta tarea son deliberados y el contrato se actualiza con su procedimiento
propio, nunca a mano para forzar verde.

- [ ] **Step 7: Commit**

```bash
git add public/css/tokens.css public/css/design-system/core.css public/css/design-system/theme-claro.css tests/design-system/forma-fase-cero.test.mjs
git commit -m "feat(ds): radios de dato, pisos de elevacion, boton hundido, pozo, foco doble y scroll tenido"
```

---

### Task 5: La escala de tabla nueva — 12/11/13, tabulares, perímetro, densidades

**Files:**
- Modify: `public/css/tokens.css` (bloque «Table contract», ~línea 697)
- Test: `tests/design-system/tabla-escala.test.mjs` (nuevo)

**Interfaces:**
- Produces: los tokens de tabla re-medidos que el plan de PG consume:
  `--ds-table-cell-font-size: 0.75rem` (12px, F31),
  `--ds-table-header-font-size: 0.6875rem` (11px, F33),
  `--ds-table-chapter-font-size: 0.8125rem` (13px, F36),
  `--ds-table-cell-pad-x-edge: 1rem` (16px, F35),
  `--ds-table-row-h-read: 2rem` (32px listado, F34),
  `--ds-table-row-h-touch: 1.75rem` (28px, F11),
  `--ds-table-row-h-projector: 2.25rem` + `--ds-table-cell-font-size-projector: 0.9375rem` (F40),
  `--ds-table-numeric` (clase utilitaria tabular, F32),
  poda de 3 líneas (F13) como utilitaria `.aia-cell-clamp`.

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/tabla-escala.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const tokens = readFileSync('public/css/tokens.css', 'utf8');
const core = readFileSync('public/css/design-system/core.css', 'utf8');

const ESCALA = {
  '--ds-table-cell-font-size': '0\\.75rem',        // F31: dato 12px (decisión de Felipe)
  '--ds-table-header-font-size': '0\\.6875rem',    // F33: cabecera 11px
  '--ds-table-chapter-font-size': '0\\.8125rem',   // F36: capítulo 13px
  '--ds-table-cell-pad-x-edge': '1rem',            // F35: perímetro 16px
  '--ds-table-row-h-read': '2rem',                 // F34: listado 32px
  '--ds-table-row-h-touch': '1\\.75rem',           // F11: táctil 28px
  '--ds-table-row-h-projector': '2\\.25rem',       // F40: proyector 36px
  '--ds-table-cell-font-size-projector': '0\\.9375rem', // F40: letra 15px
};
for (const [token, val] of Object.entries(ESCALA)) {
  test(`${token} = ${val.replaceAll('\\\\', '')}`, () => {
    assert.match(tokens, new RegExp(`${token}\\s*:\\s*${val}`));
  });
}

test('F32: utilitaria de cifras tabulares a la derecha', () => {
  assert.match(core, /\.aia-cell-numeric\s*\{[^}]*font-variant-numeric\s*:\s*tabular-nums/s);
  assert.match(core, /\.aia-cell-numeric\s*\{[^}]*text-align\s*:\s*right/s);
});

test('F13: poda de tres líneas con line-clamp', () => {
  assert.match(core, /\.aia-cell-clamp\s*\{[^}]*-webkit-line-clamp\s*:\s*3/s);
});

test('F40: el preset proyector se activa por data-density', () => {
  assert.match(tokens, /\[data-density="projector"\]/);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/tabla-escala.test.mjs`
Expected: FAIL.

- [ ] **Step 3: Tokens — actualizar el Table contract**

En el bloque «Table contract» de `tokens.css`:
cambiar `--ds-table-cell-font-size: 0.8125rem;` → `0.75rem;` y
`--ds-table-header-font-size: 0.75rem;` → `0.6875rem;`, actualizando el comentario del
bloque: la procedencia pasa a ser «spec forma 2026-08-28 F31/F33, decisión de Felipe:
densidad primero — dato 12px, cabecera 11px un peldaño abajo, jerarquía por tamaño en
la dirección correcta». Añadir al mismo bloque:

```css
    --ds-table-chapter-font-size: 0.8125rem; /* F36: capítulo 13px negrilla + filete */
    --ds-table-cell-pad-x-edge: 1rem;        /* F35: perímetro 16px contra el marco */
    --ds-table-row-h-read: 2rem;             /* F34: listado cómodo 32px */
    --ds-table-row-h-touch: 1.75rem;         /* F11: hoja táctil 28px (decisión de Felipe; su edición vive en el panel táctil — plan de PG) */
    --ds-table-row-h-projector: 2.25rem;     /* F40: preset proyector, fila 36px */
    --ds-table-cell-font-size-projector: 0.9375rem; /* F40: letra 15px en pared */
```

Y al final del `@layer theme` (junto a los `[data-density]` existentes):

```css
  [data-density="projector"] {
    --ds-table-row-h: var(--ds-table-row-h-projector);
    --ds-table-header-h: var(--ds-table-row-h-projector);
    --ds-table-cell-font-size: var(--ds-table-cell-font-size-projector);
  }
```

- [ ] **Step 4: Utilitarias en core.css**

En `@layer utilities` de `core.css`:

```css
  /* F32: columnas de cifras — unidades bajo unidades. */
  .aia-cell-numeric {
    font-variant-numeric: tabular-nums;
    text-align: right;
  }

  /* F13: el texto libre de celda se poda a tres líneas; el completo vive en
     tooltip, panel y exportación (patrón displayShort extendido). */
  .aia-cell-clamp {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
  }
```

- [ ] **Step 5: Run to verify it passes**

Run: `node --test tests/design-system/tabla-escala.test.mjs`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add public/css/tokens.css public/css/design-system/core.css tests/design-system/tabla-escala.test.mjs
git commit -m "feat(ds): escala de tabla 12/11/13, cifras tabulares, perimetro, densidades por funcion y preset proyector"
```

---

### Task 6: El conmutador de tema — claro de entrada, botón visible, por aparato

**Files:**
- Modify: `public/js/modules/aia_ui/theme-bootstrap.js`
- Create: `public/js/modules/aia_ui/theme-toggle.js`
- Test: `tests/design-system/theme-default.test.mjs` (nuevo)

**Interfaces:**
- Consumes: `[data-aia-theme]` que las hojas de tema ya leen.
- Produces: default claro (D12), persistencia `localStorage['aia-theme']` por aparato
  (D14), función global `window.aiaThemeToggle()` y el botón «Volver a oscuro» que la
  nav monta (D13; su retiro al menú del usuario a un mes es tarea del plan de cierre).

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/theme-default.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const boot = readFileSync('public/js/modules/aia_ui/theme-bootstrap.js', 'utf8');

test('D12: el default del producto es CLARO', () => {
  assert.match(boot, /const\s+DEFAULT_THEME\s*=\s*['"]light['"]/,
    'theme-bootstrap declara light como default');
  assert.doesNotMatch(boot, /const\s+DEFAULT_THEME\s*=\s*['"]dark['"]/);
});

test('D14: la preferencia persiste local por aparato', () => {
  assert.match(boot, /localStorage\.getItem\(\s*['"]aia-theme['"]\s*\)/);
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/theme-default.test.mjs`
Expected: FAIL — hoy el default es dark.

- [ ] **Step 3: Invertir el default y extraer la constante**

En `theme-bootstrap.js`: localizar dónde aplica el tema por defecto (hoy aplica dark
salvo preferencia persistida) y reescribir el arranque a:

```js
// D12 (spec temas 2026-08-28, decisión de Felipe): claro de entrada — el claro
// es la cara del producto. La elección manual persiste por aparato (D14) y gana.
const THEME_KEY = 'aia-theme';
const DEFAULT_THEME = 'light';

(function applyThemeEarly() {
  let stored = null;
  try { stored = localStorage.getItem(THEME_KEY); } catch (_) { /* privado/bloqueado */ }
  const theme = stored === 'dark' || stored === 'light' ? stored : DEFAULT_THEME;
  document.documentElement.setAttribute('data-aia-theme', theme);
})();
```

Conservar intacto el mecanismo existente de aplicar-antes-de-la-primera-hoja (evita el
flash); solo cambia la constante y la clave. Si la clave hoy tiene otro nombre, migrar el
valor viejo una vez: `const legacy = localStorage.getItem('<clave-vieja>');` y copiarlo a
`THEME_KEY` si existe.

- [ ] **Step 4: El toggle**

```js
// public/js/modules/aia_ui/theme-toggle.js
// D13: botón «Volver a oscuro» visible en la nav durante el estreno (primer mes);
// su recogida al menú del usuario es una tarea del plan de cierre de la serie.
(function () {
  const THEME_KEY = 'aia-theme';

  function currentTheme() {
    return document.documentElement.getAttribute('data-aia-theme') === 'dark' ? 'dark' : 'light';
  }

  window.aiaThemeToggle = function aiaThemeToggle() {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-aia-theme', next);
    try { localStorage.setItem(THEME_KEY, next); } catch (_) { /* sin persistencia */ }
    refreshLabel();
  };

  function refreshLabel() {
    const btn = document.querySelector('[data-aia-theme-toggle]');
    if (!btn) return;
    const dark = currentTheme() === 'dark';
    btn.textContent = dark ? 'Volver a claro' : 'Volver a oscuro';
    btn.setAttribute('aria-pressed', String(dark));
  }

  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('[data-aia-theme-toggle]');
    if (btn) btn.addEventListener('click', window.aiaThemeToggle);
    refreshLabel();
  });
})();
```

El botón `<button class="aia-btn aia-btn--secondary" data-aia-theme-toggle>` se monta en
la nav en el plan de PG (la nav clara D9 es parte del vestido por módulo); aquí queda el
mecanismo listo y probado en el laboratorio.

- [ ] **Step 5: Run to verify it passes**

Run: `node --test tests/design-system/theme-default.test.mjs`
Expected: PASS.

- [ ] **Step 6: Verificación en navegador**

Con el stack arriba (`docker compose up -d app`), abrir el laboratorio con
`preview_start`, borrar `localStorage['aia-theme']` vía consola y recargar: la página
arranca en claro. Ejecutar `aiaThemeToggle()` en consola: cambia a oscuro y
`localStorage['aia-theme'] === 'dark'`. Recargar: persiste oscuro.

- [ ] **Step 7: Commit**

```bash
git add public/js/modules/aia_ui/theme-bootstrap.js public/js/modules/aia_ui/theme-toggle.js tests/design-system/theme-default.test.mjs
git commit -m "feat(tema): claro de entrada con persistencia por aparato y toggle listo"
```

---

### Task 7: El Excel habla la paleta clara

**Files:**
- Modify: `src/Controllers/Gestion/ReportController.php` (la paleta ARGB a mano)
- Modify: `public/css/tokens.css` (retirar los 8 huérfanos `--ds-color-state-*-light`
  de la «RESERVA SIN CABLEAR», ~líneas 315–322)
- Test: `tests/unit/ReportPaletteTest.php` (nuevo, PHPUnit, `#[Group('puro')]`)

**Interfaces:**
- Produces: constante `ReportController::STATE_FILLS` (o clase `ReportStatePalette` si
  el controlador ya está gordo) con los ARGB de la paleta calibrada: los mismos hex de
  Task 1 con prefijo `FF`.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/unit/ReportPaletteTest.php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('puro')]
final class ReportPaletteTest extends TestCase
{
    /** D17: lo que se ve en pantalla clara es lo que se imprime. */
    public function testLaPaletaDelExcelEsLaCalibradaDePantalla(): void
    {
        $esperada = [
            'red' => 'FFF6C3C3',
            'orange' => 'FFF8C9A5',
            'amber' => 'FFFFECB2',
            'violet' => 'FFDAD4F5',
            'green' => 'FFC2E2D3',
            'blue' => 'FFC1D5EC',
            'teal' => 'FFC8EFEC',
            'neutral' => 'FFE4E4E7',
        ];
        $this->assertSame($esperada, \App\Controllers\Gestion\ReportController::STATE_FILLS);
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `docker compose exec app php scripts/run-php-tests.php --nivel=puro`
Expected: FAIL — la constante no existe.

- [ ] **Step 3: Leer la paleta actual del controlador y sustituirla**

Abrir `src/Controllers/Gestion/ReportController.php`, localizar los ARGB escritos a mano
(buscar `ARGB` o `FF` + hex en los estilos de fill). Declarar:

```php
    /**
     * Paleta de estado del exportador — la MISMA rampa clara calibrada de
     * pantalla (spec temas 2026-08-28, D17: unificar; retira la divergencia
     * documentada el 2026-08-03). Espejo ARGB de --ds-state-tint-*-light.
     */
    public const STATE_FILLS = [
        'red' => 'FFF6C3C3',
        'orange' => 'FFF8C9A5',
        'amber' => 'FFFFECB2',
        'violet' => 'FFDAD4F5',
        'green' => 'FFC2E2D3',
        'blue' => 'FFC1D5EC',
        'teal' => 'FFC8EFEC',
        'neutral' => 'FFE4E4E7',
    ];
```

y reemplazar cada ARGB suelto por `self::STATE_FILLS['<hue>']`, mapeando el estado del
reporte a su matiz según `docs/design-system/state-semantics.json` (`moduleMappings`).
En `tokens.css`, borrar las ocho declaraciones `--ds-color-state-*-light` de la reserva
(el comentario entero «RESERVA SIN CABLEAR» sale con ellas) y actualizar la trampa
`memoria/trampas/comentario-de-token-afirma-uso-inexistente.md` vía ingest de la wiki al
cerrar el frente.

- [ ] **Step 4: Run to verify it passes**

Run: `docker compose exec app php scripts/run-php-tests.php --nivel=puro`
Expected: PASS, incluida `ReportPaletteTest`.

- [ ] **Step 5: Verificación funcional del exportador**

Run: exportar un reporte real desde la app (nivel `datos-proyecto` si hay test que lo
cubra; si no, manual: `/dev/entrar?u=test.R&p=…` → exportar) y abrir el XLSX: los fills
de estado son los de la rampa clara.

- [ ] **Step 6: Commit**

```bash
git add src/Controllers/Gestion/ReportController.php public/css/tokens.css tests/unit/ReportPaletteTest.php
git commit -m "feat(reportes): el Excel pinta con la paleta clara calibrada (D17)"
```

---

### Task 8: El guard de forma en el catálogo de componentes

**Files:**
- Modify: `docs/design-system/component-catalog.json` (campo `shape` por familia)
- Modify: `docs/design-system/component-catalog.schema.json` (declarar el campo)
- Test: `tests/design-system/shape-contract.test.mjs` (nuevo)

**Interfaces:**
- Consumes: los tokens de Task 4 y 5.
- Produces: cada familia del catálogo declara
  `"shape": { "radius": "<token>", "border": "<token>", "floor": "rest|float|top" }`;
  el guard verifica que el CSS real use esos tokens.

- [ ] **Step 1: Write the failing test**

```js
// tests/design-system/shape-contract.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const catalog = JSON.parse(readFileSync('docs/design-system/component-catalog.json', 'utf8'));

test('F30: toda familia del catálogo declara su forma', () => {
  const familias = catalog.families ?? catalog.components ?? [];
  assert.ok(familias.length > 0, 'el catálogo tiene familias');
  for (const f of familias) {
    assert.ok(f.shape, `la familia ${f.id ?? f.name} declara shape`);
    assert.match(f.shape.radius, /^--ds-radius-/, 'radius es un token');
    assert.ok(['rest', 'float', 'top', 'none'].includes(f.shape.floor),
      `floor de ${f.id ?? f.name} es un piso con nombre (F7)`);
  }
});

test('F30: el CSS de cada familia usa el radio que declara', () => {
  const familias = catalog.families ?? catalog.components ?? [];
  for (const f of familias) {
    if (!f.shape?.cssFile) continue;
    const css = readFileSync(f.shape.cssFile, 'utf8');
    assert.ok(css.includes(`var(${f.shape.radius})`),
      `${f.shape.cssFile} usa var(${f.shape.radius})`);
  }
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `node --test tests/design-system/shape-contract.test.mjs`
Expected: FAIL — ninguna familia declara `shape`.

- [ ] **Step 3: Poblar el catálogo**

Leer `component-catalog.json` para conocer su estructura real (ids de familia). Añadir a
cada familia su bloque, con estos valores de las specs:

| Familia (id del catálogo) | radius | floor |
|---|---|---|
| botón / control | `--ds-radius-control` | rest |
| campo / input | `--ds-radius-control` | rest |
| tarjeta / panel | `--ds-radius-card` | rest |
| toolbar | `--ds-radius-panel` | rest |
| tabla (shell) | `--ds-radius-table` | rest |
| chip de estado (celda) | `--ds-radius-data` | rest |
| chip de filtro (toolbar) | `--ds-radius-pill` | rest |
| bandera | `--ds-radius-none` | rest |
| popover / dropdown / toast | `--ds-radius-popover` | float |
| modal | `--ds-radius-modal` | top |

Declarar el campo en `component-catalog.schema.json` (objeto `shape` con `radius`
string patrón `^--ds-radius-`, `border` string, `floor` enum
`["rest","float","top","none"]`, `cssFile` string opcional).

- [ ] **Step 4: Run to verify it passes**

Run: `node --test tests/design-system/shape-contract.test.mjs`
Expected: PASS.

- [ ] **Step 5: Cablear el guard a la suite estática**

Añadir el test al barrido de `scripts/design-system-static-suite.mjs` (donde enumera los
`tests/design-system/*.test.mjs`, si es glob no hay nada que hacer; si es lista
explícita, añadir la entrada).

Run: `npm run test:design-system:static`
Expected: PASS con el guard nuevo contando.

- [ ] **Step 6: Commit**

```bash
git add docs/design-system/component-catalog.json docs/design-system/component-catalog.schema.json tests/design-system/shape-contract.test.mjs scripts/design-system-static-suite.mjs
git commit -m "feat(ds): contrato de forma en el catalogo con guard (F30)"
```

---

### Task 9: CI doble — el carril visual corre ambos temas

**Files:**
- Modify: `.github/workflows/ci.yml` (job `design-system-runtime`)
- Modify: `tests/browser/design-system-lab.mjs` (parametrizar tema vía env)
- Test: la propia corrida de CI del PR del frente.

**Interfaces:**
- Consumes: el default claro de Task 6 y las hojas de Task 2.
- Produces: variable `E2E_THEME` (`light`|`dark`) que las suites Playwright leen para
  fijar `data-aia-theme` antes de capturar; el job runtime se desdobla por matriz.

- [ ] **Step 1: Parametrizar el tema en la suite del laboratorio**

En `tests/browser/design-system-lab.mjs` (y el fixture común si existe en
`tests/browser/`), tras el `page.goto` inicial añadir:

```js
const THEME = process.env.E2E_THEME === 'dark' ? 'dark' : 'light';
await page.evaluate((t) => {
  localStorage.setItem('aia-theme', t);
  document.documentElement.setAttribute('data-aia-theme', t);
}, THEME);
```

y sufijar el nombre de los goldens con el tema: donde la suite componga la ruta del
snapshot, incluir `-${THEME}` (p. ej. `lab-botones-light.png`). Los goldens `-dark`
nuevos se generan de los actuales renombrando; los `-light` se capturan y **se aprueban
visualmente antes de committear** (regla vigente: los cambios visuales requieren
aprobación explícita — presentar la galería a Felipe).

- [ ] **Step 2: La matriz en el workflow**

En `.github/workflows/ci.yml`, el job `design-system-runtime` gana:

```yaml
    strategy:
      fail-fast: false
      matrix:
        theme: [light, dark]
```

y cada paso de gates visuales exporta `E2E_THEME: ${{ matrix.theme }}` en su `env`.
El `COMPOSE_PROJECT_NAME` ya incluye `GITHUB_RUN_ID`; añadirle `-${{ matrix.theme }}`
para que las dos corridas no compartan contenedores. **Nota de la spec (D16):** esto
dobla el tiempo del carril — si una corrida excede el timeout de 60 min, subir
`timeout-minutes` del job a 90 en este mismo PR y anotar la medición real en el resumen
del PR.

- [ ] **Step 3: Verificación local de la matriz**

Run local (con el stack de CI o el normal):
`E2E_THEME=light npm run test:visual:lab` y `E2E_THEME=dark npm run test:visual:lab`
Expected: cada corrida usa sus goldens propios; light falla solo donde aún no hay golden
capturado (capturarlos y presentar galería).

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/ci.yml tests/browser/design-system-lab.mjs
git commit -m "ci: carril visual doble por tema (D16) con goldens sufijados"
```

---

### Task 10: El contrato invertido — AGENTS.md, README del DS, DESIGN.md, state-semantics

**Files:**
- Modify: `AGENTS.md` (sección «Routing por tipo de cambio»)
- Modify: `docs/design-system/README.md` (Reglas innegociables)
- Modify: `DESIGN.md` (la guía de consumo)
- Modify: `docs/design-system/state-semantics.json` (retirar `rail`)
- Modify: `docs/design-system/ds-f1a-escala-estado.json` (reinterpretar `barra`)
- Test: `node --test 'tests/design-system/*.test.mjs'` completo + lectura cruzada.

**Interfaces:**
- Consumes: todas las tareas anteriores.
- Produces: el contrato escrito que los planes de módulo citan.

- [ ] **Step 1: AGENTS.md**

En «UI, frontend o diseño compartido», reemplazar la línea «dark es el tema por defecto
y el que se valida; 1180×820 es el viewport canónico…» por:

```markdown
- **Ambos temas son contractuales** (spec 2026-08-28): claro es la cara del producto
  y el tema de entrada; oscuro es opción de primera clase. El CI corre los gates
  visuales en los dos (matriz por tema). 1180×820 sigue siendo el viewport canónico
  de escritorio; el programa móvil quedó absorbido por este frente (D21) y sus
  viewports se validan por módulo según su plan.
```

- [ ] **Step 2: README del DS y DESIGN.md**

En `docs/design-system/README.md`, actualizar la regla equivalente con el mismo texto, y
añadir a la lista de reglas innegociables:

```markdown
- **La forma anuncia la función** (spec de forma 2026-08-28): rejilla = se edita;
  renglones = se lee; punteado = recibe; deslizar = pasa ya; píldora = se toca;
  recto = se lee; hundido = recibe o está presionado; la elevación es rango
  (reposo/flotante/techo), nunca decoración.
```

En `DESIGN.md`, añadir a la guía de consumo las primitivas nuevas: `.aia-flag--urgent` /
`--attention`, `.aia-cell-numeric`, `.aia-cell-clamp`, `--ds-radius-data`,
`--ds-elevation-*`, `[data-density="projector"]`, y el vocabulario
`--ds-active-state-*` como la vía única para pintar estado (nunca los tokens crudos).

- [ ] **Step 3: Los contratos de estado**

En `state-semantics.json`: eliminar las tres entradas `"rail": "ready"` (D5 las deroga)
y sus `note` asociadas ganan la coletilla `"; rail ready derogado por spec 2026-08-28 D5
(la bandera no tiene gradación: lo positivo vive en chip y tinte)"`. En
`ds-f1a-escala-estado.json`, el objeto `canales.barra` cambia su descripción:
`"transporta": "gravedad"` se mantiene, `"posicion"` pasa a `"bandera de 26px al inicio
de la fila, con glifo"` y se añade `"desde": "spec 2026-08-28 D2-D8"`.

- [ ] **Step 4: Verificación completa**

Run: `node --test 'tests/design-system/*.test.mjs'`
Expected: PASS total. Si el guard de `ds-f1a` compara JSON contra MD, actualizar el MD
hermano en el mismo commit.

- [ ] **Step 5: Commit**

```bash
git add AGENTS.md docs/design-system/README.md DESIGN.md docs/design-system/state-semantics.json docs/design-system/ds-f1a-escala-estado.json docs/design-system/ds-f1a-escala-estado.md
git commit -m "docs(contratos): ambos temas contractuales, regla madre de forma y rail derogado"
```

---

### Task 11: Cierre de fase — verificación integral y PR

**Files:**
- Modify: `CHANGELOG.md`, `TASKS.md`, `IMPLEMENTATION_PLAN_INVENTORY.md`
- El PR del frente.

- [ ] **Step 1: La verificación integral**

```bash
node --test 'tests/design-system/*.test.mjs'
npm run test:design-system:static
docker compose exec app php scripts/run-php-tests.php --nivel=puro
npm run check:frontend
```

Expected: todo PASS. Anotar el SHA verificado (`git rev-parse HEAD`).

- [ ] **Step 2: Wiki y bitácora**

CHANGELOG (`[Sin publicar]` → Añadido): la fase cero de temas y forma, con enlace a las
dos specs y este plan. TASKS: mover el frente a «Hechas», dejar como pendientes los
planes siguientes de la serie (PG → PI → PS → PDC/SPA → resto → admin → Torre) y las dos
tareas con gate humano: la edición del manual de marca (D20 — **pide visto de Felipe en
su sesión, línea roja**) y la recogida del botón de tema al mes (D13). INVENTORY: la
fila de las specs pasa a «en curso» con este plan enlazado.

- [ ] **Step 3: PR**

```bash
git push -u origin temas-y-forma-fase-cero
gh pr create --base main --title "feat(ds): fase cero de temas y forma" --body "<resumen con comandos y salidas de Step 1, y la nota del tiempo real del CI doble>"
```

Expected: CI verde en ambos temas de la matriz. El merge del PR cierra la fase cero; el
plan de Programa General se escribe entonces, con el método destilado.

---

## Self-review (ejecutado al escribir)

- **Cobertura:** D1–D18 y D20/D24 de temas quedan en Tasks 1–10 o en planes de módulo
  declarados (D9 nav clara, D13 montaje del botón, D19/D15 estreno → plan de cierre de
  serie; D21–D23 gobiernan la serie; D24 admin → plan propio). F1–F40: las de fase cero
  en Tasks 3–5 y 8; las de módulo (F16 selección, F21 celda activa, F22 AG Grid, F25–F27,
  F34 aplicación por superficie, F37 abreviaturas, F39 anchos) viven en los planes de
  módulo que las citan desde el catálogo (Task 8) y la guía (Task 10).
- **Placeholders:** ninguno — todo step lleva código o comando con expected.
- **Consistencia de tipos:** los tokens producidos en Tasks 1/4/5 son los consumidos en
  Tasks 2/3/8; nombres verificados entre bloques.
