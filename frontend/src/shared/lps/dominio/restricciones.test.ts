import { describe, expect, test } from 'vitest';
import {
  configuracionPorDefecto,
  restriccionesDuras,
  restriccionesBlandas,
  resolverInfoRestriccion,
  calcularItr,
  tieneBrechaProfunda,
  resumenRestricciones,
  vistaRestriccionesBlandas,
  todasLasRestricciones,
  type ConfiguracionRestricciones,
} from './restricciones';

/**
 * Configuración de área "Pre-Construccion" tal como la arma
 * `GeneralApiController::restrictionConfig()` (src/Controllers/Api/GeneralApiController.php:1613-1667):
 * una única restricción dura (`Predecesora`, umbral 50) y hasta 3 blandas con nombre personalizado
 * por proyecto (`pc_restr_2/3/4_nombre`). Fixture para "both area configurations" del brief.
 */
function configuracionPreConstruccion(): ConfiguracionRestricciones {
  return {
    area: 'Pre-Construccion',
    restrictions: [
      { key: 'restriccion_pc_1', label: 'Predecesora', type: 'hard', threshold: 50 },
      { key: 'restriccion_pc_2', label: 'Diseño arquitectónico', type: 'soft', threshold: 100 },
    ],
    hardRestrictions: ['restriccion_pc_1'],
    softRestrictions: ['restriccion_pc_2'],
  };
}

describe('configuración por área', () => {
  test('Construccion: 5 duras + 2 blandas, con los umbrales de la API', () => {
    const config = configuracionPorDefecto();
    expect(restriccionesDuras(config).map((r) => r.key)).toEqual(['D_y_E', 'Materiales', 'MdeO', 'Equipos', 'Predecesora']);
    expect(restriccionesBlandas(config).map((r) => r.key)).toEqual(['Pdto_Cons', 'Seguimiento']);
    // Predecesora tiene umbral 50%, no 100% como el resto de las duras.
    expect(restriccionesDuras(config).find((r) => r.key === 'Predecesora')?.threshold).toBe(0.5);
    expect(restriccionesDuras(config).find((r) => r.key === 'D_y_E')?.threshold).toBe(1);
  });

  test('Pre-Construccion: 1 dura + blandas dinámicas por proyecto', () => {
    const config = configuracionPreConstruccion();
    expect(restriccionesDuras(config).map((r) => r.key)).toEqual(['restriccion_pc_1']);
    expect(restriccionesBlandas(config).map((r) => r.key)).toEqual(['restriccion_pc_2']);
    expect(restriccionesDuras(config)[0].threshold).toBe(0.5);
  });

  test('los alias de columna son la clave y "restr_<clave>"', () => {
    const config = configuracionPorDefecto();
    expect(todasLasRestricciones(config)[0].aliases).toEqual(['D_y_E', 'restr_D_y_E']);
  });
});

describe('resolverInfoRestriccion', () => {
  const config = configuracionPorDefecto();
  const [duraDyE] = restriccionesDuras(config);

  test('N/A -> no aplicable, cumplida por defecto (no bloquea el ITR)', () => {
    const info = resolverInfoRestriccion({ D_y_E: 'N/A' }, duraDyE);
    expect(info).toMatchObject({ applicable: false, met: true, ratio: null, progress: 1 });
  });

  test('ausente (ninguna clave/alias presente) -> igual que N/A', () => {
    const info = resolverInfoRestriccion({}, duraDyE);
    expect(info).toMatchObject({ applicable: false, met: true, ratio: null, progress: 1 });
  });

  test('en blanco pero la columna existe -> aplicable, ratio 0, no cumplida', () => {
    const info = resolverInfoRestriccion({ D_y_E: '' }, duraDyE);
    expect(info).toMatchObject({ applicable: true, met: false, ratio: 0, progress: 0 });
  });

  test('inválido (no numérico) se trata como ratio 0 -> aplicable, no cumplida', () => {
    const info = resolverInfoRestriccion({ D_y_E: 'texto-no-numerico' }, duraDyE);
    expect(info).toMatchObject({ applicable: true, met: false, ratio: 0 });
  });

  test('100% alcanza el umbral -> cumplida', () => {
    const info = resolverInfoRestriccion({ D_y_E: '100%' }, duraDyE);
    expect(info).toMatchObject({ applicable: true, met: true, ratio: 1, progress: 1 });
  });

  test('umbral fraccionario (Predecesora 50%): 50% cumple, 33% no', () => {
    const predecesora = restriccionesDuras(config).find((r) => r.key === 'Predecesora')!;
    expect(resolverInfoRestriccion({ Predecesora: '50%' }, predecesora).met).toBe(true);
    expect(resolverInfoRestriccion({ Predecesora: '33%' }, predecesora).met).toBe(false);
  });

  test('alias "restr_<clave>" también resuelve el valor', () => {
    const info = resolverInfoRestriccion({ restr_D_y_E: '100%' }, duraDyE);
    expect(info.met).toBe(true);
  });
});

