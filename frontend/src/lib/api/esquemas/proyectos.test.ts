import { expect, test } from 'vitest';
import {
  EsquemaListaProyectos,
  EsquemaResultadoSeleccionProyecto,
  EsquemaSolicitudSeleccionProyecto,
  MENSAJE_RECHAZO_PROYECTO,
} from './proyectos';

const project = {
  id: 73,
  name: 'Da Porto',
  area: 'Construccion',
  active: true,
  role: 'A',
  roleLabel: 'Administrador',
};

test('lista exige tarjeta completa y BI coherente', () => {
  expect(EsquemaListaProyectos.parse({
    projects: [project], navigation: { bi: { visible: true, href: '/bi/control-tower' } },
  }).projects[0]).toEqual(project);
  expect(EsquemaListaProyectos.safeParse({
    projects: [{ ...project, db: 'da_porto' }],
    navigation: { bi: { visible: false, href: null } },
  }).success).toBe(false);
  expect(EsquemaListaProyectos.safeParse({
    projects: [project], navigation: { bi: { visible: false, href: '/bi/control-tower' } },
  }).success).toBe(false);
});

test('selección solo acepta name y route interno seguro', () => {
  expect(EsquemaSolicitudSeleccionProyecto.parse({ name: '  Da Porto  ' }))
    .toEqual({ name: 'Da Porto' });
  expect(EsquemaSolicitudSeleccionProyecto.safeParse({ name: 'Da Porto', project_id: 73 }).success)
    .toBe(false);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: true, message: null, route: '/programacion-semanal',
  }).success).toBe(true);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: true, message: null, route: '//evil.example',
  }).success).toBe(false);
  expect(EsquemaResultadoSeleccionProyecto.safeParse({
    success: false, message: MENSAJE_RECHAZO_PROYECTO, route: null,
  }).success).toBe(true);
});
