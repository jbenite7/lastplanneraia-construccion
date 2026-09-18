import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, expect, test, vi } from 'vitest';
import { ApiError } from '../../lib/api/cliente';
import { listarProyectos, seleccionarProyecto } from '../../lib/api/proyectos';
import { MENSAJE_RECHAZO_PROYECTO } from '../../lib/api/esquemas/proyectos';
import type {
  ListaProyectos,
  ProyectoDisponible,
  ResultadoSeleccionProyecto,
} from '../../lib/api/esquemas/proyectos';
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

// --- Tarea 6: selección real, clasificación de errores y foco de recuperación ---

test('éxito bloquea la concurrencia y entrega exactamente el route del servidor', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));
  let resolverSeleccion!: (valor: ResultadoSeleccionProyecto) => void;
  vi.mocked(seleccionarProyecto).mockReturnValue(
    new Promise((resolve) => { resolverSeleccion = resolve; }),
  );
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  const boton = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });

  // Dos despachos dentro del mismo `act`, sin flush entre ellos: en el segundo el botón todavía
  // está habilitado (React no ha repintado), así que lo único que puede frenarlo es `enviandoRef`.
  // Con `user.click` el test no mordería: user-event no despacha sobre un botón ya `disabled`.
  await act(async () => {
    boton.click();
    boton.click();
  });

  expect(seleccionarProyecto).toHaveBeenCalledOnce();
  expect(screen.getByRole('button', { name: 'Abriendo Da Porto…' })).toBeDisabled();
  const otro = screen.getByRole('button', { name: 'Ingresar al proyecto Ágora' });
  expect(otro).toBeDisabled();
  await usuario.click(otro);
  expect(seleccionarProyecto).toHaveBeenCalledOnce();

  resolverSeleccion({ success: true, message: null, route: '/programacion-semanal' });

  await waitFor(() => expect(propiedades.onOpen).toHaveBeenCalledWith('/programacion-semanal'));
  expect(propiedades.onOpen).toHaveBeenCalledOnce();
});

test('el gateway recibe el nombre del proyecto y el CSRF de la sesión, sin recalcular la ruta', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 7, name: 'Ágora' })]));
  vi.mocked(seleccionarProyecto).mockResolvedValue({ success: true, message: null, route: '/lps/semana' });
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Ágora' }));

  expect(seleccionarProyecto).toHaveBeenCalledWith('Ágora', '0'.repeat(64));
  await waitFor(() => expect(propiedades.onOpen).toHaveBeenCalledWith('/lps/semana'));
});

test('tras el éxito los controles siguen bloqueados mientras el shell navega', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));
  vi.mocked(seleccionarProyecto).mockResolvedValue({ success: true, message: null, route: '/inicio' });
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);
  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));
  await waitFor(() => expect(propiedades.onOpen).toHaveBeenCalledOnce());

  expect(screen.getByRole('button', { name: 'Abriendo Da Porto…' })).toBeDisabled();
  expect(screen.getByRole('button', { name: 'Ingresar al proyecto Ágora' })).toBeDisabled();
});

test('rechazo no enumerativo conserva el filtro y devuelve el foco a la tarjeta', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));
  vi.mocked(seleccionarProyecto).mockResolvedValue({
    success: false,
    message: MENSAJE_RECHAZO_PROYECTO,
    route: null,
  });
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  await usuario.type(await screen.findByRole('searchbox', { name: 'Buscar proyecto' }), 'porto');
  const boton = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await usuario.click(boton);

  expect(await screen.findByRole('alert')).toHaveTextContent(
    'No pudimos abrir ese proyecto. Verifica tu acceso e inténtalo de nuevo.',
  );
  // El mensaje no delata si el proyecto no existe o si falta permiso.
  expect(screen.queryByText(MENSAJE_RECHAZO_PROYECTO)).not.toBeInTheDocument();
  expect(propiedades.onOpen).not.toHaveBeenCalled();
  expect(screen.getByRole('searchbox', { name: 'Buscar proyecto' })).toHaveValue('porto');
  expect(boton).toBeEnabled();

  await usuario.click(screen.getByRole('button', { name: 'Cerrar aviso' }));

  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  expect(boton).toHaveFocus();
});

