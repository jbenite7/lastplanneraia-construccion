import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vitest';
import { SesionProvider, useSesion } from './SesionProvider';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

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

function cuerpoAutenticado(nombreProyecto: string): unknown {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: nombreProyecto, area: 'Construccion' },
    capabilities: {},
    navigation: { bi: null, groups: [] },
    week: null,
    csrfToken,
  };
}

afterEach(() => vi.unstubAllGlobals());

// Checkpoint T01 Tarea 6: "un cambio de sesión/proyecto invalida todo resultado operativo previo".
// `recargar()` es la vía por la que hoy pasan cambiar de proyecto (`alCambiarProyecto`), logout
// (`ControlActividad.alCerrarSesion`) y cualquier "proyecto inválido" — así que la garantía de
// generación vive ahí, una sola vez, para las tres causas.
test('recargar() aborta la petición /api/session anterior si todavía estaba en vuelo', async () => {
  const fetchFalso = vi.fn().mockReturnValue(new Promise<Response>(() => {})); // nunca resuelve
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });

  await waitFor(() => expect(fetchFalso).toHaveBeenCalledTimes(1));
  const señalPrimeraGeneracion = (fetchFalso.mock.calls[0]?.[1] as RequestInit).signal as AbortSignal;
  expect(señalPrimeraGeneracion.aborted).toBe(false);

  await act(async () => {
    void result.current.recargar();
  });

  expect(señalPrimeraGeneracion.aborted).toBe(true);
});

test('una respuesta tardía de una generación anterior se ignora — no pisa el estado ya actualizado', async () => {
  let liberarPrimeraGeneracion!: (respuesta: Response) => void;
  const primeraGeneracion = new Promise<Response>((resolver) => {
    liberarPrimeraGeneracion = resolver;
  });

  const fetchFalso = vi
    .fn()
    .mockReturnValueOnce(primeraGeneracion)
    .mockResolvedValueOnce(new Response(JSON.stringify(cuerpoAnonimo()), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });

  await waitFor(() => expect(fetchFalso).toHaveBeenCalledTimes(1));

  await act(async () => {
    void result.current.recargar();
  });

  await waitFor(() => expect(result.current.estado).toBe('anonimo'));

  // La generación 1, ya descartada, por fin resuelve — con un cuerpo *autenticado* que, si se
  // aplicara, taparía el "anonimo" que ya es el estado correcto y vigente.
  await act(async () => {
    liberarPrimeraGeneracion(new Response(JSON.stringify(cuerpoAutenticado('Da Porto')), { status: 200 }));
    await primeraGeneracion;
  });

  expect(result.current.estado).toBe('anonimo');
  expect(result.current.autenticado).toBeNull();
});

test('logout (vía ControlActividad) descarta una recarga anterior (p. ej. un cambio de proyecto) que seguía en vuelo', async () => {
  let liberarCambioDeProyecto!: (respuesta: Response) => void;
  const cambioDeProyectoEnVuelo = new Promise<Response>((resolver) => {
    liberarCambioDeProyecto = resolver;
  });

  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(new Response(JSON.stringify(cuerpoAutenticado('Da Porto')), { status: 200 })) // bootstrap inicial
    .mockReturnValueOnce(cambioDeProyectoEnVuelo) // recargar() de un cambio de proyecto, todavía sin resolver
    .mockResolvedValueOnce(new Response(JSON.stringify({ success: true }), { status: 200 })) // POST logout
    .mockResolvedValueOnce(new Response(JSON.stringify(cuerpoAnonimo()), { status: 200 })); // recargar() tras logout
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.autenticado).not.toBeNull());

  // Doble acción del usuario: pidió cambiar de proyecto y, antes de que esa recarga terminara,
  // también cerró sesión (p. ej. el timeout local disparó justo en ese instante).
  await act(async () => {
    void result.current.recargar();
  });

  await act(async () => {
    await result.current.cerrarSesion();
  });

  await waitFor(() => expect(result.current.estado).toBe('anonimo'));

  // La recarga del cambio de proyecto, ya descartada, por fin resuelve — con un proyecto distinto
  // que, si se aplicara, taparía el "anonimo" que ya es el estado correcto y vigente.
  await act(async () => {
    liberarCambioDeProyecto(new Response(JSON.stringify(cuerpoAutenticado('Otro Proyecto')), { status: 200 }));
    await cambioDeProyectoEnVuelo;
  });

  expect(result.current.estado).toBe('anonimo');
});

test('cambiar de proyecto (recargar tras seleccionar otro) también ignora una respuesta tardía del proyecto anterior', async () => {
  let liberarPrimeraGeneracion!: (respuesta: Response) => void;
  const primeraGeneracion = new Promise<Response>((resolver) => {
    liberarPrimeraGeneracion = resolver;
  });

  const fetchFalso = vi
    .fn()
    .mockReturnValueOnce(primeraGeneracion)
    .mockResolvedValueOnce(new Response(JSON.stringify(cuerpoAutenticado('Proyecto Nuevo')), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(fetchFalso).toHaveBeenCalledTimes(1));

  await act(async () => {
    void result.current.recargar(); // simula "cambiar de proyecto" (mismo mecanismo que useSelectorProyecto)
  });

  await waitFor(() => expect(result.current.autenticado?.project?.name).toBe('Proyecto Nuevo'));

  await act(async () => {
    liberarPrimeraGeneracion(new Response(JSON.stringify(cuerpoAutenticado('Proyecto Viejo')), { status: 200 }));
    await primeraGeneracion;
  });

  expect(result.current.autenticado?.project?.name).toBe('Proyecto Nuevo');
});

test('la generación expuesta avanza en cada recargar()', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify(cuerpoAnonimo()), { status: 200 })));

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.estado).toBe('anonimo'));

  const generacionInicial = result.current.generacion;

  await act(async () => {
    await result.current.recargar();
  });

  expect(result.current.generacion).toBeGreaterThan(generacionInicial);
});

test('la señal de aborto expuesta corresponde a la generación vigente', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify(cuerpoAnonimo()), { status: 200 })));

  const { result } = renderHook(() => useSesion(), { wrapper: SesionProvider });
  await waitFor(() => expect(result.current.estado).toBe('anonimo'));

  expect(result.current.señal).not.toBeNull();
  expect(result.current.señal?.aborted).toBe(false);
});
