import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { AppShell } from './AppShell';
import { aplicarTema, type Tema } from './tema';

/**
 * Tarea 7 (T01): oscuro y claro deben exponer exactamente los mismos controles del shell,
 * usables (roles accesibles reales, no solo presentes en el DOM) en los dos temas. El tema es
 * puramente un atributo/clase en `<html>` — ningún componente del shell condiciona su render al
 * tema activo — así que estas pruebas fijan esa invariante: si alguna vez alguien introduce
 * markup condicional por tema, una de estas aserciones lo atrapa.
 */
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
    week: { current: 6, options: [{ number: 6, startsOn: '2026-08-24', endsOn: '2026-08-30' }], actions: { select: true, create: true, deleteLast: true } },
    csrfToken,
  };
}

function establecerAncho(ancho: number) {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: ancho });
  window.dispatchEvent(new Event('resize'));
}

function renderizar() {
  return render(
    <MemoryRouter initialEntries={['/']}>
      <Routes>
        <Route element={<AppShell cerrarSesion={vi.fn().mockResolvedValue(undefined)} recargar={vi.fn().mockResolvedValue(undefined)} sesion={sesionLista()} />} path="/">
          <Route element={<p>Contenido del módulo</p>} index />
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
  document.documentElement.removeAttribute('data-aia-theme');
  document.documentElement.classList.remove('aia-theme-dark');
  establecerAncho(anchoOriginal);
  vi.unstubAllGlobals();
});

function verificarControlesDelShell() {
  expect(screen.getByRole('complementary', { name: /aplicación/i })).toBeInTheDocument();
  expect(screen.getByRole('navigation', { name: /navegación del proyecto/i })).toBeInTheDocument();
  expect(screen.getByRole('main')).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /saltar al contenido/i })).toBeInTheDocument();
  expect(screen.getByRole('link', { name: /programa general/i })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /colapsar menú/i })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /cambiar a tema (claro|oscuro)/i })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: /cuenta · ana/i })).toBeInTheDocument();
}

test.each<Tema>(['oscuro', 'claro'])('el shell expone los mismos controles usables en tema %s', (tema) => {
  aplicarTema(tema);
  renderizar();

  expect(document.documentElement.getAttribute('data-aia-theme')).toBe(tema === 'oscuro' ? 'dark' : 'light');
  verificarControlesDelShell();
});

test.each<Tema>(['oscuro', 'claro'])('el conmutador de tema es enfocable con teclado en tema %s', (tema) => {
  aplicarTema(tema);
  renderizar();

  const conmutador = screen.getByRole('button', { name: /cambiar a tema (claro|oscuro)/i });
  conmutador.focus();

  expect(document.activeElement).toBe(conmutador);
});

test('el disparador del drawer móvil también existe en ambos temas bajo el umbral flotante', () => {
  establecerAncho(390);

  aplicarTema('oscuro');
  const { unmount } = renderizar();
  expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toBeInTheDocument();
  unmount();

  aplicarTema('claro');
  renderizar();
  expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toBeInTheDocument();
});
