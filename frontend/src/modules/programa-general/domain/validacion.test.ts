import { describe, it, expect } from 'vitest';
import {
  calcularDesviacionFisica,
  validarBorradorActividad,
  normalizarBorradorActividad,
} from './validacion';

describe('Dominio S05: validacion y calculos de avance', () => {
  it('calcula la desviacion fisica (Delta) correctamente entre real y teorico', () => {
    const delta = calcularDesviacionFisica(0.25, 0.50, 450.0, 'm³');
    expect(delta.porcentajeDelta).toBe(-25.0);
    expect(delta.magnitudDelta).toBe(-112.5);
    expect(delta.textoFormateado).toBe('-25.0% (-112.5 m³)');
    expect(delta.esNegativo).toBe(true);
  });

  it('calcula desviacion fisica positiva con magnitud y signo +', () => {
    const delta = calcularDesviacionFisica(0.70, 0.50, 100.0, 'ton');
    expect(delta.porcentajeDelta).toBe(20.0);
    expect(delta.magnitudDelta).toBe(20.0);
    expect(delta.textoFormateado).toBe('+20.0% (+20 ton)');
    expect(delta.esNegativo).toBe(false);
  });

  it('calcula desviacion neutral (cero) sin signo negativo', () => {
    const delta = calcularDesviacionFisica(0.50, 0.50, 100.0, 'm²');
    expect(delta.porcentajeDelta).toBe(0.0);
    expect(delta.magnitudDelta).toBe(0.0);
    expect(delta.textoFormateado).toBe('0.0% (0 m²)');
    expect(delta.esNegativo).toBe(false);
  });

  it('omite magnitud cuando la unidad es %', () => {
    const delta = calcularDesviacionFisica(0.30, 0.50, 100, '%');
    expect(delta.porcentajeDelta).toBe(-20.0);
    expect(delta.magnitudDelta).toBeNull();
    expect(delta.textoFormateado).toBe('-20.0%');
    expect(delta.esNegativo).toBe(true);
  });

  it('omite magnitud cuando cantidadPpto es null o 0', () => {
    const delta = calcularDesviacionFisica(0.60, 0.50, null, 'm³');
    expect(delta.porcentajeDelta).toBe(10.0);
    expect(delta.magnitudDelta).toBeNull();
    expect(delta.textoFormateado).toBe('+10.0%');
  });

  it('rechaza borrador con fecha fin anterior a fecha inicio', () => {
    const error = validarBorradorActividad({
      Fecha_Inicio: '2026-08-25',
      Fecha_Fin: '2026-08-10',
      unidad: 'm³',
      cantidad_ppto: 100,
      ejecutadoVisible: 20,
    });
    expect(error).toContain('La fecha de fin no puede ser anterior a la fecha de inicio');
  });

  it('rechaza borrador con cantidad_ppto negativa', () => {
    const error = validarBorradorActividad({
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      unidad: 'm³',
      cantidad_ppto: -5,
      ejecutadoVisible: 20,
    });
    expect(error).toContain('La cantidad de presupuesto no puede ser negativa');
  });

  it('rechaza borrador con ejecutadoVisible negativo', () => {
    const error = validarBorradorActividad({
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      unidad: 'm³',
      cantidad_ppto: 100,
      ejecutadoVisible: -10,
    });
    expect(error).toContain('El avance no puede ser un valor negativo');
  });

  it('normaliza cantidad_ppto a null cuando la unidad es %', () => {
    const res = validarBorradorActividad({
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      unidad: '%',
      cantidad_ppto: 100,
      ejecutadoVisible: 50,
    });
    expect(res).toBeNull(); // Válido

    const normalizado = normalizarBorradorActividad({
      unidad: '%',
      cantidad_ppto: 100,
    });
    expect(normalizado.cantidad_ppto).toBeNull();
  });

  it('permite borrador valido sin fechas o con fechas validas', () => {
    const res = validarBorradorActividad({
      Fecha_Inicio: '2026-08-10',
      Fecha_Fin: '2026-08-20',
      unidad: 'm²',
      cantidad_ppto: 50,
      ejecutadoVisible: 25,
    });
    expect(res).toBeNull();
  });

  it('rechaza avance porcentual mayor a 100%', () => {
    const error = validarBorradorActividad({
      unidad: '%',
      ejecutadoVisible: 105,
    });
    expect(error).toContain('El avance porcentual no puede ser superior al 100%');
  });

  it('rechaza avance fisico que supera cantidad presupuestada', () => {
    const error = validarBorradorActividad({
      unidad: 'm³',
      cantidad_ppto: 100,
      ejecutadoVisible: 120,
    });
    expect(error).toContain('El avance no puede ser superior a la cantidad presupuestada');
  });
});
