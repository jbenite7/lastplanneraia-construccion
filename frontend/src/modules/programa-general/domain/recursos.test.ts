import { describe, it, expect } from 'vitest';
import { recursosConDato, type ConfigRestriccionesPg } from './recursos';

const configConstruccion: ConfigRestriccionesPg = {
  area: 'Construccion',
  restrictions: [
    { key: 'D_y_E', label: 'D y E', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
    { key: 'Materiales', label: 'Materiales', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
    { key: 'MdeO', label: 'M de O', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
    { key: 'Equipos', label: 'Equipos', hard: true, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
    { key: 'Predecesora', label: 'Predecesora', hard: true, thresholdPercent: 50, options: ['0%', '50%', '100%', 'N/A'] },
  ],
  hardRestrictions: ['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora'],
  softRestrictions: [],
};

describe('recursosConDato', () => {
  it('solo devuelve los recursos con dato real en la fila, con la etiqueta del catálogo', () => {
    const recursos = recursosConDato(
      { D_y_E: '100%', Materiales: null, MdeO: '', Equipos: '0%' },
      configConstruccion,
    );
    expect(recursos.map((r) => r.label)).toEqual(['D y E', 'Equipos']);
  });

  it('decide liberada/pendiente con el umbral del catálogo y respeta N/A', () => {
    const recursos = recursosConDato(
      { D_y_E: '1', Materiales: '0%', MdeO: 'N/A', Predecesora: '50%' },
      configConstruccion,
    );
    expect(recursos).toEqual([
      expect.objectContaining({ key: 'D_y_E', valor: '100%', estado: 'liberada' }),
      expect.objectContaining({ key: 'Materiales', valor: '0%', estado: 'pendiente' }),
      expect.objectContaining({ key: 'MdeO', valor: 'N/A', estado: 'no-aplica' }),
      expect.objectContaining({ key: 'Predecesora', valor: '50%', estado: 'liberada' }),
    ]);
  });

  it('sin catálogo no inventa recursos', () => {
    expect(recursosConDato({ D_y_E: '100%' }, null)).toEqual([]);
  });

  it('en Preconstrucción usa restriccion_pc_* con las etiquetas del proyecto', () => {
    const config: ConfigRestriccionesPg = {
      area: 'Pre-Construccion',
      restrictions: [
        { key: 'restriccion_pc_1', label: 'Predecesora', hard: true, thresholdPercent: 50, options: ['0%', '50%', '100%', 'N/A'] },
        { key: 'restriccion_pc_2', label: 'Licencia', hard: false, thresholdPercent: 100, options: ['0%', '100%', 'N/A'] },
      ],
      hardRestrictions: ['restriccion_pc_1'],
      softRestrictions: ['restriccion_pc_2'],
    };
    const recursos = recursosConDato({ restriccion_pc_1: '0%', restriccion_pc_2: '100%', D_y_E: '100%' }, config);
    expect(recursos.map((r) => [r.label, r.estado])).toEqual([
      ['Predecesora', 'pendiente'],
      ['Licencia', 'liberada'],
    ]);
  });
});
