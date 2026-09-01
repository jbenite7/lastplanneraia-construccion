import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { HarnessLps } from './HarnessLps';
import { ESCENARIOS_CUATRO_CONSUMIDORES } from './fixturesLps';
import { queryDeTarget } from '../api/esquemas';

/**
 * Tarea 10 (T02-AC-182..186): demuestra la costura de los cuatro consumidores (PG/PI/PS/S25) sin
 * DML — sólo `LpsActivityContext` sintéticos interceptando `fetch`, nunca una fila cruda ni un
 * escritura real. Dos afirmaciones por escenario:
 *
 * 1. El cajón abre y muestra el nombre accesible correcto para cada uno de los diez escenarios
 *    canónicos (`fixturesLps.ts`) — la costura funciona igual sea cual sea el consumidor.
 * 2. El único dato que sale por red es exactamente `queryDeTarget(contexto.target)`: ni una clave
 *    de más (ninguna clave de la fila cruda sintética — `Actividad`, `estado_operativo`,
 *    `Semanas_Inicio`, `unique_id`, `alerta_crisis`… — llega jamás a la petición). Es la prueba en
 *    tiempo de ejecución de que "el provider nunca recibe una fila cruda ni un setter de grilla"
 *    (AC-007): si algún adapter futuro colara `fila` completa en el contexto, esta comparación
 *    exacta de claves lo pondría en rojo.
 */

function respuestaHiloPara(escenario: (typeof ESCENARIOS_CUATRO_CONSUMIDORES)[number]) {
  const target = 'alertaId' in escenario.contexto.target
    ? { kind: 'alert' as const, activityId: escenario.contexto.activity.id, module: 'PG' as const, week: 1, alertId: escenario.contexto.target.alertaId }
    : { kind: 'activity' as const, activityId: escenario.contexto.activity.id, module: escenario.contexto.target.modulo, week: 1 };

  return {
    respuesta: 'OK',
    ok: true,
    data: [],
    comments: [],
    target,
    actions: { read: true, comment: true, notifyNext: true, close: true, actorWriteBlock: 'none' as const },
    crisisAlert: escenario.contexto.crisis
      ? { id: escenario.contexto.crisis.alertId, active: escenario.contexto.crisis.active, level: escenario.contexto.crisis.level ?? 1 }
      : undefined,
    meta: { requestId: `req-${escenario.nombre}` },
  };
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

describe.each(ESCENARIOS_CUATRO_CONSUMIDORES)('$nombre', (escenario) => {
  test('abre el cajón con el contexto sintético y expone su nombre accesible', async () => {
    fetchMock.mockResolvedValue(jsonOk(respuestaHiloPara(escenario)));
    const usuario = userEvent.setup();

    render(
      <HarnessLps>
        {(api) => (
          <button type="button" onClick={() => api.abrir(escenario.contexto)}>
            Abrir {escenario.nombre}
          </button>
        )}
      </HarnessLps>,
    );

    await usuario.click(screen.getByRole('button', { name: `Abrir ${escenario.nombre}` }));

    const dialogo = await screen.findByRole('dialog');
    expect(dialogo).toHaveAccessibleName(escenario.contexto.activity.label);
  });

  test('el único dato que sale por red es el target — nunca una clave de la fila cruda', async () => {
    fetchMock.mockResolvedValue(jsonOk(respuestaHiloPara(escenario)));
    const usuario = userEvent.setup();

    render(
      <HarnessLps>
        {(api) => (
          <button type="button" onClick={() => api.abrir(escenario.contexto)}>
            Abrir {escenario.nombre}
          </button>
        )}
      </HarnessLps>,
    );

    await usuario.click(screen.getByRole('button', { name: `Abrir ${escenario.nombre}` }));
    await waitFor(() => expect(fetchMock).toHaveBeenCalledTimes(1));

    const [url] = fetchMock.mock.calls[0] as [string];
    const claveEsperada = queryDeTarget(escenario.contexto.target);
    const urlReal = new URL(url, 'http://localhost');

    // Comparación exacta de claves — ni una de más, ni una de menos.
    expect([...urlReal.searchParams.keys()].sort()).toEqual([...claveEsperada.keys()].sort());
    for (const [clave, valor] of claveEsperada.entries()) {
      expect(urlReal.searchParams.get(clave)).toBe(valor);
    }

    // Claves específicas de la fila cruda sintética de este escenario: si alguna llegara a la
    // petición, sería la prueba de que un adapter coló la fila completa en vez del contrato.
    // `alerta_id` queda fuera de esta lista a propósito: es la clave legítima que `queryDeTarget`
    // emite para el target de alerta (ver comparación exacta arriba), no una fuga de la fila
    // cruda — coincide de nombre con el campo sintético `alerta_id` de las fixtures S25 sólo
    // porque el dominio real usa el mismo término para las dos cosas.
    const clavesProhibidas = ['Actividad', 'estado_operativo', 'Semanas_Inicio', 'unique_id', 'alerta_crisis', 'prioridad', 'Predecesora', 'subcontratista'];
    for (const clave of clavesProhibidas) {
      expect(urlReal.searchParams.has(clave)).toBe(false);
    }
  });
});
