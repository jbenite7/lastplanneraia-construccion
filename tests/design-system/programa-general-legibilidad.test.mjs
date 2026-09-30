// Contrato de legibilidad de Programa General (revisión visual, 2026-09-29).
//
// Defecto que Felipe vio en el cajón: las píldoras «Pendiente» y el botón «Declarar Crisis SOS LPS»
// eran ilegibles en los dos temas. Causa: usaban `--ds-active-state-solid-*-text` (el texto pensado
// para ir SOBRE el color sólido `solid-*`) encima de fondos `--ds-active-state-tint-*` (translúcidos).
// Medido en oscuro: 1.16:1 la píldora y 1.09:1 el botón (mínimo WCAG 4.5:1).
//
// El texto sobre un tinte es `--ds-color-state-{success,warning,critical,info}-text` (cambia con el
// tema) o `--ds-active-text-primary`. Esta prueba fija ese emparejamiento sin DOM.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const raiz = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const CSS_PG = 'frontend/src/modules/programa-general/programa-general.css';

function reglas(ruta) {
  const texto = readFileSync(join(raiz, ruta), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
  return [...texto.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((m) => ({
    selector: m[1].trim().replace(/\s+/g, ' '),
    cuerpo: m[2],
  }));
}
const declara = (cuerpo, prop) => cuerpo.match(new RegExp(`(?:^|;|\\s)${prop}\\s*:\\s*([^;]+)`))?.[1]?.trim() ?? null;

test('ningún texto de Programa General usa el color «sobre sólido» encima de un fondo tinte', () => {
  const malas = reglas(CSS_PG)
    .filter((r) => /var\(--ds-active-state-tint-/.test(declara(r.cuerpo, 'background') ?? ''))
    .filter((r) => /var\(--ds-active-state-solid-[a-z]+-text\)/.test(declara(r.cuerpo, 'color') ?? ''))
    .map((r) => r.selector);
  assert.deepEqual(malas, [], `texto «solid-*-text» sobre fondo «tint-*» (ilegible): ${malas.join(' | ')}`);
});

test('las píldoras de recurso y el botón SOS llevan el texto de estado que corresponde a su tinte', () => {
  const lista = reglas(CSS_PG);
  const color = (selector) => {
    const regla = lista.find((r) => r.selector === selector);
    assert.ok(regla, `falta la regla ${selector}`);
    return declara(regla.cuerpo, 'color');
  };
  assert.equal(color('.pill-liberado'), 'var(--ds-color-state-success-text)');
  assert.equal(color('.pill-gestion'), 'var(--ds-color-state-warning-text)');
  assert.equal(color('.pill-bloqueado'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.btn-sos-trigger'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.pg-error'), 'var(--ds-color-state-critical-text)');
  assert.equal(color('.drawer-sos-feedback--error'), 'var(--ds-color-state-critical-text)');
});

// --- Tamaño de texto ----------------------------------------------------------------------------
// Felipe (2026-09-29): «todos los textos, excepto en la sidebar, se están viendo muy pequeños».
// Medido en Programa General: 22.573 caracteres a 11 px y ~6.800 entre 8,5 y 10,5 px, o sea por
// debajo del piso duro de 11 px que documenta DESIGN.md, con 76 `font-size` en px sueltos.
// La rampa del módulo son tres pasos con nombre: nada baja de 12 px.
const RAMPA = { '--pg-type-meta': 0.75, '--pg-type-dato': 0.8125, '--pg-type-cuerpo': 0.875 };

test('la rampa tipográfica de Programa General existe y su piso es 12 px', () => {
  const texto = readFileSync(join(raiz, CSS_PG), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
  for (const [nombre, rem] of Object.entries(RAMPA)) {
    const m = texto.match(new RegExp(`${nombre}\\s*:\\s*([\\d.]+)rem`));
    assert.ok(m, `falta ${nombre} en rem`);
    assert.equal(Number(m[1]), rem, `${nombre} debe valer ${rem}rem`);
  }
});

test('ningún font-size de Programa General baja de 12 px ni se escribe en px sueltos pequeños', () => {
  const sueltos = [];
  for (const r of reglas(CSS_PG)) {
    const tam = declara(r.cuerpo, 'font-size');
    if (!tam) continue;
    if (/^var\(--pg-type-(meta|dato|cuerpo)\)$/.test(tam)) continue;
    const px = tam.match(/^([\d.]+)px$/);
    if (px && Number(px[1]) >= 14) continue; // títulos y números de mayor jerarquía
    sueltos.push(`${r.selector} { font-size: ${tam} }`);
  }
  assert.deepEqual(sueltos, [], `usa la rampa --pg-type-*: ${sueltos.slice(0, 6).join(' | ')}${sueltos.length > 6 ? ` … (+${sueltos.length - 6})` : ''}`);
});

// La tabla es de layout fijo: con el texto a 13 px las columnas angostas se desbordaban sobre la
// vecina (medido el 2026-09-29: CÓD. hasta 50 px —«1.1.1LOCALIZACIÓN»—, fechas 3 px, estado 7 px).
// Mínimos medidos con la numeración WBS real (hasta 5 niveles) y fechas DD/MM/AAAA a 13 px.
const MINIMOS_REM = {
  'pg-col-id': 3, 'pg-col-codigo': 6, 'pg-col-inicio': 5.75, 'pg-col-fin': 5.75, 'pg-col-estado': 8,
  // Modo de 13 columnas.
  'pg-col-rc': 2.75, 'pg-col-real': 4.25, 'pg-col-teorico': 4.25, 'pg-col-restricciones': 5.25,
};

test('las columnas fijas de la tabla de Programa General caben su contenido a 13 px', () => {
  const lista = reglas(CSS_PG);
  for (const [clase, minimo] of Object.entries(MINIMOS_REM)) {
    const regla = lista.find((r) => r.selector.split(',').some((sel) => sel.trim().endsWith(`.${clase}`)) && declara(r.cuerpo, 'width'));
    assert.ok(regla, `falta el ancho de .${clase}`);
    const rem = Number(declara(regla.cuerpo, 'width').match(/^([\d.]+)rem$/)?.[1]);
    assert.ok(rem >= minimo, `.${clase} mide ${rem}rem y necesita al menos ${minimo}rem`);
  }
});
