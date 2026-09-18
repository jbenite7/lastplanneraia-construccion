import { act, fireEvent, render, screen, within } from '@testing-library/react';
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
  // T7-4/Tarea 10: contrato DOM vinculante con el sidebar legado — cada entrada lleva
  // `data-destination-id` (era el único atributo que solo emitía la vista PHP; ahora también
  // lo emite BarraLateral, ver tests/browser/project-selector-sidebar.spec.mjs).
  expect(screen.getByRole('link', { name: 'Tus proyectos' })).toHaveAttribute('data-destination-id', 'projects');
  expect(screen.getByRole('link', { name: 'Control Tower - Informes' })).toHaveAttribute('data-destination-id', 'bi');
});

// T7-6 (ronda de arreglo 1, hallazgo Important del revisor): "Cerrar sesión" en el bloque de
// cuenta propio NUNCA puede ser un `<a href="/logout">` — ese GET destruye la sesión sin CSRF ni
// comprobación de método (`LoginController::show()`, `$router->get` en `public/index.php`).
// Esta prueba muerde: falla si alguien reintroduce ese enlace destructivo, y confirma que el
// botón dispara el mismo `cerrarSesion` (CSRF, POST) que usa `MenuCuenta`.
// Tarea 9b, S04 (pedido de Felipe: «en todas las vistas de la app quiero ver el logo»). En
// escritorio (sin `flotante`) la marca vive solo en la cabecera del `<aside>`, con el mismo
// enlace e ícono que la barra canónica PHP.
test('en escritorio, la marca de la cabecera enlaza a /proyectos con el ícono de la barra canónica', () => {
  fijarAncho(1440);
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  const enlaces = screen.getAllByRole('link', { name: 'Last Planner AIA' });
  expect(enlaces).toHaveLength(1);
  expect(enlaces[0]).toHaveAttribute('href', '/proyectos');
  expect(enlaces[0]).toHaveClass('aia-sidebar__brand', 'aia-brand-lockup');
  const icono = enlaces[0].querySelector('img');
  expect(icono).toHaveAttribute('src', '/public/img/brand/icon.svg');
});

// Ronda 2 (T9b, hallazgo del coordinador): el botón "Colapsar/Expandir menú" quedaba sin nada
// visible — solo traía `.aia-sidebar__toggle-label`, y el adaptador oculta esa etiqueta en
// expandido. Sin este ícono el botón medía 44×44 con fondo y borde transparentes. Muerde: falla
// si se quita el ícono o se rompe la paridad con `DesignSystemComponent::icon()`.
test('el botón de colapsar/expandir menú trae el ícono decorativo de la barra canónica', () => {
  fijarAncho(1440);
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  const boton = screen.getByRole('button', { name: 'Colapsar menú' });
  const icono = boton.querySelector('.aia-sidebar__toggle-icon svg.aia-icon__glyph');
  expect(icono).not.toBeNull();
  expect(icono).toHaveAttribute('viewBox', '0 0 24 24');
  expect(icono).toHaveAttribute('aria-hidden', 'true');
  expect(icono).toHaveAttribute('focusable', 'false');
});

// Bajo 1180px (modo autónomo, `flotante`), la marca se repite en la fila superior junto al
// disparador «Menú» — con el drawer cerrado, es la única marca visible en pantalla porque el
// `<aside>` está fuera de vista (`translateX(-100%)`).
test('bajo 1180px, la fila superior lleva la marca junto al disparador «Menú»', () => {
  fijarAncho(800);
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  const fila = document.querySelector('.shell-mobile-topbar');
  expect(fila).not.toBeNull();
  const disparador = screen.getByRole('button', { name: /abrir menú de navegación/i });
  expect(fila).toContainElement(disparador);

  const enlacesMarca = screen.getAllByRole('link', { name: 'Last Planner AIA' });
  // Dos coincidencias a propósito: la fila (visible) y la cabecera del `<aside>` (fuera de
  // vista por `transform`, pero presente en el DOM/árbol de accesibilidad de jsdom).
  expect(enlacesMarca).toHaveLength(2);
  const enlaceEnFila = enlacesMarca.find((enlace) => fila?.contains(enlace));
  expect(enlaceEnFila).toHaveAttribute('href', '/proyectos');
  expect(enlaceEnFila?.querySelector('img')).toHaveAttribute('src', '/public/img/brand/icon.svg');
});

