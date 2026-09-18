import { act, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { BarraLateral } from './BarraLateral';

const GRUPOS = [
  {
    id: 'global',
    label: 'Navegación',
    items: [
      { id: 'projects', label: 'Tus proyectos', href: '/proyectos' },
      { id: 'bi', label: 'Control Tower - Informes', href: '/bi/control-tower' },
    ],
  },
];

function fijarAncho(ancho: number) {
  window.innerWidth = ancho;
  window.dispatchEvent(new Event('resize'));
}

afterEach(() => {
  fijarAncho(1024);
  vi.restoreAllMocks();
});

test('marca un único aria-current en la entrada activa, sin "Cambiar proyecto" si no aplica', () => {
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia={true}
      cerrarSesion={vi.fn().mockResolvedValue(undefined)}
    />,
  );

  expect(screen.getByRole('link', { name: 'Tus proyectos' })).toHaveAttribute('aria-current', 'page');
  expect(screen.getAllByRole('link', { current: 'page' })).toHaveLength(1);
  expect(screen.queryByRole('link', { name: 'Cambiar proyecto' })).not.toBeInTheDocument();
  expect(screen.getByRole('button', { name: /tema/i })).toBeVisible();
});

// T7-6 (ronda de arreglo 1, hallazgo Important del revisor): "Cerrar sesión" en el bloque de
// cuenta propio NUNCA puede ser un `<a href="/logout">` — ese GET destruye la sesión sin CSRF ni
// comprobación de método (`LoginController::show()`, `$router->get` en `public/index.php`).
// Esta prueba muerde: falla si alguien reintroduce ese enlace destructivo, y confirma que el
// botón dispara el mismo `cerrarSesion` (CSRF, POST) que usa `MenuCuenta`.
test('cerrar sesión dispara cerrarSesion (POST con CSRF), nunca un enlace GET a /logout', async () => {
  const cerrarSesion = vi.fn().mockResolvedValue(undefined);
  const usuario = userEvent.setup();
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia={true}
      cerrarSesion={cerrarSesion}
    />,
  );

  expect(screen.queryByRole('link', { name: /cerrar sesión/i })).not.toBeInTheDocument();
  expect(document.querySelector('a[href="/logout"]')).not.toBeInTheDocument();

  const boton = screen.getByRole('button', { name: /cerrar sesión/i });
  expect(boton).not.toHaveAttribute('href');

  await usuario.click(boton);

  expect(cerrarSesion).toHaveBeenCalledOnce();
});

test('muestra "Cambiar proyecto" cuando showChangeProject es true', () => {
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={true}
      cuentaPropia={true}
      cerrarSesion={vi.fn().mockResolvedValue(undefined)}
    />,
  );

  expect(screen.getByRole('link', { name: 'Cambiar proyecto' })).toHaveAttribute('href', '/proyectos');
});

test('sin children ni cuentaPropia, el pie solo trae el conmutador de tema', () => {
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  expect(screen.queryByRole('link', { name: 'Cerrar sesión' })).not.toBeInTheDocument();
  expect(screen.queryByText('Ana')).not.toBeInTheDocument();
});

test('con children, delega en ellos y no arma su propio bloque de cuenta', () => {
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia={true}
    >
      <button type="button">Utilidad ajena</button>
    </BarraLateral>,
  );

  expect(screen.getByRole('button', { name: 'Utilidad ajena' })).toBeInTheDocument();
  expect(screen.queryByRole('link', { name: 'Cerrar sesión' })).not.toBeInTheDocument();
});

test('una entrada sin href (action) se pinta como botón deshabilitado', () => {
  render(
    <BarraLateral
      activeId=""
      accountName="Ana"
      showChangeProject={false}
      groups={[
        {
          id: 'informacion',
          label: 'Información',
          items: [{ id: 'semanas', label: 'Semanas del Proyecto', href: null, action: true }],
        },
      ]}
    />,
  );

  expect(screen.getByRole('button', { name: /semanas del proyecto/i })).toBeDisabled();
});

test('drawer móvil: el disparador anuncia estado, abre, Escape cierra y devuelve el foco', async () => {
  fijarAncho(800);
  const usuario = userEvent.setup();
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  const disparador = screen.getByRole('button', { name: /abrir menú de navegación/i });
  expect(disparador).toHaveAttribute('aria-expanded', 'false');

  await usuario.click(disparador);

  expect(screen.getByRole('button', { name: /cerrar menú de navegación/i })).toHaveAttribute('aria-expanded', 'true');
  // Al abrir, el foco entra al primer enlace del rail.
  expect(screen.getByRole('link', { name: 'Tus proyectos' })).toHaveFocus();

  fireEvent.keyDown(document, { key: 'Escape' });

  const disparadorCerrado = screen.getByRole('button', { name: /abrir menú de navegación/i });
  expect(disparadorCerrado).toHaveAttribute('aria-expanded', 'false');
  expect(disparadorCerrado).toHaveFocus();
});

