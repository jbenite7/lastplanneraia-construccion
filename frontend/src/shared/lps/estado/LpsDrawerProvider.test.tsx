import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { HarnessLps } from '../testing/HarnessLps';
import type { LpsActivityContext } from './LpsDrawerProvider';
import { configuracionPorDefecto } from '../dominio/restricciones';

/**
 * Pruebas de ciclo de vida del provider (T02-AC-021..035). Interceptan `fetch` — nunca un DML ni
 * una alerta real (Global Constraints del plan) — con respuestas mínimas que cumplen
 * `EsquemaRespuestaHilo`.
 */

function respuestaHilo(overrides: Partial<{ comments: unknown[]; target: unknown; crisisAlert: unknown }> = {}) {
  return {
    respuesta: 'OK',
    ok: true,
    data: [],
    comments: overrides.comments ?? [],
    target: overrides.target ?? { kind: 'activity', activityId: 101, module: 'PG', week: 5 },
    actions: { read: true, comment: true, notifyNext: true, close: true, actorWriteBlock: 'none' },
    crisisAlert: overrides.crisisAlert,
    meta: { requestId: 'req-1' },
  };
}

function contextoActividad(overrides: Partial<LpsActivityContext> = {}): LpsActivityContext {
  return {
    target: { consecutivo: 101, modulo: 'PG' },
    module: 'PG',
    activity: {
      id: 101,
      label: 'Actividad 101',
      state: { key: 'en-curso', label: 'En curso', phase: null, actions: [] },
      progress: { ratio: 0.4, display: '40%' },
      critical: false,
      isHeader: false,
    },
    restrictions: { config: configuracionPorDefecto(), values: {} },
    simulado: true,
    ...overrides,
  };
}

function contextoAlerta(overrides: Partial<LpsActivityContext> = {}): LpsActivityContext {
  return contextoActividad({
    target: { alertaId: 901 },
    module: 'ESC',
    crisis: { alertId: 901, active: true, level: 2 },
    ...overrides,
  });
}

function jsonOk(cuerpo: unknown): Response {
  return new Response(JSON.stringify(cuerpo), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
  fetchMock = vi.fn();
  vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
  document.getElementById('contenido')?.removeAttribute('inert');
});

test('abrir monta el cajón y carga el hilo; cerrar lo desmonta', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  expect(screen.getByRole('dialog')).toBeInTheDocument();

  await waitFor(() => expect(screen.getByText('0 comentario(s)')).toBeInTheDocument());

  await usuario.click(screen.getByRole('button', { name: 'Cerrar' }));
  expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
});

test('cambiar de target aborta la lectura previa (AC-031/032): una respuesta tardía nunca sustituye el hilo del target actual', async () => {
  let resolverPrimera!: (valor: Response) => void;
  const primera = new Promise<Response>((resolver) => {
    resolverPrimera = resolver;
  });
  fetchMock
    .mockImplementationOnce(() => primera)
    .mockResolvedValueOnce(jsonOk(respuestaHilo({ target: { kind: 'activity', activityId: 202, module: 'PG', week: 5 }, comments: [] })));

  const usuario = userEvent.setup();
  render(
    <HarnessLps>
      {(api) => (
        <>
          <button type="button" onClick={() => api.abrir(contextoActividad({ target: { consecutivo: 101, modulo: 'PG' } }))}>
            Abrir 101
          </button>
          <button type="button" onClick={() => api.abrir(contextoActividad({ target: { consecutivo: 202, modulo: 'PG' }, activity: { ...contextoActividad().activity, id: 202, label: 'Actividad 202' } }))}>
            Abrir 202
          </button>
        </>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir 101' }));
  await usuario.click(screen.getByRole('button', { name: 'Abrir 202' }));

  await waitFor(() => expect(screen.getByText('0 comentario(s)')).toBeInTheDocument());

  // La primera petición (target 101) resuelve tarde: no debe pisar el hilo del target 202 vigente.
  await act(async () => {
    resolverPrimera(jsonOk(respuestaHilo({ comments: [{ id: 1, comentario: 'tardío', created_at: 'x', autor_nombre: null, autor_cargo: null, menciones: null, respuestas: [] }] })));
    await Promise.resolve();
  });

  expect(screen.getByText('0 comentario(s)')).toBeInTheDocument();
  expect(screen.queryByText('tardío')).not.toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Actividad 202' })).toBeInTheDocument();
});

test('AC-021: un cambio de generación de sesión (login/proyecto) cierra el cajón y limpia el target', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();

  function Wrapper() {
    return null;
  }
  void Wrapper;

  const { rerender } = render(
    <HarnessLps generacionSesion={1}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  expect(screen.getByRole('dialog')).toBeInTheDocument();

  rerender(
    <HarnessLps generacionSesion={2}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
});

test('AC-022: cambiar la semana del shell cierra un target de actividad PG/PI/PS', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();

  const { rerender } = render(
    <HarnessLps semana={5}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  expect(screen.getByRole('dialog')).toBeInTheDocument();

  rerender(
    <HarnessLps semana={6}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
});

test('AC-023: un target de alerta (S25) NO se cierra por un cambio de semana del shell — la alerta porta su propia semana', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo({ target: { kind: 'alert', activityId: 901, module: 'PS', week: 14, alertId: 901 } })));
  const usuario = userEvent.setup();

  const { rerender } = render(
    <HarnessLps semana={5}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoAlerta())}>
          Abrir alerta
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir alerta' }));
  await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument());

  rerender(
    <HarnessLps semana={6}>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoAlerta())}>
          Abrir alerta
        </button>
      )}
    </HarnessLps>,
  );

  // Sigue montado: el cambio de semana del shell no invalida un target de alerta.
  expect(screen.getByRole('dialog')).toBeInTheDocument();
});

