import { render, screen, waitFor } from '@testing-library/react';
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { afterEach, expect, test, vi } from 'vitest';
import { SesionProvider, useSesion } from './SesionProvider';

const csrfToken = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

function responderSesion(cuerpo: unknown) {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValue(
    new Response(JSON.stringify(cuerpo), { status: 200 }),
  ));
}

afterEach(() => vi.unstubAllGlobals());

// React solo permite capturar el throw de un hook con un error boundary de
// clase (todavía no hay equivalente funcional) — ver rules/ecc/react/patterns.md.
class LimiteDeError extends Component<{ children: ReactNode }, { mensaje: string | null }> {
  state: { mensaje: string | null } = { mensaje: null };

  static getDerivedStateFromError(error: unknown): { mensaje: string } {
    return { mensaje: error instanceof Error ? error.message : 'error desconocido' };
  }

  componentDidCatch(_error: unknown, _info: ErrorInfo): void {
    // Silencia el log de consola de React para este caso esperado; no oculta el string.
  }

  render() {
    if (this.state.mensaje !== null) {
      return <p role="alert">{this.state.mensaje}</p>;
    }

    return this.props.children;
  }
}

function SondaSesion() {
  const { estado } = useSesion();
  return <p role="status">{estado}</p>;
}

test('useSesion() fuera de <SesionProvider> lanza en vez de fingir un estado', () => {
  const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});

  render(
    <LimiteDeError>
      <SondaSesion />
    </LimiteDeError>,
  );

  expect(screen.getByRole('alert')).toHaveTextContent('useSesion() debe usarse dentro de <SesionProvider>');

  consoleError.mockRestore();
});

test('el Provider expone cargando mientras la primera consulta está en vuelo', () => {
  vi.stubGlobal('fetch', vi.fn().mockReturnValue(new Promise<Response>(() => {})));

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  expect(screen.getByRole('status')).toHaveTextContent('cargando');
});

test('el Provider deriva "anonimo" de state=anonymous con reason=missing_session', async () => {
  responderSesion({
    state: 'anonymous',
    authenticated: false,
    reason: 'missing_session',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('anonimo'));
});

test('el Provider deriva "expirado" de state=anonymous con reason=timeout', async () => {
  responderSesion({
    state: 'anonymous',
    authenticated: false,
    reason: 'timeout',
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('expirado'));
});

test('el Provider deriva "cambio_clave_requerido" de state=password_change_required', async () => {
  responderSesion({
    state: 'password_change_required',
    authenticated: false,
    reason: null,
    user: null,
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('cambio_clave_requerido'));
});

test('el Provider deriva "autenticado_sin_proyecto" de authenticated con project=null', async () => {
  responderSesion({
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: null,
    capabilities: {},
    navigation: { bi: null },
    week: null,
    csrfToken,
  });

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('autenticado_sin_proyecto'));
});

test('el Provider deriva "listo" de authenticated con project', async () => {
  responderSesion({
    state: 'authenticated',
    authenticated: true,
    reason: null,
    user: { username: 'test.A', displayName: 'Ana', role: 'A' },
    project: { id: 1, name: 'Da Porto' },
    capabilities: {},
    navigation: { bi: null },
    week: { current: 6 },
    csrfToken,
  });

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('listo'));
});

test('el Provider deriva "error_recuperable" cuando la consulta falla', async () => {
  vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));

  render(
    <SesionProvider>
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getByRole('status')).toHaveTextContent('error_recuperable'));
});

test('dos sondas bajo el mismo Provider comparten una sola consulta', async () => {
  const fetchFalso = vi.fn().mockResolvedValue(
    new Response(JSON.stringify({
      state: 'anonymous',
      authenticated: false,
      reason: 'missing_session',
      user: null,
      project: null,
      capabilities: {},
      navigation: { bi: null },
      week: null,
      csrfToken,
    }), { status: 200 }),
  );
  vi.stubGlobal('fetch', fetchFalso);

  render(
    <SesionProvider>
      <SondaSesion />
      <SondaSesion />
    </SesionProvider>,
  );

  await waitFor(() => expect(screen.getAllByRole('status')[0]).toHaveTextContent('anonimo'));
  expect(fetchFalso).toHaveBeenCalledTimes(1);
});