test('tras un rechazo la lista sigue utilizable y admite un intento nuevo', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto)
    .mockResolvedValueOnce({ success: false, message: MENSAJE_RECHAZO_PROYECTO, route: null })
    .mockResolvedValueOnce({ success: true, message: null, route: '/inicio' });
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  const boton = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await usuario.click(boton);
  await screen.findByRole('alert');

  await usuario.click(boton);

  await waitFor(() => expect(propiedades.onOpen).toHaveBeenCalledWith('/inicio'));
  expect(seleccionarProyecto).toHaveBeenCalledTimes(2);
});

test('401 revalida la sesión, oculta la lista operativa mientras se resuelve y nunca abre proyecto', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockRejectedValue(
    new ApiError('Tu sesión no está activa.', { tipo: 'http', status: 401, codigo: 'session_invalid' }),
  );
  let resolverRevalidacion!: () => void;
  const propiedades = props();
  propiedades.onRevalidate.mockReturnValue(new Promise<void>((resolve) => { resolverRevalidacion = resolve; }));
  const usuario = userEvent.setup();

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));

  await waitFor(() => expect(propiedades.onRevalidate).toHaveBeenCalledOnce());
  expect(screen.getByRole('status')).toHaveTextContent('Actualizando tu sesión…');
  expect(screen.queryByRole('button', { name: /Ingresar al proyecto/ })).not.toBeInTheDocument();
  expect(screen.queryByRole('searchbox')).not.toBeInTheDocument();

  resolverRevalidacion();

  await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  expect(propiedades.onOpen).not.toHaveBeenCalled();
  expect(seleccionarProyecto).toHaveBeenCalledOnce();
});

test('403 de CSRF ofrece "Actualizar sesión" y no reenvía la selección', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockRejectedValue(
    new ApiError('No fue posible validar la solicitud.', { tipo: 'http', status: 403, codigo: 'csrf_invalid' }),
  );
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(
    'Tu sesión de seguridad cambió. Actualízala antes de volver a intentar.',
  );
  expect(propiedades.onRevalidate).not.toHaveBeenCalled();
  expect(seleccionarProyecto).toHaveBeenCalledOnce();

  const actualizar = await screen.findByRole('button', { name: 'Actualizar sesión' });
  await waitFor(() => expect(actualizar).toHaveFocus());

  await usuario.click(actualizar);

  await waitFor(() => expect(propiedades.onRevalidate).toHaveBeenCalledOnce());
  expect(seleccionarProyecto).toHaveBeenCalledOnce();
  expect(propiedades.onOpen).not.toHaveBeenCalled();
  await waitFor(() => expect(screen.queryByRole('alert')).not.toBeInTheDocument());
  // La lista nunca se ocultó en el 403: el botón de origen sigue vivo y recupera el foco.
  await waitFor(() => expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toHaveFocus());
});

test('si la revalidación del 403 falla, el aviso lo dice y el foco vuelve a "Actualizar sesión"', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockRejectedValue(
    new ApiError('csrf', { tipo: 'http', status: 403, codigo: 'csrf_invalid' }),
  );
  const usuario = userEvent.setup();
  const propiedades = props();
  propiedades.onRevalidate.mockRejectedValue(new Error('sin red'));

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));
  await usuario.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  expect(await screen.findByRole('alert')).toHaveTextContent(
    'No pudimos actualizar la sesión. Intenta nuevamente.',
  );
  await waitFor(() => expect(screen.getByRole('button', { name: 'Actualizar sesión' })).toHaveFocus());
  expect(propiedades.onOpen).not.toHaveBeenCalled();
});

test.each([
  ['422 de validación', new ApiError('Selecciona un proyecto de la lista.', { tipo: 'http', status: 422, codigo: 'validation_error' })],
  ['500 del servidor', new ApiError('boom', { tipo: 'http', status: 500, codigo: 'invalid_landing' })],
  ['caída de red', new ApiError('sin conexión', { tipo: 'red', codigo: 'NETWORK_ERROR' })],
  ['contrato roto', new ApiError('forma inesperada', { tipo: 'forma_invalida', status: 200, codigo: 'INVALID_SHAPE' })],
])('%s muestra copy seguro, no afirma éxito y permite reintentar a mano', async (_caso, causa) => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockRejectedValue(causa);
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  const boton = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await usuario.click(boton);

  expect(await screen.findByRole('alert')).toHaveTextContent(
    'No pudimos confirmar si el proyecto se abrió. Inténtalo nuevamente.',
  );
  expect(propiedades.onOpen).not.toHaveBeenCalled();
  expect(propiedades.onRevalidate).not.toHaveBeenCalled();
  // Un solo POST: nada reintenta solo.
  expect(seleccionarProyecto).toHaveBeenCalledOnce();
  expect(boton).toBeEnabled();
  expect(screen.queryByRole('button', { name: 'Actualizar sesión' })).not.toBeInTheDocument();
});

