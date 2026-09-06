import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { expect, test, vi } from 'vitest';
import { configuracionPorDefecto } from '../dominio/restricciones';
import { LpsDrawerProvider, type LpsActivityContext } from '../estado/LpsDrawerProvider';
import { DisparadorLps } from './DisparadorLps';

function jsonOk(cuerpo: unknown): Response {
  return new Response(JSON.stringify(cuerpo), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

function contextoActividad(): LpsActivityContext {
  return {
    target: { consecutivo: 1, modulo: 'PI' },
    module: 'PI',
    activity: {
      id: 1,
      label: 'Instalación eléctrica',
      state: { key: 'atrasada', label: 'Atrasada', phase: null, actions: [] },
      progress: { ratio: 0.2, display: '20%' },
      critical: true,
      isHeader: false,
    },
    restrictions: { config: configuracionPorDefecto(), values: {} },
  };
}

test('T02-AC-155: el disparador es un botón nativo con nombre accesible que incluye la severidad en texto', () => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      jsonOk({
        respuesta: 'OK',
        ok: true,
        data: [],
        comments: [],
        target: { kind: 'activity', activityId: 1, module: 'PI', week: 1 },
        actions: { read: true, comment: true, notifyNext: true, close: true, actorWriteBlock: 'none' },
        meta: { requestId: 'r' },
      }),
    ),
  );

  render(
    <LpsDrawerProvider csrfToken="t" generacionSesion={0} semana={1}>
      <DisparadorLps contexto={contextoActividad()} severidad="critical" />
    </LpsDrawerProvider>,
  );

  const boton = screen.getByRole('button', { name: /Instalación eléctrica.*Crítico/i });
  expect(boton).toBeInTheDocument();
  expect(screen.getByText('Crítico')).toBeInTheDocument();

  vi.unstubAllGlobals();
});

test('al hacer clic, abre el cajón con el contexto entregado — nunca una fila cruda', async () => {
  const fetchMock = vi.fn().mockResolvedValue(
    jsonOk({
      respuesta: 'OK',
      ok: true,
      data: [],
      comments: [],
      target: { kind: 'activity', activityId: 1, module: 'PI', week: 1 },
      actions: { read: true, comment: true, notifyNext: true, close: true, actorWriteBlock: 'none' },
      meta: { requestId: 'r' },
    }),
  );
  vi.stubGlobal('fetch', fetchMock);
  const usuario = userEvent.setup();

  render(
    <LpsDrawerProvider csrfToken="t" generacionSesion={0} semana={1}>
      <DisparadorLps contexto={contextoActividad()} severidad="attention" />
    </LpsDrawerProvider>,
  );

  await usuario.click(screen.getByRole('button'));

  expect(fetchMock).toHaveBeenCalledWith(
    expect.stringContaining('/api/lps/comments?'),
    expect.anything(),
  );

  vi.unstubAllGlobals();
});
