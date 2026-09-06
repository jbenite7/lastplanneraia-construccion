import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { AppShell } from './AppShell';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function sesionLista(): ArranqueAutenticado {
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
    week: null,
    csrfToken,
  };
}

function establecerAncho(ancho: number) {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: ancho });
  window.dispatchEvent(new Event('resize'));
}

function Explota(): never {
  throw new Error('boom de módulo — nunca debe llegar al DOM');
}

function renderizar(elementoOutlet: React.ReactElement, rutaInicial = '/') {
  return render(
    <MemoryRouter initialEntries={[rutaInicial]}>
      <Routes>
        <Route element={<AppShell cerrarSesion={vi.fn().mockResolvedValue(undefined)} recargar={vi.fn().mockResolvedValue(undefined)} sesion={sesionLista()} />} path="/">
          <Route element={elementoOutlet} index />
          <Route element={<p>Otra vista interna</p>} path="/otra-vista-interna" />
        </Route>
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

test('el skip link mueve el foco a #contenido, no solo lo desplaza en pantalla', async () => {
  const usuario = userEvent.setup();
  renderizar(<p>Contenido</p>);

  await usuario.click(screen.getByRole('link', { name: /saltar al contenido/i }));

  expect(screen.getByRole('main')).toHaveFocus();
});

test('un error de render dentro del outlet no tumba el resto del shell: el nav sigue presente', () => {
  const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

  renderizar(<Explota />);

  expect(screen.getByRole('navigation')).toBeInTheDocument();
  expect(screen.getByRole('alert')).toHaveTextContent(/algo salió mal/i);
  expect(document.body.textContent).not.toMatch(/boom de módulo/i);

  consoleError.mockRestore();
});

test('actualiza document.title al montar el shell en una ruta con proyecto activo', () => {
  renderizar(<p>Contenido</p>, '/');
  expect(document.title).toContain('Da Porto');
  expect(document.title).toContain('Last Planner AIA');
});

test('existe una región aria-live que anuncia el título vigente', () => {
  renderizar(<p>Contenido</p>, '/');
  const region = screen.getByRole('status');
  expect(region).toHaveAttribute('aria-live', 'polite');
  expect(region.textContent).toBe(document.title);
});

test('al abrir el drawer en móvil, el foco entra al primer control enfocable del nav', async () => {
  establecerAncho(390);
  const usuario = userEvent.setup();
  renderizar(<p>Contenido</p>);

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  await waitFor(() => {
    expect(document.activeElement).not.toBe(document.body);
    expect(screen.getByRole('navigation').closest('aside')).toContainElement(document.activeElement as HTMLElement);
  });
});

test('Tab no deja escapar el foco del drawer mientras está abierto en móvil (trampa de foco)', async () => {
  establecerAncho(390);
  const usuario = userEvent.setup();
  renderizar(<p>Contenido</p>);

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  const aside = screen.getByRole('navigation').closest('aside') as HTMLElement;
  const enfocables = aside.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  );
  expect(enfocables.length).toBeGreaterThan(0);
  const ultimo = enfocables[enfocables.length - 1];

  ultimo.focus();
  await usuario.tab();

  expect(aside).toContainElement(document.activeElement as HTMLElement);
});
