import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { MenuCuenta } from './MenuCuenta';

function cerrarSesionFalso() {
  return vi.fn().mockResolvedValue(undefined);
}

afterEach(() => vi.unstubAllGlobals());

// Hallazgo del revisor de código (ronda de arreglos 1, T01): `MenuCuenta` anidaba la pantalla
// completa `SelectorProyecto` —con su `<h1>Elige un proyecto</h1>` y `.aia-card`— dentro del
// panel angosto del menú de cuenta. Este contrato sigue vigente tras T7-1 (Tarea 7, S04): ningún
// encabezado de nivel de página ni clase de envoltorio de pantalla completa puede filtrarse aquí.
test('el panel de cuenta no contiene un h1 ni la clase de envoltorio de pantalla completa', async () => {
  const usuario = userEvent.setup();
  render(<MenuCuenta cerrarSesion={cerrarSesionFalso()} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));

  const panel = screen.getByRole('menu');
  expect(panel.querySelector('h1')).not.toBeInTheDocument();
  expect(panel.querySelector('.aia-card')).not.toBeInTheDocument();
});

test('el disparador referencia el panel con aria-controls', () => {
  render(<MenuCuenta cerrarSesion={cerrarSesionFalso()} nombre="Ana" />);

  const disparador = screen.getByRole('button', { name: /cuenta · ana/i });
  const idPanel = disparador.getAttribute('aria-controls');

  expect(idPanel).toBeTruthy();
  expect(document.getElementById(idPanel as string)).toHaveAttribute('role', 'menu');
});

// T7-1 (Tarea 7, S04, decisión del coordinador): "Cambiar proyecto" deja de abrir un panel en
// sitio (`PanelCambiarProyecto` + `useSelectorProyecto`, ya retirados) y pasa a un enlace de
// navegación completa a `/proyectos` — spec S04 §421, y §474 exige descartar las cachés del
// proyecto anterior antes de cualquier render operativo, cosa que la recarga completa garantiza
// y un panel en sitio no.
test('cambiar proyecto es un enlace de navegación completa a /proyectos, sin panel en sitio', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);
  const usuario = userEvent.setup();
  render(<MenuCuenta cerrarSesion={cerrarSesionFalso()} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));

  const enlace = screen.getByRole('menuitem', { name: /cambiar proyecto/i });
  expect(enlace.tagName).toBe('A');
  expect(enlace).toHaveAttribute('href', '/proyectos');
  // Ningún fetch propio: es una navegación de documento completo, no una mutación de la SPA.
  expect(fetchFalso).not.toHaveBeenCalled();
});

// Tarea 6, T01: el logout ya no es un fetch propio de `MenuCuenta` — delega en el `cerrarSesion`
// que expone `SesionProvider` (respaldado por el único `ControlActividad` del árbol). El contrato
// de "CSRF por header contra /api/auth/logout, nunca un GET a /logout" ahora vive en
// `ControlActividad.ciclo-vida.test.ts`; aquí solo se prueba la delegación.
test('cerrar sesión llama al cerrarSesion del SesionProvider, nunca un fetch propio', async () => {
  const fetchFalso = vi.fn();
  vi.stubGlobal('fetch', fetchFalso);
  const cerrarSesion = cerrarSesionFalso();
  const usuario = userEvent.setup();
  render(<MenuCuenta cerrarSesion={cerrarSesion} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cerrar sesión/i }));

  await waitFor(() => expect(cerrarSesion).toHaveBeenCalledOnce());
  expect(fetchFalso).not.toHaveBeenCalled();
});

test('mientras cerrarSesion está en curso, el botón se deshabilita y muestra el estado de progreso', async () => {
  let liberar!: () => void;
  const cerrarSesion = vi.fn().mockReturnValue(new Promise<void>((resolver) => {
    liberar = resolver;
  }));
  const usuario = userEvent.setup();
  render(<MenuCuenta cerrarSesion={cerrarSesion} nombre="Ana" />);

  await usuario.click(screen.getByRole('button', { name: /cuenta · ana/i }));
  await usuario.click(screen.getByRole('menuitem', { name: /cerrar sesión/i }));

  expect(screen.getByRole('menuitem', { name: /cerrando sesión/i })).toBeDisabled();

  liberar();
  await waitFor(() => expect(cerrarSesion).toHaveBeenCalledOnce());
});
