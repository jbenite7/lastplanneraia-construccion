import { fireEvent, render, screen } from '@testing-library/react';
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
