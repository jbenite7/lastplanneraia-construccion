import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { AppShell } from './AppShell';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function sesionLista(overrides: Partial<ArranqueAutenticado> = {}): ArranqueAutenticado {
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
          id: 'obra',
          label: 'Obra',
          items: [
            { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false },
          ],
        },
      ],
    },
    week: { current: 6, options: [{ number: 6, startsOn: "2026-08-24", endsOn: "2026-08-30" }], actions: { select: true, create: true, deleteLast: true } },
    csrfToken,
    ...overrides,
  };
}

function establecerAncho(ancho: number) {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: ancho });
  window.dispatchEvent(new Event('resize'));
}

function renderizarConRuta(sesion: ArranqueAutenticado, recargar = vi.fn().mockResolvedValue(undefined)) {
  return render(
    <MemoryRouter initialEntries={['/']}>
      <Routes>
        <Route element={<AppShell recargar={recargar} sesion={sesion} />} path="/">
          <Route element={<p>Contenido del módulo</p>} index />
        </Route>
        <Route element={<p>Otra pantalla</p>} path="/otra" />
      </Routes>
    </MemoryRouter>,
  );
}

const anchoOriginal = window.innerWidth;

beforeEach(() => {
  establecerAncho(1440);
});

afterEach(() => {
  document.body.classList.remove('aia-shell--sidebar');
  document.body.style.overflow = '';
  establecerAncho(anchoOriginal);
  vi.unstubAllGlobals();
});

test('renderiza exactamente un nav, un main, skip link y el contenido del outlet', () => {
  renderizarConRuta(sesionLista());

  expect(screen.getAllByRole('navigation')).toHaveLength(1);
  expect(screen.getAllByRole('main')).toHaveLength(1);
  expect(screen.getByRole('link', { name: /saltar al contenido/i })).toHaveAttribute('href', '#contenido');
  expect(within(screen.getByRole('main')).getByText('Contenido del módulo')).toBeInTheDocument();
});

test('marca la única entrada activa con aria-current', () => {
  window.history.pushState({}, '', '/programa-general');
  renderizarConRuta(sesionLista());

  expect(screen.getByRole('link', { name: /programa general/i })).toHaveAttribute('aria-current', 'page');

  window.history.pushState({}, '', '/');
});

test('muestra el proyecto y la semana activos', () => {
  renderizarConRuta(sesionLista());

  expect(screen.getByText('Da Porto')).toBeInTheDocument();
  expect(screen.getAllByText(/semana 6/i).length).toBeGreaterThan(0);
});

test('el menú de cuenta abre y ofrece cambiar proyecto y cerrar sesión', async () => {
  const usuario = userEvent.setup();
  renderizarConRuta(sesionLista());

  const disparador = screen.getByRole('button', { name: /cuenta · ana/i });
  expect(disparador).toHaveAttribute('aria-expanded', 'false');

  await usuario.click(disparador);

  expect(disparador).toHaveAttribute('aria-expanded', 'true');
  expect(screen.getByRole('menuitem', { name: /cambiar proyecto/i })).toBeInTheDocument();
  expect(screen.getByRole('menuitem', { name: /cerrar sesión/i })).toBeInTheDocument();
});

test('no duplica el shell: una sola aside de navegación en el árbol', () => {
  const { container } = renderizarConRuta(sesionLista());

  expect(container.querySelectorAll('aside.aia-navigation--sidebar')).toHaveLength(1);
  expect(container.querySelectorAll('main')).toHaveLength(1);
});