test('el proyecto actual conserva su chip y se puede volver a seleccionar', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 2, name: 'Ágora' })]));
  vi.mocked(seleccionarProyecto).mockResolvedValue({ success: true, message: null, route: '/inicio' });
  const usuario = userEvent.setup();
  const propiedades = props({ session: { csrfToken: '0'.repeat(64), project: { id: 2, name: 'Ágora', area: 'Construccion' } } });

  render(<SelectorProyectos {...propiedades} />);

  expect(await screen.findByText('Proyecto actual')).toBeVisible();
  await usuario.click(screen.getByRole('button', { name: 'Ingresar al proyecto Ágora' }));

  await waitFor(() => expect(propiedades.onOpen).toHaveBeenCalledWith('/inicio'));
  expect(screen.getByText('Proyecto actual')).toBeVisible();
});

test('si onOpen lanza, la pantalla sigue operable en vez de quedar bloqueada', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockResolvedValue({ success: true, message: null, route: '/inicio' });
  const usuario = userEvent.setup();
  const propiedades = props();
  propiedades.onOpen.mockImplementation(() => { throw new Error('el shell no pudo navegar'); });

  render(<SelectorProyectos {...propiedades} />);

  const boton = await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' });
  await usuario.click(boton);

  expect(await screen.findByRole('alert')).toHaveTextContent(
    'No pudimos confirmar si el proyecto se abrió. Inténtalo nuevamente.',
  );
  // El candado se suelta: si el shell no navegó, dejar todo bloqueado obliga a recargar a mano.
  expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toBeEnabled();
});

test('tras un 401, "Actualizar sesión" con éxito devuelve el foco a la tarjeta remontada', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([proyecto({ id: 1, name: 'Da Porto' })]));
  vi.mocked(seleccionarProyecto).mockRejectedValue(
    new ApiError('sesión caída', { tipo: 'http', status: 401, codigo: 'session_invalid' }),
  );
  const usuario = userEvent.setup();
  const propiedades = props();
  propiedades.onRevalidate
    .mockRejectedValueOnce(new Error('sin red'))
    .mockResolvedValueOnce(undefined);

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));
  await usuario.click(await screen.findByRole('button', { name: 'Actualizar sesión' }));

  // La lista se desmontó y volvió: el nodo del botón es otro, así que el foco no puede ir por
  // referencia guardada; se resuelve por el id del proyecto contra el DOM vivo.
  await waitFor(() =>
    expect(screen.getByRole('button', { name: 'Ingresar al proyecto Da Porto' })).toHaveFocus(),
  );
  expect(propiedades.onOpen).not.toHaveBeenCalled();
});

test('si la tarjeta de origen quedó filtrada fuera, el foco cae en la pantalla y no en el body', async () => {
  vi.mocked(listarProyectos).mockResolvedValue(lista([
    proyecto({ id: 1, name: 'Da Porto' }),
    proyecto({ id: 2, name: 'Ágora' }),
  ]));
  let resolverSeleccion!: (valor: ResultadoSeleccionProyecto) => void;
  vi.mocked(seleccionarProyecto).mockReturnValue(
    new Promise((resolve) => { resolverSeleccion = resolve; }),
  );
  const usuario = userEvent.setup();
  const propiedades = props();

  render(<SelectorProyectos {...propiedades} />);

  await usuario.click(await screen.findByRole('button', { name: 'Ingresar al proyecto Da Porto' }));
  // Mientras el POST vuela, el usuario filtra fuera su propia tarjeta.
  await usuario.type(screen.getByRole('searchbox', { name: 'Buscar proyecto' }), 'agora');

  await act(async () => {
    resolverSeleccion({ success: false, message: MENSAJE_RECHAZO_PROYECTO, route: null });
  });

  await usuario.click(await screen.findByRole('button', { name: 'Cerrar aviso' }));

  expect(screen.queryByRole('button', { name: 'Ingresar al proyecto Da Porto' })).not.toBeInTheDocument();
  await waitFor(() => expect(document.getElementById('main-content')).toHaveFocus());
  expect(document.body).not.toHaveFocus();
});
