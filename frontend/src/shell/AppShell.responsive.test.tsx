import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Link, MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import type { ArranqueAutenticado } from '../lib/api/esquemas/arranque';
import { AppShell } from './AppShell';
import { UMBRAL_BARRA_LATERAL_FLOTANTE, esBarraLateralFlotante } from './modoBarraLateral';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function sesionLista(): ArranqueAutenticado {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto', area: 'Construccion' },
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: { current: 6 },
    csrfToken,
  };
}

function establecerAncho(ancho: number) {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: ancho });
  window.dispatchEvent(new Event('resize'));
}

function renderizar(rutaInicial = '/') {
  return render(
    <MemoryRouter initialEntries={[rutaInicial]}>
      <Routes>
        <Route element={<AppShell recargar={vi.fn().mockResolvedValue(undefined)} sesion={sesionLista()} />} path="/">
          <Route element={<Link to="/otra-vista-interna">Ir a otra vista</Link>} index />
          <Route element={<p>Otra vista</p>} path="/otra-vista-interna" />
        </Route>
      </Routes>
    </MemoryRouter>,
  );
}

const anchoOriginal = window.innerWidth;

afterEach(() => {
  document.body.classList.remove('aia-shell--sidebar');
  document.body.style.overflow = '';
  establecerAncho(anchoOriginal);
  vi.unstubAllGlobals();
});

test('el umbral responsive de 1180px separa drawer de rail persistente (mismo corte que shell-drawer.js)', () => {
  expect(UMBRAL_BARRA_LATERAL_FLOTANTE).toBe(1180);
  expect(esBarraLateralFlotante(1179)).toBe(true);
  expect(esBarraLateralFlotante(1180)).toBe(false);
});

test('a 390px (móvil) el drawer arranca cerrado con disparador visible y velo ausente', () => {
  establecerAncho(390);
  renderizar();

  expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toHaveAttribute('aria-expanded', 'false');
  expect(document.querySelector('.shell-menu-velo')).not.toBeInTheDocument();
  expect(screen.getByRole('navigation').closest('aside')).not.toHaveAttribute('data-shell-drawer-open');
});

// Regresión ronda de arreglos 2 (hallazgo del coordinador, navegador real, resize genuino
// 1440×900 → 390×844): un cambio anterior podía, en teoría, desincronizar la detección de modo
// (drawer vs. rail persistente) del transform del estado abierto — son dos mecanismos distintos
// en `AppShell` (el `useEffect` de `sincronizar()` que decide `flotante`, y el `useEffect` del
// respaldo inline que solo actúa sobre `transform`). Esta prueba monta en escritorio, redimensiona
// de verdad (no carga inicial en el viewport angosto) y verifica que el disparador del drawer
// aparece y que el `<aside>` conserva `data-shell-pattern="sidebar"` — el atributo cuya ausencia
// total fue el síntoma reportado — en vez de solo verificar la ausencia de `data-shell-drawer-open`.
test('redimensionar de verdad de escritorio a móvil activa el modo drawer: aparece el disparador y el aside conserva data-shell-pattern', async () => {
  establecerAncho(1440);
  renderizar();

  expect(screen.queryByRole('button', { name: /abrir menú de navegación/i })).not.toBeInTheDocument();

  establecerAncho(390);

  // El `resize` se dispara fuera del sistema de eventos de React (evento nativo del DOM vía
  // `window.dispatchEvent`), así que el `setState` que dispara queda fuera de `act()` — de ahí
  // `waitFor` en vez de una aserción síncrona, igual que la prueba de redimensionar en sentido
  // contrario más abajo.
  await waitFor(() => {
    expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toBeInTheDocument();
  });
  const aside = screen.getByRole('navigation').closest('aside') as HTMLElement;
  expect(aside).toHaveAttribute('data-shell-pattern', 'sidebar');
});

// Misma cobertura para el escalón intermedio (tablet, 768px): el disparador debe existir y el
// aside debe seguir siendo reconocible como el patrón "sidebar" tras el resize, sin depender de
// cuál fue el ancho de la carga inicial.
test('redimensionar de verdad de escritorio a tablet también activa el modo drawer', async () => {
  establecerAncho(1440);
  renderizar();

  establecerAncho(768);

  await waitFor(() => {
    expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toBeInTheDocument();
  });
  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-shell-pattern', 'sidebar');
});

test('a 768px (tablet) el disparador abre el drawer, pone el velo y marca data-shell-drawer-open', async () => {
  establecerAncho(768);
  const usuario = userEvent.setup();
  renderizar();

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-shell-drawer-open', 'true');
  expect(document.querySelector('.shell-menu-velo')).toBeInTheDocument();
  expect(document.body.style.overflow).toBe('hidden');
});

