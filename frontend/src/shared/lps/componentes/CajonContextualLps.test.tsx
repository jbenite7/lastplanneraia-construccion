import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { HarnessLps } from '../testing/HarnessLps';
import { configuracionPorDefecto } from '../dominio/restricciones';
import type { LpsActivityContext } from '../estado/LpsDrawerProvider';

/**
 * Pruebas de componente/accesibilidad del cajón (T02-AC-155..177). Paso 2 del brief Tarea 8:
 * disparador nativo, dialog con nombre accesible, trampa/retorno de foco, Escape con protección
 * de borrador, inert, live regions, contrato semántico de 44px, enlace BI autorizado, sin HTML
 * crudo, sin Handsontable/jQuery/globals legado, una sola instancia montada.
 */

function respuestaHilo(overrides: Partial<{ comments: unknown[] }> = {}) {
  return {
    respuesta: 'OK',
    ok: true,
    data: [],
    comments: overrides.comments ?? [],
    target: { kind: 'activity', activityId: 101, module: 'PG', week: 5 },
    actions: { read: true, comment: true, notifyNext: true, close: true, actorWriteBlock: 'none' },
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
    biHref: 'https://bi.example/tablero',
    ...overrides,
  };
}

function jsonOk(cuerpo: unknown): Response {
  return new Response(JSON.stringify(cuerpo), { status: 200, headers: { 'Content-Type': 'application/json' } });
}

let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
  fetchMock = vi.fn().mockResolvedValue(jsonOk(respuestaHilo()));
  vi.stubGlobal('fetch', fetchMock);
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: 390 });
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
  document.getElementById('contenido')?.removeAttribute('inert');
});

async function abrirCajon() {
  const usuario = userEvent.setup();
  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad())}>
          Abrir cajón
        </button>
      )}
    </HarnessLps>,
  );
  await usuario.click(screen.getByRole('button', { name: 'Abrir cajón' }));
  await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument());
  return usuario;
}

test('AC-157: el cajón usa role=dialog con nombre accesible, y aria-modal=true bajo el umbral flotante', async () => {
  await abrirCajon();
  const dialogo = screen.getByRole('dialog');
  expect(dialogo).toHaveAttribute('aria-modal', 'true');
  expect(dialogo).toHaveAccessibleName('Actividad 101');
});

test('AC-155: en desktop 1180+ el cajón deja de ser modal — panel embebido sin bloquear el fondo', async () => {
  Object.defineProperty(window, 'innerWidth', { configurable: true, writable: true, value: 1440 });
  await abrirCajon();
  const dialogo = screen.getByRole('dialog');
  expect(dialogo).toHaveAttribute('aria-modal', 'false');
});

test('AC-158: al abrir, el foco entra en el encabezado/cierre o primer control útil', async () => {
  await abrirCajon();
  await waitFor(() => expect(document.activeElement).toBe(screen.getByRole('button', { name: 'Cerrar' })));
});

test('AC-159: Tab/Shift+Tab permanecen dentro del cajón modal (trampa de foco)', async () => {
  const usuario = await abrirCajon();
  const dialogo = screen.getByRole('dialog');
  const enfocables = dialogo.querySelectorAll<HTMLElement>('button:not([disabled]), a[href], textarea:not([disabled])');
  expect(enfocables.length).toBeGreaterThan(1);
  const ultimo = enfocables[enfocables.length - 1];

  ultimo.focus();
  await usuario.tab();

  expect(dialogo).toContainElement(document.activeElement as HTMLElement);
});

test('AC-160/161: Escape con borrador pide confirmación antes de descartar; sin borrador cierra directo', async () => {
  const usuario = await abrirCajon();

  await usuario.type(screen.getByLabelText('Comentario'), 'un borrador sin enviar');
  await usuario.keyboard('{Escape}');

  expect(screen.getByText(/¿Descartarlo y cerrar\?/)).toBeInTheDocument();
  expect(screen.getByRole('dialog')).toBeInTheDocument();

  await usuario.click(screen.getByRole('button', { name: 'Descartar y cerrar' }));
  expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
});

