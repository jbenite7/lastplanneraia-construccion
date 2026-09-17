import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { listarProyectos } from '../../lib/api/proyectos';
import type { ListaProyectos, ProyectoDisponible } from '../../lib/api/esquemas/proyectos';
import { SelectorProyectos } from './SelectorProyectos';

vi.mock('../../lib/api/proyectos', () => ({
  listarProyectos: vi.fn(),
  seleccionarProyecto: vi.fn(),
}));

function proyecto(overrides: Partial<ProyectoDisponible> = {}): ProyectoDisponible {
  return {
    id: 1,
    name: 'Da Porto',
    area: 'Construccion',
    active: true,
    role: 'A',
    roleLabel: 'Administrador',
    ...overrides,
  };
}

function lista(projects: ProyectoDisponible[], biVisible = false): ListaProyectos {
  return {
    projects,
    navigation: { bi: biVisible ? { visible: true, href: '/bi/control-tower' } : { visible: false, href: null } },
  };
}

function props(overrides: Partial<{ session: { csrfToken: string; project: { id: number; name: string; area: string } | null } }> = {}) {
  return {
    session: { csrfToken: '0'.repeat(64), project: null, ...overrides.session },
    onOpen: vi.fn(),
    onRevalidate: vi.fn().mockResolvedValue(undefined),
  };
}

afterEach(() => {
  vi.clearAllMocks();
});

test('lista, busca, cuenta y limpia el filtro', async () => {
  const tresProyectos = [
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
    proyecto({ id: 3, name: 'Aeropuerto' }),
  ];
  vi.mocked(listarProyectos).mockResolvedValue(lista(tresProyectos));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...props()} />);

  expect(await screen.findByText('3 proyectos disponibles')).toBeVisible();

  await usuario.type(screen.getByRole('searchbox', { name: 'Buscar proyecto' }), 'agora');
  expect(await screen.findByText('1 proyecto encontrado')).toBeVisible();
  expect(screen.queryByRole('heading', { name: 'Da Porto' })).not.toBeInTheDocument();

  await usuario.click(screen.getByRole('button', { name: 'Limpiar búsqueda' }));
  expect(await screen.findByText('3 proyectos disponibles')).toBeVisible();
  expect(screen.getByRole('heading', { name: 'Da Porto' })).toBeVisible();
});

test('muestra un estado de carga estable mientras llega la respuesta', async () => {
  let resolver!: (value: ListaProyectos) => void;
  vi.mocked(listarProyectos).mockReturnValue(new Promise((resolve) => { resolver = resolve; }));

  render(<SelectorProyectos {...props()} />);

  expect(screen.getByRole('status', { name: '' })).toHaveTextContent('Cargando proyectos…');

  resolver(lista([proyecto()]));
  await screen.findByRole('heading', { name: 'Da Porto' });
});

test('sin proyectos asignados muestra el vacío sin enlace de administración, con el texto del legado', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([]));

  render(<SelectorProyectos {...props()} />);

  expect(await screen.findByText('No tienes proyectos asignados')).toBeVisible();
  expect(screen.getByText('Contacta al administrador para solicitar acceso.')).toBeVisible();
  expect(screen.queryByRole('link')).not.toBeInTheDocument();
});

test('el buscador lleva el placeholder del legado', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto()]));

  render(<SelectorProyectos {...props()} />);

  expect(await screen.findByRole('searchbox', { name: 'Buscar proyecto' }))
    .toHaveAttribute('placeholder', 'Buscar proyecto...');
});

test('el id de aria-controls existe también cuando la búsqueda no encuentra nada', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto()]));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...props()} />);

  const buscador = await screen.findByRole('searchbox', { name: 'Buscar proyecto' });
  expect(buscador).toHaveAttribute('aria-controls', 'project-list');

  await usuario.type(buscador, 'zzz-no-existe');
  await screen.findByText('No encontramos proyectos');

  // El nodo que `aria-controls` referencia debe existir en las dos ramas (con y sin resultados).
  expect(document.getElementById('project-list')).not.toBeNull();
  expect(document.getElementById('project-list')).toHaveTextContent('No encontramos proyectos');
});

