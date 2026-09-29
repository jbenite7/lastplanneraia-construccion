import { useEffect, useRef } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, expect, test, vi } from 'vitest';
import { Rutas } from './rutas';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

// Pantalla de mentira: cuenta cuántas veces se monta y deja escribir un filtro. Es el sensor de lo que
// importa: la barra de semana debe aparecer SIN remontar la pantalla y SIN perder lo que el usuario escribió.
const montajes = vi.hoisted(() => ({ n: 0 }));

vi.mock('../modules/programa-general/ProgramaGeneralPage', () => ({
  ProgramaGeneralPage: ({ alCargarContexto }: { alCargarContexto?: () => void }) => {
    const aviso = useRef(alCargarContexto);
    aviso.current = alCargarContexto;
    useEffect(() => {
      montajes.n += 1;
      aviso.current?.();
    }, []);
    return <input aria-label="Filtro de prueba" />;
  },
}));

const semana4 = {
  actions: { create: true, deleteLast: true, select: true },
  current: 4,
  options: [{ number: 4, startsOn: '2026-09-10', endsOn: '2026-09-16' }],
};

function sesion(semana: unknown) {
  return {
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.R', displayName: 'Rita', role: 'R' },
    project: { id: 1, name: 'PDC Sandbox E2E', area: 'Construccion' },
    capabilities: {},
    navigation: {
      bi: null,
      groups: [
        {
          id: 'programacion',
          label: 'Programación',
          items: [{ id: 'programa-general', label: 'Programa General', href: '/programa-general', icon: 'program', action: false }],
        },
      ],
    },
    week: semana,
    csrfToken,
  };
}

beforeEach(() => {
  montajes.n = 0;
  window.history.pushState({}, '', '/programa-general');
});

afterEach(() => {
  vi.unstubAllGlobals();
  window.history.pushState({}, '', '/');
});

test('tras «quitar semana», la barra de semana aparece a la primera sin remontar la pantalla ni perder el filtro', async () => {
  // Primera lectura: el shell aún no conoce la semana (el servidor la guarda al servir el contexto de
  // la pantalla, DESPUÉS). Segunda lectura, la silenciosa: ya la trae.
  const fetchFalso = vi
    .fn()
    .mockResolvedValueOnce(new Response(JSON.stringify(sesion(null)), { status: 200 }))
    .mockResolvedValue(new Response(JSON.stringify(sesion(semana4)), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  const usuario = userEvent.setup();
  render(<Rutas />);

  const filtro = await screen.findByRole('textbox', { name: 'Filtro de prueba' });
  await usuario.type(filtro, 'cimentación');

  // La barra aparece sola, tras la relectura silenciosa.
  await waitFor(() => expect(document.querySelector('#shellContextBar #ctxSemanaTexto')).toHaveTextContent('Semana 4'));

  // Y la pantalla sigue siendo la misma instancia, con lo escrito intacto.
  expect(montajes.n).toBe(1);
  expect(screen.getByRole('textbox', { name: 'Filtro de prueba' })).toHaveValue('cimentación');
  expect(fetchFalso.mock.calls.filter(([url]) => String(url).includes('/api/session'))).toHaveLength(2);
});

test('si el shell ya tiene semana, la pantalla no provoca ninguna relectura', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(new Response(JSON.stringify(sesion(semana4)), { status: 200 }));
  vi.stubGlobal('fetch', fetchFalso);

  render(<Rutas />);
  await screen.findByRole('textbox', { name: 'Filtro de prueba' });
  await waitFor(() => expect(document.querySelector('#shellContextBar #ctxSemanaTexto')).toHaveTextContent('Semana 4'));

  expect(fetchFalso.mock.calls.filter(([url]) => String(url).includes('/api/session'))).toHaveLength(1);
});