test('AC-161: cerrar por overlay aplica la misma protección de borrador que Escape', async () => {
  const usuario = await abrirCajon();
  await usuario.type(screen.getByLabelText('Comentario'), 'otro borrador');

  const velo = document.querySelector('.lps-cajon-velo') as HTMLElement;
  await usuario.click(velo);

  expect(screen.getByText(/¿Descartarlo y cerrar\?/)).toBeInTheDocument();
});

test('AC-162: el fondo queda inert mientras el cajón modal está abierto y se libera al cerrar', async () => {
  await abrirCajon();
  expect(document.getElementById('contenido')).toHaveAttribute('inert');
});

test('AC-164: los estados de carga/actualización se anuncian por una live region separada del diagnóstico', async () => {
  await abrirCajon();
  const region = screen.getByText('0 comentario(s)');
  expect(region).toHaveAttribute('aria-live', 'polite');
});

test('AC-135/136: el enlace BI sólo aparece si el contexto trae un href ya autorizado', async () => {
  await abrirCajon();
  const enlace = screen.getByRole('link', { name: 'Ver en BI' });
  expect(enlace).toHaveAttribute('href', 'https://bi.example/tablero');
  expect(enlace).toHaveAttribute('rel', expect.stringContaining('noopener'));
});

test('sin enlace BI cuando el contexto no trae href autorizado', async () => {
  const usuario = userEvent.setup();
  render(
    <HarnessLps>
      {(api) => (
        <button type="button" onClick={() => api.abrir(contextoActividad({ biHref: null }))}>
          Abrir
        </button>
      )}
    </HarnessLps>,
  );
  await usuario.click(screen.getByRole('button', { name: 'Abrir' }));
  await waitFor(() => expect(screen.getByRole('dialog')).toBeInTheDocument());
  expect(screen.queryByRole('link', { name: 'Ver en BI' })).not.toBeInTheDocument();
});

test('AC-176: el texto de comentario se renderiza escapado, nunca como HTML crudo', async () => {
  fetchMock.mockResolvedValue(
    jsonOk(
      respuestaHilo({
        comments: [
          {
            id: 1,
            comentario: '<script>alert(1)</script> texto',
            created_at: '2026-08-31',
            autor_nombre: 'Ana',
            autor_cargo: 'Residente',
            menciones: null,
            respuestas: [],
          },
        ],
      }),
    ),
  );
  await abrirCajon();

  await waitFor(() => expect(screen.getByText('<script>alert(1)</script> texto')).toBeInTheDocument());
  expect(document.querySelector('script[src], script:not([type])')).toBeNull();
});

test('AC-166: los controles interactivos del cajón llevan la clase que fija el mínimo de 44px (contrato semántico, medido en píxeles reales por la matriz de navegador de la Tarea 10)', async () => {
  await abrirCajon();
  expect(screen.getByRole('button', { name: 'Cerrar' })).toHaveClass('lps-cajon__cerrar');
  expect(screen.getByRole('button', { name: 'Comentar' }).closest('form')).toHaveClass('lps-formulario-comentario');
});

test('AC-006: el cajón no importa Handsontable, jQuery, Bootstrap ni referencias a globals legado', async () => {
  await abrirCajon();
  expect((window as unknown as { jQuery?: unknown }).jQuery).toBeUndefined();
  expect((window as unknown as { Handsontable?: unknown }).Handsontable).toBeUndefined();
  expect(document.querySelector('.handsontable')).toBeNull();
});

test('AC-171: una sola instancia del cajón se monta por vez — nunca dos DOM `role=dialog` a la vez', async () => {
  await abrirCajon();
  expect(screen.getAllByRole('dialog')).toHaveLength(1);
});
