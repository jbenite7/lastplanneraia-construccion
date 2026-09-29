import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vitest';
import { SesionProvider, useSesion, type EstadoSesion } from './SesionProvider';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

const semana4 = {
  actions: { create: true, deleteLast: true, select: true },
  current: 4,
  options: [{ number: 4, startsOn: '2026-09-10', endsOn: '2026-09-16' }],
};

function cuerpoAutenticado(semana: unknown): unknown {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Rita', role: 'R' },
    project: { id: 1, name: 'PDC Sandbox E2E', area: 'Construccion' },
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: semana,
    csrfToken,
  };
}

function cuerpoAnonimo(): unknown {
  return {
    state: 'anonymous',
    authenticated: false,
    reason: 'missing_session',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  };
}

const respuesta = (cuerpo: unknown) => new Response(JSON.stringify(cuerpo), { status: 200 });

afterEach(() => vi.unstubAllGlobals());

// Tras «quitar semana», el shell lee /api/session ANTES de que Programa General pida su contexto y
// escriba la semana en la sesión, así que la primera carga se queda sin barra ni selector.
// `recargar()` lo arreglaría, pero vacía el arranque y deja el estado en «cargando»: la pantalla se
// remontaría con un parpadeo y perdería los filtros que el usuario ya escribió. `refrescarSemana()`
// relee la sesión EN SILENCIO: reemplaza el arranque en su sitio, sin estado intermedio.
test('refrescarSemana() trae la semana nueva sin pasar por «cargando» ni subir la generación', async () => {
  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(respuesta(cuerpoAutenticado(null)))
    .mockResolvedValueOnce(respuesta(cuerpoAutenticado(semana4)));
  vi.stubGlobal('fetch', fetchFalso);

  const estados: EstadoSesion[] = [];
  const { result } = renderHook(
    () => {
      const sesion = useSesion();
      estados.push(sesion.estado);
      return sesion;
    },
    { wrapper: SesionProvider },
  );

  await waitFor(() => expect(result.current.estado).toBe('listo'));
  expect(result.current.autenticado?.week).toBeNull();
  const generacionAntes = result.current.generacion;
  const vistosAntes = estados.length;

  await act(async () => {
    await result.current.refrescarSemana();
  });

  expect(result.current.autenticado?.week?.current).toBe(4);
  expect(result.current.generacion).toBe(generacionAntes);
  expect(estados.slice(vistosAntes).every((estado) => estado === 'listo')).toBe(true);
});

test('una relectura silenciosa que llega después de un recargar() se ignora', async () => {
  let liberarSilenciosa!: (respuesta: Response) => void;
  const silenciosa = new Promise<Response>((resolver) => {
    liberarSilenciosa = resolver;
  });

  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(respuesta(cuerpoAutenticado(null)))
    .mockReturnValueOnce(silenciosa)
    .mockResolvedValueOnce(respuesta(cuerpoAnonimo()));
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.estado).toBe('listo'));

  let pendiente!: Promise<void>;
  await act(async () => {
    pendiente = result.current.refrescarSemana();
  });
  await act(async () => {
    void result.current.recargar();
  });
  await waitFor(() => expect(result.current.estado).toBe('anonimo'));

  await act(async () => {
    liberarSilenciosa(respuesta(cuerpoAutenticado(semana4)));
    await pendiente;
  });

  expect(result.current.estado).toBe('anonimo');
  expect(result.current.autenticado).toBeNull();
});

test('una respuesta no autenticada no tumba una sesión que estaba lista: la sigue mandando recargar()', async () => {
  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(respuesta(cuerpoAutenticado(null)))
    .mockResolvedValueOnce(respuesta(cuerpoAnonimo()));
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.estado).toBe('listo'));

  await act(async () => {
    await result.current.refrescarSemana();
  });

  expect(result.current.estado).toBe('listo');
});

test('un fallo de red en la relectura es silencioso: no degrada la pantalla ya cargada', async () => {
  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(respuesta(cuerpoAutenticado(null)))
    .mockRejectedValueOnce(new TypeError('red caída'));
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.estado).toBe('listo'));

  await act(async () => {
    await result.current.refrescarSemana();
  });

  expect(result.current.estado).toBe('listo');
  expect(result.current.error).toBeNull();
});
