import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { AppShell } from './AppShell';

// El cableado entre el riel, los diálogos y el hook de semana: donde vive el riesgo de que
// «Eliminar» borre la semana equivocada o de que se navegue cuando no toca (hallazgo 3 de la
// revisión independiente, 2026-09-29). Las piezas sueltas ya se prueban en sus propios archivos.
const { mockSeleccionar, mockCrear, mockEliminarUltima, mockLimpiarError, estadoHook } = vi.hoisted(() => ({
  mockSeleccionar: vi.fn(),
  mockCrear: vi.fn(),
  mockEliminarUltima: vi.fn(),
  mockLimpiarError: vi.fn(),
  estadoHook: { error: null as string | null },
}));

vi.mock('./useContextoSemana', () => ({
  useContextoSemana: () => ({
    seleccionar: mockSeleccionar,
    crear: mockCrear,
    eliminarUltima: mockEliminarUltima,
    limpiarError: mockLimpiarError,
    seleccionando: false,
    creando: false,
    eliminando: false,
    error: estadoHook.error,
  }),
}));

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function sesion(): ArranqueAutenticado {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: {
      bi: null,
      groups: [
        {
          id: 'informacion',
          label: 'Información',
          items: [{ id: 'semanas-proyecto', label: 'Semanas del Proyecto', href: null, icon: 'calendar', action: true }],
        },
        {
          id: 'obra',
          label: 'Obra',
          items: [
            { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false },
            { id: 'programacion-semanal', label: 'Programación Semanal', href: '/programacion-semanal', icon: 'calendar', action: false },
          ],
        },
      ],
    },
    // La semana vigente (1) NO es la última (2): distingue «la última» de «la vigente».
    week: {
      current: 1,
      options: [
        { number: 1, startsOn: '2026-08-18', endsOn: '2026-08-24' },
        { number: 2, startsOn: '2026-08-25', endsOn: '2026-08-31' },
      ],
      actions: { select: true, create: true, deleteLast: true },
    },
    csrfToken,
  };
}

function pintar(irA = vi.fn()) {
  render(
    <MemoryRouter initialEntries={['/']}>
      <Routes>
        <Route
          element={<AppShell cerrarSesion={vi.fn().mockResolvedValue(undefined)} irA={irA} recargar={vi.fn().mockResolvedValue(undefined)} sesion={sesion()} />}
          path="/"
        >
          <Route element={<p>Contenido del módulo</p>} index />
        </Route>
      </Routes>
    </MemoryRouter>,
  );
  return irA;
}

beforeEach(() => {
  mockSeleccionar.mockReset().mockResolvedValue(true);
  mockCrear.mockReset().mockResolvedValue(true);
  mockEliminarUltima.mockReset().mockResolvedValue(true);
  mockLimpiarError.mockReset();
  estadoHook.error = null;
  window.history.pushState({}, '', '/programa-general');
});

afterEach(() => {
  window.history.pushState({}, '', '/');
  document.body.classList.remove('aia-shell--sidebar');
});

test('elegir semana en el flyout del módulo ACTIVO la cambia y no navega', async () => {
  const irA = pintar();

  const menu = screen.getByRole('menu', { name: 'Semanas de Programa General' });
  await userEvent.click(within(menu).getByRole('menuitem', { name: /Semana 2/ }));

  expect(mockSeleccionar).toHaveBeenCalledWith(2);
  expect(irA).not.toHaveBeenCalled();
});

test('elegir semana en el flyout de OTRO módulo la cambia y navega a ese módulo', async () => {
  const irA = pintar();

  const menu = screen.getByRole('menu', { name: 'Semanas de Programación Semanal' });
  await userEvent.click(within(menu).getByRole('menuitem', { name: /Semana 2/ }));

  expect(mockSeleccionar).toHaveBeenCalledWith(2);
  await waitFor(() => expect(irA).toHaveBeenCalledWith('/programacion-semanal'));
});

test('si el cambio de semana falla no se navega', async () => {
  mockSeleccionar.mockResolvedValue(false);
  const irA = pintar();

  const menu = screen.getByRole('menu', { name: 'Semanas de Programación Semanal' });
  await userEvent.click(within(menu).getByRole('menuitem', { name: /Semana 2/ }));

  await waitFor(() => expect(mockSeleccionar).toHaveBeenCalled());
  expect(irA).not.toHaveBeenCalled();
});

test('la papelera confirma y elimina la ÚLTIMA semana, no la vigente', async () => {
  pintar();

  const menu = screen.getByRole('menu', { name: 'Semanas del Proyecto' });
  await userEvent.click(within(menu).getByRole('button', { name: 'Eliminar Semana 2' }));

  const dialogo = screen.getByRole('dialog', { name: /eliminar semana 2/i });
  expect(mockEliminarUltima).not.toHaveBeenCalled();
  await userEvent.click(within(dialogo).getByRole('button', { name: 'Eliminar semana' }));

  expect(mockEliminarUltima).toHaveBeenCalledWith(2);
  await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
});

test('«+ Nueva semana» abre la confirmación de crear con la fecha sugerida', async () => {
  pintar();

  const menu = screen.getByRole('menu', { name: 'Semanas del Proyecto' });
  await userEvent.click(within(menu).getByRole('menuitem', { name: '+ Nueva semana' }));

  const dialogo = screen.getByRole('dialog', { name: /crear semana 3/i });
  expect(within(dialogo).getByLabelText('Fecha de inicio')).toHaveValue('2026-09-01');
  await userEvent.click(within(dialogo).getByRole('button', { name: 'Crear semana' }));
  expect(mockCrear).toHaveBeenCalledWith('2026-09-01');
});

test('al cerrar la confirmación el foco vuelve a «Semanas del Proyecto» sin reabrir el flyout', async () => {
  pintar();
  const menu = screen.getByRole('menu', { name: 'Semanas del Proyecto' });
  await userEvent.click(within(menu).getByRole('menuitem', { name: '+ Nueva semana' }));

  await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Cancelar' }));

  const boton = screen.getByRole('button', { name: 'Semanas del Proyecto' });
  await waitFor(() => expect(boton).toHaveFocus());
  expect(boton.closest('li')).toHaveAttribute('data-flyout-descartado', 'true');
  expect(boton.closest('li')).not.toHaveClass('shell-week-open');
});

test('un error al cambiar de semana se anuncia fuera del flyout y se puede descartar', async () => {
  estadoHook.error = 'No pudimos cambiar de semana. Intenta de nuevo.';
  pintar();

  const alerta = within(screen.getByRole('main')).getByRole('alert');
  expect(alerta).toHaveTextContent('No pudimos cambiar de semana');
  // El flyout ya no carga con el mensaje: quedaría oculto por CSS y pegado al reabrirlo.
  expect(within(screen.getByRole('menu', { name: 'Semanas de Programa General' })).queryByRole('alert')).not.toBeInTheDocument();

  await userEvent.click(within(alerta).getByRole('button', { name: /cerrar/i }));
  expect(mockLimpiarError).toHaveBeenCalledTimes(1);
});
