import { describe, expect, test } from 'vitest';
import {
  esValorEnBlanco,
  primerValor,
  primerValorExistente,
  normalizarCadenaNumerica,
  analizarNumero,
  esNoAplica,
  analizarRatio,
  formatearPorcentajeDesdeRatio,
  normalizarBandera,
} from './campos';

/**
 * Puerto de las primitivas de `public/js/modules/lps_drawer.js:358-442`. Cubre exactamente los
 * bordes que el brief de la Tarea 3 pide: "percent/comma/ratio normalization" y "N/A, blank,
 * absent and invalid".
 */
describe('esValorEnBlanco', () => {
  test.each([
    [null, true],
    [undefined, true],
    ['', true],
    ['   ', true],
    ['null', true],
    ['NULL', true],
    ['0', false],
    [0, false],
    ['abc', false],
  ])('%p -> %p', (valor, esperado) => {
    expect(esValorEnBlanco(valor)).toBe(esperado);
  });
});

describe('primerValor / primerValorExistente', () => {
  test('primerValor devuelve el primer valor no-blanco entre las claves, en orden', () => {
    expect(primerValor({ a: '', b: null, c: '5' }, ['a', 'b', 'c'])).toBe('5');
  });

  test('primerValor devuelve undefined si ninguna clave trae dato', () => {
    expect(primerValor({ a: '', b: null }, ['a', 'b'])).toBeUndefined();
  });

  test('primerValor sobre fila ausente devuelve undefined', () => {
    expect(primerValor(null, ['a'])).toBeUndefined();
  });

  test('primerValorExistente distingue "ausente" de "presente pero en blanco"', () => {
    expect(primerValorExistente({}, ['a'])).toEqual({ encontrado: false, valor: undefined, clave: '' });
    expect(primerValorExistente({ a: '' }, ['a'])).toEqual({ encontrado: true, valor: '', clave: 'a' });
  });

  test('primerValorExistente prioriza la primera clave con valor no-blanco, aunque una anterior esté presente y en blanco', () => {
    expect(primerValorExistente({ a: '', b: 'x' }, ['a', 'b'])).toEqual({ encontrado: true, valor: 'x', clave: 'b' });
  });
});

describe('normalizarCadenaNumerica', () => {
  test.each([
    ['1.234,56', '1234.56'], // coma más a la derecha: coma es decimal, punto es miles
    ['1,234.56', '1234.56'], // punto más a la derecha: punto es decimal, coma es miles
    ['66,5', '66.5'], // solo coma: coma es decimal
    ['66.5', '66.5'], // solo punto: ya es decimal
    ['  100  ', '100'],
    ['', ''],
    ['null', ''],
    ['NULL', ''],
  ])('%p -> %p', (entrada, esperado) => {
    expect(normalizarCadenaNumerica(entrada)).toBe(esperado);
  });
});

describe('analizarNumero', () => {
  test('parsea con separadores mixtos y devuelve el fallback si está en blanco o es inválido', () => {
    expect(analizarNumero('1.234,56', 0)).toBeCloseTo(1234.56);
    expect(analizarNumero('', -1)).toBe(-1);
    expect(analizarNumero(null, null)).toBeNull();
    expect(analizarNumero('abc', 0)).toBe(0);
  });
});

describe('esNoAplica', () => {
  test.each([
    ['N/A', true],
    ['n/a', true],
    ['NA', true],
    ['no aplica', true],
    [' NO APLICA ', true],
    ['100%', false],
    ['', false],
  ])('%p -> %p', (valor, esperado) => {
    expect(esNoAplica(valor)).toBe(esperado);
  });
});

describe('analizarRatio', () => {
  test('en blanco o "no aplica" -> null', () => {
    expect(analizarRatio('')).toBeNull();
    expect(analizarRatio(null)).toBeNull();
    expect(analizarRatio('N/A')).toBeNull();
  });

  test('porcentaje explícito con "%"', () => {
    expect(analizarRatio('66%')).toBeCloseTo(0.66);
    expect(analizarRatio('100%')).toBe(1);
  });

  test('coma decimal sin "%"', () => {
    expect(analizarRatio('66,5')).toBeCloseTo(0.665);
  });

  test('entero > 1 sin "%" se interpreta como porcentaje entero (heurística /100 repetida)', () => {
    expect(analizarRatio('66')).toBeCloseTo(0.66);
    expect(analizarRatio('100')).toBe(1);
  });

  test('ratio ya en 0-1 se conserva', () => {
    expect(analizarRatio('0.5')).toBe(0.5);
    expect(analizarRatio('0')).toBe(0);
  });

  test('se acota a [0, 1]', () => {
    expect(analizarRatio('-5')).toBe(0);
    expect(analizarRatio('99999999')).toBe(1);
  });

  test('valor no numérico -> null', () => {
    expect(analizarRatio('abc')).toBeNull();
  });
});

describe('formatearPorcentajeDesdeRatio', () => {
  test.each([
    [0.665, '67%'], // redondeo estándar
    [0, '0%'],
    [1, '100%'],
    [null, '0%'],
    [undefined, '0%'],
    [NaN, '0%'],
  ])('%p -> %p', (ratio, esperado) => {
    expect(formatearPorcentajeDesdeRatio(ratio)).toBe(esperado);
  });
});

describe('normalizarBandera', () => {
  test.each([
    [true, true],
    [false, false],
    [null, false],
    [undefined, false],
    [1, true],
    [0, false],
    ['1', true],
    ['si', true],
    ['SÍ', true],
    ['true', true],
    ['p1', true],
    ['0', false],
    ['no', false],
  ])('%p -> %p', (valor, esperado) => {
    expect(normalizarBandera(valor)).toBe(esperado);
  });
});
