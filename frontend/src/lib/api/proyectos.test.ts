import { beforeEach, expect, test, vi } from 'vitest';
import { pedir } from './cliente';
import { EsquemaListaProyectos, EsquemaResultadoSeleccionProyecto } from './esquemas/proyectos';
import { listarProyectos, seleccionarProyecto } from './proyectos';

vi.mock('./cliente', () => ({ pedir: vi.fn() }));

const pedirMock = vi.mocked(pedir);

beforeEach(() => {
  pedirMock.mockReset();
});

test('listarProyectos pide /api/proyectos con el esquema y el signal', async () => {
  const controlador = new AbortController();
  pedirMock.mockResolvedValue({
    projects: [],
    navigation: { bi: { visible: false, href: null } },
  });

  await listarProyectos(controlador.signal);

  expect(pedirMock).toHaveBeenCalledWith(
    '/api/proyectos',
    EsquemaListaProyectos,
    { signal: controlador.signal },
  );
});

test('seleccionarProyecto valida el name, envía CSRF y el body esperado', async () => {
  pedirMock.mockResolvedValue({ success: true, message: null, route: '/programacion-semanal' });

  await seleccionarProyecto('  Da Porto  ', 'token-csrf');

  expect(pedirMock).toHaveBeenCalledWith(
    '/api/proyectos/seleccionar',
    EsquemaResultadoSeleccionProyecto,
    {
      method: 'POST',
      headers: { 'X-CSRF-Token': 'token-csrf' },
      body: JSON.stringify({ name: 'Da Porto' }),
    },
  );
});

test('seleccionarProyecto rechaza un name vacío sin llamar al cliente', async () => {
  await expect(seleccionarProyecto('   ', 'token-csrf')).rejects.toThrow();

  expect(pedirMock).not.toHaveBeenCalled();
});
