import { describe, expect, it } from 'vitest';
import { formatearInicioRelativo } from './inicioRelativo';

describe('formatearInicioRelativo (desfase respecto de la semana vigente)', () => {
  it('sin valor muestra un guion', () => {
    expect(formatearInicioRelativo(null)).toBe('–');
    expect(formatearInicioRelativo(undefined)).toBe('–');
    expect(formatearInicioRelativo(Number.NaN)).toBe('–');
    expect(formatearInicioRelativo(Number.POSITIVE_INFINITY)).toBe('–');
  });
  it('cero es esta semana', () => {
    expect(formatearInicioRelativo(0)).toBe('Esta sem');
  });
  it('negativo es hace N semanas', () => {
    expect(formatearInicioRelativo(-9)).toBe('Hace 9 sem');
    expect(formatearInicioRelativo(-1)).toBe('Hace 1 sem');
  });
  it('positivo es en N semanas', () => {
    expect(formatearInicioRelativo(3)).toBe('En 3 sem');
  });
  it('redondea como el legado', () => {
    expect(formatearInicioRelativo(2.6)).toBe('En 3 sem');
    expect(formatearInicioRelativo(-0.4)).toBe('Esta sem');
  });
});
