/**
 * Primitivas de lectura y normalización de campos de una fila LPS (Programa General,
 * Programación Intermedia, Programación Semanal). Puerto 1:1 de las funciones homónimas de
 * `public/js/modules/lps_drawer.js` (líneas 358-442), sin DOM ni red: reciben el valor crudo tal
 * como llega en la fila y devuelven el valor normalizado.
 *
 * El original vive en un único IIFE sin tipos; aquí cada función queda separada y tipada, pero el
 * comportamiento —incluidas sus rarezas— se conserva exactamente. Ver `task-3-report.md` para las
 * divergencias documentadas (ninguna en este archivo: es puro texto/número).
 */

/** Fila cruda de Handsontable/API tal como la ve el cajón: claves dinámicas, valores sin tipar. */
export type FilaLps = Record<string, unknown>;

/**
 * `undefined`, `null`, cadena vacía (tras trim) o la cadena literal `"null"` (insensible a
 * mayúsculas) cuentan como "en blanco". Puerto de `isBlankValue` (lps_drawer.js:358-362).
 */
export function esValorEnBlanco(valor: unknown): boolean {
  if (valor === null || valor === undefined) return true;
  const texto = String(valor).trim();
  return texto === '' || texto.toLowerCase() === 'null';
}

/**
 * Primer valor no-blanco de `fila` entre `claves`, en orden. Puerto de `firstValue`
 * (lps_drawer.js:364-371).
 */
export function primerValor(fila: FilaLps | null | undefined, claves: readonly string[]): unknown {
  if (!fila) return undefined;
  for (const clave of claves) {
    const valor = fila[clave];
    if (!esValorEnBlanco(valor)) return valor;
  }
  return undefined;
}

export interface ValorExistente {
  encontrado: boolean;
  valor: unknown;
  clave: string;
}

/**
 * Como `primerValor`, pero distingue "la clave no existe en la fila" de "existe y está en
 * blanco": si ninguna clave trae un valor no-blanco, devuelve la primera clave presente aunque
 * esté en blanco (en vez de `undefined` a secas). Puerto de `firstExistingValue`
 * (lps_drawer.js:373-384) — lo usa `resolverInfoRestriccion` para diferenciar "no aplica/ausente"
 * de "en blanco pero aplicable".
 */
export function primerValorExistente(fila: FilaLps | null | undefined, claves: readonly string[]): ValorExistente {
  if (!fila) return { encontrado: false, valor: undefined, clave: '' };
  let candidatoEnBlanco: ValorExistente | null = null;
  for (const clave of claves) {
    if (!Object.prototype.hasOwnProperty.call(fila, clave)) continue;
    const valor = fila[clave];
    if (!esValorEnBlanco(valor)) return { encontrado: true, valor, clave };
    if (candidatoEnBlanco === null) candidatoEnBlanco = { encontrado: true, valor, clave };
  }
  return candidatoEnBlanco ?? { encontrado: false, valor: undefined, clave: '' };
}

/**
 * Normaliza separadores decimales: si trae coma Y punto, el que esté más a la derecha es el
 * decimal (el otro se trata como separador de miles); si solo trae coma, la coma es el decimal.
 * Puerto de `normalizeNumericString` (lps_drawer.js:386-401).
 */
export function normalizarCadenaNumerica(valor: unknown): string {
  let normalizado = String(valor === null || valor === undefined ? '' : valor).trim().replace(/\s+/g, '');
  if (!normalizado || normalizado.toLowerCase() === 'null') return '';

  const posComa = normalizado.lastIndexOf(',');
  const posPunto = normalizado.lastIndexOf('.');
  if (posComa > -1 && posPunto > -1) {
    normalizado = posComa > posPunto
      ? normalizado.replace(/\./g, '').replace(',', '.')
      : normalizado.replace(/,/g, '');
  } else if (posComa > -1) {
    normalizado = normalizado.replace(',', '.');
  }

  return normalizado;
}

/**
 * `parseFloat` sobre el valor normalizado, con `valorPorDefecto` si está en blanco o no es un
 * número finito. Puerto de `parseNumber` (lps_drawer.js:403-407).
 */
export function analizarNumero<T>(valor: unknown, valorPorDefecto: T): number | T {
  if (esValorEnBlanco(valor)) return valorPorDefecto;
  const analizado = parseFloat(normalizarCadenaNumerica(valor));
  return Number.isFinite(analizado) ? analizado : valorPorDefecto;
}

/**
 * `"N/A"`, `"NA"` o `"NO APLICA"` (sin distinguir mayúsculas/espacios extremos). Puerto de
 * `isNotApplicable` (lps_drawer.js:409-412).
 */
export function esNoAplica(valor: unknown): boolean {
  const normalizado = String(valor === null || valor === undefined ? '' : valor).trim().toUpperCase();
  return normalizado === 'N/A' || normalizado === 'NA' || normalizado === 'NO APLICA';
}

/**
 * Convierte un valor de restricción (porcentaje con `%`, coma/punto decimal, o número crudo) a un
 * ratio 0-1. `null` si está en blanco o es "no aplica". Puerto exacto de `parseRatioValue`
 * (lps_drawer.js:414-429), incluida la heurística `while (ratio > 1 && ratio <= 10000) ratio /= 100`
 * que interpreta números > 1 sin `%` como porcentajes enteros (p. ej. `"66"` → 0.66, `"6600"` →
 * 0.66) y el redondeo a 4 decimales.
 */
export function analizarRatio(valor: unknown): number | null {
  if (esValorEnBlanco(valor) || esNoAplica(valor)) return null;

  const crudo = String(valor).trim();
  const tienePorcentaje = crudo.indexOf('%') > -1;
  const normalizado = normalizarCadenaNumerica(crudo.replace(/%/g, ''));
  if (!normalizado) return null;

  let ratio = parseFloat(normalizado);
  if (!Number.isFinite(ratio)) return null;
  if (tienePorcentaje) ratio = ratio / 100;
  while (ratio > 1 && ratio <= 10000) ratio = ratio / 100;
  if (ratio < 0) return 0;
  if (ratio > 1) return 1;
  return Math.round((ratio + Number.EPSILON) * 10000) / 10000;
}

/**
 * Formatea un ratio 0-1 como porcentaje entero (`0.665` → `"67%"`). `"0%"` si no es un número
 * finito. Puerto de `formatPercentFromRatio` (lps_drawer.js:431-434).
 */
export function formatearPorcentajeDesdeRatio(ratio: number | null | undefined): string {
  if (ratio === null || ratio === undefined || !Number.isFinite(Number(ratio))) return '0%';
  return `${Math.round(Number(ratio) * 100)}%`;
}

/**
 * Bandera booleana laxa: `true`/`1`/`"1"`/`"si"`/`"sí"`/`"true"`/`"p1"` (insensible a mayúsculas)
 * cuentan como verdadero; cualquier número >= 1 también. Puerto de `normalizeFlag`
 * (lps_drawer.js:436-442).
 */
export function normalizarBandera(valor: unknown): boolean {
  if (valor === true) return true;
  if (valor === false || valor === null || valor === undefined) return false;
  if (typeof valor === 'number') return valor >= 1;
  const normalizado = String(valor).trim().toLowerCase();
  return normalizado === '1' || normalizado === 'si' || normalizado === 'sí' || normalizado === 'true' || normalizado === 'p1';
}
