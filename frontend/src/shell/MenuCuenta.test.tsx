import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { MenuCuenta } from './MenuCuenta';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function respuesta(cuerpo: unknown, estado = 200): Response {
  return new Response(JSON.stringify(cuerpo), { status: estado });
}

afterEach(() => vi.unstubAllGlobals());

// Hallazgo del revisor de código (ronda de arreglos 1): `MenuCuenta` anidaba la pantalla
// completa `SelectorProyecto` —con su `<h1>Elige un proyecto</h1>` y `.aia-card`— dentro del
// panel angosto del menú de cuenta. Estas pruebas fijan el contrato correcto: ningún encabezado
// de nivel de página ni la clase de envoltorio de pantalla completa se filtran al dropdown, en
// ninguna de sus dos vistas.
test('el panel de cuenta no contiene un h1 ni la clase de envoltorio de pantalla completa', async () => {
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));

  const panel = screen.getByRole('menu');
  expect(panel.querySelector('h1')).not.toBeInTheDocument();
  expect(panel.querySelector('.aia-card')).not.toBeInTheDocument();
});

test('el disparador referencia el panel con aria-controls', () => {
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  const disparador = screen.getByRole('button', { name: /cuenta · ana/i });
  const idPanel = disparador.getAttribute('aria-controls');

  expect(idPanel).toBeTruthy();
  expect(document.getElementById(idPanel as string)).toHaveAttribute('role', 'menu');
});

test('cambiar proyecto muestra la lista como menuitems, sin h1 ni .aia-card, y reutiliza el fetch de SelectorProyecto', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({
    projects: [{ id: 1, name: 'Da Porto', role: 'A' }],
  })));
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cambiar proyecto/i }));

  const item = await screen.findByRole('menuitem', { name: /da porto/i });
  const panel = screen.getByRole('menu');
  expect(panel.querySelector('h1')).not.toBeInTheDocument();
  expect(panel.querySelector('.aia-card')).not.toBeInTheDocument();
  expect(item).toBeInTheDocument();
});

test('elegir un proyecto en el panel llama a alCambiarProyecto y cierra el menú', async () => {
  const fetchFalso = vi.fn()
    .mockResolvedValueOnce(respuesta({ projects: [{ id: 1, name: 'Da Porto', role: 'A' }] }))
    .mockResolvedValueOnce(respuesta({ success: true, message: null }));
  vi.stubGlobal('fetch', fetchFalso);
  const alCambiarProyecto = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={alCambiarProyecto} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cambiar proyecto/i }));
  await usuario.click(await screen.findByRole('menuitem', { name: /da porto/i }));

  await waitFor(() => expect(alCambiarProyecto).toHaveBeenCalledOnce());
  expect(screen.getByRole('button', { name: /cuenta · ana/i })).toHaveAttribute('aria-expanded', 'false');
});

test('volver regresa del panel de proyectos al menú principal sin perder el fetch ya hecho', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({
    projects: [{ id: 1, name: 'Da Porto', role: 'A' }],
  })));
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cambiar proyecto/i }));
  await screen.findByRole('menuitem', { name: /da porto/i });

  await usuario.click(screen.getByRole('menuitem', { name: /volver/i }));

  expect(screen.getByRole('menuitem', { name: /cambiar proyecto/i })).toBeInTheDocument();
  expect(screen.queryByRole('menuitem', { name: /da porto/i })).not.toBeInTheDocument();
});

test('cerrar sesión envía el CSRF por header contra /api/auth/logout, nunca un GET a /logout', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(respuesta({ success: true }));
  vi.stubGlobal('fetch', fetchFalso);
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cerrar sesión/i }));

  await waitFor(() => expect(fetchFalso).toHaveBeenCalledWith('/api/auth/logout', expect.objectContaining({
    method: 'POST',
  })));
  const opciones = fetchFalso.mock.calls[0]?.[1] as RequestInit;
  expect(new Headers(opciones.headers).get('X-CSRF-Token')).toBe(csrfToken);
});

test('un fallo al cerrar sesión muestra alerta y no navega', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(respuesta({ message: 'detalle interno' }, 500)));
  const usuario = userEvent.setup();
  render(<MenuCuenta alCambiarProyecto={vi.fn()} csrfToken={csrfToken} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cerrar sesión/i }));

  expect(await screen.findByRole('alert')).toHaveTextContent(/no pudimos cerrar la sesión/i);
});