// Tarea 8, S04: pendiente dejado a propósito por la Tarea 7 (ver BarraLateral.tsx). En modo
// autónomo (la pantalla standalone `/proyectos`, sin `AppShell` alrededor) es este componente
// quien es dueño del `<body>`, así que reutiliza la misma clase que ya consume `shell-sidebar.css`
// (rail fijo + padding + drawer bajo 1180px) en vez de escribir una hoja nueva — mismo patrón que
// `AppShell.tsx` aplica para sí mismo.
test('modo autónomo: añade aia-shell--sidebar al body al montar y lo retira al desmontar', () => {
  const { unmount } = render(
    <BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />,
  );

  expect(document.body.classList.contains('aia-shell--sidebar')).toBe(true);

  unmount();

  expect(document.body.classList.contains('aia-shell--sidebar')).toBe(false);
});

test('modo no autónomo: no toca la clase aia-shell--sidebar del body (la gobierna AppShell)', () => {
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      barraAutonoma={false}
    />,
  );

  expect(document.body.classList.contains('aia-shell--sidebar')).toBe(false);
});

// Bloquea SOLO el fondo (clase CSS, `body.aia-shell-drawer-open { overflow: hidden }` en
// `project-selector-react.css`), nunca `document.body.style` — a diferencia del bloqueo que
// `AppShell.tsx` aplica para su propio drawer (T01), que sí usa `style.overflow` y queda fuera del
// alcance de esta tarea.
test('drawer móvil autónomo: bloquea el fondo con una clase del body, nunca con document.body.style', async () => {
  fijarAncho(800);
  const usuario = userEvent.setup();
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  expect(document.body.classList.contains('aia-shell-drawer-open')).toBe(false);

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  expect(document.body.classList.contains('aia-shell-drawer-open')).toBe(true);
  expect(document.body.style.overflow).toBe('');

  await usuario.click(screen.getByRole('button', { name: /cerrar menú de navegación/i }));

  expect(document.body.classList.contains('aia-shell-drawer-open')).toBe(false);
  expect(document.body.style.overflow).toBe('');
});

// Ronda de arreglo 1 (hallazgo Important del revisor): `AppShell.tsx:127-129` suelta el drawer al
// dejar de ser flotante (`if (!ahoraFlotante) setAbierto(false)`); el `sincronizar()` de
// `BarraLateral` no lo hacía. Sin este cierre, alguien que abre el drawer bajo 1180px y luego
// ensancha la ventana (acopla el portátil, gira la tablet) se queda con `abiertoPropio` en `true`
// para siempre: en escritorio ya no hay botón «Menú» para cerrarlo, `Escape` está gateado por
// `flotante` y el velo desaparece — y como el efecto de bloqueo de fondo (Tarea 8) solo mira
// `[barraAutonoma, abierto]`, `body.aia-shell-drawer-open`/`overflow: hidden` queda pegado.
test('al cruzar a escritorio con el drawer abierto, lo cierra y libera el fondo', () => {
  fijarAncho(800);
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  fireEvent.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));
  expect(screen.getByRole('button', { name: /cerrar menú de navegación/i })).toHaveAttribute('aria-expanded', 'true');
  expect(document.body.classList.contains('aia-shell-drawer-open')).toBe(true);

  act(() => {
    fijarAncho(1200);
  });

  expect(screen.queryByRole('button', { name: /menú de navegación/i })).not.toBeInTheDocument();
  expect(document.body.classList.contains('aia-shell-drawer-open')).toBe(false);
  expect(document.body.style.overflow).toBe('');
});

test('drawer móvil: un clic en el velo cierra el drawer', async () => {
  fijarAncho(800);
  const usuario = userEvent.setup();
  const { container } = render(
    <BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />,
  );

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));
  const velo = container.querySelector('.shell-menu-velo');
  expect(velo).toBeTruthy();

  fireEvent.click(velo as Element);

  expect(screen.getByRole('button', { name: /abrir menú de navegación/i })).toHaveAttribute('aria-expanded', 'false');
});

test('en modo no autónomo (T01/AppShell), no arma su propio disparador ni velo', () => {
  fijarAncho(800);
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      barraAutonoma={false}
      abiertoEnMovil={false}
    />,
  );

  expect(screen.queryByRole('button', { name: /abrir menú de navegación/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('button', { name: /cerrar menú de navegación/i })).not.toBeInTheDocument();
});
