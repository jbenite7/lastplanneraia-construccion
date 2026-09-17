import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { SelectorProyecto } from './SelectorProyecto';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

const navegacion = { bi: { visible: false, href: null } };

function proyecto(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    id: 1,
    name: 'Da Porto',
    area: 'Construccion',
    active: true,
    role: 'A',
    roleLabel: 'Administrador',
    ...overrides,
  };
}

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

afterEach(() => vi.unstubAllGlobals());

test('lista los proyectos disponibles', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({
    projects: [
      proyecto({ id: 1, name: 'Da Porto' }),
      proyecto({ id: 2, name: 'Aeropuerto', role: 'R', roleLabel: 'Residente de Obra' }),
    ],
    navigation: navegacion,
  })));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByRole('button', { name: /da porto/i })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /aeropuerto/i })).toBeInTheDocument();
});

test('envía el proyecto con CSRF y avisa al shell al elegirlo', async () => {
  const fetchFalso = vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [proyecto()], navigation: navegacion }))
    .mockResolvedValueOnce(respuesta({ success: true, message: null, route: '/programacion-semanal' }));
  vi.stubGlobal('fetch', fetchFalso);
  const alElegir = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();

  render(<SelectorProyecto alElegir={alElegir} csrfToken={csrfToken} />);

  await usuario.click(await screen.findByRole('button', { name: /da porto/i }));

  await waitFor(() => expect(alElegir).toHaveBeenCalledOnce());
  expect(fetchFalso).toHaveBeenLastCalledWith('/api/proyectos/seleccionar', expect.objectContaining({
    method: 'POST',
    credentials: 'same-origin',
    headers: expect.any(Headers),
  }));
  const opciones = fetchFalso.mock.calls[1]?.[1] as RequestInit;
  expect(new Headers(opciones.headers).get('X-CSRF-Token')).toBe(csrfToken);
});

test('explica cuando no hay proyectos asignados', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ projects: [], navigation: navegacion })));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByText(/no tienes proyectos/i)).toBeInTheDocument();
});

test('muestra una alerta segura si no puede cargar los proyectos', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ message: 'detalle interno' }, 500)));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos cargar tus proyectos/i);
});

test('muestra una alerta segura si el servidor rechaza el proyecto', async () => {
  vi.stubGlobal('fetch', vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [proyecto()], navigation: navegacion }))
    .mockResolvedValueOnce(respuesta({
      success: false,
      message: 'No se pudo acceder al proyecto seleccionado.',
      route: null,
    })));
  const usuario = userEvent.setup();

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  await usuario.click(await screen.findByRole('button', { name: /da porto/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos abrir ese proyecto/i);
});

test('muestra una alerta segura si la respuesta de selección no cumple el esquema', async () => {
  vi.stubGlobal('fetch', vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [proyecto()], navigation: navegacion }))
    .mockResolvedValueOnce(respuesta({ success: false, message: 'Detalle interno' })));
  const usuario = userEvent.setup();

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  await usuario.click(await screen.findByRole('button', { name: /da porto/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos abrir ese proyecto/i);
});
