import { act, fireEvent, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { BarraLateral } from './BarraLateral';

const GRUPOS = [
  {
    id: 'global',
    label: 'Navegación',
    items: [
      { id: 'projects', label: 'Tus proyectos', href: '/proyectos', icon: 'project' },
      { id: 'bi', label: 'Control Tower - Informes', href: '/bi/control-tower', icon: 'chart' },
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

test('el rail colapsado conserva un ícono decorativo por cada entrada de navegación', async () => {
  fijarAncho(1440);
  render(<BarraLateral activeId="projects" accountName="Ana" groups={GRUPOS} showChangeProject={false} />);

  fireEvent.click(screen.getByRole('button', { name: 'Colapsar menú' }));

  for (const item of GRUPOS[0].items) {
    const enlace = screen.getByRole('link', { name: item.label });
    expect(enlace).toHaveAttribute('data-sidebar-icon', item.icon);
    expect(enlace.querySelector(`.aia-icon--${item.icon} svg.aia-icon__glyph`)).not.toBeNull();
  }
});

// R1.2-4: el manifiesto de `ShellNavigationService` usa estos nombres; cada uno debe conservar
// el glifo que sirve `DesignSystemComponent::icon()` al shell PHP. Antes de esta prueba, el
// React solo conocía cinco nombres y convertía los demás en el círculo de respaldo.
const GRUPOS_CON_MANIFIESTO_COMPLETO = [
  {
    id: 'informacion',
    label: 'Información',
    items: [
      { id: 'control-tower', label: 'Control Tower - Informes', href: '/bi/control-tower', icon: 'chart' },
      { id: 'semanas-proyecto', label: 'Semanas del Proyecto', href: null, icon: 'calendar', action: true },
      { id: 'profesionales', label: 'Profesionales', href: '/profesionales', icon: 'user' },
      { id: 'subcontratistas', label: 'Subcontratistas', href: '/subcontratistas', icon: 'contract' },
      { id: 'indicadores', label: 'Indicadores LPS', href: '/indicadores', icon: 'overview' },
      { id: 'control-cambios', label: 'Control de Cambios', href: '/control-cambios', icon: 'integration' },
    ],
  },
  {
    id: 'obra',
    label: 'Obra',
    items: [
      { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program' },
      { id: 'programacion-intermedia', label: 'Programación Intermedia', href: '/programacion-intermedia', icon: 'tasks' },
      { id: 'programacion-semanal', label: 'Programación Semanal', href: '/programacion-semanal', icon: 'calendar' },
      { id: 'actualizar-cronograma', label: 'Actualizar Cronograma', href: '/programa-general-actualizar', icon: 'sync' },
    ],
  },
  {
    id: 'compras',
    label: 'Compras',
    items: [
      { id: 'plan-compras', label: 'Plan de Compras', href: '/plan-compras', icon: 'clipboard' },
    ],
  },
] as const;

const GLIFOS_LEGADO: Record<string, readonly string[]> = {
  chart: ['path:d=M5 20V10M12 20V4M19 20v-7', 'path:d=M3 20h18'],
  calendar: ['rect:height=15,rx=2,width=16,x=4,y=5', 'path:d=M8 3v4M16 3v4M4 10h16'],
  user: ['circle:cx=12,cy=8,r=3', 'path:d=M5 20a7 7 0 0 1 14 0'],
  contract: ['path:d=M6 3h9l3 3v15H6z', 'path:d=M15 3v4h3M9 12h6M9 16h6'],
  overview: ['rect:height=6,rx=1,width=6,x=4,y=4', 'rect:height=6,rx=1,width=6,x=14,y=4', 'rect:height=6,rx=1,width=6,x=4,y=14', 'rect:height=6,rx=1,width=6,x=14,y=14'],
  integration: ['circle:cx=7,cy=12,r=3', 'circle:cx=17,cy=7,r=3', 'circle:cx=17,cy=17,r=3', 'path:d=m9.5 10.5 5-2M9.5 13.5l5 2'],
  program: ['path:d=M5 5h14v14H5z', 'path:d=M8 9h8M8 13h5M8 17h3'],
  tasks: ['path:d=M5 6h14M5 12h14M5 18h14', 'path:d=m7 6 .01 0M7 12 .01 0M7 18 .01 0'],
  sync: ['path:d=M20 12a8 8 0 1 1-2.34-5.66', 'path:d=M20 3v4h-4'],
  clipboard: ['path:d=M8 5h8a2 2 0 0 1 2 2v13H6V7a2 2 0 0 1 2-2Z', 'path:d=M9 5a3 3 0 0 1 6 0M9 11h6M9 15h4'],
};

function firmaGlifo(svg: SVGSVGElement): string[] {
  return Array.from(svg.children).map((elemento) => {
    const atributos = Array.from(elemento.attributes)
      .sort((a, b) => a.name.localeCompare(b.name))
      .map((atributo) => `${atributo.name}=${atributo.value}`)
      .join(',');
    return `${elemento.tagName.toLowerCase()}:${atributos}`;
  });
}

test('cada nombre emitido por ShellNavigationService conserva el glifo exacto del sidebar legado', () => {
  fijarAncho(1440);
  render(
    <BarraLateral
      activeId="programa-general"
      accountName="Ana"
      groups={GRUPOS_CON_MANIFIESTO_COMPLETO}
      showChangeProject={false}
    />,
  );

  for (const grupo of GRUPOS_CON_MANIFIESTO_COMPLETO) {
    for (const item of grupo.items) {
      const destino = item.href
        ? screen.getByRole('link', { name: item.label })
        : screen.getByRole('button', { name: item.label });
      const svg = destino.querySelector(`.aia-icon--${item.icon} svg.aia-icon__glyph`);
      expect(svg, `${item.id}: falta el SVG para ${item.icon}`).not.toBeNull();
      expect(firmaGlifo(svg as SVGSVGElement), `${item.id}: debe igualar DesignSystemComponent::icon(${item.icon})`)
        .toEqual(GLIFOS_LEGADO[item.icon]);
      expect(svg?.querySelector('circle[cx="12"][cy="12"][r="7"]'), `${item.id}: no puede usar el respaldo genérico`)
        .toBeNull();
    }
  }
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

// Revisión visual de Programa General (Felipe, 2026-09-29): las etiquetas flotantes del riel
// colapsado no se veían porque el nav recorta lo que sale por la derecha (overflow-y: auto, que
// existe para los menús largos). `data-menu-cabe` le dice al CSS cuándo puede soltar ese recorte:
// solo si el menú entra sin scroll. jsdom no calcula layout, así que las medidas se simulan.
//
// Se mide el borde inferior del ÚLTIMO GRUPO en flujo contra el alto útil del nav, NO el
// `scrollHeight`: los flyouts de semana están ocultos (`visibility: hidden`) pero conservan layout,
// y los del final del menú sobresalen por abajo del nav, inflando el `scrollHeight` (medido el
// 2026-09-29: el riel desplegado marcaba «no cabe» con 11 ítems). Eso volvía a poner el
// `overflow-y: auto` que recorta esos mismos flyouts.
function simularAlturasNav(bordeInferiorUltimoGrupo: number, altoUtilNav: number, scrollHeightInflado = 0) {
  const rect = (top: number, bottom: number) => ({ top, bottom, left: 0, right: 0, width: 0, height: bottom - top, x: 0, y: top, toJSON: () => ({}) }) as DOMRect;
  vi.spyOn(Element.prototype, 'getBoundingClientRect').mockImplementation(function (this: Element) {
    if (this.matches('nav.aia-sidebar__nav')) return rect(0, altoUtilNav);
    if (this.matches('section.aia-sidebar__group:last-child')) return rect(0, bordeInferiorUltimoGrupo);
    return rect(0, 0);
  });
  vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(altoUtilNav);
  vi.spyOn(HTMLElement.prototype, 'scrollHeight', 'get').mockReturnValue(scrollHeightInflado || bordeInferiorUltimoGrupo);
}

function montarBarra() {
  return render(
    <BarraLateral
      activeId="projects"
      accountName="Ana"
      groups={GRUPOS}
      showChangeProject={false}
      cuentaPropia={true}
      cerrarSesion={vi.fn().mockResolvedValue(undefined)}
    />,
  );
}

test('data-menu-cabe es "true" cuando el contenido del nav entra sin scroll', () => {
  simularAlturasNav(400, 500);
  const { container } = montarBarra();
  expect(container.querySelector('aside')).toHaveAttribute('data-menu-cabe', 'true');
});

test('data-menu-cabe sigue "true" aunque los flyouts ocultos inflen el scrollHeight del nav', () => {
  simularAlturasNav(400, 500, 900);
  const { container } = montarBarra();
  expect(container.querySelector('aside')).toHaveAttribute('data-menu-cabe', 'true');
});

test('data-menu-cabe es "false" cuando el menú es más alto que el nav (queda el scroll propio)', () => {
  simularAlturasNav(900, 500);
  const { container } = montarBarra();
  expect(container.querySelector('aside')).toHaveAttribute('data-menu-cabe', 'false');
});

test('data-menu-cabe se recalcula al cambiar el tamaño de la ventana', () => {
  simularAlturasNav(400, 500);
  const { container } = montarBarra();
  const aside = container.querySelector('aside');
  expect(aside).toHaveAttribute('data-menu-cabe', 'true');

  simularAlturasNav(900, 500);
  act(() => {
    window.dispatchEvent(new Event('resize'));
  });
  expect(aside).toHaveAttribute('data-menu-cabe', 'false');
});

// --- Flyouts de semana (paridad con views/partials/shell_sidebar.php) ---------------------------
// Programa General / Intermedia / Semanal despliegan al pasar el cursor la lista de semanas, y
// «Semanas del Proyecto» despliega la misma lista más «+ Nueva semana» y la papelera de la última.

const GRUPOS_SEMANAS = [
  {
    id: 'informacion',
    label: 'Información',
    items: [{ id: 'semanas-proyecto', label: 'Semanas del Proyecto', href: null, icon: 'calendar', action: true }],
  },
  {
    id: 'obra',
    label: 'Obra',
    items: [
      { id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program' },
      { id: 'programacion-semanal', label: 'Programación Semanal', href: '/programacion-semanal', icon: 'calendar' },
      { id: 'actualizar-cronograma', label: 'Actualizar Cronograma', href: '/programa-general-actualizar', icon: 'sync' },
    ],
  },
];

const SEMANA = {
  current: 2,
  options: [
    { number: 1, startsOn: '2026-08-18', endsOn: '2026-08-24' },
    { number: 2, startsOn: '2026-08-25', endsOn: '2026-08-31' },
  ],
  actions: { select: true, create: true, deleteLast: true },
};

function pintarConSemanas(sobreescribir: Partial<Parameters<typeof BarraLateral>[0]['menuSemanas'] & object> = {}) {
  const menu = { semana: SEMANA, alElegir: vi.fn(), alCrear: vi.fn(), alEliminar: vi.fn(), ...sobreescribir };
  render(
    <BarraLateral
      activeId="programa-general"
      accountName="Ana"
      groups={GRUPOS_SEMANAS}
      showChangeProject={false}
      menuSemanas={menu}
      alEjecutarAccion={vi.fn()}
    />,
  );
  return menu;
}

test('sin menuSemanas no se pinta ningún flyout', () => {
  render(<BarraLateral activeId="programa-general" accountName="Ana" groups={GRUPOS_SEMANAS} showChangeProject={false} alEjecutarAccion={vi.fn()} />);

  expect(screen.queryByRole('menu')).not.toBeInTheDocument();
});

test('cada módulo con semanas lleva su flyout con título, semanas y fechas; la vigente solo en el módulo activo', () => {
  pintarConSemanas();

  const flyoutPG = screen.getByRole('menu', { name: 'Semanas de Programa General' });
  expect(within(flyoutPG).getByText('Programa General')).toHaveClass('shell-week-flyout__head');
  expect(within(flyoutPG).getByRole('menuitem', { name: /Semana 2/ })).toHaveAttribute('aria-current', 'true');
  expect(within(flyoutPG).getByRole('menuitem', { name: /Semana 1/ })).not.toHaveAttribute('aria-current');
  expect(within(flyoutPG).getByText('Del 2026-08-18 al 2026-08-24')).toBeInTheDocument();

  const flyoutPS = screen.getByRole('menu', { name: 'Semanas de Programación Semanal' });
  expect(within(flyoutPS).getByRole('menuitem', { name: /Semana 2/ })).not.toHaveAttribute('aria-current');
  // «Actualizar Cronograma» no es un módulo con semanas.
  expect(screen.queryByRole('menu', { name: /Actualizar Cronograma/ })).not.toBeInTheDocument();
});

test('elegir una semana en el flyout de un módulo la cambia y lleva a ese módulo', async () => {
  const menu = pintarConSemanas();

  const flyoutPS = screen.getByRole('menu', { name: 'Semanas de Programación Semanal' });
  await userEvent.click(within(flyoutPS).getByRole('menuitem', { name: /Semana 1/ }));

  expect(menu.alElegir).toHaveBeenCalledWith(1, '/programacion-semanal');
});

test('«Semanas del Proyecto» ofrece + Nueva semana, la lista y la papelera solo en la última semana', async () => {
  const menu = pintarConSemanas();

  const flyout = screen.getByRole('menu', { name: 'Semanas del Proyecto' });
  await userEvent.click(within(flyout).getByRole('menuitem', { name: '+ Nueva semana' }));
  expect(menu.alCrear).toHaveBeenCalledTimes(1);

  await userEvent.click(within(flyout).getByRole('menuitem', { name: /Semana 1/ }));
  expect(menu.alElegir).toHaveBeenCalledWith(1, null);

  expect(within(flyout).getAllByRole('button', { name: /Eliminar Semana/ })).toHaveLength(1);
  await userEvent.click(within(flyout).getByRole('button', { name: 'Eliminar Semana 2' }));
  expect(menu.alEliminar).toHaveBeenCalledWith(2);
});

test('sin permisos del servidor no hay + Nueva semana ni papelera, y elegir semana queda deshabilitado', () => {
  pintarConSemanas({ semana: { ...SEMANA, actions: { select: false, create: false, deleteLast: false } } });

  const flyout = screen.getByRole('menu', { name: 'Semanas del Proyecto' });
  expect(within(flyout).queryByRole('menuitem', { name: '+ Nueva semana' })).not.toBeInTheDocument();
  expect(within(flyout).queryByRole('button', { name: /Eliminar Semana/ })).not.toBeInTheDocument();
  expect(within(flyout).getByRole('menuitem', { name: /Semana 1/ })).toBeDisabled();
});

test('el flyout se abre al pasar el cursor y se cierra 350 ms después de salir (periodo de gracia)', () => {
  vi.useFakeTimers();
  try {
    pintarConSemanas();
    const li = screen.getByRole('link', { name: 'Programa General' }).closest('li') as HTMLElement;
    expect(li).toHaveClass('shell-has-week-menu');
    expect(li).not.toHaveClass('shell-week-open');

    fireEvent.mouseEnter(li);
    expect(li).toHaveClass('shell-week-open');

    fireEvent.mouseLeave(li);
    act(() => vi.advanceTimersByTime(300));
    expect(li).toHaveClass('shell-week-open');
    act(() => vi.advanceTimersByTime(60));
    expect(li).not.toHaveClass('shell-week-open');

    // Volver a entrar dentro de la gracia cancela el cierre.
    fireEvent.mouseEnter(li);
    fireEvent.mouseLeave(li);
    act(() => vi.advanceTimersByTime(200));
    fireEvent.mouseEnter(li);
    act(() => vi.advanceTimersByTime(500));
    expect(li).toHaveClass('shell-week-open');
  } finally {
    vi.useRealTimers();
  }
});

test('el botón «Semanas del Proyecto» abre el flyout (teclado/táctil) en vez de disparar la acción, y Escape lo cierra', async () => {
  const alEjecutarAccion = vi.fn();
  render(
    <BarraLateral
      activeId="programa-general"
      accountName="Ana"
      groups={GRUPOS_SEMANAS}
      showChangeProject={false}
      menuSemanas={{ semana: SEMANA, alElegir: vi.fn(), alCrear: vi.fn(), alEliminar: vi.fn() }}
      alEjecutarAccion={alEjecutarAccion}
    />,
  );
  const boton = screen.getByRole('button', { name: 'Semanas del Proyecto' });
  const li = boton.closest('li') as HTMLElement;

  await userEvent.click(boton);
  expect(li).toHaveClass('shell-week-open');
  expect(boton).toHaveAttribute('aria-expanded', 'true');
  // Un segundo clic NO lo cierra: el cursor ya lo había abierto al pasar por encima.
  await userEvent.click(boton);
  expect(li).toHaveClass('shell-week-open');
  await userEvent.keyboard('{Escape}');
  expect(li).not.toHaveClass('shell-week-open');
  expect(boton).toHaveAttribute('aria-expanded', 'false');
  expect(alEjecutarAccion).not.toHaveBeenCalled();
});