// Ronda de arreglo 1 (hallazgo Important del revisor): bajo 1180px, con el drawer cerrado, el
// `<aside>` solo se saca de la vista con `transform` (`shell-sidebar.css`) — sin `inert` ni
// `aria-hidden` sigue en el orden de tabulación y en el árbol de accesibilidad. Con la marca de
// la Tarea 9b, quien navega con Tab encuentra un enlace "Last Planner AIA" visible en la fila
// móvil y otro idéntico invisible dentro del aside. Este test muerde: falla si el `<aside>`
// flotante y cerrado no lleva `inert`, y confirma que al abrir lo recupera.
test('bajo 1180px, con el drawer cerrado el <aside> queda inert; al abrir deja de estarlo', () => {
  fijarAncho(800);
  const { container } = render(
    <BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />,
  );

  const aside = container.querySelector('aside');
  expect(aside).toHaveAttribute('inert');

  // Ni la marca ni los enlaces de navegación del aside cerrado son enfocables: ambos viven
  // dentro de un ancestro `[inert]`.
  const enlacesMarca = screen.getAllByRole('link', { name: 'Last Planner AIA' });
  const enlaceEnAside = enlacesMarca.find((enlace) => aside?.contains(enlace));
  expect(enlaceEnAside?.closest('[inert]')).not.toBeNull();
  const enlaceNav = screen.getByRole('link', { name: 'Tus proyectos' });
  expect(enlaceNav.closest('[inert]')).not.toBeNull();

  fireEvent.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  expect(aside).not.toHaveAttribute('inert');
  expect(enlaceNav.closest('[inert]')).toBeNull();
});

// Ronda de arreglo 1 (hallazgo del asesor durante la implementación): `flotante` en modo NO
// autónomo (`NavegacionLateral`/`AppShell`) se congelaba en el valor que tenía al montar — el
// `resize` que lo recalcula empezaba con `if (!barraAutonoma) return`. Si `inert` dependiera de
// ese `flotante` congelado, montar a 800px (`flotante=true`) y luego ensanchar a 1440px con el
// drawer ya cerrado por `AppShell` dejaría el rail de escritorio marcado `inert` para siempre —
// una regresión peor que el defecto que este arreglo corrige. Este test cubre justo ese cruce en
// modo no autónomo.
test('modo no autónomo: al cruzar a escritorio, el <aside> deja de ser inert aunque el drawer ya estaba cerrado', () => {
  fijarAncho(800);
  const { container } = render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      barraAutonoma={false}
      abiertoEnMovil={false}
    />,
  );

  const aside = container.querySelector('aside');
  expect(aside).toHaveAttribute('inert');

  act(() => {
    fijarAncho(1440);
  });

  expect(aside).not.toHaveAttribute('inert');
});

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

// Correcciones de revisión final S04 (T10): el drawer autónomo (`barraAutonoma && flotante`)
// solo tenía foco de entrada y Escape — nunca atrapaba Tab/Shift+Tab, a diferencia de
// `AppShell.tsx` (T01), que sí lo hace para el suyo. La spec T01 §508 exige "Escape, trampa y
// retorno de foco" para todo drawer flotante. Primero y último enfocable del `<aside>`, en orden
// real de DOM: el enlace de la marca (`MarcaLockup`, cabecera) y "Cerrar sesión" (pie, con
// `cuentaPropia`) — ninguno es un link de navegación, así que atrapar solo dentro de `navRef`
// (como hace el foco de entrada, a propósito) dejaría el pie inalcanzable por teclado.
test('drawer móvil: Tab en el último control del <aside> vuelve al primero (foco atrapado)', async () => {
  fijarAncho(390);
  const usuario = userEvent.setup();
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia
      cerrarSesion={vi.fn().mockResolvedValue(undefined)}
    />,
  );

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  const aside = document.querySelector<HTMLElement>('aside.aia-navigation--sidebar')!;
  const primero = within(aside).getByRole('link', { name: 'Last Planner AIA' });
  const ultimo = within(aside).getByRole('button', { name: /cerrar sesión/i });

  ultimo.focus();
  fireEvent.keyDown(document, { key: 'Tab' });

  expect(primero).toHaveFocus();
});

test('drawer móvil: Shift+Tab en el primer control del <aside> vuelve al último (foco atrapado)', async () => {
  fijarAncho(390);
  const usuario = userEvent.setup();
  render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia
      cerrarSesion={vi.fn().mockResolvedValue(undefined)}
    />,
  );

  await usuario.click(screen.getByRole('button', { name: /abrir menú de navegación/i }));

  const aside = document.querySelector<HTMLElement>('aside.aia-navigation--sidebar')!;
  const primero = within(aside).getByRole('link', { name: 'Last Planner AIA' });
  const ultimo = within(aside).getByRole('button', { name: /cerrar sesión/i });

  primero.focus();
  fireEvent.keyDown(document, { key: 'Tab', shiftKey: true });

  expect(ultimo).toHaveFocus();
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