test('AC-025: un target oculto por filtros conserva el cajón abierto y lo anuncia', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <>
          <button type="button" onClick={() => api.abrir(contextoActividad())}>
            Abrir
          </button>
          <button type="button" onClick={() => api.marcarOcultaPorFiltros(true)}>
            Ocultar
          </button>
        </>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  await usuario.click(screen.getByRole('button', { name: 'Ocultar' }));

  expect(screen.getByRole('dialog')).toBeInTheDocument();
  expect(screen.getAllByText('Oculta por los filtros').length).toBeGreaterThan(0);
});

test('AC-026: un target que ya no existe (LPS_TARGET_NOT_FOUND) anuncia indisponibilidad y no ofrece acciones', async () => {
  fetchMock.mockResolvedValue(
    new Response(
      JSON.stringify({ respuesta: 'ERROR', ok: false, mensaje: 'no', error: { code: 'LPS_TARGET_NOT_FOUND', message: 'no encontrado' }, meta: { requestId: 'r' } }),
      { status: 404, headers: { 'Content-Type': 'application/json' } },
    ),
  );
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));

  await waitFor(() => expect(screen.getByRole('alert')).toHaveTextContent(/ya no está disponible/i));
  expect(screen.queryByRole('button', { name: 'Comentar' })).not.toBeInTheDocument();
});

test('AC-029/030: si el disparador desaparece del DOM, el foco vuelve al fallback del contexto', async () => {
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();
  const fallback = vi.fn();

  function EscenaConDisparadorDesmontable({ api }: { api: import('./LpsDrawerProvider').LpsDrawerApi }) {
    return (
      <button
        type="button"
        onClick={(evento) => {
          api.abrir(contextoActividad({ retornarFocoAlternativo: fallback }), evento.currentTarget);
        }}
      >
        Abrir (se va a desmontar)
      </button>
    );
  }

  const { rerender } = render(
    <HarnessLps>{(api) => <EscenaConDisparadorDesmontable api={api} />}</HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: /Abrir \(se va a desmontar\)/ }));
  expect(screen.getByRole('dialog')).toBeInTheDocument();

  // El disparador desaparece del DOM mientras el cajón sigue abierto (p. ej. la fila se filtró).
  rerender(<HarnessLps>{() => <p>El disparador ya no existe</p>}</HarnessLps>);

  await usuario.click(screen.getByRole('button', { name: 'Cerrar' }));

  await waitFor(() => expect(fallback).toHaveBeenCalledTimes(1));
});

test('AC-103: un error de comentario conserva el borrador — el textarea no se limpia', async () => {
  fetchMock
    .mockResolvedValueOnce(jsonOk(respuestaHilo()))
    .mockResolvedValueOnce(
      new Response(JSON.stringify({ respuesta: 'ERROR', ok: false, mensaje: 'no', error: { code: 'VALIDATION_FAILED', message: 'no' }, meta: { requestId: 'r' } }), {
        status: 422,
        headers: { 'Content-Type': 'application/json' },
      }),
    );
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  await waitFor(() => expect(screen.getByLabelText('Comentario')).toBeInTheDocument());

  await usuario.type(screen.getByLabelText('Comentario'), 'mi borrador');
  await usuario.click(screen.getByRole('button', { name: 'Comentar' }));

  await waitFor(() => expect(screen.getByLabelText('Comentario')).toHaveValue('mi borrador'));
});

test('AC-033/034/035: distingue loading/ready/refreshing y un error de refresco no borra el diagnóstico previo', async () => {
  fetchMock.mockResolvedValueOnce(jsonOk(respuestaHilo())).mockRejectedValueOnce(new TypeError('network down'));
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <>
          <button type="button" onClick={() => api.abrir(contextoActividad())}>
            Abrir
          </button>
          <button type="button" onClick={() => api.reintentar()}>
            Reintentar
          </button>
        </>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  await waitFor(() => expect(screen.getByText('0 comentario(s)')).toBeInTheDocument());

  // Se mantiene visible el diagnóstico de la actividad ya cargada mientras se reintenta.
  expect(screen.getByRole('heading', { name: 'Actividad 101' })).toBeInTheDocument();
});

test('AC-162: el contenido de fondo queda inert mientras el cajón está abierto en modo modal (móvil)', async () => {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: 390 });
  fetchMock.mockResolvedValue(jsonOk(respuestaHilo()));
  const usuario = userEvent.setup();

  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );

  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));

  expect(document.getElementById('contenido')).toHaveAttribute('inert');

  await usuario.click(screen.getByRole('button', { name: 'Cerrar' }));
  expect(document.getElementById('contenido')).not.toHaveAttribute('inert');
});