describe('calcularItr', () => {
  const config = configuracionPorDefecto();

  test('todas las duras N/A y todas las blandas N/A -> resultado neutral completo (no-applicable-hard)', () => {
    const fila = { D_y_E: 'N/A', Materiales: 'N/A', MdeO: 'N/A', Equipos: 'N/A', Predecesora: 'N/A', Pdto_Cons: 'N/A', Seguimiento: 'N/A' };
    const itr = calcularItr(fila, config);
    // Todas "no aplicables" cuentan como met=true -> liberadas === total configurado -> isComplete.
    expect(itr.isComplete).toBe(true);
    expect(itr.liberadas).toBe(config.restrictions.length);
    expect(itr.porcentaje).toBe(100);
  });

  test('fila vacía: todas ausentes, mismo resultado que todas N/A', () => {
    const itr = calcularItr({}, config);
    expect(itr.isComplete).toBe(true);
    expect(itr.porcentaje).toBe(100);
  });

  test('con 0 restricciones configuradas, isComplete se decide por porcentaje >= 0.999 (caso borde)', () => {
    const configVacia: ConfiguracionRestricciones = { area: 'X', restrictions: [], hardRestrictions: [], softRestrictions: [] };
    const itr = calcularItr({}, configVacia);
    expect(itr.aplicables).toBe(0);
    expect(itr.isComplete).toBe(true);
    expect(itr.porcentaje).toBe(100);
  });

  test('una restricción dura sin liberar mantiene isComplete en false aunque el resto esté 100%', () => {
    const fila = { D_y_E: '100%', Materiales: '100%', MdeO: '100%', Equipos: '100%', Predecesora: '0%', Pdto_Cons: 'N/A', Seguimiento: 'N/A' };
    const itr = calcularItr(fila, config);
    expect(itr.isComplete).toBe(false);
    expect(itr.liberadas).toBe(6);
  });
});

describe('tieneBrechaProfunda', () => {
  const config = configuracionPorDefecto();

  test('Predecesora por debajo de 0.5 -> brecha profunda', () => {
    const itr = calcularItr({ Predecesora: '33%' }, config);
    expect(tieneBrechaProfunda(itr)).toBe(true);
  });

  test('Predecesora exactamente en 0.5 (cumplida, umbral 50%) -> sin brecha', () => {
    const itr = calcularItr({ Predecesora: '50%' }, config);
    expect(tieneBrechaProfunda(itr)).toBe(false);
  });

  test('otra restricción (umbral 100%) por debajo de 0.66 -> brecha profunda', () => {
    const itr = calcularItr({ D_y_E: '33%' }, config);
    expect(tieneBrechaProfunda(itr)).toBe(true);
  });

  test('otra restricción entre 0.66 y el umbral -> sin brecha profunda (solo "pendiente")', () => {
    const itr = calcularItr({ D_y_E: '80%' }, config);
    expect(tieneBrechaProfunda(itr)).toBe(false);
  });

  test('todo liberado -> sin brecha', () => {
    const itr = calcularItr({}, config);
    expect(tieneBrechaProfunda(itr)).toBe(false);
  });
});

describe('resumenRestricciones', () => {
  const config = configuracionPorDefecto();

  test('campo explícito de causa tiene prioridad sobre el cálculo de pendientes', () => {
    const itr = calcularItr({ D_y_E: '33%' }, config);
    expect(resumenRestricciones({ causa_no_cumplimiento: 'Falta permiso ambiental' }, itr)).toBe('Falta permiso ambiental');
  });

  test('sin campo explícito, lista las restricciones aplicables no cumplidas con su porcentaje', () => {
    const fila = { D_y_E: '33%', Materiales: '100%', MdeO: 'N/A', Equipos: 'N/A', Predecesora: 'N/A', Pdto_Cons: 'N/A', Seguimiento: 'N/A' };
    const itr = calcularItr(fila, config);
    expect(resumenRestricciones(fila, itr)).toBe('Diseños y Especif. 33%');
  });

  test('sin pendientes ni campo explícito -> mensaje fijo', () => {
    const itr = calcularItr({}, config);
    expect(resumenRestricciones({}, itr)).toBe('Sin restricciones habilitantes pendientes');
  });
});

describe('vistaRestriccionesBlandas', () => {
  const config = configuracionPorDefecto();

  test('omite blandas sin dato, N/A o vacías; clasifica por tono', () => {
    const fila = { Pdto_Cons: '100%', Seguimiento: '50%' };
    const vista = vistaRestriccionesBlandas(fila, config);
    expect(vista).toEqual([
      { label: 'Procedimiento Constructivo', percent: 100, tono: 'exito' },
      { label: 'Seguimiento', percent: 50, tono: 'advertencia' },
    ]);
  });

  test('0% -> tono crítico; N/A/ausente -> se omite', () => {
    const vista = vistaRestriccionesBlandas({ Pdto_Cons: '0%', Seguimiento: 'N/A' }, config);
    expect(vista).toEqual([{ label: 'Procedimiento Constructivo', percent: 0, tono: 'critico' }]);
  });
});
