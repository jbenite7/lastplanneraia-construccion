import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { SelectorProyecto } from './SelectorProyecto';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

afterEach(() => vi.unstubAllGlobals());

test('lista los proyectos disponibles', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({
    projects: [
      { id: 1, name: 'Da Porto', role: 'A' },
      { id: 2, name: 'Aeropuerto', role: 'R' },
    ],
  })));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByRole('button', { name: /da porto/i })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /aeropuerto/i })).toBeInTheDocument();
});

test('envía el proyecto con CSRF y avisa al shell al elegirlo', async () => {
  const fetchFalso = vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [{ id: 1, name: 'Da Porto', role: 'A' }] }))
    .mockResolvedValueOnce(respuesta({ success: true, message: null }));
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
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ projects: [] })));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByText(/no tienes proyectos/i)).toBeInTheDocument();
});

test('muestra una alerta segura si no puede cargar los proyectos', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ message: 'detalle interno' }, 500)));

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos cargar tus proyectos/i);
});

test('muestra una alerta segura si no puede abrir el proyecto', async () => {
  vi.stubGlobal('fetch', vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [{ id: 1, name: 'Da Porto', role: 'A' }] }))
    .mockResolvedValueOnce(respuesta({ success: false, message: 'Detalle interno' })));
  const usuario = userEvent.setup();

  render(<SelectorProyecto alElegir={vi.fn()} csrfToken={csrfToken} />);

  await usuario.click(await screen.findByRole('button', { name: /da porto/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos abrir ese proyecto/i);
});