// Regresión ronda de arreglos 1 (hallazgo del coordinador, navegador real 390×844): con el
// drawer "abierto" en el árbol de accesibilidad (`data-shell-drawer-open="true"`, `aria-expanded`
// en el disparador), la pantalla se quedaba en negro — `shell-sidebar.css` seguía aplicando
// `transform: translateX(-100%)` de la regla "cerrado" por delante de la regla "abierto", pese a
// tener la misma especificidad y venir después en el archivo. jsdom no calcula cascada CSS real
// (no habría detectado esto — es exactamente lo que el coordinador señaló), así que esta prueba
// no verifica `getComputedStyle`: verifica el respaldo inline que `AppShell` aplica directo sobre
// el nodo (`style.transform`), que es el mecanismo real de la corrección y sí es observable en
// jsdom. Ver el comentario en `AppShell.tsx` junto al `useEffect` que lo aplica.
test('el respaldo inline deja el aside visualmente en pantalla cuando el drawer abre (no solo el atributo)', async () => {
  establecerAncho(768);
  const usuario = userEvent.setup();
  renderizar();

  const aside = screen.getByRole('navigation').closest('aside') as HTMLElement;
  expect(aside.style.transform).toBe('');

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  expect(aside).toHaveAttribute('data-shell-drawer-open', 'true');
  expect(aside.style.transform).toBe('translateX(0)');

  await usuario.click(document.querySelector('.shell-menu-velo') as HTMLElement);

  expect(aside).not.toHaveAttribute('data-shell-drawer-open');
  expect(aside.style.transform).toBe('');
});

test('el respaldo inline no se aplica en escritorio: el rail persistente no lleva transform propio', () => {
  establecerAncho(1440);
  renderizar();

  const aside = screen.getByRole('navigation').closest('aside') as HTMLElement;
  expect(aside.style.transform).toBe('');
});

test('el velo cierra el drawer', async () => {
  establecerAncho(768);
  const usuario = userEvent.setup();
  renderizar();

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));
  expect(document.querySelector('.shell-menu-velo')).toBeInTheDocument();

  await usuario.click(document.querySelector('.shell-menu-velo') as HTMLElement);

  expect(screen.getByRole('navigation').closest('aside')).not.toHaveAttribute('data-shell-drawer-open');
  expect(document.body.style.overflow).toBe('');
});

test('Escape cierra el drawer y devuelve el foco a su disparador', async () => {
  establecerAncho(768);
  const usuario = userEvent.setup();
  renderizar();

  const disparador = screen.getByRole('button', { name: /abrir menú de navegación/i });
  await usuario.click(disparador);
  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-shell-drawer-open', 'true');

  await usuario.keyboard('{Escape}');

  expect(screen.getByRole('navigation').closest('aside')).not.toHaveAttribute('data-shell-drawer-open');
  expect(disparador).toHaveFocus();
});

test('un cambio de ruta interno cierra el drawer', async () => {
  establecerAncho(768);
  const usuario = userEvent.setup();
  renderizar();

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));
  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-shell-drawer-open', 'true');

  await usuario.click(screen.getByRole('link', { name: /ir a otra vista/i }));

  await waitFor(() =>
    expect(screen.getByRole('navigation').closest('aside')).not.toHaveAttribute('data-shell-drawer-open'),
  );
});

test('a 1180px (desktop canónico) no hay disparador de drawer: el rail es persistente', () => {
  establecerAncho(1180);
  renderizar();

  expect(screen.queryByRole('button', { name: /abrir menú de navegación/i })).not.toBeInTheDocument();
  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-sidebar-state', 'expanded');
});

test('en escritorio el botón de colapso alterna data-sidebar-state sin abrir ningún drawer', async () => {
  establecerAncho(1440);
  const usuario = userEvent.setup();
  renderizar();

  const alternador = screen.getByRole('button', { name: /colapsar menú/i });
  await usuario.click(alternador);

  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-sidebar-state', 'collapsed');
  expect(screen.getByRole('button', { name: /expandir menú/i })).toBeInTheDocument();
});

test('redimensionar de móvil a escritorio cierra el drawer que quedó abierto', async () => {
  establecerAncho(390);
  const usuario = userEvent.setup();
  renderizar();

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));
  expect(screen.getByRole('navigation').closest('aside')).toHaveAttribute('data-shell-drawer-open', 'true');

  establecerAncho(1440);

  await waitFor(() =>
    expect(screen.queryByRole('button', { name: /abrir menú de navegación/i })).not.toBeInTheDocument(),
  );
  expect(screen.getByRole('navigation').closest('aside')).not.toHaveAttribute('data-shell-drawer-open');
});