test('una búsqueda de solo espacios no ofrece "Limpiar búsqueda" ni cuenta como filtro activo', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...props()} />);

  const buscador = await screen.findByRole('searchbox', { name: 'Buscar proyecto' });
  expect(await screen.findByText('2 proyectos disponibles')).toBeVisible();

  await usuario.type(buscador, '   ');

  expect(screen.getByText('2 proyectos disponibles')).toBeVisible();
  expect(screen.queryByRole('button', { name: 'Limpiar búsqueda' })).not.toBeInTheDocument();
});

test('sin coincidencias de búsqueda ofrece limpiar el filtro', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto()]));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...props()} />);

  await usuario.type(await screen.findByRole('searchbox', { name: 'Buscar proyecto' }), 'zzz-no-existe');
  expect(await screen.findByText('No encontramos proyectos')).toBeVisible();

  const botonesLimpiar = screen.getAllByRole('button', { name: 'Limpiar búsqueda' });
  expect(botonesLimpiar).toHaveLength(1);

  await usuario.click(botonesLimpiar[0]);
  expect(await screen.findByRole('heading', { name: 'Da Porto' })).toBeVisible();
});

test('aborta la carga al desmontar', async () => {
  let señalCapturada: AbortSignal | undefined;
  vi.mocked(listarProyectos).mockImplementation((signal?: AbortSignal) => {
    señalCapturada = signal;
    return new Promise(() => {});
  });

  const { unmount } = render(<SelectorProyectos {...props()} />);
  unmount();

  expect(señalCapturada?.aborted).toBe(true);
});

test('un error de carga muestra aviso y el reintento hace exactamente una llamada nueva', async () => {
  vi.mocked(listarProyectos)
    .mockRejectedValueOnce(new ApiError('falló', { tipo: 'http', status: 500 }))
    .mockResolvedValueOnce(lista([proyecto()]));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...props()} />);

  expect(await screen.findByRole('alert')).toHaveTextContent('No pudimos cargar tus proyectos. Intenta de nuevo.');
  expect(listarProyectos).toHaveBeenCalledTimes(1);

  await usuario.click(screen.getByRole('button', { name: 'Reintentar' }));

  await waitFor(() => expect(listarProyectos).toHaveBeenCalledTimes(2));
  await screen.findByRole('heading', { name: 'Da Porto' });
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
});

test('un abort del gateway no se muestra como error de carga', async () => {
  let rechazar!: (razon: unknown) => void;
  vi.mocked(listarProyectos).mockReturnValue(new Promise((_resolve, reject) => { rechazar = reject; }));

  render(<SelectorProyectos {...props()} />);

  rechazar(new ApiError('/api/proyectos se canceló', { tipo: 'abortado', codigo: 'ABORTED' }));
  await waitFor(() => expect(listarProyectos).toHaveBeenCalledTimes(1));

  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  expect(screen.getByRole('status')).toHaveTextContent('Cargando proyectos…');
});

test('Control Tower solo aparece cuando el manifiesto lo marca visible', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto()], true));

  render(<SelectorProyectos {...props()} />);

  const enlace = await screen.findByRole('link', { name: 'Control Tower' });
  expect(enlace).toHaveAttribute('href', '/bi/control-tower');
});

test('marca "Proyecto actual" solo en la tarjeta del proyecto de la sesión', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));

  render(<SelectorProyectos {...props({ session: { csrfToken: '0'.repeat(64), project: { id: 2, name: 'Ágora', area: 'Construccion' } } })} />);

  await screen.findByRole('heading', { name: 'Ágora' });
  expect(screen.getAllByText('Proyecto actual')).toHaveLength(1);

  const tarjetaAgora = screen.getByRole('heading', { name: 'Ágora' }).closest('article');
  expect(tarjetaAgora).toHaveTextContent('Proyecto actual');
});
