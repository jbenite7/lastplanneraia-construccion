import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CLAVE_ESTADO_RIEL, guardarEstadoRiel, leerEstadoRiel } from './modoBarraLateral';

function crearAlmacenamientoMemoria() {
  const valores = new Map<string, string>();
  return {
    clear: () => valores.clear(),
    getItem: (clave: string) => valores.get(clave) ?? null,
    setItem: (clave: string, valor: string) => valores.set(clave, valor),
  };
}

describe('estado del riel (paridad con el legado)', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', crearAlmacenamientoMemoria());
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  it('arranca colapsado cuando no hay nada guardado', () => {
    expect(leerEstadoRiel()).toBe('collapsed');
  });

  it('usa la misma clave que el legado y respeta lo guardado', () => {
    localStorage.setItem(CLAVE_ESTADO_RIEL, 'expanded');

    expect(CLAVE_ESTADO_RIEL).toBe('aia-sidebar-state');
    expect(leerEstadoRiel()).toBe('expanded');
  });

  it('ignora valores inválidos', () => {
    localStorage.setItem(CLAVE_ESTADO_RIEL, 'abierto');

    expect(leerEstadoRiel()).toBe('collapsed');
  });

  it('no lanza si localStorage falla', () => {
    vi.stubGlobal('localStorage', {
      getItem: () => {
        throw new Error('bloqueado');
      },
      setItem: () => {
        throw new Error('bloqueado');
      },
    });

    expect(leerEstadoRiel()).toBe('collapsed');
    expect(() => guardarEstadoRiel('expanded')).not.toThrow();
  });
});
