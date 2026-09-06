import { describe, expect, test } from 'vitest';
import { construirTextoSos, nivelSuperior, rolSuperior, triggerSos, urlCorreo, urlWhatsapp } from './crisis';

describe('rolSuperior / triggerSos — jerarquía de escalamiento (T02-AC-109)', () => {
  test('nivel 1 escala a Director (SOS-DIR)', () => {
    expect(rolSuperior(1)).toBe('Director');
    expect(triggerSos(1)).toBe('SOS-DIR');
  });

  test('nivel 2 escala a Coordinador de Integración (SOS-COO)', () => {
    expect(rolSuperior(2)).toBe('Coordinador de Integración');
    expect(triggerSos(2)).toBe('SOS-COO');
  });

  test('nivel 5 (terminal) se mantiene tope en Gerente General (SOS-GER)', () => {
    expect(nivelSuperior(5)).toBe(5);
    expect(rolSuperior(5)).toBe('Gerente General');
    expect(triggerSos(5)).toBe('SOS-GER');
  });

  test('todo trigger producido pertenece al enum del servidor', () => {
    const enumServidor = ['MANUAL', 'SOS-RES', 'SOS-DIR', 'SOS-COO', 'SOS-GER'];
    for (let nivel = 1; nivel <= 5; nivel += 1) {
      expect(enumServidor).toContain(triggerSos(nivel));
    }
  });
});

describe('construirTextoSos', () => {
  test('arma el texto con los datos entregados, sin leer nada externo', () => {
    const texto = construirTextoSos({
      consecutivo: 4102,
      actividad: 'Vaciado losa piso 3',
      subcontratista: 'Concretos AIA',
      restriccion: 'Sin acero disponible',
      nivelActual: 1,
    });

    expect(texto).toContain('Actividad: #4102 - Vaciado losa piso 3');
    expect(texto).toContain('Subcontratista: Concretos AIA');
    expect(texto).toContain('Restricción/Causa: Sin acero disponible');
    expect(texto).toContain('Estimado superior en calidad de Director');
  });
});

describe('urlWhatsapp / urlCorreo — construcción pura de URL, sin abrir nada', () => {
  test('urlWhatsapp quita espacios del teléfono y codifica el texto', () => {
    const url = urlWhatsapp('300 123 4567', 'texto con espacios');
    expect(url).toBe('https://api.whatsapp.com/send?phone=3001234567&text=texto%20con%20espacios');
  });

  test('urlCorreo codifica asunto y cuerpo en un mailto', () => {
    const url = urlCorreo('lider@aia.com', 'texto');
    expect(url.startsWith('mailto:lider@aia.com?subject=')).toBe(true);
    expect(url).toContain('body=texto');
  });
});
