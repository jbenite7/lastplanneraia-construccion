import { describe, it, expect } from 'vitest';
import { obtenerConfigEstado } from './presentacionEstados';

describe('Dominio S05: presentacion de estados canonicos', () => {
  it('retorna configuracion canónica para Atrasada', () => {
    const config = obtenerConfigEstado('Atrasada');
    expect(config.claseChip).toBe('chip-red');
    expect(config.colorDot).toBe('var(--ds-color-state-critical-text)');
    expect(config.texto).toBe('Atrasada');
  });

  it('retorna configuracion canónica para Con Alerta', () => {
    const config = obtenerConfigEstado('Con Alerta');
    expect(config.claseChip).toBe('chip-amber');
    expect(config.colorDot).toBe('var(--ds-color-state-warning-text)');
    expect(config.texto).toBe('Con Alerta');
  });

  it('retorna configuracion canónica para Debe Iniciar', () => {
    const config = obtenerConfigEstado('Debe Iniciar');
    expect(config.claseChip).toBe('chip-orange');
    expect(config.colorDot).toBe('var(--ds-state-solid-orange)');
    expect(config.texto).toBe('Debe Iniciar');
  });

  it('retorna configuracion canónica para En Curso', () => {
    const config = obtenerConfigEstado('En Curso');
    expect(config.claseChip).toBe('chip-blue');
    expect(config.colorDot).toBe('var(--ds-color-state-info-text)');
    expect(config.texto).toBe('En Curso');
  });

  it('retorna configuracion canónica para Actividad Futura (texto: Futura)', () => {
    const config = obtenerConfigEstado('Actividad Futura');
    expect(config.claseChip).toBe('chip-green');
    expect(config.colorDot).toBe('var(--ds-color-state-success-text)');
    expect(config.texto).toBe('Futura');
  });

  it('retorna configuracion canónica para Terminada', () => {
    const config = obtenerConfigEstado('Terminada');
    expect(config.claseChip).toBe('chip-gray');
    expect(config.colorDot).toBe('var(--ds-text-muted)');
    expect(config.texto).toBe('Terminada');
  });

  it('retorna fallback sobrio para estados nulos, indefinidos o desconocidos', () => {
    const configNull = obtenerConfigEstado(null);
    expect(configNull.claseChip).toBe('chip-gray');
    expect(configNull.colorDot).toBe('var(--ds-text-muted)');
    expect(configNull.texto).toBe('Sin Datos');

    const configUndefined = obtenerConfigEstado(undefined);
    expect(configUndefined.texto).toBe('Sin Datos');

    const configCustom = obtenerConfigEstado('Pausada');
    expect(configCustom.claseChip).toBe('chip-gray');
    expect(configCustom.texto).toBe('Pausada');
  });
});
