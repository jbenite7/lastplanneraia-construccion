import { describe, expect, test } from 'vitest';
import {
  esRutaCritica,
  consecutivoCanonico,
  tituloActividad,
  textoPlano,
  subcontratista,
  responsable,
  esFilaCabecera,
  ratioAvance,
  avanceVisible,
} from './filaLps';

describe('esRutaCritica', () => {
  test('prioridad literal "P1" (insensible a mayúsculas y espacios)', () => {
    expect(esRutaCritica({ prioridad: ' p1 ' })).toBe(true);
  });

  test('bandera laxa en cualquiera de las claves conocidas', () => {
    expect(esRutaCritica({ Ruta_Critica: 'si' })).toBe(true);
    expect(esRutaCritica({ critica: 1 })).toBe(true);
  });

  test('sin prioridad P1 ni bandera -> false', () => {
    expect(esRutaCritica({ prioridad: 'P2' })).toBe(false);
  });

  test('fila ausente -> false', () => {
    expect(esRutaCritica(null)).toBe(false);
  });
});

describe('consecutivoCanonico', () => {
  test('usa el primer alias no-blanco, en orden de prioridad', () => {
    expect(consecutivoCanonico({ Consecutivo: '10', Id: '99' })).toBe('10');
  });

  test('sin ningún alias con dato -> "N/A"', () => {
    expect(consecutivoCanonico({})).toBe('N/A');
  });
});

describe('tituloActividad', () => {
  test('usa "Actividad", luego "nombre"/"Nombre"', () => {
    expect(tituloActividad({ Actividad: 'Excavación' })).toBe('Excavación');
    expect(tituloActividad({ nombre: 'Fundida' })).toBe('Fundida');
  });

  test('sin dato -> "Tarea sin nombre"', () => {
    expect(tituloActividad({})).toBe('Tarea sin nombre');
  });
});

describe('textoPlano', () => {
  test('quita etiquetas HTML simples', () => {
    expect(textoPlano('<mark>Excavación</mark> torre A')).toBe('Excavación torre A');
  });

  test('decodifica las entidades comunes', () => {
    expect(textoPlano('Uni&oacute;n &amp; obra &lt;fase 1&gt;')).toBe('Uni&oacute;n & obra <fase 1>');
  });

  test('valor vacío/nulo -> cadena vacía', () => {
    expect(textoPlano(null)).toBe('');
    expect(textoPlano(undefined)).toBe('');
  });

  test('texto sin marcado se conserva (con trim)', () => {
    expect(textoPlano('  Excavación torre A  ')).toBe('Excavación torre A');
  });
});

describe('subcontratista / responsable', () => {
  test('subcontratista usa el primer alias no-blanco, con respaldo "Sin Asignar"', () => {
    expect(subcontratista({ Sub_Contratista: 'ACME' })).toBe('ACME');
    expect(subcontratista({})).toBe('Sin Asignar');
  });

  test('responsable usa el primer alias no-blanco, con respaldo "Sin Asignar"', () => {
    expect(responsable({ Responsable_AIA: 'Juan' })).toBe('Juan');
    expect(responsable({})).toBe('Sin Asignar');
  });
});

describe('esFilaCabecera', () => {
  test('Titulo numérico distinto de 0 -> cabecera, sin importar el estado', () => {
    expect(esFilaCabecera({ Titulo: 1 }, 'en-curso')).toBe(true);
  });

  test('clave de estado "header" -> cabecera aunque Titulo esté ausente', () => {
    expect(esFilaCabecera({}, 'header')).toBe(true);
  });

  test('Titulo 0/ausente y estado distinto de "header" -> no es cabecera', () => {
    expect(esFilaCabecera({ Titulo: 0 }, 'en-curso')).toBe(false);
    expect(esFilaCabecera({}, 'en-curso')).toBe(false);
  });

  test('fila ausente -> false', () => {
    expect(esFilaCabecera(null, 'header')).toBe(false);
  });
});

describe('ratioAvance / avanceVisible', () => {
  test('ratioAvance normaliza "Ejecutado" a 0-1, 0 si está en blanco', () => {
    expect(ratioAvance({ Ejecutado: '50%' })).toBe(0.5);
    expect(ratioAvance({})).toBe(0);
  });

  test('avanceVisible con unidad distinta de "%" muestra cantidad + unidad + porcentaje', () => {
    expect(avanceVisible({ Ejecutado: '50%', EjecutadoDisplay: '120.5', unidad: 'm2' })).toBe('120,5 m2 (50%)');
  });

  test('avanceVisible sin unidad o con "%" muestra solo el porcentaje', () => {
    expect(avanceVisible({ Ejecutado: '75%' })).toBe('75%');
  });
});
