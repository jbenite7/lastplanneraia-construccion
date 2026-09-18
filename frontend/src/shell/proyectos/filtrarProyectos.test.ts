import { describe, expect, test } from 'vitest';
import { filtrarProyectos, normalizarBusqueda, textoConteo } from './filtrarProyectos';
import type { ProyectoDisponible } from '../../lib/api/esquemas/proyectos';

/**
 * Dominio puro de búsqueda/conteo del selector de proyectos (Tarea 4, S04). Reproduce el
 * comportamiento observable de `views/core/project_selector.view.php` (búsqueda por nombre,
 * normalizada con `trim().toLocaleLowerCase()`, y los cuatro textos de `updateResults()`), pero
 * usando el tipo real `ProyectoDisponible` (`lib/api/esquemas/proyectos.ts`), que exige
 * `active: true` en todo elemento — el contrato de `/api/proyectos` solo entrega proyectos
 * activos. Por eso "proyectos inactivos" no es un caso de este dominio: el servidor nunca los
 * incluye en la lista que llega aquí, así que no hay nada que filtrar.
 *
 * Nota de comportamiento real de `normalizarBusqueda`: `String.normalize('NFD')` descompone la
 * ñ en "n" + tilde combinante, y el `replace(/\p{M}/gu, '')` la retira igual que un acento —
 * "Cañaveral" y "Canaveral" son equivalentes bajo esta normalización. No es un bug del dominio:
 * es lo que hace `String.prototype.normalize`, verificado en Node antes de fijar el test.
 */

function proyecto(overrides: Partial<ProyectoDisponible> & { id: number; name: string }): ProyectoDisponible {
  return {
    area: 'Construccion',
    active: true,
    role: 'R',
    roleLabel: 'Residente de Obra',
    ...overrides,
  };
}

describe('normalizarBusqueda', () => {
  test('recorta espacios, pasa a minúsculas es-CO y retira acentos', () => {
    expect(normalizarBusqueda('  CONSTRUCCIÓN Norte ')).toBe('construccion norte');
  });

  test('la ñ se reduce a n bajo NFD, igual que un acento (comportamiento real de `String.normalize`)', () => {
    expect(normalizarBusqueda('Añil')).toBe('anil');
  });

  test('una cadena de solo espacios normaliza a vacío', () => {
    expect(normalizarBusqueda('   ')).toBe('');
  });
});

describe('filtrarProyectos', () => {
  const projects = [
    proyecto({ id: 1, name: 'Construcción Norte' }),
    proyecto({ id: 2, name: 'Ágora' }),
    proyecto({ id: 3, name: 'Da Porto' }),
  ];

  test('trim, locale y diacríticos encuentran sin reordenar', () => {
    expect(filtrarProyectos(projects, '  CONSTRUCCION ')).toEqual([projects[0]]);
    expect(filtrarProyectos(projects, 'agora')).toEqual([projects[1]]);
    expect(filtrarProyectos(projects, '')).toEqual(projects);
    expect(projects.map(({ id }) => id)).toEqual([1, 2, 3]);
  });

  test('query de solo espacios equivale a búsqueda vacía: conserva orden y referencia lógica', () => {
    expect(filtrarProyectos(projects, '   ')).toEqual(projects);
  });

  test('nombres con ñ se encuentran con o sin ñ en la búsqueda (NFD la reduce a n en ambos lados)', () => {
    const conEnie = [proyecto({ id: 4, name: 'Cañaveral' })];
    expect(filtrarProyectos(conEnie, 'cañaveral')).toEqual(conEnie);
    expect(filtrarProyectos(conEnie, 'canaveral')).toEqual(conEnie);
  });

  test('sin coincidencias devuelve lista vacía', () => {
    expect(filtrarProyectos(projects, 'zzz-no-existe')).toEqual([]);
  });

  test('lista vacía de entrada devuelve lista vacía para cualquier query', () => {
    expect(filtrarProyectos([], 'algo')).toEqual([]);
    expect(filtrarProyectos([], '')).toEqual([]);
  });

  test('nunca muta el arreglo de entrada', () => {
    const original = [...projects];
    filtrarProyectos(projects, 'norte');
    filtrarProyectos(projects, '');
    expect(projects).toEqual(original);
  });

  test('con query vacío no reutiliza la misma referencia de arreglo (evita mutación externa accidental)', () => {
    expect(filtrarProyectos(projects, '')).not.toBe(projects);
  });
});

describe('textoConteo', () => {
  test('conteos conservan los cuatro textos', () => {
    expect(textoConteo(1, false)).toBe('1 proyecto disponible');
    expect(textoConteo(3, false)).toBe('3 proyectos disponibles');
    expect(textoConteo(1, true)).toBe('1 proyecto encontrado');
    expect(textoConteo(0, true)).toBe('0 proyectos encontrados');
  });

  test('cero proyectos disponibles (lista vacía sin búsqueda) usa plural', () => {
    expect(textoConteo(0, false)).toBe('0 proyectos disponibles');
  });
});
